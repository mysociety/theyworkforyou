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

"""DOS configuration tools.

Library for parsing dos.yaml files and working with these in memory.
"""



import google
import ipaddr

from google.appengine.api import validation
from google.appengine.api import yaml_builder
from google.appengine.api import yaml_listener
from google.appengine.api import yaml_object

_DESCRIPTION_REGEX = r'^.{0,499}$'

BLACKLIST = 'blacklist'
DESCRIPTION = 'description'
SUBNET = 'subnet'


class SubnetValidator(validation.Validator):
  """Checks that a subnet can be parsed and is a valid IPv4 or IPv6 subnet."""

  def Validate(self, value):
    """Validates a subnet."""
    if value is None:
      raise validation.MissingAttribute('subnet must be specified')
    try:
      ipaddr.IP(value)
    except ValueError:
      raise validation.ValidationError('%s is not a valid IPv4 or IPv6 subnet' %
                                       value)
    else:
      return value


class MalformedDosConfiguration(Exception):
  """Configuration file for DOS API is malformed."""


class BlacklistEntry(validation.Validated):
  """A blacklist entry descibes a blocked IP address or subnet."""
  ATTRIBUTES = {
      DESCRIPTION: validation.Optional(_DESCRIPTION_REGEX),
      SUBNET: SubnetValidator(),
  }


class DosInfoExternal(validation.Validated):
  ATTRIBUTES = {
      BLACKLIST: validation.Optional(validation.Repeated(BlacklistEntry)),
  }


def LoadSingleDos(dos_info):
  """Load a dos.yaml file or string and return a DosInfoExternal object.

  Args:
    dos_info: The contents of a dos.yaml file, as a string.

  Returns:
    A DosInfoExternal instance which represents the contents of the parsed yaml
    file.

  Raises:
    MalformedDosConfiguration if the yaml file contains multiple blacklist
      sections.
    yaml_errors.EventError if any errors occured while parsing the yaml file.
  """
  builder = yaml_object.ObjectBuilder(DosInfoExternal)
  handler = yaml_builder.BuilderHandler(builder)
  listener = yaml_listener.EventListener(handler)
  listener.Parse(dos_info)

  dos_info = handler.GetResults()
  if len(dos_info) < 1:
    return DosInfoExternal()
  if len(dos_info) > 1:
    raise MalformedDosConfiguration('Multiple blacklist: sections '
                                    'in configuration.')
  return dos_info[0]
