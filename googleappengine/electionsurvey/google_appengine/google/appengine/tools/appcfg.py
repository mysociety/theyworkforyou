#!/usr/bin/env python
#
# Copyright 2007 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

"""Tool for deploying apps to an app server.

Currently, the application only uploads new appversions. To do this, it first
walks the directory tree rooted at the path the user specifies, adding all the
files it finds to a list. It then uploads the application configuration
(app.yaml) to the server using HTTP, followed by uploading each of the files.
It then commits the transaction with another request.

The bulk of this work is handled by the AppVersionUpload class, which exposes
methods to add to the list of files, fetch a list of modified files, upload
files, and commit or rollback the transaction.
"""


import calendar
import datetime
import getpass
import logging
import mimetypes
import optparse
import os
import random
import re
import sha
import sys
import tempfile
import time
import urllib
import urllib2

import google
import yaml
from google.appengine.cron import groctimespecification
from google.appengine.api import appinfo
from google.appengine.api import croninfo
from google.appengine.api import dosinfo
from google.appengine.api import queueinfo
from google.appengine.api import validation
from google.appengine.api import yaml_errors
from google.appengine.api import yaml_object
from google.appengine.datastore import datastore_index
from google.appengine.tools import appengine_rpc
from google.appengine.tools import bulkloader


MAX_FILES_TO_CLONE = 100
LIST_DELIMITER = '\n'
TUPLE_DELIMITER = '|'

VERSION_FILE = '../VERSION'

UPDATE_CHECK_TIMEOUT = 3

NAG_FILE = '.appcfg_nag'

MAX_LOG_LEVEL = 4

MAX_BATCH_SIZE = 1000000
MAX_BATCH_COUNT = 100
MAX_BATCH_FILE_SIZE = 200000
BATCH_OVERHEAD = 500

verbosity = 1


appinfo.AppInfoExternal.ATTRIBUTES[appinfo.RUNTIME] = 'python'
_api_versions = os.environ.get('GOOGLE_TEST_API_VERSIONS', '1')
_options = validation.Options(*_api_versions.split(','))
appinfo.AppInfoExternal.ATTRIBUTES[appinfo.API_VERSION] = _options
del _api_versions, _options


def StatusUpdate(msg):
  """Print a status message to stderr.

  If 'verbosity' is greater than 0, print the message.

  Args:
    msg: The string to print.
  """
  if verbosity > 0:
    print >>sys.stderr, msg


def GetMimeTypeIfStaticFile(config, filename):
  """Looks up the mime type for 'filename'.

  Uses the handlers in 'config' to determine if the file should
  be treated as a static file.

  Args:
    config: The app.yaml object to check the filename against.
    filename: The name of the file.

  Returns:
    The mime type string.  For example, 'text/plain' or 'image/gif'.
    None if this is not a static file.
  """
  for handler in config.handlers:
    handler_type = handler.GetHandlerType()
    if handler_type in ('static_dir', 'static_files'):
      if handler_type == 'static_dir':
        regex = os.path.join(re.escape(handler.GetHandler()), '.*')
      else:
        regex = handler.upload
      if re.match(regex, filename):
        if handler.mime_type is not None:
          return handler.mime_type
        else:
          guess = mimetypes.guess_type(filename)[0]
          if guess is None:
            default = 'application/octet-stream'
            print >>sys.stderr, ('Could not guess mimetype for %s.  Using %s.'
                                 % (filename, default))
            return default
          return guess
  return None


def BuildClonePostBody(file_tuples):
  """Build the post body for the /api/clone{files,blobs} urls.

  Args:
    file_tuples: A list of tuples.  Each tuple should contain the entries
      appropriate for the endpoint in question.

  Returns:
    A string containing the properly delimited tuples.
  """
  file_list = []
  for tup in file_tuples:
    path = tup[0]
    tup = tup[1:]
    file_list.append(TUPLE_DELIMITER.join([path] + list(tup)))
  return LIST_DELIMITER.join(file_list)


class NagFile(validation.Validated):
  """A validated YAML class to represent the user's nag preferences.

  Attributes:
    timestamp: The timestamp of the last nag.
    opt_in: True if the user wants to check for updates on dev_appserver
      start.  False if not.  May be None if we have not asked the user yet.
  """

  ATTRIBUTES = {
      'timestamp': validation.TYPE_FLOAT,
      'opt_in': validation.Optional(validation.TYPE_BOOL),
  }

  @staticmethod
  def Load(nag_file):
    """Load a single NagFile object where one and only one is expected.

    Args:
      nag_file: A file-like object or string containing the yaml data to parse.

    Returns:
      A NagFile instance.
    """
    return yaml_object.BuildSingleObject(NagFile, nag_file)


def GetVersionObject(isfile=os.path.isfile, open_fn=open):
  """Gets the version of the SDK by parsing the VERSION file.

  Args:
    isfile: used for testing.
    open_fn: Used for testing.

  Returns:
    A Yaml object or None if the VERSION file does not exist.
  """
  version_filename = os.path.join(os.path.dirname(google.__file__),
                                  VERSION_FILE)
  if not isfile(version_filename):
    logging.error('Could not find version file at %s', version_filename)
    return None

  version_fh = open_fn(version_filename, 'r')
  try:
    version = yaml.safe_load(version_fh)
  finally:
    version_fh.close()

  return version


def RetryWithBackoff(initial_delay, backoff_factor, max_delay, max_tries,
                     callable_func):
  """Calls a function multiple times, backing off more and more each time.

  Args:
    initial_delay: Initial delay after first try, in seconds.
    backoff_factor: Delay will be multiplied by this factor after each try.
    max_delay: Max delay factor.
    max_tries: Maximum number of tries.
    callable_func: The method to call, will pass no arguments.

  Returns:
    True if the function succeded in one of its tries.

  Raises:
    Whatever the function raises--an exception will immediately stop retries.
  """
  delay = initial_delay
  if callable_func():
    return True
  while max_tries > 1:
    StatusUpdate('Will check again in %s seconds.' % delay)
    time.sleep(delay)
    delay *= backoff_factor
    if max_delay and delay > max_delay:
      delay = max_delay
    max_tries -= 1
    if callable_func():
      return True
  return False


def _VersionList(release):
  """Parse a version string into a list of ints.

  Args:
    release: The 'release' version, e.g. '1.2.4'.
        (Due to YAML parsing this may also be an int or float.)

  Returns:
    A list of ints corresponding to the parts of the version string
    between periods.  Example:
      '1.2.4' -> [1, 2, 4]
      '1.2.3.4' -> [1, 2, 3, 4]

  Raises:
    ValueError if not all the parts are valid integers.
  """
  return [int(part) for part in str(release).split('.')]


class UpdateCheck(object):
  """Determines if the local SDK is the latest version.

  Nags the user when there are updates to the SDK.  As the SDK becomes
  more out of date, the language in the nagging gets stronger.  We
  store a little yaml file in the user's home directory so that we nag
  the user only once a week.

  The yaml file has the following field:
    'timestamp': Last time we nagged the user in seconds since the epoch.

  Attributes:
    server: An AbstractRpcServer instance used to check for the latest SDK.
    config: The app's AppInfoExternal.  Needed to determine which api_version
      the app is using.
  """

  def __init__(self,
               server,
               config,
               isdir=os.path.isdir,
               isfile=os.path.isfile,
               open_fn=open):
    """Create a new UpdateCheck.

    Args:
      server: The AbstractRpcServer to use.
      config: The yaml object that specifies the configuration of this
        application.
      isdir: Replacement for os.path.isdir (for testing).
      isfile: Replacement for os.path.isfile (for testing).
      open_fn: Replacement for the open builtin (for testing).
    """
    self.server = server
    self.config = config
    self.isdir = isdir
    self.isfile = isfile
    self.open = open_fn

  @staticmethod
  def MakeNagFilename():
    """Returns the filename for the nag file for this user."""
    user_homedir = os.path.expanduser('~/')
    if not os.path.isdir(user_homedir):
      drive, unused_tail = os.path.splitdrive(os.__file__)
      if drive:
        os.environ['HOMEDRIVE'] = drive

    return os.path.expanduser('~/' + NAG_FILE)

  def _ParseVersionFile(self):
    """Parse the local VERSION file.

    Returns:
      A Yaml object or None if the file does not exist.
    """
    return GetVersionObject(isfile=self.isfile, open_fn=self.open)

  def CheckSupportedVersion(self):
    """Determines if the app's api_version is supported by the SDK.

    Uses the api_version field from the AppInfoExternal to determine if
    the SDK supports that api_version.

    Raises:
      SystemExit if the api_version is not supported.
    """
    version = self._ParseVersionFile()
    if version is None:
      logging.error('Could not determine if the SDK supports the api_version '
                    'requested in app.yaml.')
      return
    if self.config.api_version not in version['api_versions']:
      logging.critical('The api_version specified in app.yaml (%s) is not '
                       'supported by this release of the SDK.  The supported '
                       'api_versions are %s.',
                       self.config.api_version, version['api_versions'])
      sys.exit(1)

  def CheckForUpdates(self):
    """Queries the server for updates and nags the user if appropriate.

    Queries the server for the latest SDK version at the same time reporting
    the local SDK version.  The server will respond with a yaml document
    containing the fields:
      'release': The name of the release (e.g. 1.2).
      'timestamp': The time the release was created (YYYY-MM-DD HH:MM AM/PM TZ).
      'api_versions': A list of api_version strings (e.g. ['1', 'beta']).

    We will nag the user with increasing severity if:
    - There is a new release.
    - There is a new release with a new api_version.
    - There is a new release that does not support the api_version named in
      self.config.
    """
    version = self._ParseVersionFile()
    if version is None:
      logging.info('Skipping update check')
      return
    logging.info('Checking for updates to the SDK.')

    try:
      response = self.server.Send('/api/updatecheck',
                                  timeout=UPDATE_CHECK_TIMEOUT,
                                  release=version['release'],
                                  timestamp=version['timestamp'],
                                  api_versions=version['api_versions'])
    except urllib2.URLError, e:
      logging.info('Update check failed: %s', e)
      return

    latest = yaml.safe_load(response)
    if version['release'] == latest['release']:
      logging.info('The SDK is up to date.')
      return

    try:
      this_release = _VersionList(version['release'])
    except ValueError:
      logging.warn('Could not parse this release version (%r)',
                   version['release'])
    else:
      try:
        advertised_release = _VersionList(latest['release'])
      except ValueError:
        logging.warn('Could not parse advertised release version (%r)',
                     latest['release'])
      else:
        if this_release > advertised_release:
          logging.info('This SDK release is newer than the advertised release.')
          return

    api_versions = latest['api_versions']
    if self.config.api_version not in api_versions:
      self._Nag(
          'The api version you are using (%s) is obsolete!  You should\n'
          'upgrade your SDK and test that your code works with the new\n'
          'api version.' % self.config.api_version,
          latest, version, force=True)
      return

    if self.config.api_version != api_versions[len(api_versions) - 1]:
      self._Nag(
          'The api version you are using (%s) is deprecated. You should\n'
          'upgrade your SDK to try the new functionality.' %
          self.config.api_version, latest, version)
      return

    self._Nag('There is a new release of the SDK available.',
              latest, version)

  def _ParseNagFile(self):
    """Parses the nag file.

    Returns:
      A NagFile if the file was present else None.
    """
    nag_filename = UpdateCheck.MakeNagFilename()
    if self.isfile(nag_filename):
      fh = self.open(nag_filename, 'r')
      try:
        nag = NagFile.Load(fh)
      finally:
        fh.close()
      return nag
    return None

  def _WriteNagFile(self, nag):
    """Writes the NagFile to the user's nag file.

    If the destination path does not exist, this method will log an error
    and fail silently.

    Args:
      nag: The NagFile to write.
    """
    nagfilename = UpdateCheck.MakeNagFilename()
    try:
      fh = self.open(nagfilename, 'w')
      try:
        fh.write(nag.ToYAML())
      finally:
        fh.close()
    except (OSError, IOError), e:
      logging.error('Could not write nag file to %s. Error: %s', nagfilename, e)

  def _Nag(self, msg, latest, version, force=False):
    """Prints a nag message and updates the nag file's timestamp.

    Because we don't want to nag the user everytime, we store a simple
    yaml document in the user's home directory.  If the timestamp in this
    doc is over a week old, we'll nag the user.  And when we nag the user,
    we update the timestamp in this doc.

    Args:
      msg: The formatted message to print to the user.
      latest: The yaml document received from the server.
      version: The local yaml version document.
      force: If True, always nag the user, ignoring the nag file.
    """
    nag = self._ParseNagFile()
    if nag and not force:
      last_nag = datetime.datetime.fromtimestamp(nag.timestamp)
      if datetime.datetime.now() - last_nag < datetime.timedelta(weeks=1):
        logging.debug('Skipping nag message')
        return

    if nag is None:
      nag = NagFile()
    nag.timestamp = time.time()
    self._WriteNagFile(nag)

    print '****************************************************************'
    print msg
    print '-----------'
    print 'Latest SDK:'
    print yaml.dump(latest)
    print '-----------'
    print 'Your SDK:'
    print yaml.dump(version)
    print '-----------'
    print 'Please visit http://code.google.com/appengine for the latest SDK'
    print '****************************************************************'

  def AllowedToCheckForUpdates(self, input_fn=raw_input):
    """Determines if the user wants to check for updates.

    On startup, the dev_appserver wants to check for updates to the SDK.
    Because this action reports usage to Google when the user is not
    otherwise communicating with Google (e.g. pushing a new app version),
    the user must opt in.

    If the user does not have a nag file, we will query the user and
    save the response in the nag file.  Subsequent calls to this function
    will re-use that response.

    Args:
      input_fn: used to collect user input. This is for testing only.

    Returns:
      True if the user wants to check for updates.  False otherwise.
    """
    nag = self._ParseNagFile()
    if nag is None:
      nag = NagFile()
      nag.timestamp = time.time()

    if nag.opt_in is None:
      answer = input_fn('Allow dev_appserver to check for updates on startup? '
                        '(Y/n): ')
      answer = answer.strip().lower()
      if answer == 'n' or answer == 'no':
        print ('dev_appserver will not check for updates on startup.  To '
               'change this setting, edit %s' % UpdateCheck.MakeNagFilename())
        nag.opt_in = False
      else:
        print ('dev_appserver will check for updates on startup.  To change '
               'this setting, edit %s' % UpdateCheck.MakeNagFilename())
        nag.opt_in = True
      self._WriteNagFile(nag)
    return nag.opt_in


class IndexDefinitionUpload(object):
  """Provides facilities to upload index definitions to the hosting service."""

  def __init__(self, server, config, definitions):
    """Creates a new DatastoreIndexUpload.

    Args:
      server: The RPC server to use.  Should be an instance of HttpRpcServer
        or TestRpcServer.
      config: The AppInfoExternal object derived from the app.yaml file.
      definitions: An IndexDefinitions object.
    """
    self.server = server
    self.config = config
    self.definitions = definitions

  def DoUpload(self):
    """Uploads the index definitions."""
    StatusUpdate('Uploading index definitions.')
    self.server.Send('/api/datastore/index/add',
                     app_id=self.config.application,
                     version=self.config.version,
                     payload=self.definitions.ToYAML())


class CronEntryUpload(object):
  """Provides facilities to upload cron entries to the hosting service."""

  def __init__(self, server, config, cron):
    """Creates a new CronEntryUpload.

    Args:
      server: The RPC server to use.  Should be an instance of a subclass of
      AbstractRpcServer
      config: The AppInfoExternal object derived from the app.yaml file.
      cron: The CronInfoExternal object loaded from the cron.yaml file.
    """
    self.server = server
    self.config = config
    self.cron = cron

  def DoUpload(self):
    """Uploads the cron entries."""
    StatusUpdate('Uploading cron entries.')
    self.server.Send('/api/datastore/cron/update',
                     app_id=self.config.application,
                     version=self.config.version,
                     payload=self.cron.ToYAML())


class QueueEntryUpload(object):
  """Provides facilities to upload task queue entries to the hosting service."""

  def __init__(self, server, config, queue):
    """Creates a new QueueEntryUpload.

    Args:
      server: The RPC server to use.  Should be an instance of a subclass of
      AbstractRpcServer
      config: The AppInfoExternal object derived from the app.yaml file.
      queue: The QueueInfoExternal object loaded from the queue.yaml file.
    """
    self.server = server
    self.config = config
    self.queue = queue

  def DoUpload(self):
    """Uploads the task queue entries."""
    StatusUpdate('Uploading task queue entries.')
    self.server.Send('/api/queue/update',
                     app_id=self.config.application,
                     version=self.config.version,
                     payload=self.queue.ToYAML())


class DosEntryUpload(object):
  """Provides facilities to upload dos entries to the hosting service."""

  def __init__(self, server, config, dos):
    """Creates a new DosEntryUpload.

    Args:
      server: The RPC server to use. Should be an instance of a subclass of
        AbstractRpcServer.
      config: The AppInfoExternal object derived from the app.yaml file.
      dos: The DosInfoExternal object loaded from the dos.yaml file.
    """
    self.server = server
    self.config = config
    self.dos = dos

  def DoUpload(self):
    """Uploads the dos entries."""
    StatusUpdate('Uploading DOS entries.')
    self.server.Send('/api/dos/update',
                     app_id=self.config.application,
                     version=self.config.version,
                     payload=self.dos.ToYAML())


class IndexOperation(object):
  """Provide facilities for writing Index operation commands."""

  def __init__(self, server, config):
    """Creates a new IndexOperation.

    Args:
      server: The RPC server to use.  Should be an instance of HttpRpcServer
        or TestRpcServer.
      config: appinfo.AppInfoExternal configuration object.
    """
    self.server = server
    self.config = config

  def DoDiff(self, definitions):
    """Retrieve diff file from the server.

    Args:
      definitions: datastore_index.IndexDefinitions as loaded from users
        index.yaml file.

    Returns:
      A pair of datastore_index.IndexDefinitions objects.  The first record
      is the set of indexes that are present in the index.yaml file but missing
      from the server.  The second record is the set of indexes that are
      present on the server but missing from the index.yaml file (indicating
      that these indexes should probably be vacuumed).
    """
    StatusUpdate('Fetching index definitions diff.')
    response = self.server.Send('/api/datastore/index/diff',
                                app_id=self.config.application,
                                payload=definitions.ToYAML())
    return datastore_index.ParseMultipleIndexDefinitions(response)

  def DoDelete(self, definitions):
    """Delete indexes from the server.

    Args:
      definitions: Index definitions to delete from datastore.

    Returns:
      A single datstore_index.IndexDefinitions containing indexes that were
      not deleted, probably because they were already removed.  This may
      be normal behavior as there is a potential race condition between fetching
      the index-diff and sending deletion confirmation through.
    """
    StatusUpdate('Deleting selected index definitions.')
    response = self.server.Send('/api/datastore/index/delete',
                                app_id=self.config.application,
                                payload=definitions.ToYAML())
    return datastore_index.ParseIndexDefinitions(response)


class VacuumIndexesOperation(IndexOperation):
  """Provide facilities to request the deletion of datastore indexes."""

  def __init__(self, server, config, force,
               confirmation_fn=raw_input):
    """Creates a new VacuumIndexesOperation.

    Args:
      server: The RPC server to use.  Should be an instance of HttpRpcServer
        or TestRpcServer.
      config: appinfo.AppInfoExternal configuration object.
      force: True to force deletion of indexes, else False.
      confirmation_fn: Function used for getting input form user.
    """
    super(VacuumIndexesOperation, self).__init__(server, config)
    self.force = force
    self.confirmation_fn = confirmation_fn

  def GetConfirmation(self, index):
    """Get confirmation from user to delete an index.

    This method will enter an input loop until the user provides a
    response it is expecting.  Valid input is one of three responses:

      y: Confirm deletion of index.
      n: Do not delete index.
      a: Delete all indexes without asking for further confirmation.

    If the user enters nothing at all, the default action is to skip
    that index and do not delete.

    If the user selects 'a', as a side effect, the 'force' flag is set.

    Args:
      index: Index to confirm.

    Returns:
      True if user enters 'y' or 'a'.  False if user enter 'n'.
    """
    while True:
      print 'This index is no longer defined in your index.yaml file.'
      print
      print index.ToYAML()
      print

      confirmation = self.confirmation_fn(
          'Are you sure you want to delete this index? (N/y/a): ')
      confirmation = confirmation.strip().lower()

      if confirmation == 'y':
        return True
      elif confirmation == 'n' or not confirmation:
        return False
      elif confirmation == 'a':
        self.force = True
        return True
      else:
        print 'Did not understand your response.'

  def DoVacuum(self, definitions):
    """Vacuum indexes in datastore.

    This method will query the server to determine which indexes are not
    being used according to the user's local index.yaml file.  Once it has
    made this determination, it confirms with the user which unused indexes
    should be deleted.  Once confirmation for each index is receives, it
    deletes those indexes.

    Because another user may in theory delete the same indexes at the same
    time as the user, there is a potential race condition.  In this rare cases,
    some of the indexes previously confirmed for deletion will not be found.
    The user is notified which indexes these were.

    Args:
      definitions: datastore_index.IndexDefinitions as loaded from users
        index.yaml file.
    """
    unused_new_indexes, notused_indexes = self.DoDiff(definitions)

    deletions = datastore_index.IndexDefinitions(indexes=[])
    if notused_indexes.indexes is not None:
      for index in notused_indexes.indexes:
        if self.force or self.GetConfirmation(index):
          deletions.indexes.append(index)

    if deletions.indexes:
      not_deleted = self.DoDelete(deletions)

      if not_deleted.indexes:
        not_deleted_count = len(not_deleted.indexes)
        if not_deleted_count == 1:
          warning_message = ('An index was not deleted.  Most likely this is '
                             'because it no longer exists.\n\n')
        else:
          warning_message = ('%d indexes were not deleted.  Most likely this '
                             'is because they no longer exist.\n\n'
                             % not_deleted_count)
        for index in not_deleted.indexes:
          warning_message += index.ToYAML()
        logging.warning(warning_message)


class LogsRequester(object):
  """Provide facilities to export request logs."""

  def __init__(self, server, config, output_file,
               num_days, append, severity, end, vhost, include_vhost,
               time_func=time.time):
    """Constructor.

    Args:
      server: The RPC server to use.  Should be an instance of HttpRpcServer
        or TestRpcServer.
      config: appinfo.AppInfoExternal configuration object.
      output_file: Output file name.
      num_days: Number of days worth of logs to export; 0 for all available.
      append: True if appending to an existing file.
      severity: App log severity to request (0-4); None for no app logs.
      end: date object representing last day of logs to return.
      vhost: The virtual host of log messages to get. None for all hosts.
      include_vhost: If true, the virtual host is included in log messages.
      time_func: Method that return a timestamp representing now (for testing).
    """
    self.server = server
    self.config = config
    self.output_file = output_file
    self.append = append
    self.num_days = num_days
    self.severity = severity
    self.vhost = vhost
    self.include_vhost = include_vhost
    self.version_id = self.config.version + '.1'
    self.sentinel = None
    self.write_mode = 'w'
    if self.append:
      self.sentinel = FindSentinel(self.output_file)
      self.write_mode = 'a'

    self.skip_until = False
    now = PacificDate(time_func())
    if end < now:
      self.skip_until = end
    else:
      end = now

    self.valid_dates = None
    if self.num_days:
      start = end - datetime.timedelta(self.num_days - 1)
      self.valid_dates = (start, end)

  def DownloadLogs(self):
    """Download the requested logs.

    This will write the logs to the file designated by
    self.output_file, or to stdout if the filename is '-'.
    Multiple roundtrips to the server may be made.
    """
    StatusUpdate('Downloading request logs for %s %s.' %
                 (self.config.application, self.version_id))
    tf = tempfile.TemporaryFile()
    last_offset = None
    try:
      while True:
        try:
          new_offset = self.RequestLogLines(tf, last_offset)
          if not new_offset or new_offset == last_offset:
            break
          last_offset = new_offset
        except KeyboardInterrupt:
          StatusUpdate('Keyboard interrupt; saving data downloaded so far.')
          break
      StatusUpdate('Copying request logs to %r.' % self.output_file)
      if self.output_file == '-':
        of = sys.stdout
      else:
        try:
          of = open(self.output_file, self.write_mode)
        except IOError, err:
          StatusUpdate('Can\'t write %r: %s.' % (self.output_file, err))
          sys.exit(1)
      try:
        line_count = CopyReversedLines(tf, of)
      finally:
        of.flush()
        if of is not sys.stdout:
          of.close()
    finally:
      tf.close()
    StatusUpdate('Copied %d records.' % line_count)

  def RequestLogLines(self, tf, offset):
    """Make a single roundtrip to the server.

    Args:
      tf: Writable binary stream to which the log lines returned by
        the server are written, stripped of headers, and excluding
        lines skipped due to self.sentinel or self.valid_dates filtering.
      offset: Offset string for a continued request; None for the first.

    Returns:
      The offset string to be used for the next request, if another
      request should be issued; or None, if not.
    """
    logging.info('Request with offset %r.', offset)
    kwds = {'app_id': self.config.application,
            'version': self.version_id,
            'limit': 1000,
           }
    if offset:
      kwds['offset'] = offset
    if self.severity is not None:
      kwds['severity'] = str(self.severity)
    if self.vhost is not None:
      kwds['vhost'] = str(self.vhost)
    if self.include_vhost is not None:
      kwds['include_vhost'] = str(self.include_vhost)
    response = self.server.Send('/api/request_logs', payload=None, **kwds)
    response = response.replace('\r', '\0')
    lines = response.splitlines()
    logging.info('Received %d bytes, %d records.', len(response), len(lines))
    offset = None
    if lines and lines[0].startswith('#'):
      match = re.match(r'^#\s*next_offset=(\S+)\s*$', lines[0])
      del lines[0]
      if match:
        offset = match.group(1)
    if lines and lines[-1].startswith('#'):
      del lines[-1]
    valid_dates = self.valid_dates
    sentinel = self.sentinel
    skip_until = self.skip_until
    len_sentinel = None
    if sentinel:
      len_sentinel = len(sentinel)
    for line in lines:
      if (sentinel and
          line.startswith(sentinel) and
          line[len_sentinel : len_sentinel+1] in ('', '\0')):
        return None

      linedate = DateOfLogLine(line)
      if not linedate:
        continue

      if skip_until:
        if linedate > skip_until:
          continue
        else:
          self.skip_until = skip_until = False

      if valid_dates and not valid_dates[0] <= linedate <= valid_dates[1]:
        return None
      tf.write(line + '\n')
    if not lines:
      return None
    return offset


def DateOfLogLine(line):
  """Returns a date object representing the log line's timestamp.

  Args:
    line: a log line string.
  Returns:
    A date object representing the timestamp or None if parsing fails.
  """
  m = re.compile(r'[^[]+\[(\d+/[A-Za-z]+/\d+):[^\d]*').match(line)
  if not m:
    return None
  try:
    return datetime.date(*time.strptime(m.group(1), '%d/%b/%Y')[:3])
  except ValueError:
    return None


def PacificDate(now):
  """For a UTC timestamp, return the date in the US/Pacific timezone.

  Args:
    now: A posix timestamp giving current UTC time.

  Returns:
    A date object representing what day it is in the US/Pacific timezone.
  """
  return datetime.date(*time.gmtime(PacificTime(now))[:3])


def PacificTime(now):
  """Helper to return the number of seconds between UTC and Pacific time.

  This is needed to compute today's date in Pacific time (more
  specifically: Mountain View local time), which is how request logs
  are reported.  (Google servers always report times in Mountain View
  local time, regardless of where they are physically located.)

  This takes (post-2006) US DST into account.  Pacific time is either
  8 hours or 7 hours west of UTC, depending on whether DST is in
  effect.  Since 2007, US DST starts on the Second Sunday in March
  March, and ends on the first Sunday in November.  (Reference:
  http://aa.usno.navy.mil/faq/docs/daylight_time.php.)

  Note that the server doesn't report its local time (the HTTP Date
  header uses UTC), and the client's local time is irrelevant.

  Args:
    now: A posix timestamp giving current UTC time.

  Returns:
    A pseudo-posix timestamp giving current Pacific time.  Passing
    this through time.gmtime() will produce a tuple in Pacific local
    time.
  """
  now -= 8*3600
  if IsPacificDST(now):
    now += 3600
  return now


def IsPacificDST(now):
  """Helper for PacificTime to decide whether now is Pacific DST (PDT).

  Args:
    now: A pseudo-posix timestamp giving current time in PST.

  Returns:
    True if now falls within the range of DST, False otherwise.
  """
  DAY = 24*3600
  SUNDAY = 6
  pst = time.gmtime(now)
  year = pst[0]
  assert year >= 2007
  begin = calendar.timegm((year, 3, 8, 2, 0, 0, 0, 0, 0))
  while time.gmtime(begin).tm_wday != SUNDAY:
    begin += DAY
  end = calendar.timegm((year, 11, 1, 2, 0, 0, 0, 0, 0))
  while time.gmtime(end).tm_wday != SUNDAY:
    end += DAY
  return begin <= now < end


def CopyReversedLines(instream, outstream, blocksize=2**16):
  r"""Copy lines from input stream to output stream in reverse order.

  As a special feature, null bytes in the input are turned into
  newlines followed by tabs in the output, but these 'sub-lines'
  separated by null bytes are not reversed.  E.g. If the input is
  'A\0B\nC\0D\n', the output is 'C\n\tD\nA\n\tB\n'.

  Args:
    instream: A seekable stream open for reading in binary mode.
    outstream: A stream open for writing; doesn't have to be seekable or binary.
    blocksize: Optional block size for buffering, for unit testing.

  Returns:
    The number of lines copied.
  """
  line_count = 0
  instream.seek(0, 2)
  last_block = instream.tell() // blocksize
  spillover = ''
  for iblock in xrange(last_block + 1, -1, -1):
    instream.seek(iblock * blocksize)
    data = instream.read(blocksize)
    lines = data.splitlines(True)
    lines[-1:] = ''.join(lines[-1:] + [spillover]).splitlines(True)
    if lines and not lines[-1].endswith('\n'):
      lines[-1] += '\n'
    lines.reverse()
    if lines and iblock > 0:
      spillover = lines.pop()
    if lines:
      line_count += len(lines)
      data = ''.join(lines).replace('\0', '\n\t')
      outstream.write(data)
  return line_count


def FindSentinel(filename, blocksize=2**16):
  """Return the sentinel line from the output file.

  Args:
    filename: The filename of the output file.  (We'll read this file.)
    blocksize: Optional block size for buffering, for unit testing.

  Returns:
    The contents of the last line in the file that doesn't start with
    a tab, with its trailing newline stripped; or None if the file
    couldn't be opened or no such line could be found by inspecting
    the last 'blocksize' bytes of the file.
  """
  if filename == '-':
    StatusUpdate('Can\'t combine --append with output to stdout.')
    sys.exit(2)
  try:
    fp = open(filename, 'rb')
  except IOError, err:
    StatusUpdate('Append mode disabled: can\'t read %r: %s.' % (filename, err))
    return None
  try:
    fp.seek(0, 2)
    fp.seek(max(0, fp.tell() - blocksize))
    lines = fp.readlines()
    del lines[:1]
    sentinel = None
    for line in lines:
      if not line.startswith('\t'):
        sentinel = line
    if not sentinel:
      StatusUpdate('Append mode disabled: can\'t find sentinel in %r.' %
                   filename)
      return None
    return sentinel.rstrip('\n')
  finally:
    fp.close()


class UploadBatcher(object):
  """Helper to batch file uploads."""

  def __init__(self, what, app_id, version, server):
    """Constructor.

    Args:
      what: Either 'file' or 'blob' indicating what kind of objects
        this batcher uploads.  Used in messages and URLs.
      app_id: The application ID.
      version: The application version string.
      server: The RPC server.
    """
    assert what in ('file', 'blob'), repr(what)
    self.what = what
    self.app_id = app_id
    self.version = version
    self.server = server
    self.single_url = '/api/appversion/add' + what
    self.batch_url = self.single_url + 's'
    self.batching = True
    self.batch = []
    self.batch_size = 0

  def SendBatch(self):
    """Send the current batch on its way.

    If successful, resets self.batch and self.batch_size.

    Raises:
      HTTPError with code=404 if the server doesn't support batching.
    """
    boundary = 'boundary'
    parts = []
    for path, payload, mime_type in self.batch:
      while boundary in payload:
        boundary += '%04x' % random.randint(0, 0xffff)
        assert len(boundary) < 80, 'Unexpected error, please try again.'
      part = '\n'.join(['',
                        'X-Appcfg-File: %s' % urllib.quote(path),
                        'X-Appcfg-Hash: %s' % _Hash(payload),
                        'Content-Type: %s' % mime_type,
                        'Content-Length: %d' % len(payload),
                        'Content-Transfer-Encoding: 8bit',
                        '',
                        payload,
                        ])
      parts.append(part)
    parts.insert(0,
                 'MIME-Version: 1.0\n'
                 'Content-Type: multipart/mixed; boundary="%s"\n'
                 '\n'
                 'This is a message with multiple parts in MIME format.' %
                 boundary)
    parts.append('--\n')
    delimiter = '\n--%s' % boundary
    payload = delimiter.join(parts)
    logging.info('Uploading batch of %d %ss to %s with boundary="%s".',
                 len(self.batch), self.what, self.batch_url, boundary)
    self.server.Send(self.batch_url,
                     payload=payload,
                     content_type='message/rfc822',
                     app_id=self.app_id,
                     version=self.version)
    self.batch = []
    self.batch_size = 0

  def SendSingleFile(self, path, payload, mime_type):
    """Send a single file on its way."""
    logging.info('Uploading %s %s (%s bytes, type=%s) to %s.',
                 self.what, path, len(payload), mime_type, self.single_url)
    self.server.Send(self.single_url,
                     payload=payload,
                     content_type=mime_type,
                     path=path,
                     app_id=self.app_id,
                     version=self.version)

  def Flush(self):
    """Flush the current batch.

    This first attempts to send the batch as a single request; if that
    fails because the server doesn't support batching, the files are
    sent one by one, and self.batching is reset to False.

    At the end, self.batch and self.batch_size are reset.
    """
    if not self.batch:
      return
    try:
      self.SendBatch()
    except urllib2.HTTPError, err:
      if err.code != 404:
        raise

      logging.info('Old server detected; turning off %s batching.', self.what)
      self.batching = False

      for path, payload, mime_type in self.batch:
        self.SendSingleFile(path, payload, mime_type)

      self.batch = []
      self.batch_size = 0

  def AddToBatch(self, path, payload, mime_type):
    """Batch a file, possibly flushing first, or perhaps upload it directly.

    Args:
      path: The name of the file.
      payload: The contents of the file.
      mime_type: The MIME Content-type of the file, or None.

    If mime_type is None, application/octet-stream is substituted.
    """
    if not mime_type:
      mime_type = 'application/octet-stream'
    size = len(payload)
    if size <= MAX_BATCH_FILE_SIZE:
      if (len(self.batch) >= MAX_BATCH_COUNT or
          self.batch_size + size > MAX_BATCH_SIZE):
        self.Flush()
      if self.batching:
        logging.info('Adding %s %s (%s bytes, type=%s) to batch.',
                     self.what, path, size, mime_type)
        self.batch.append((path, payload, mime_type))
        self.batch_size += size + BATCH_OVERHEAD
        return
    self.SendSingleFile(path, payload, mime_type)


def _Hash(content):
  """Compute the hash of the content.

  Args:
    content: The data to hash as a string.

  Returns:
    The string representation of the hash.
  """
  h = sha.new(content).hexdigest()
  return '%s_%s_%s_%s_%s' % (h[0:8], h[8:16], h[16:24], h[24:32], h[32:40])


class AppVersionUpload(object):
  """Provides facilities to upload a new appversion to the hosting service.

  Attributes:
    server: The AbstractRpcServer to use for the upload.
    config: The AppInfoExternal object derived from the app.yaml file.
    app_id: The application string from 'config'.
    version: The version string from 'config'.
    files: A dictionary of files to upload to the server, mapping path to
      hash of the file contents.
    in_transaction: True iff a transaction with the server has started.
      An AppVersionUpload can do only one transaction at a time.
    deployed: True iff the Deploy method has been called.
  """

  def __init__(self, server, config):
    """Creates a new AppVersionUpload.

    Args:
      server: The RPC server to use. Should be an instance of HttpRpcServer or
        TestRpcServer.
      config: An AppInfoExternal object that specifies the configuration for
        this application.
    """
    self.server = server
    self.config = config
    self.app_id = self.config.application
    self.version = self.config.version
    self.files = {}
    self.in_transaction = False
    self.deployed = False
    self.batching = True
    self.file_batcher = UploadBatcher('file', self.app_id, self.version,
                                      self.server)
    self.blob_batcher = UploadBatcher('blob', self.app_id, self.version,
                                      self.server)

  def AddFile(self, path, file_handle):
    """Adds the provided file to the list to be pushed to the server.

    Args:
      path: The path the file should be uploaded as.
      file_handle: A stream containing data to upload.
    """
    assert not self.in_transaction, 'Already in a transaction.'
    assert file_handle is not None

    reason = appinfo.ValidFilename(path)
    if reason:
      logging.error(reason)
      return

    pos = file_handle.tell()
    content_hash = _Hash(file_handle.read())
    file_handle.seek(pos, 0)

    self.files[path] = content_hash

  def Begin(self):
    """Begins the transaction, returning a list of files that need uploading.

    All calls to AddFile must be made before calling Begin().

    Returns:
      A list of pathnames for files that should be uploaded using UploadFile()
      before Commit() can be called.
    """
    assert not self.in_transaction, 'Already in a transaction.'

    StatusUpdate('Initiating update.')
    self.server.Send('/api/appversion/create', app_id=self.app_id,
                     version=self.version, payload=self.config.ToYAML())
    self.in_transaction = True

    files_to_clone = []
    blobs_to_clone = []
    for path, content_hash in self.files.iteritems():
      mime_type = GetMimeTypeIfStaticFile(self.config, path)
      if mime_type is not None:
        blobs_to_clone.append((path, content_hash, mime_type))
      else:
        files_to_clone.append((path, content_hash))

    files_to_upload = {}

    def CloneFiles(url, files, file_type):
      """Sends files to the given url.

      Args:
        url: the server URL to use.
        files: a list of files
        file_type: the type of the files
      """
      if not files:
        return

      StatusUpdate('Cloning %d %s file%s.' %
                   (len(files), file_type, len(files) != 1 and 's' or ''))
      for i in xrange(0, len(files), MAX_FILES_TO_CLONE):
        if i > 0 and i % MAX_FILES_TO_CLONE == 0:
          StatusUpdate('Cloned %d files.' % i)

        chunk = files[i:min(len(files), i + MAX_FILES_TO_CLONE)]
        result = self.server.Send(url,
                                  app_id=self.app_id, version=self.version,
                                  payload=BuildClonePostBody(chunk))
        if result:
          files_to_upload.update(dict(
              (f, self.files[f]) for f in result.split(LIST_DELIMITER)))

    CloneFiles('/api/appversion/cloneblobs', blobs_to_clone, 'static')
    CloneFiles('/api/appversion/clonefiles', files_to_clone, 'application')

    logging.debug('Files to upload: %s', files_to_upload)

    self.files = files_to_upload
    return sorted(files_to_upload.iterkeys())

  def UploadFile(self, path, file_handle):
    """Uploads a file to the hosting service.

    Must only be called after Begin().
    The path provided must be one of those that were returned by Begin().

    Args:
      path: The path the file is being uploaded as.
      file_handle: A file-like object containing the data to upload.

    Raises:
      KeyError: The provided file is not amongst those to be uploaded.
    """
    assert self.in_transaction, 'Begin() must be called before UploadFile().'
    if path not in self.files:
      raise KeyError('File \'%s\' is not in the list of files to be uploaded.'
                     % path)

    del self.files[path]
    mime_type = GetMimeTypeIfStaticFile(self.config, path)
    payload = file_handle.read()
    if mime_type is None:
      self.file_batcher.AddToBatch(path, payload, mime_type)
    else:
      self.blob_batcher.AddToBatch(path, payload, mime_type)

  def Precompile(self):
    """Handle bytecode precompilation."""
    StatusUpdate('Precompilation starting.')
    files = []
    while True:
      if files:
        StatusUpdate('Precompilation: %d files left.' % len(files))
      files = self.PrecompileBatch(files)
      if not files:
        break
    StatusUpdate('Precompilation completed.')

  def PrecompileBatch(self, files):
    """Precompile a batch of files.

    Args:
      files: Either an empty list (for the initial request) or a list
        of files to be precompiled.

    Returns:
      Either an empty list (if no more files need to be precompiled)
      or a list of files to be precompiled subsequently.
    """
    payload = LIST_DELIMITER.join(files)
    response = self.server.Send('/api/appversion/precompile',
                                app_id=self.app_id,
                                version=self.version,
                                payload=payload)
    if not response:
      return []
    return response.split(LIST_DELIMITER)

  def Commit(self):
    """Commits the transaction, making the new app version available.

    All the files returned by Begin() must have been uploaded with UploadFile()
    before Commit() can be called.

    This tries the new 'deploy' method; if that fails it uses the old 'commit'.

    Raises:
      Exception: Some required files were not uploaded.
    """
    assert self.in_transaction, 'Begin() must be called before Commit().'
    if self.files:
      raise Exception('Not all required files have been uploaded.')

    try:
      self.Deploy()
      if not RetryWithBackoff(1, 2, 60, 20, self.IsReady):
        logging.warning('Version still not ready to serve, aborting.')
        raise Exception('Version not ready.')
      self.StartServing()
    except urllib2.HTTPError, e:
      if e.code != 404:
        raise
      StatusUpdate('Closing update.')
      self.server.Send('/api/appversion/commit', app_id=self.app_id,
                       version=self.version)
      self.in_transaction = False

  def Deploy(self):
    """Deploys the new app version but does not make it default.

    All the files returned by Begin() must have been uploaded with UploadFile()
    before Deploy() can be called.

    Raises:
      Exception: Some required files were not uploaded.
    """
    assert self.in_transaction, 'Begin() must be called before Deploy().'
    if self.files:
      raise Exception('Not all required files have been uploaded.')

    StatusUpdate('Deploying new version.')
    self.server.Send('/api/appversion/deploy', app_id=self.app_id,
                     version=self.version)
    self.deployed = True

  def IsReady(self):
    """Check if the new app version is ready to serve traffic.

    Raises:
      Exception: Deploy has not yet been called.

    Returns:
      True if the server returned the app is ready to serve.
    """
    assert self.deployed, 'Deploy() must be called before IsReady().'

    StatusUpdate('Checking if new version is ready to serve.')
    result = self.server.Send('/api/appversion/isready', app_id=self.app_id,
                              version=self.version)
    return result == '1'

  def StartServing(self):
    """Start serving with the newly created version.

    Raises:
      Exception: Deploy has not yet been called.
    """
    assert self.deployed, 'Deploy() must be called before IsReady().'

    StatusUpdate('Closing update: new version is ready to start serving.')
    self.server.Send('/api/appversion/startserving',
                     app_id=self.app_id, version=self.version)
    self.in_transaction = False

  def Rollback(self):
    """Rolls back the transaction if one is in progress."""
    if not self.in_transaction:
      return
    StatusUpdate('Rolling back the update.')
    self.server.Send('/api/appversion/rollback', app_id=self.app_id,
                     version=self.version)
    self.in_transaction = False
    self.files = {}

  def DoUpload(self, paths, max_size, openfunc):
    """Uploads a new appversion with the given config and files to the server.

    Args:
      paths: An iterator that yields the relative paths of the files to upload.
      max_size: The maximum size file to upload.
      openfunc: A function that takes a path and returns a file-like object.
    """
    logging.info('Reading app configuration.')

    path = ''
    try:
      StatusUpdate('Scanning files on local disk.')
      num_files = 0
      for path in paths:
        file_handle = openfunc(path)
        try:
          file_length = GetFileLength(file_handle)
          if file_length > max_size:
            logging.error('Ignoring file \'%s\': Too long '
                          '(max %d bytes, file is %d bytes)',
                          path, max_size, file_length)
          else:
            logging.info('Processing file \'%s\'', path)
            self.AddFile(path, file_handle)
        finally:
          file_handle.close()
        num_files += 1
        if num_files % 500 == 0:
          StatusUpdate('Scanned %d files.' % num_files)
    except KeyboardInterrupt:
      logging.info('User interrupted. Aborting.')
      raise
    except EnvironmentError, e:
      logging.error('An error occurred processing file \'%s\': %s. Aborting.',
                    path, e)
      raise

    try:
      missing_files = self.Begin()
      if missing_files:
        StatusUpdate('Uploading %d files and blobs.' % len(missing_files))
        num_files = 0
        for missing_file in missing_files:
          file_handle = openfunc(missing_file)
          try:
            self.UploadFile(missing_file, file_handle)
          finally:
            file_handle.close()
          num_files += 1
          if num_files % 500 == 0:
            StatusUpdate('Processed %d out of %s.' %
                         (num_files, len(missing_files)))
        self.file_batcher.Flush()
        self.blob_batcher.Flush()
        StatusUpdate('Uploaded %d files and blobs' % num_files)

      if (self.config.derived_file_type and
          appinfo.PYTHON_PRECOMPILED in self.config.derived_file_type):
        self.Precompile()

      self.Commit()

    except KeyboardInterrupt:
      logging.info('User interrupted. Aborting.')
      self.Rollback()
      raise
    except urllib2.HTTPError, err:
      logging.info('HTTP Error (%s)', err)
      self.Rollback()
      raise
    except:
      logging.exception('An unexpected error occurred. Aborting.')
      self.Rollback()
      raise

    logging.info('Done!')


def FileIterator(base, skip_files, separator=os.path.sep):
  """Walks a directory tree, returning all the files. Follows symlinks.

  Args:
    base: The base path to search for files under.
    skip_files: A regular expression object for files/directories to skip.
    separator: Path separator used by the running system's platform.

  Yields:
    Paths of files found, relative to base.
  """
  dirs = ['']
  while dirs:
    current_dir = dirs.pop()
    for entry in os.listdir(os.path.join(base, current_dir)):
      name = os.path.join(current_dir, entry)
      fullname = os.path.join(base, name)
      if separator == '\\':
        name = name.replace('\\', '/')
      if os.path.isfile(fullname):
        if skip_files.match(name):
          logging.info('Ignoring file \'%s\': File matches ignore regex.', name)
        else:
          yield name
      elif os.path.isdir(fullname):
        if skip_files.match(name):
          logging.info(
              'Ignoring directory \'%s\': Directory matches ignore regex.',
              name)
        else:
          dirs.append(name)


def GetFileLength(fh):
  """Returns the length of the file represented by fh.

  This function is capable of finding the length of any seekable stream,
  unlike os.fstat, which only works on file streams.

  Args:
    fh: The stream to get the length of.

  Returns:
    The length of the stream.
  """
  pos = fh.tell()
  fh.seek(0, 2)
  length = fh.tell()
  fh.seek(pos, 0)
  return length


def GetUserAgent(get_version=GetVersionObject,
                 get_platform=appengine_rpc.GetPlatformToken):
  """Determines the value of the 'User-agent' header to use for HTTP requests.

  If the 'APPCFG_SDK_NAME' environment variable is present, that will be
  used as the first product token in the user-agent.

  Args:
    get_version: Used for testing.
    get_platform: Used for testing.

  Returns:
    String containing the 'user-agent' header value, which includes the SDK
    version, the platform information, and the version of Python;
    e.g., 'appcfg_py/1.0.1 Darwin/9.2.0 Python/2.5.2'.
  """
  product_tokens = []

  sdk_name = os.environ.get('APPCFG_SDK_NAME')
  if sdk_name:
    product_tokens.append(sdk_name)
  else:
    version = get_version()
    if version is None:
      release = 'unknown'
    else:
      release = version['release']

    product_tokens.append('appcfg_py/%s' % release)

  product_tokens.append(get_platform())

  python_version = '.'.join(str(i) for i in sys.version_info)
  product_tokens.append('Python/%s' % python_version)

  return ' '.join(product_tokens)


def GetSourceName(get_version=GetVersionObject):
  """Gets the name of this source version."""
  version = get_version()
  if version is None:
    release = 'unknown'
  else:
    release = version['release']
  return 'Google-appcfg-%s' % (release,)


class AppCfgApp(object):
  """Singleton class to wrap AppCfg tool functionality.

  This class is responsible for parsing the command line and executing
  the desired action on behalf of the user.  Processing files and
  communicating with the server is handled by other classes.

  Attributes:
    actions: A dictionary mapping action names to Action objects.
    action: The Action specified on the command line.
    parser: An instance of optparse.OptionParser.
    options: The command line options parsed by 'parser'.
    argv: The original command line as a list.
    args: The positional command line args left over after parsing the options.
    raw_input_fn: Function used for getting raw user input, like email.
    password_input_fn: Function used for getting user password.
    error_fh: Unexpected HTTPErrors are printed to this file handle.

  Attributes for testing:
    parser_class: The class to use for parsing the command line.  Because
      OptionsParser will exit the program when there is a parse failure, it
      is nice to subclass OptionsParser and catch the error before exiting.
  """

  def __init__(self, argv, parser_class=optparse.OptionParser,
               rpc_server_class=appengine_rpc.HttpRpcServer,
               raw_input_fn=raw_input,
               password_input_fn=getpass.getpass,
               error_fh=sys.stderr,
               update_check_class=UpdateCheck):
    """Initializer.  Parses the cmdline and selects the Action to use.

    Initializes all of the attributes described in the class docstring.
    Prints help or error messages if there is an error parsing the cmdline.

    Args:
      argv: The list of arguments passed to this program.
      parser_class: Options parser to use for this application.
      rpc_server_class: RPC server class to use for this application.
      raw_input_fn: Function used for getting user email.
      password_input_fn: Function used for getting user password.
      error_fh: Unexpected HTTPErrors are printed to this file handle.
      update_check_class: UpdateCheck class (can be replaced for testing).
    """
    self.parser_class = parser_class
    self.argv = argv
    self.rpc_server_class = rpc_server_class
    self.raw_input_fn = raw_input_fn
    self.password_input_fn = password_input_fn
    self.error_fh = error_fh
    self.update_check_class = update_check_class

    self.parser = self._GetOptionParser()
    for action in self.actions.itervalues():
      action.options(self, self.parser)

    self.options, self.args = self.parser.parse_args(argv[1:])

    if len(self.args) < 1:
      self._PrintHelpAndExit()
    if self.args[0] not in self.actions:
      self.parser.error('Unknown action \'%s\'\n%s' %
                        (self.args[0], self.parser.get_description()))
    action_name = self.args.pop(0)
    self.action = self.actions[action_name]

    self.parser, self.options = self._MakeSpecificParser(self.action)

    if self.options.help:
      self._PrintHelpAndExit()

    if self.options.verbose == 2:
      logging.getLogger().setLevel(logging.INFO)
    elif self.options.verbose == 3:
      logging.getLogger().setLevel(logging.DEBUG)

    global verbosity
    verbosity = self.options.verbose

  def Run(self):
    """Executes the requested action.

    Catches any HTTPErrors raised by the action and prints them to stderr.

    Returns:
      1 on error, 0 if successful.
    """
    try:
      self.action(self)
    except urllib2.HTTPError, e:
      body = e.read()
      print >>self.error_fh, ('Error %d: --- begin server output ---\n'
                              '%s\n--- end server output ---' %
                              (e.code, body.rstrip('\n')))
      return 1
    except yaml_errors.EventListenerError, e:
      print >>self.error_fh, ('Error parsing yaml file:\n%s' % e)
      return 1
    return 0

  def _GetActionDescriptions(self):
    """Returns a formatted string containing the short_descs for all actions."""
    action_names = self.actions.keys()
    action_names.sort()
    desc = ''
    for action_name in action_names:
      desc += '  %s: %s\n' % (action_name, self.actions[action_name].short_desc)
    return desc

  def _GetOptionParser(self):
    """Creates an OptionParser with generic usage and description strings.

    Returns:
      An OptionParser instance.
    """

    class Formatter(optparse.IndentedHelpFormatter):
      """Custom help formatter that does not reformat the description."""

      def format_description(self, description):
        """Very simple formatter."""
        return description + '\n'

    desc = self._GetActionDescriptions()
    desc = ('Action must be one of:\n%s'
            'Use \'help <action>\' for a detailed description.') % desc

    parser = self.parser_class(usage='%prog [options] <action>',
                               description=desc,
                               formatter=Formatter(),
                               conflict_handler='resolve')
    parser.add_option('-h', '--help', action='store_true',
                      dest='help', help='Show the help message and exit.')
    parser.add_option('-q', '--quiet', action='store_const', const=0,
                      dest='verbose', help='Print errors only.')
    parser.add_option('-v', '--verbose', action='store_const', const=2,
                      dest='verbose', default=1,
                      help='Print info level logs.')
    parser.add_option('--noisy', action='store_const', const=3,
                      dest='verbose', help='Print all logs.')
    parser.add_option('-s', '--server', action='store', dest='server',
                      default='appengine.google.com',
                      metavar='SERVER', help='The server to connect to.')
    parser.add_option('--secure', action='store_true', dest='secure',
                      default=True, help=optparse.SUPPRESS_HELP)
    parser.add_option('--insecure', action='store_false', dest='secure',
                      help='Use HTTP when communicating with the server.')
    parser.add_option('-e', '--email', action='store', dest='email',
                      metavar='EMAIL', default=None,
                      help='The username to use. Will prompt if omitted.')
    parser.add_option('-H', '--host', action='store', dest='host',
                      metavar='HOST', default=None,
                      help='Overrides the Host header sent with all RPCs.')
    parser.add_option('--no_cookies', action='store_false',
                      dest='save_cookies', default=True,
                      help='Do not save authentication cookies to local disk.')
    parser.add_option('--passin', action='store_true',
                      dest='passin', default=False,
                      help='Read the login password from stdin.')
    parser.add_option('-A', '--application', action='store', dest='app_id',
                      help='Override application from app.yaml file.')
    parser.add_option('-V', '--version', action='store', dest='version',
                      help='Override (major) version from app.yaml file.')
    return parser

  def _MakeSpecificParser(self, action):
    """Creates a new parser with documentation specific to 'action'.

    Args:
      action: An Action instance to be used when initializing the new parser.

    Returns:
      A tuple containing:
      parser: An instance of OptionsParser customized to 'action'.
      options: The command line options after re-parsing.
    """
    parser = self._GetOptionParser()
    parser.set_usage(action.usage)
    parser.set_description('%s\n%s' % (action.short_desc, action.long_desc))
    action.options(self, parser)
    options, unused_args = parser.parse_args(self.argv[1:])
    return parser, options

  def _PrintHelpAndExit(self, exit_code=2):
    """Prints the parser's help message and exits the program.

    Args:
      exit_code: The integer code to pass to sys.exit().
    """
    self.parser.print_help()
    sys.exit(exit_code)

  def _GetRpcServer(self):
    """Returns an instance of an AbstractRpcServer.

    Returns:
      A new AbstractRpcServer, on which RPC calls can be made.
    """

    def GetUserCredentials():
      """Prompts the user for a username and password."""
      email = self.options.email
      if email is None:
        email = self.raw_input_fn('Email: ')

      password_prompt = 'Password for %s: ' % email
      if self.options.passin:
        password = self.raw_input_fn(password_prompt)
      else:
        password = self.password_input_fn(password_prompt)

      return (email, password)

    StatusUpdate('Server: %s.' % self.options.server)

    if self.options.host and self.options.host == 'localhost':
      email = self.options.email
      if email is None:
        email = 'test@example.com'
        logging.info('Using debug user %s.  Override with --email' % email)
      server = self.rpc_server_class(
          self.options.server,
          lambda: (email, 'password'),
          GetUserAgent(),
          GetSourceName(),
          host_override=self.options.host,
          save_cookies=self.options.save_cookies,

          secure=False)
      server.authenticated = True
      return server

    if self.options.passin:
      auth_tries = 1
    else:
      auth_tries = 3

    return self.rpc_server_class(self.options.server, GetUserCredentials,
                                 GetUserAgent(), GetSourceName(),
                                 host_override=self.options.host,
                                 save_cookies=self.options.save_cookies,
                                 auth_tries=auth_tries,
                                 account_type='HOSTED_OR_GOOGLE',
                                 secure=self.options.secure)

  def _FindYaml(self, basepath, file_name):
    """Find yaml files in application directory.

    Args:
      basepath: Base application directory.
      file_name: Filename without extension to search for.

    Returns:
      Path to located yaml file if one exists, else None.
    """
    if not os.path.isdir(basepath):
      self.parser.error('Not a directory: %s' % basepath)

    for yaml_file in (file_name + '.yaml', file_name + '.yml'):
      yaml_path = os.path.join(basepath, yaml_file)
      if os.path.isfile(yaml_path):
        return yaml_path

    return None

  def _ParseAppYaml(self, basepath):
    """Parses the app.yaml file.

    Args:
      basepath: the directory of the application.

    Returns:
      An AppInfoExternal object.
    """
    appyaml_filename = self._FindYaml(basepath, 'app')
    if appyaml_filename is None:
      self.parser.error('Directory does not contain an app.yaml '
                        'configuration file.')

    fh = open(appyaml_filename, 'r')
    try:
      appyaml = appinfo.LoadSingleAppInfo(fh)
    finally:
      fh.close()
    orig_application = appyaml.application
    orig_version = appyaml.version
    if self.options.app_id:
      appyaml.application = self.options.app_id
    if self.options.version:
      appyaml.version = self.options.version
    msg = 'Application: %s' % appyaml.application
    if appyaml.application != orig_application:
      msg += ' (was: %s)' % orig_application
    msg += '; version: %s' % appyaml.version
    if appyaml.version != orig_version:
      msg += ' (was: %s)' % orig_version
    msg += '.'
    StatusUpdate(msg)
    return appyaml

  def _ParseYamlFile(self, basepath, basename, parser):
    """Parses the a yaml file.

    Args:
      basepath: the directory of the application.
      basename: the base name of the file (with the '.yaml' stripped off).
      parser: the function or method used to parse the file.

    Returns:
      A single parsed yaml file or None if the file does not exist.
    """
    file_name = self._FindYaml(basepath, basename)
    if file_name is not None:
      fh = open(file_name, 'r')
      try:
        defns = parser(fh)
      finally:
        fh.close()
      return defns
    return None

  def _ParseIndexYaml(self, basepath):
    """Parses the index.yaml file.

    Args:
      basepath: the directory of the application.

    Returns:
      A single parsed yaml file or None if the file does not exist.
    """
    return self._ParseYamlFile(basepath, 'index',
                               datastore_index.ParseIndexDefinitions)

  def _ParseCronYaml(self, basepath):
    """Parses the cron.yaml file.

    Args:
      basepath: the directory of the application.

    Returns:
      A CronInfoExternal object or None if the file does not exist.
    """
    return self._ParseYamlFile(basepath, 'cron', croninfo.LoadSingleCron)

  def _ParseQueueYaml(self, basepath):
    """Parses the queue.yaml file.

    Args:
      basepath: the directory of the application.

    Returns:
      A CronInfoExternal object or None if the file does not exist.
    """
    return self._ParseYamlFile(basepath, 'queue', queueinfo.LoadSingleQueue)

  def _ParseDosYaml(self, basepath):
    """Parses the dos.yaml file.

    Args:
      basepath: the directory of the application.

    Returns:
      A DosInfoExternal object or None if the file does not exist.
    """
    return self._ParseYamlFile(basepath, 'dos', dosinfo.LoadSingleDos)

  def Help(self):
    """Prints help for a specific action.

    Expects self.args[0] to contain the name of the action in question.
    Exits the program after printing the help message.
    """
    if len(self.args) != 1 or self.args[0] not in self.actions:
      self.parser.error('Expected a single action argument. Must be one of:\n' +
                        self._GetActionDescriptions())

    action = self.actions[self.args[0]]
    self.parser, unused_options = self._MakeSpecificParser(action)
    self._PrintHelpAndExit(exit_code=0)

  def Update(self):
    """Updates and deploys a new appversion."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()

    updatecheck = self.update_check_class(rpc_server, appyaml)
    updatecheck.CheckForUpdates()

    appversion = AppVersionUpload(rpc_server, appyaml)
    appversion.DoUpload(FileIterator(basepath, appyaml.skip_files),
                        self.options.max_size,
                        lambda path: open(os.path.join(basepath, path), 'rb'))

    index_defs = self._ParseIndexYaml(basepath)
    if index_defs:
      index_upload = IndexDefinitionUpload(rpc_server, appyaml, index_defs)
      try:
        index_upload.DoUpload()
      except urllib2.HTTPError, e:
        StatusUpdate('Error %d: --- begin server output ---\n'
                     '%s\n--- end server output ---' %
                     (e.code, e.read().rstrip('\n')))
        print >> self.error_fh, (
            'Your app was updated, but there was an error updating your '
            'indexes. Please retry later with appcfg.py update_indexes.')

    cron_entries = self._ParseCronYaml(basepath)
    if cron_entries:
      cron_upload = CronEntryUpload(rpc_server, appyaml, cron_entries)
      cron_upload.DoUpload()

    queue_entries = self._ParseQueueYaml(basepath)
    if queue_entries:
      queue_upload = QueueEntryUpload(rpc_server, appyaml, queue_entries)
      queue_upload.DoUpload()

    dos_entries = self._ParseDosYaml(basepath)
    if dos_entries:
      dos_upload = DosEntryUpload(rpc_server, appyaml, dos_entries)
      dos_upload.DoUpload()

  def _UpdateOptions(self, parser):
    """Adds update-specific options to 'parser'.

    Args:
      parser: An instance of OptionsParser.
    """
    parser.add_option('-S', '--max_size', type='int', dest='max_size',
                      default=10485760, metavar='SIZE',
                      help='Maximum size of a file to upload.')

  def VacuumIndexes(self):
    """Deletes unused indexes."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    config = self._ParseAppYaml(basepath)

    index_defs = self._ParseIndexYaml(basepath)
    if index_defs is None:
      index_defs = datastore_index.IndexDefinitions()

    rpc_server = self._GetRpcServer()
    vacuum = VacuumIndexesOperation(rpc_server,
                                    config,
                                    self.options.force_delete)
    vacuum.DoVacuum(index_defs)

  def _VacuumIndexesOptions(self, parser):
    """Adds vacuum_indexes-specific options to 'parser'.

    Args:
      parser: An instance of OptionsParser.
    """
    parser.add_option('-f', '--force', action='store_true', dest='force_delete',
                      default=False,
                      help='Force deletion without being prompted.')

  def UpdateCron(self):
    """Updates any new or changed cron definitions."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()

    cron_entries = self._ParseCronYaml(basepath)
    if cron_entries:
      cron_upload = CronEntryUpload(rpc_server, appyaml, cron_entries)
      cron_upload.DoUpload()

  def UpdateIndexes(self):
    """Updates indexes."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()

    index_defs = self._ParseIndexYaml(basepath)
    if index_defs:
      index_upload = IndexDefinitionUpload(rpc_server, appyaml, index_defs)
      index_upload.DoUpload()

  def UpdateQueues(self):
    """Updates any new or changed task queue definitions."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()

    queue_entries = self._ParseQueueYaml(basepath)
    if queue_entries:
      queue_upload = QueueEntryUpload(rpc_server, appyaml, queue_entries)
      queue_upload.DoUpload()

  def UpdateDos(self):
    """Updates any new or changed dos definitions."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()

    dos_entries = self._ParseDosYaml(basepath)
    if dos_entries:
      dos_upload = DosEntryUpload(rpc_server, appyaml, dos_entries)
      dos_upload.DoUpload()

  def Rollback(self):
    """Does a rollback of any existing transaction for this app version."""
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)

    appversion = AppVersionUpload(self._GetRpcServer(), appyaml)
    appversion.in_transaction = True
    appversion.Rollback()

  def RequestLogs(self):
    """Write request logs to a file."""
    if len(self.args) != 2:
      self.parser.error(
          'Expected a <directory> argument and an <output_file> argument.')
    if (self.options.severity is not None and
        not 0 <= self.options.severity <= MAX_LOG_LEVEL):
      self.parser.error(
          'Severity range is 0 (DEBUG) through %s (CRITICAL).' % MAX_LOG_LEVEL)

    if self.options.num_days is None:
      self.options.num_days = int(not self.options.append)

    try:
      end_date = self._ParseEndDate(self.options.end_date)
    except (TypeError, ValueError):
      self.parser.error('End date must be in the format YYYY-MM-DD.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)
    rpc_server = self._GetRpcServer()
    logs_requester = LogsRequester(rpc_server, appyaml, self.args[1],
                                   self.options.num_days,
                                   self.options.append,
                                   self.options.severity,
                                   end_date,
                                   self.options.vhost,
                                   self.options.include_vhost)
    logs_requester.DownloadLogs()

  def _ParseEndDate(self, date, time_func=time.time):
    """Translates an ISO 8601 date to a date object.

    Args:
      date: A date string as YYYY-MM-DD.
      time_func: time.time() function for testing.

    Returns:
      A date object representing the last day of logs to get.
      If no date is given, returns today in the US/Pacific timezone.
    """
    if not date:
      return PacificDate(time_func())
    return datetime.date(*[int(i) for i in date.split('-')])

  def _RequestLogsOptions(self, parser):
    """Adds request_logs-specific options to 'parser'.

    Args:
      parser: An instance of OptionsParser.
    """
    parser.add_option('-n', '--num_days', type='int', dest='num_days',
                      action='store', default=None,
                      help='Number of days worth of log data to get. '
                      'The cut-off point is midnight US/Pacific. '
                      'Use 0 to get all available logs. '
                      'Default is 1, unless --append is also given; '
                      'then the default is 0.')
    parser.add_option('-a', '--append', dest='append',
                      action='store_true', default=False,
                      help='Append to existing file.')
    parser.add_option('--severity', type='int', dest='severity',
                      action='store', default=None,
                      help='Severity of app-level log messages to get. '
                      'The range is 0 (DEBUG) through 4 (CRITICAL). '
                      'If omitted, only request logs are returned.')
    parser.add_option('--vhost', type='string', dest='vhost',
                      action='store', default=None,
                      help='The virtual host of log messages to get. '
                      'If omitted, all log messages are returned.')
    parser.add_option('--include_vhost', dest='include_vhost',
                      action='store_true', default=False,
                      help='Include virtual host in log messages.')
    parser.add_option('--end_date', dest='end_date',
                      action='store', default='',
                      help='End date (as YYYY-MM-DD) of period for log data. '
                      'Defaults to today.')

  def CronInfo(self, now=None, output=sys.stdout):
    """Displays information about cron definitions.

    Args:
      now: used for testing.
      output: Used for testing.
    """
    if len(self.args) != 1:
      self.parser.error('Expected a single <directory> argument.')
    if now is None:
      now = datetime.datetime.now()

    basepath = self.args[0]
    cron_entries = self._ParseCronYaml(basepath)
    if cron_entries and cron_entries.cron:
      for entry in cron_entries.cron:
        description = entry.description
        if not description:
          description = '<no description>'
        print >>output, '\n%s:\nURL: %s\nSchedule: %s' % (description,
                                                          entry.url,
                                                          entry.schedule)
        schedule = groctimespecification.GrocTimeSpecification(entry.schedule)
        matches = schedule.GetMatches(now, self.options.num_runs)
        for match in matches:
          print >>output, '%s, %s from now' % (
              match.strftime('%Y-%m-%d %H:%M:%S'), match - now)

  def _CronInfoOptions(self, parser):
    """Adds cron_info-specific options to 'parser'.

    Args:
      parser: An instance of OptionsParser.
    """
    parser.add_option('-n', '--num_runs', type='int', dest='num_runs',
                      action='store', default=5,
                      help='Number of runs of each cron job to display'
                      'Default is 5')

  def _CheckRequiredLoadOptions(self):
    """Checks that upload/download options are present."""
    for option in ['filename', 'kind', 'config_file']:
      if getattr(self.options, option) is None:
        self.parser.error('Option \'%s\' is required.' % option)
    if not self.options.url:
      self.parser.error('You must have google.appengine.ext.remote_api.handler '
                        'assigned to an endpoint in app.yaml, or provide '
                        'the url of the handler via the \'url\' option.')

  def InferRemoteApiUrl(self, appyaml):
    """Uses app.yaml to determine the remote_api endpoint.

    Args:
      appyaml: A parsed app.yaml file.

    Returns:
      The url of the remote_api endpoint as a string, or None
    """
    handlers = appyaml.handlers
    handler_suffix = 'remote_api/handler.py'
    app_id = appyaml.application
    for handler in handlers:
      if hasattr(handler, 'script') and handler.script:
        if handler.script.endswith(handler_suffix):
          server = self.options.server
          if server == 'appengine.google.com':
            return 'http://%s.appspot.com%s' % (app_id, handler.url)
          else:
            return 'http://%s%s' % (server, handler.url)
    return None

  def RunBulkloader(self, arg_dict):
    """Invokes the bulkloader with the given keyword arguments.

    Args:
      arg_dict: Dictionary of arguments to pass to bulkloader.Run().
    """
    try:
      import sqlite3
    except ImportError:
      logging.error('upload_data action requires SQLite3 and the python '
                    'sqlite3 module (included in python since 2.5).')
      sys.exit(1)

    sys.exit(bulkloader.Run(arg_dict))

  def _SetupLoad(self):
    """Performs common verification and set up for upload and download."""
    if len(self.args) != 1:
      self.parser.error('Expected <directory> argument.')

    basepath = self.args[0]
    appyaml = self._ParseAppYaml(basepath)

    self.options.app_id = appyaml.application

    if not self.options.url:
      url = self.InferRemoteApiUrl(appyaml)
      if url is not None:
        self.options.url = url

    self._CheckRequiredLoadOptions()

    if self.options.batch_size < 1:
      self.parser.error('batch_size must be 1 or larger.')

    if verbosity == 1:
      logging.getLogger().setLevel(logging.INFO)
      self.options.debug = False
    else:
      logging.getLogger().setLevel(logging.DEBUG)
      self.options.debug = True

  def _MakeLoaderArgs(self):
    return dict([(arg_name, getattr(self.options, arg_name, None)) for
                 arg_name in (
                     'app_id',
                     'url',
                     'filename',
                     'batch_size',
                     'kind',
                     'num_threads',
                     'bandwidth_limit',
                     'rps_limit',
                     'http_limit',
                     'db_filename',
                     'config_file',
                     'auth_domain',
                     'has_header',
                     'loader_opts',
                     'log_file',
                     'passin',
                     'email',
                     'debug',
                     'exporter_opts',
                     'mapper_opts',
                     'result_db_filename',
                     'mapper_opts',
                     'dry_run',
                     'dump',
                     'restore',
                     )])

  def PerformDownload(self, run_fn=None):
    """Performs a datastore download via the bulkloader.

    Args:
      run_fn: Function to invoke the bulkloader, used for testing.
    """
    if run_fn is None:
      run_fn = self.RunBulkloader
    self._SetupLoad()

    StatusUpdate('Downloading data records.')

    args = self._MakeLoaderArgs()
    args['download'] = True
    args['has_header'] = False
    args['map'] = False
    args['dump'] = False
    args['restore'] = False

    run_fn(args)

  def PerformUpload(self, run_fn=None):
    """Performs a datastore upload via the bulkloader.

    Args:
      run_fn: Function to invoke the bulkloader, used for testing.
    """
    if run_fn is None:
      run_fn = self.RunBulkloader
    self._SetupLoad()

    StatusUpdate('Uploading data records.')

    args = self._MakeLoaderArgs()
    args['download'] = False
    args['map'] = False
    args['dump'] = False
    args['restore'] = False

    run_fn(args)

  def _PerformLoadOptions(self, parser):
    """Adds options common to 'upload_data' and 'download_data'.

    Args:
      parser: An instance of OptionsParser.
    """
    parser.add_option('--filename', type='string', dest='filename',
                      action='store',
                      help='The name of the file containing the input data.'
                      ' (Required)')
    parser.add_option('--config_file', type='string', dest='config_file',
                      action='store',
                      help='Name of the configuration file. (Required)')
    parser.add_option('--kind', type='string', dest='kind',
                      action='store',
                      help='The kind of the entities to store. (Required)')
    parser.add_option('--url', type='string', dest='url',
                      action='store',
                      help='The location of the remote_api endpoint.')
    parser.add_option('--num_threads', type='int', dest='num_threads',
                      action='store', default=10,
                      help='Number of threads to upload records with.')
    parser.add_option('--batch_size', type='int', dest='batch_size',
                      action='store', default=10,
                      help='Number of records to post in each request.')
    parser.add_option('--bandwidth_limit', type='int', dest='bandwidth_limit',
                      action='store', default=250000,
                      help='The maximum bytes/second bandwidth for transfers.')
    parser.add_option('--rps_limit', type='int', dest='rps_limit',
                      action='store', default=20,
                      help='The maximum records/second for transfers.')
    parser.add_option('--http_limit', type='int', dest='http_limit',
                      action='store', default=8,
                      help='The maximum requests/second for transfers.')
    parser.add_option('--db_filename', type='string', dest='db_filename',
                      action='store',
                      help='Name of the progress database file.')
    parser.add_option('--auth_domain', type='string', dest='auth_domain',
                      action='store', default='gmail.com',
                      help='The name of the authorization domain to use.')
    parser.add_option('--log_file', type='string', dest='log_file',
                      help='File to write bulkloader logs.  If not supplied '
                      'then a new log file will be created, named: '
                      'bulkloader-log-TIMESTAMP.')
    parser.add_option('--dry_run', action='store_true',
                      dest='dry_run', default=False,
                      help='Do not execute any remote_api calls')

  def _PerformUploadOptions(self, parser):
    """Adds 'upload_data' specific options to the 'parser' passed in.

    Args:
      parser: An instance of OptionsParser.
    """
    self._PerformLoadOptions(parser)
    parser.add_option('--has_header', dest='has_header',
                      action='store_true', default=False,
                      help='Whether the first line of the input file should be'
                      ' skipped')
    parser.add_option('--loader_opts', type='string', dest='loader_opts',
                      help='A string to pass to the Loader.initialize method.')

  def _PerformDownloadOptions(self, parser):
    """Adds 'download_data' specific options to the 'parser' passed in.

    Args:
      parser: An instance of OptionsParser.
    """
    self._PerformLoadOptions(parser)
    parser.add_option('--exporter_opts', type='string', dest='exporter_opts',
                      help='A string to pass to the Exporter.initialize method.'
                     )
    parser.add_option('--result_db_filename', type='string',
                      dest='result_db_filename',
                      action='store',
                      help='Database to write entities to for download.')

  class Action(object):
    """Contains information about a command line action.

    Attributes:
      function: The name of a function defined on AppCfg or its subclasses
        that will perform the appropriate action.
      usage: A command line usage string.
      short_desc: A one-line description of the action.
      long_desc: A detailed description of the action.  Whitespace and
        formatting will be preserved.
      options: A function that will add extra options to a given OptionParser
        object.
    """

    def __init__(self, function, usage, short_desc, long_desc='',
                 options=lambda obj, parser: None):
      """Initializer for the class attributes."""
      self.function = function
      self.usage = usage
      self.short_desc = short_desc
      self.long_desc = long_desc
      self.options = options

    def __call__(self, appcfg):
      """Invoke this Action on the specified AppCfg.

      This calls the function of the appropriate name on AppCfg, and
      respects polymophic overrides.

      Args:
        appcfg: The appcfg to use.
      Returns:
        The result of the function call.
      """
      method = getattr(appcfg, self.function)
      return method()

  actions = {

      'help': Action(
          function='Help',
          usage='%prog help <action>',
          short_desc='Print help for a specific action.'),

      'update': Action(
          function='Update',
          usage='%prog [options] update <directory>',
          options=_UpdateOptions,
          short_desc='Create or update an app version.',
          long_desc="""
Specify a directory that contains all of the files required by
the app, and appcfg.py will create/update the app version referenced
in the app.yaml file at the top level of that directory.  appcfg.py
will follow symlinks and recursively upload all files to the server.
Temporary or source control files (e.g. foo~, .svn/*) will be skipped."""),

      'update_cron': Action(
          function='UpdateCron',
          usage='%prog [options] update_cron <directory>',
          short_desc='Update application cron definitions.',
          long_desc="""
The 'update_cron' command will update any new, removed or changed cron
definitions from the optional cron.yaml file."""),

      'update_indexes': Action(
          function='UpdateIndexes',
          usage='%prog [options] update_indexes <directory>',
          short_desc='Update application indexes.',
          long_desc="""
The 'update_indexes' command will add additional indexes which are not currently
in production as well as restart any indexes that were not completed."""),

      'update_queues': Action(
          function='UpdateQueues',
          usage='%prog [options] update_queues <directory>',
          short_desc='Update application task queue definitions.',
          long_desc="""
The 'update_queue' command will update any new, removed or changed task queue
definitions from the optional queue.yaml file."""),

      'update_dos': Action(
          function='UpdateDos',
          usage='%prog [options] update_dos <directory>',
          short_desc='Update application dos definitions.',
          long_desc="""
The 'update_dos' command will update any new, removed or changed dos
definitions from the optional dos.yaml file."""),

      'vacuum_indexes': Action(
          function='VacuumIndexes',
          usage='%prog [options] vacuum_indexes <directory>',
          options=_VacuumIndexesOptions,
          short_desc='Delete unused indexes from application.',
          long_desc="""
The 'vacuum_indexes' command will help clean up indexes which are no longer
in use.  It does this by comparing the local index configuration with
indexes that are actually defined on the server.  If any indexes on the
server do not exist in the index configuration file, the user is given the
option to delete them."""),

      'rollback': Action(
          function='Rollback',
          usage='%prog [options] rollback <directory>',
          short_desc='Rollback an in-progress update.',
          long_desc="""
The 'update' command requires a server-side transaction.  Use 'rollback'
if you get an error message about another transaction being in progress
and you are sure that there is no such transaction."""),

      'request_logs': Action(
          function='RequestLogs',
          usage='%prog [options] request_logs <directory> <output_file>',
          options=_RequestLogsOptions,
          short_desc='Write request logs in Apache common log format.',
          long_desc="""
The 'request_logs' command exports the request logs from your application
to a file.  It will write Apache common log format records ordered
chronologically.  If output file is '-' stdout will be written."""),

      'cron_info': Action(
          function='CronInfo',
          usage='%prog [options] cron_info <directory>',
          options=_CronInfoOptions,
          short_desc='Display information about cron jobs.',
          long_desc="""
The 'cron_info' command will display the next 'number' runs (default 5) for
each cron job defined in the cron.yaml file."""),

      'upload_data': Action(
          function='PerformUpload',
          usage='%prog [options] upload_data <directory>',
          options=_PerformUploadOptions,
          short_desc='Upload data records to datastore.',
          long_desc="""
The 'upload_data' command translates input records into datastore entities and
uploads them into your application's datastore."""),

      'download_data': Action(
          function='PerformDownload',
          usage='%prog [options] download_data <directory>',
          options=_PerformDownloadOptions,
          short_desc='Download entities from datastore.',
          long_desc="""
The 'download_data' command downloads datastore entities and writes them to
file as CSV or developer defined format."""),



  }


def main(argv):
  logging.basicConfig(format=('%(asctime)s %(levelname)s %(filename)s:'
                              '%(lineno)s %(message)s '))
  try:
    result = AppCfgApp(argv).Run()
    if result:
      sys.exit(result)
  except KeyboardInterrupt:
    StatusUpdate('Interrupted.')
    sys.exit(1)


if __name__ == '__main__':
  main(sys.argv)
