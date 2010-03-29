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


AF_INET = None
SOCK_STREAM = None
SOCK_DGRAM = None

_GLOBAL_DEFAULT_TIMEOUT = object()


class error(OSError):
  pass

class herror(error):
  pass

class gaierror(error):
  pass

class timeout(error):
  pass


def _fileobject(fp, mode='rb', bufsize=-1, close=False):
  """Assuming that the argument is a StringIO or file instance."""
  if not hasattr(fp, 'fileno'):
    fp.fileno = lambda: None
  return fp

ssl = None
