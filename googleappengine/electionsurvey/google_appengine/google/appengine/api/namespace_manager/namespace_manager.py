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

"""Control the namespacing system used by various APIs.

A namespace may be specified in various API calls exemplified
by the datastore and memcache interfaces.  The default can be
specified using this module.
"""



import os

__all__ = ['set_namespace',
           'get_namespace',
           'enable_request_namespace',
          ]


_ENV_DEFAULT_NAMESPACE = 'HTTP_X_APPENGINE_DEFAULT_NAMESPACE'
_ENV_CURRENT_NAMESPACE = '__INTERNAL_CURRENT_NAMESPACE'


def set_namespace(namespace):
  """Set the default namespace for the current HTTP request.

  Args:
    namespace: A string naming the new namespace to use. A value of None
      will clear the default namespace value.
  """
  if namespace is None:
    os.environ.pop(_ENV_CURRENT_NAMESPACE, None)
  else:
    os.environ[_ENV_CURRENT_NAMESPACE] = namespace


def get_namespace():
  """Get the the current default namespace."""
  return os.getenv(_ENV_CURRENT_NAMESPACE, '')


def enable_request_namespace():
  """Automatically enable namespace to default to Apps domain.

  Calling this function will automatically default the namespace to the
  chosen Google Apps domain for the current request only if the
  default namespace is clear or not set to a non 'None' value.
  """
  if _ENV_CURRENT_NAMESPACE not in os.environ:
    if _ENV_DEFAULT_NAMESPACE in os.environ:
      os.environ[_ENV_CURRENT_NAMESPACE] = os.environ[_ENV_DEFAULT_NAMESPACE]
