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

"""AppInfo tools.

Library for working with AppInfo records in memory, store and load from
configuration files.
"""





import re

from google.appengine.api import appinfo_errors
from google.appengine.api import validation
from google.appengine.api import yaml_builder
from google.appengine.api import yaml_listener
from google.appengine.api import yaml_object


_URL_REGEX = r'(?!\^)/|\.|(\(.).*(?!\$).'
_FILES_REGEX = r'(?!\^).*(?!\$).'

_DELTA_REGEX = r'([0-9]+)([DdHhMm]|[sS]?)'
_EXPIRATION_REGEX = r'\s*(%s)(\s+%s)*\s*' % (_DELTA_REGEX, _DELTA_REGEX)

_SERVICE_RE_STRING = r'(mail|xmpp_message|rest)'

_PAGE_NAME_REGEX = r'^.+$'


_EXPIRATION_CONVERSIONS = {
    'd': 60 * 60 * 24,
    'h': 60 * 60,
    'm': 60,
    's': 1,
}

APP_ID_MAX_LEN = 100
MAJOR_VERSION_ID_MAX_LEN = 100
MAX_URL_MAPS = 100

APPLICATION_RE_STRING = r'(?!-)[a-z\d\-]{1,%d}' % APP_ID_MAX_LEN
VERSION_RE_STRING = r'(?!-)[a-z\d\-]{1,%d}' % MAJOR_VERSION_ID_MAX_LEN

RUNTIME_RE_STRING = r'[a-z]{1,30}'

API_VERSION_RE_STRING = r'[\w.]{1,32}'

HANDLER_STATIC_FILES = 'static_files'
HANDLER_STATIC_DIR = 'static_dir'
HANDLER_SCRIPT = 'script'

LOGIN_OPTIONAL = 'optional'
LOGIN_REQUIRED = 'required'
LOGIN_ADMIN = 'admin'

AUTH_FAIL_ACTION_REDIRECT = 'redirect'
AUTH_FAIL_ACTION_UNAUTHORIZED = 'unauthorized'

SECURE_HTTP = 'never'
SECURE_HTTPS = 'always'
SECURE_HTTP_OR_HTTPS = 'optional'
SECURE_DEFAULT = 'default'

REQUIRE_MATCHING_FILE = 'require_matching_file'

DEFAULT_SKIP_FILES = (r'^(.*/)?('
                      r'(app\.yaml)|'
                      r'(app\.yml)|'
                      r'(index\.yaml)|'
                      r'(index\.yml)|'
                      r'(#.*#)|'
                      r'(.*~)|'
                      r'(.*\.py[co])|'
                      r'(.*/RCS/.*)|'
                      r'(\..*)|'
                      r')$')

LOGIN = 'login'
AUTH_FAIL_ACTION = 'auth_fail_action'
SECURE = 'secure'
URL = 'url'
STATIC_FILES = 'static_files'
UPLOAD = 'upload'
STATIC_DIR = 'static_dir'
MIME_TYPE = 'mime_type'
SCRIPT = 'script'
EXPIRATION = 'expiration'

APPLICATION = 'application'
VERSION = 'version'
RUNTIME = 'runtime'
API_VERSION = 'api_version'
HANDLERS = 'handlers'
DEFAULT_EXPIRATION = 'default_expiration'
SKIP_FILES = 'skip_files'
SERVICES = 'inbound_services'
DERIVED_FILE_TYPE = 'derived_file_type'
JAVA_PRECOMPILED = 'java_precompiled'
PYTHON_PRECOMPILED = 'python_precompiled'
ADMIN_CONSOLE = 'admin_console'

PAGES = 'pages'
NAME = 'name'


class URLMap(validation.Validated):
  """Mapping from URLs to handlers.

  This class acts like something of a union type.  Its purpose is to
  describe a mapping between a set of URLs and their handlers.  What
  handler type a given instance has is determined by which handler-id
  attribute is used.

  Each mapping can have one and only one handler type.  Attempting to
  use more than one handler-id attribute will cause an UnknownHandlerType
  to be raised during validation.  Failure to provide any handler-id
  attributes will cause MissingHandlerType to be raised during validation.

  The regular expression used by the url field will be used to match against
  the entire URL path and query string of the request.  This means that
  partial maps will not be matched.  Specifying a url, say /admin, is the
  same as matching against the regular expression '^/admin$'.  Don't begin
  your matching url with ^ or end them with $.  These regular expressions
  won't be accepted and will raise ValueError.

  Attributes:
    login: Whether or not login is required to access URL.  Defaults to
      'optional'.
    secure: Restriction on the protocol which can be used to serve
            this URL/handler (HTTP, HTTPS or either).
    url: Regular expression used to fully match against the request URLs path.
      See Special Cases for using static_dir.
    static_files: Handler id attribute that maps URL to the appropriate
      file.  Can use back regex references to the string matched to url.
    upload: Regular expression used by the application configuration
      program to know which files are uploaded as blobs.  It's very
      difficult to determine this using just the url and static_files
      so this attribute must be included.  Required when defining a
      static_files mapping.
      A matching file name must fully match against the upload regex, similar
      to how url is matched against the request path.  Do not begin upload
      with ^ or end it with $.
    static_dir: Handler id that maps the provided url to a sub-directory
      within the application directory.  See Special Cases.
    mime_type: When used with static_files and static_dir the mime-type
      of files served from those directories are overridden with this
      value.
    script: Handler id that maps URLs to scipt handler within the application
      directory that will run using CGI.
    expiration: When used with static files and directories, the time delta to
      use for cache expiration. Has the form '4d 5h 30m 15s', where each letter
      signifies days, hours, minutes, and seconds, respectively. The 's' for
      seconds may be omitted. Only one amount must be specified, combining
      multiple amounts is optional. Example good values: '10', '1d 6h',
      '1h 30m', '7d 7d 7d', '5m 30'.

  Special cases:
    When defining a static_dir handler, do not use a regular expression
    in the url attribute.  Both the url and static_dir attributes are
    automatically mapped to these equivalents:

      <url>/(.*)
      <static_dir>/\1

    For example:

      url: /images
      static_dir: images_folder

    Is the same as this static_files declaration:

      url: /images/(.*)
      static_files: images/\1
      upload: images/(.*)
  """

  ATTRIBUTES = {

      URL: validation.Optional(_URL_REGEX),
      LOGIN: validation.Options(LOGIN_OPTIONAL,
                                LOGIN_REQUIRED,
                                LOGIN_ADMIN,
                                default=LOGIN_OPTIONAL),

      AUTH_FAIL_ACTION: validation.Options(AUTH_FAIL_ACTION_REDIRECT,
                                           AUTH_FAIL_ACTION_UNAUTHORIZED,
                                           default=AUTH_FAIL_ACTION_REDIRECT),

      SECURE: validation.Options(SECURE_HTTP,
                                 SECURE_HTTPS,
                                 SECURE_HTTP_OR_HTTPS,
                                 SECURE_DEFAULT,
                                 default=SECURE_DEFAULT),



      HANDLER_STATIC_FILES: validation.Optional(_FILES_REGEX),
      UPLOAD: validation.Optional(_FILES_REGEX),


      HANDLER_STATIC_DIR: validation.Optional(_FILES_REGEX),


      MIME_TYPE: validation.Optional(str),
      EXPIRATION: validation.Optional(_EXPIRATION_REGEX),


      HANDLER_SCRIPT: validation.Optional(_FILES_REGEX),

      REQUIRE_MATCHING_FILE: validation.Optional(bool),
  }

  COMMON_FIELDS = set([URL, LOGIN, AUTH_FAIL_ACTION, SECURE])

  ALLOWED_FIELDS = {
      HANDLER_STATIC_FILES: (MIME_TYPE, UPLOAD, EXPIRATION,
                             REQUIRE_MATCHING_FILE),
      HANDLER_STATIC_DIR: (MIME_TYPE, EXPIRATION, REQUIRE_MATCHING_FILE),
      HANDLER_SCRIPT: (),
  }

  def GetHandler(self):
    """Get handler for mapping.

    Returns:
      Value of the handler (determined by handler id attribute).
    """
    return getattr(self, self.GetHandlerType())

  def GetHandlerType(self):
    """Get handler type of mapping.

    Returns:
      Handler type determined by which handler id attribute is set.

    Raises:
      UnknownHandlerType when none of the no handler id attributes
      are set.

      UnexpectedHandlerAttribute when an unexpected attribute
      is set for the discovered handler type.

      HandlerTypeMissingAttribute when the handler is missing a
      required attribute for its handler type.
    """
    for id_field in URLMap.ALLOWED_FIELDS.iterkeys():
      if getattr(self, id_field) is not None:
        mapping_type = id_field
        break
    else:
      raise appinfo_errors.UnknownHandlerType(
          'Unknown url handler type.\n%s' % str(self))

    allowed_fields = URLMap.ALLOWED_FIELDS[mapping_type]

    for attribute in self.ATTRIBUTES.iterkeys():
      if (getattr(self, attribute) is not None and
          not (attribute in allowed_fields or
               attribute in URLMap.COMMON_FIELDS or
               attribute == mapping_type)):
        raise appinfo_errors.UnexpectedHandlerAttribute(
            'Unexpected attribute "%s" for mapping type %s.' %
            (attribute, mapping_type))

    if mapping_type == HANDLER_STATIC_FILES and not self.upload:
      raise appinfo_errors.MissingHandlerAttribute(
          'Missing "%s" attribute for URL "%s".' % (UPLOAD, self.url))

    return mapping_type

  def CheckInitialized(self):
    """Adds additional checking to make sure handler has correct fields.

    In addition to normal ValidatedCheck calls GetHandlerType
    which validates all the handler fields are configured
    properly.

    Raises:
      UnknownHandlerType when none of the no handler id attributes
      are set.

      UnexpectedHandlerAttribute when an unexpected attribute
      is set for the discovered handler type.

      HandlerTypeMissingAttribute when the handler is missing a
      required attribute for its handler type.
    """
    super(URLMap, self).CheckInitialized()
    self.GetHandlerType()


class AdminConsolePage(validation.Validated):
  """Class representing admin console page in AdminConsole object.
  """
  ATTRIBUTES = {
      URL: _URL_REGEX,
      NAME: _PAGE_NAME_REGEX,
      }


class AdminConsole(validation.Validated):
  """Class representing admin console directives in application info.
  """
  ATTRIBUTES = {
      PAGES: validation.Optional(validation.Repeated(AdminConsolePage)),
  }


class AppInfoExternal(validation.Validated):
  """Class representing users application info.

  This class is passed to a yaml_object builder to provide the validation
  for the application information file format parser.

  Attributes:
    application: Unique identifier for application.
    version: Application's major version number.
    runtime: Runtime used by application.
    api_version: Which version of APIs to use.
    handlers: List of URL handlers.
    default_expiration: Default time delta to use for cache expiration for
      all static files, unless they have their own specific 'expiration' set.
      See the URLMap.expiration field's documentation for more information.
    skip_files: An re object.  Files that match this regular expression will
      not be uploaded by appcfg.py.  For example:
        skip_files: |
          .svn.*|
          #.*#
  """

  ATTRIBUTES = {


      APPLICATION: APPLICATION_RE_STRING,
      VERSION: VERSION_RE_STRING,
      RUNTIME: RUNTIME_RE_STRING,


      API_VERSION: API_VERSION_RE_STRING,
      HANDLERS: validation.Optional(validation.Repeated(URLMap)),

      SERVICES: validation.Optional(validation.Repeated(
          validation.Regex(_SERVICE_RE_STRING))),
      DEFAULT_EXPIRATION: validation.Optional(_EXPIRATION_REGEX),
      SKIP_FILES: validation.RegexStr(default=DEFAULT_SKIP_FILES),
      DERIVED_FILE_TYPE: validation.Optional(validation.Repeated(
          validation.Options(JAVA_PRECOMPILED, PYTHON_PRECOMPILED))),
      ADMIN_CONSOLE: validation.Optional(AdminConsole),
  }

  def CheckInitialized(self):
    """Ensures that at least one url mapping is provided.

    Raises:
      MissingURLMapping when no URLMap objects are present in object.
      TooManyURLMappings when there are too many URLMap entries.
    """
    super(AppInfoExternal, self).CheckInitialized()
    if not self.handlers:
      raise appinfo_errors.MissingURLMapping(
          'No URLMap entries found in application configuration')
    if len(self.handlers) > MAX_URL_MAPS:
      raise appinfo_errors.TooManyURLMappings(
          'Found more than %d URLMap entries in application configuration' %
          MAX_URL_MAPS)

  def FixSecureDefaults(self):
    """Force omitted 'secure: ...' handler fields to 'secure: optional'.

    The effect is that handler.secure is never equal to the (nominal)
    default.

    See http://b/issue?id=2073962.
    """
    if self.handlers:
      for handler in self.handlers:
        if handler.secure == SECURE_DEFAULT:
          handler.secure = SECURE_HTTP_OR_HTTPS


def LoadSingleAppInfo(app_info):
  """Load a single AppInfo object where one and only one is expected.

  Args:
    app_info: A file-like object or string.  If it is a string, parse it as
    a configuration file.  If it is a file-like object, read in data and
    parse.

  Returns:
    An instance of AppInfoExternal as loaded from a YAML file.

  Raises:
    ValueError: if a specified service is not valid.
    EmptyConfigurationFile: when there are no documents in YAML file.
    MultipleConfigurationFile: when there is more than one document in YAML
    file.
  """
  builder = yaml_object.ObjectBuilder(AppInfoExternal)
  handler = yaml_builder.BuilderHandler(builder)
  listener = yaml_listener.EventListener(handler)
  listener.Parse(app_info)

  app_infos = handler.GetResults()
  if len(app_infos) < 1:
    raise appinfo_errors.EmptyConfigurationFile()
  if len(app_infos) > 1:
    raise appinfo_errors.MultipleConfigurationFile()
  app_infos[0].FixSecureDefaults()
  return app_infos[0]


def ParseExpiration(expiration):
  """Parses an expiration delta string.

  Args:
    expiration: String that matches _DELTA_REGEX.

  Returns:
    Time delta in seconds.
  """
  delta = 0
  for match in re.finditer(_DELTA_REGEX, expiration):
    amount = int(match.group(1))
    units = _EXPIRATION_CONVERSIONS.get(match.group(2).lower(), 1)
    delta += amount * units
  return delta



_file_path_positive_re = re.compile(r'^[ 0-9a-zA-Z\._\+/\$-]{1,256}$')

_file_path_negative_1_re = re.compile(r'\.\.|^\./|\.$|/\./|^-|^_ah/')

_file_path_negative_2_re = re.compile(r'//|/$')

_file_path_negative_3_re = re.compile(r'^ | $|/ | /')


def ValidFilename(filename):
  """Determines if filename is valid.

  filename must be a valid pathname.
  - It must contain only letters, numbers, _, +, /, $, ., and -.
  - It must be less than 256 chars.
  - It must not contain "/./", "/../", or "//".
  - It must not end in "/".
  - All spaces must be in the middle of a directory or file name.

  Args:
    filename: The filename to validate.

  Returns:
    An error string if the filename is invalid.  Returns '' if the filename
    is valid.
  """
  if _file_path_positive_re.match(filename) is None:
    return 'Invalid character in filename: %s' % filename
  if _file_path_negative_1_re.search(filename) is not None:
    return ('Filename cannot contain "." or ".." '
            'or start with "-" or "_ah/": %s' %
            filename)
  if _file_path_negative_2_re.search(filename) is not None:
    return 'Filename cannot have trailing / or contain //: %s' % filename
  if _file_path_negative_3_re.search(filename) is not None:
    return 'Any spaces must be in the middle of a filename: %s' % filename
  return ''
