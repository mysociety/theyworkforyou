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

"""An interactive python shell that uses remote_api.

Usage:
  remote_api_shell.py [-s HOSTNAME] APPID [PATH]
"""


import atexit
import code
import getpass
import optparse
import os
import sys

try:
  import readline
except ImportError:
  readline = None

from google.appengine.ext.remote_api import remote_api_stub

from google.appengine.api import datastore
from google.appengine.api import memcache
from google.appengine.api import urlfetch
from google.appengine.api import users
from google.appengine.ext import db
from google.appengine.ext import search


HISTORY_PATH = os.path.expanduser('~/.remote_api_shell_history')
DEFAULT_PATH = '/remote_api'
BANNER = """App Engine remote_api shell
Python %s
The db, users, urlfetch, and memcache modules are imported.""" % sys.version


def auth_func():
  return (raw_input('Email: '), getpass.getpass('Password: '))


def main(argv):
  parser = optparse.OptionParser()
  parser.add_option('-s', '--server', dest='server',
                    help='The hostname your app is deployed on. '
                         'Defaults to <app_id>.appspot.com.')
  (options, args) = parser.parse_args()

  if not args or len(args) > 2:
    print >> sys.stderr, __doc__
    if len(args) > 2:
      print >> sys.stderr, 'Unexpected arguments: %s' % args[2:]
    sys.exit(1)

  appid = args[0]
  if len(args) == 2:
    path = args[1]
  else:
    path = DEFAULT_PATH

  remote_api_stub.ConfigureRemoteApi(appid, path, auth_func,
                                     servername=options.server)
  remote_api_stub.MaybeInvokeAuthentication()

  os.environ['SERVER_SOFTWARE'] = 'Development (remote_api_shell)/1.0'

  sys.ps1 = '%s> ' % appid
  if readline is not None:
    readline.parse_and_bind('tab: complete')
    atexit.register(lambda: readline.write_history_file(HISTORY_PATH))
    if os.path.exists(HISTORY_PATH):
      readline.read_history_file(HISTORY_PATH)

  code.interact(banner=BANNER, local=globals())


if __name__ == '__main__':
  main(sys.argv)
