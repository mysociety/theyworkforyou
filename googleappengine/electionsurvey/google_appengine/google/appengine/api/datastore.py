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

"""The Python datastore API used by app developers.

Defines Entity, Query, and Iterator classes, as well as methods for all of the
datastore's calls. Also defines conversions between the Python classes and
their PB counterparts.

The datastore errors are defined in the datastore_errors module. That module is
only required to avoid circular imports. datastore imports datastore_types,
which needs BadValueError, so it can't be defined in datastore.
"""






import heapq
import itertools
import logging
import os
import re
import string
import sys
import traceback
from xml.sax import saxutils

from google.appengine.api import api_base_pb
from google.appengine.api import apiproxy_rpc
from google.appengine.api import apiproxy_stub_map
from google.appengine.api import capabilities
from google.appengine.api import datastore_errors
from google.appengine.api import datastore_types
from google.appengine.datastore import datastore_index
from google.appengine.datastore import datastore_pb
from google.appengine.runtime import apiproxy_errors
from google.appengine.datastore import entity_pb

try:
  __import__('google.appengine.api.labs.taskqueue.taskqueue_service_pb')
  taskqueue_service_pb = sys.modules.get(
      'google.appengine.api.labs.taskqueue.taskqueue_service_pb')
except ImportError:
  from google.appengine.api.taskqueue import taskqueue_service_pb

MAX_ALLOWABLE_QUERIES = 30

MAXIMUM_RESULTS = 1000

DEFAULT_TRANSACTION_RETRIES = 3

READ_CAPABILITY = capabilities.CapabilitySet('datastore_v3')
WRITE_CAPABILITY = capabilities.CapabilitySet(
    'datastore_v3',
    capabilities=['write'])

_MAX_INDEXED_PROPERTIES = 5000

_MAX_ID_BATCH_SIZE = 1000 * 1000 * 1000

Key = datastore_types.Key
typename = datastore_types.typename

_txes = {}

_ALLOWED_API_KWARGS = frozenset(['rpc'])

_ALLOWED_FAILOVER_READ_METHODS = set(
    ('Get', 'RunQuery', 'RunCompiledQuery', 'Count', 'Next'))

ARBITRARY_FAILOVER_MS = -1

STRONG_CONSISTENCY = 0
EVENTUAL_CONSISTENCY = 1


def NormalizeAndTypeCheck(arg, types):
  """Normalizes and type checks the given argument.

  Args:
    arg: an instance, tuple, list, iterator, or generator of the given type(s)
    types: allowed type or tuple of types

  Returns:
    A (list, bool) tuple. The list is a normalized, shallow copy of the
    argument. The boolean is True if the argument was a sequence, False
    if it was a single object.

  Raises:
    AssertionError: types includes list or tuple.
    BadArgumentError: arg is not an instance or sequence of one of the given
    types.
  """
  if not isinstance(types, (list, tuple)):
    types = (types,)

  assert list not in types and tuple not in types

  if isinstance(arg, types):
    return ([arg], False)
  else:
    try:
      for val in arg:
        if not isinstance(val, types):
          raise datastore_errors.BadArgumentError(
              'Expected one of %s; received %s (a %s).' %
              (types, val, typename(val)))
    except TypeError:
      raise datastore_errors.BadArgumentError(
          'Expected an instance or sequence of %s; received %s (a %s).' %
          (types, arg, typename(arg)))

    return (list(arg), True)


def NormalizeAndTypeCheckKeys(keys):
  """Normalizes and type checks that the given argument is a valid key or keys.

  A wrapper around NormalizeAndTypeCheck() that accepts strings, Keys, and
  Entities, and normalizes to Keys.

  Args:
    keys: a Key or sequence of Keys

  Returns:
    A (list of Keys, bool) tuple. See NormalizeAndTypeCheck.

  Raises:
    BadArgumentError: arg is not an instance or sequence of one of the given
    types.
  """
  keys, multiple = NormalizeAndTypeCheck(keys, (basestring, Entity, Key))

  keys = [_GetCompleteKeyOrError(key) for key in keys]

  return (keys, multiple)


def GetRpcFromKwargs(kwargs):
  if not kwargs:
    return None
  args_diff = set(kwargs) - _ALLOWED_API_KWARGS
  if args_diff:
    raise TypeError('Invalid arguments: %s' % ', '.join(args_diff))
  return kwargs.get('rpc')


def _MakeSyncCall(service, call, request, response, rpc=None):
  """The APIProxy entry point for a synchronous API call.

  Args:
    service: string representing which service to call
    call: string representing which function to call
    request: protocol buffer for the request
    response: protocol buffer for the response
    rpc: datastore.DatastoreRPC to use for this request.

  Returns:
    Response protocol buffer. Caller should always use returned value
    which may or may not be same as passed in 'response'.

  Raises:
    apiproxy_errors.Error or a subclass.
  """
  if not rpc:
    rpc = CreateRPC(service)

  rpc.make_call(call, request, response)
  rpc.wait()
  rpc.check_success()
  return response


def CreateRPC(service='datastore_v3', deadline=None, callback=None,
              read_policy=STRONG_CONSISTENCY):
  """Create an rpc for use in configuring datastore calls.

  Args:
    deadline: float, deadline for calls in seconds.
    callback: callable, a callback triggered when this rpc completes,
      accepts one argument: the returned rpc.
    read_policy: flag, set to EVENTUAL_CONSISTENCY to enable eventually
      consistent reads

  Returns:
    A datastore.DatastoreRPC instance.
  """
  return DatastoreRPC(service, deadline, callback, read_policy)


class DatastoreRPC(apiproxy_stub_map.UserRPC):
  """Specialized RPC for the datastore.

  Wraps the default RPC class and sets appropriate values for use by the
  datastore.

  This class or a sublcass of it is intended to be instatiated by
  developers interested in setting specific request parameters, such as
  deadline, on API calls. It will be used to make the actual call.
  """

  def __init__(self, service='datastore_v3', deadline=None, callback=None,
               read_policy=STRONG_CONSISTENCY):
    super(DatastoreRPC, self).__init__(service, deadline, callback)
    self.read_policy = read_policy

  def make_call(self, call, request, response):
    if self.read_policy == EVENTUAL_CONSISTENCY:
      if call not in _ALLOWED_FAILOVER_READ_METHODS:
        raise datastore_errors.BadRequestError(
            'read_policy is only supported on read operations.')
      if call != 'Next':
        request.set_failover_ms(ARBITRARY_FAILOVER_MS)
    super(DatastoreRPC, self).make_call(call, request, response)

  def clone(self):
    """Make a shallow copy of this instance.

    This is usually used when an RPC has been specified with some configuration
    options and is being used as a template for multiple RPCs outside of a
    developer's easy control.
    """
    assert self.state == apiproxy_rpc.RPC.IDLE
    return self.__class__(
        self.service, self.deadline, self.callback, self.read_policy)


def Put(entities, **kwargs):
  """Store one or more entities in the datastore.

  The entities may be new or previously existing. For new entities, Put() will
  fill in the app id and key assigned by the datastore.

  If the argument is a single Entity, a single Key will be returned. If the
  argument is a list of Entity, a list of Keys will be returned.

  Args:
    entities: Entity or list of Entities
    rpc: datastore.RPC to use for this request.

  Returns:
    Key or list of Keys

  Raises:
    TransactionFailedError, if the Put could not be committed.
  """
  rpc = GetRpcFromKwargs(kwargs)
  entities, multiple = NormalizeAndTypeCheck(entities, Entity)

  if multiple and not entities:
    return []

  for entity in entities:
    if not entity.kind() or not entity.app():
      raise datastore_errors.BadRequestError(
          'App and kind must not be empty, in entity: %s' % entity)

  req = datastore_pb.PutRequest()
  req.entity_list().extend([e._ToPb() for e in entities])

  keys = [e.key() for e in entities]
  tx = _MaybeSetupTransaction(req, keys)

  try:
    resp = _MakeSyncCall(
        'datastore_v3', 'Put', req, datastore_pb.PutResponse(), rpc)
  except apiproxy_errors.ApplicationError, err:
    raise _ToDatastoreError(err)

  keys = resp.key_list()
  num_keys = len(keys)
  num_entities = len(entities)
  if num_keys != num_entities:
    raise datastore_errors.InternalError(
        'Put accepted %d entities but returned %d keys.' %
        (num_entities, num_keys))

  for entity, key in zip(entities, keys):
    entity._Entity__key._Key__reference.CopyFrom(key)

  if tx:
    tx.entity_group = entities[0].entity_group()

  if multiple:
    return [Key._FromPb(k) for k in keys]
  else:
    return Key._FromPb(resp.key(0))


def Get(keys, **kwargs):
  """Retrieves one or more entities from the datastore.

  Retrieves the entity or entities with the given key(s) from the datastore
  and returns them as fully populated Entity objects, as defined below. If
  there is an error, raises a subclass of datastore_errors.Error.

  If keys is a single key or string, an Entity will be returned, or
  EntityNotFoundError will be raised if no existing entity matches the key.

  However, if keys is a list or tuple, a list of entities will be returned
  that corresponds to the sequence of keys. It will include entities for keys
  that were found and None placeholders for keys that were not found.

  Args:
    # the primary key(s) of the entity(ies) to retrieve
    keys: Key or string or list of Keys or strings
    rpc: datastore.RPC to use for this request.

  Returns:
    Entity or list of Entity objects
  """
  rpc = GetRpcFromKwargs(kwargs)
  keys, multiple = NormalizeAndTypeCheckKeys(keys)

  if multiple and not keys:
    return []
  req = datastore_pb.GetRequest()
  req.key_list().extend([key._Key__reference for key in keys])
  _MaybeSetupTransaction(req, keys)

  try:
    resp = _MakeSyncCall(
        'datastore_v3', 'Get', req, datastore_pb.GetResponse(), rpc)
  except apiproxy_errors.ApplicationError, err:
    raise _ToDatastoreError(err)

  entities = []
  for group in resp.entity_list():
    if group.has_entity():
      entities.append(Entity._FromPb(group.entity()))
    else:
      entities.append(None)

  if multiple:
    return entities
  else:
    if entities[0] is None:
      raise datastore_errors.EntityNotFoundError()
    return entities[0]


def Delete(keys, **kwargs):
  """Deletes one or more entities from the datastore. Use with care!

  Deletes the given entity(ies) from the datastore. You can only delete
  entities from your app. If there is an error, raises a subclass of
  datastore_errors.Error.

  Args:
    # the primary key(s) of the entity(ies) to delete
    keys: Key or string or list of Keys or strings
    rpc: datastore.RPC to use for this request.

  Raises:
    TransactionFailedError, if the Delete could not be committed.
  """
  rpc = GetRpcFromKwargs(kwargs)
  keys, multiple = NormalizeAndTypeCheckKeys(keys)

  if multiple and not keys:
    return

  req = datastore_pb.DeleteRequest()
  req.key_list().extend([key._Key__reference for key in keys])

  tx = _MaybeSetupTransaction(req, keys)

  try:
    _MakeSyncCall(
        'datastore_v3', 'Delete', req, datastore_pb.DeleteResponse(), rpc)
  except apiproxy_errors.ApplicationError, err:
    raise _ToDatastoreError(err)


class Entity(dict):
  """A datastore entity.

  Includes read-only accessors for app id, kind, and primary key. Also
  provides dictionary-style access to properties.
  """
  def __init__(self, kind, parent=None, _app=None, name=None, id=None,
               unindexed_properties=[], namespace=None, **kwds):
    """Constructor. Takes the kind and transaction root, which cannot be
    changed after the entity is constructed, and an optional parent. Raises
    BadArgumentError or BadKeyError if kind is invalid or parent is not an
    existing Entity or Key in the datastore.

    Args:
      # this entity's kind
      kind: string
      # if provided, this entity's parent. Its key must be complete.
      parent: Entity or Key
      # if provided, this entity's name.
      name: string
      # if provided, this entity's id.
      id: integer
      # if provided, a sequence of property names that should not be indexed
      # by the built-in single property indices.
      unindexed_properties: list or tuple of strings
      namespace: string
      # if provided, overrides the default namespace_manager setting.
    """
    ref = entity_pb.Reference()
    _app = datastore_types.ResolveAppId(_app)
    ref.set_app(_app)

    _namespace = kwds.pop('_namespace', None)
    if kwds:
      raise datastore_errors.BadArgumentError(
          'Excess keyword arguments ' + repr(kwds))

    if namespace is None:
      namespace = _namespace
    elif _namespace is not None:
        raise datastore_errors.BadArgumentError(
            "Must not set both _namespace and namespace parameters.")

    datastore_types.ValidateString(kind, 'kind',
                                   datastore_errors.BadArgumentError)
    if parent is not None:
      parent = _GetCompleteKeyOrError(parent)
      if _app != parent.app():
        raise datastore_errors.BadArgumentError(
            " %s doesn't match parent's app %s" %
            (_app, parent.app()))
      if namespace is None:
        namespace = parent.namespace()
      elif namespace != parent.namespace():
        raise datastore_errors.BadArgumentError(
            " %s doesn't match parent's namespace %s" %
            (namespace, parent.namespace()))
      ref.CopyFrom(parent._Key__reference)

    namespace = datastore_types.ResolveNamespace(namespace)
    datastore_types.SetNamespace(ref, namespace)

    last_path = ref.mutable_path().add_element()
    last_path.set_type(kind.encode('utf-8'))

    if name is not None and id is not None:
      raise datastore_errors.BadArgumentError(
          "Cannot set both name and id on an Entity")

    if name is not None:
      datastore_types.ValidateString(name, 'name')
      last_path.set_name(name.encode('utf-8'))

    if id is not None:
      datastore_types.ValidateInteger(id, 'id')
      last_path.set_id(id)

    self.set_unindexed_properties(unindexed_properties)

    self.__key = Key._FromPb(ref)

  def app(self):
    """Returns the name of the application that created this entity, a
    string or None if not set.
    """
    return self.__key.app()

  def namespace(self):
    """Returns the namespace of this entity, a string or None.
    """
    return self.__key.namespace()

  def kind(self):
    """Returns this entity's kind, a string.
    """
    return self.__key.kind()

  def is_saved(self):
    """Returns if this entity has been saved to the datastore
    """
    last_path = self.__key._Key__reference.path().element_list()[-1]
    return ((last_path.has_name() ^ last_path.has_id()) and
            self.__key.has_id_or_name())

  def key(self):
    """Returns this entity's primary key, a Key instance.
    """
    return self.__key

  def parent(self):
    """Returns this entity's parent, as a Key. If this entity has no parent,
    returns None.
    """
    return self.key().parent()

  def entity_group(self):
    """Returns this entity's entity group as a Key.

    Note that the returned Key will be incomplete if this is a a root entity
    and its key is incomplete.
    """
    return self.key().entity_group()

  def unindexed_properties(self):
    """Returns this entity's unindexed properties, as a frozenset of strings."""
    return getattr(self, '_Entity__unindexed_properties', [])

  def set_unindexed_properties(self, unindexed_properties):
    unindexed_properties, multiple = NormalizeAndTypeCheck(unindexed_properties, basestring)
    if not multiple:
      raise datastore_errors.BadArgumentError(
        'unindexed_properties must be a sequence; received %s (a %s).' %
        (unindexed_properties, typename(unindexed_properties)))
    for prop in unindexed_properties:
      datastore_types.ValidateProperty(prop, None)
    self.__unindexed_properties = frozenset(unindexed_properties)

  def __setitem__(self, name, value):
    """Implements the [] operator. Used to set property value(s).

    If the property name is the empty string or not a string, raises
    BadPropertyError. If the value is not a supported type, raises
    BadValueError.
    """
    datastore_types.ValidateProperty(name, value)
    dict.__setitem__(self, name, value)

  def setdefault(self, name, value):
    """If the property exists, returns its value. Otherwise sets it to value.

    If the property name is the empty string or not a string, raises
    BadPropertyError. If the value is not a supported type, raises
    BadValueError.
    """
    datastore_types.ValidateProperty(name, value)
    return dict.setdefault(self, name, value)

  def update(self, other):
    """Updates this entity's properties from the values in other.

    If any property name is the empty string or not a string, raises
    BadPropertyError. If any value is not a supported type, raises
    BadValueError.
    """
    for name, value in other.items():
      self.__setitem__(name, value)

  def copy(self):
    """The copy method is not supported.
    """
    raise NotImplementedError('Entity does not support the copy() method.')

  def ToXml(self):
    """Returns an XML representation of this entity. Atom and gd:namespace
    properties are converted to XML according to their respective schemas. For
    more information, see:

      http://www.atomenabled.org/developers/syndication/
      http://code.google.com/apis/gdata/common-elements.html

    This is *not* optimized. It shouldn't be used anywhere near code that's
    performance-critical.
    """
    xml = u'<entity kind=%s' % saxutils.quoteattr(self.kind())
    if self.__key.has_id_or_name():
      xml += ' key=%s' % saxutils.quoteattr(str(self.__key))
    xml += '>'
    if self.__key.has_id_or_name():
      xml += '\n  <key>%s</key>' % self.__key.ToTagUri()


    properties = self.keys()
    if properties:
      properties.sort()
      xml += '\n  ' + '\n  '.join(self._PropertiesToXml(properties))

    xml += '\n</entity>\n'
    return xml

  def _PropertiesToXml(self, properties):
    """ Returns a list of the XML representations of each of the given
    properties. Ignores properties that don't exist in this entity.

    Arg:
      properties: string or list of strings

    Returns:
      list of strings
    """
    xml_properties = []

    for propname in properties:
      if not self.has_key(propname):
        continue

      propname_xml = saxutils.quoteattr(propname)

      values = self[propname]
      if not isinstance(values, list):
        values = [values]

      proptype = datastore_types.PropertyTypeName(values[0])
      proptype_xml = saxutils.quoteattr(proptype)

      escaped_values = self._XmlEscapeValues(propname)
      open_tag = u'<property name=%s type=%s>' % (propname_xml, proptype_xml)
      close_tag = u'</property>'
      xml_properties += [open_tag + val + close_tag for val in escaped_values]

    return xml_properties

  def _XmlEscapeValues(self, property):
    """ Returns a list of the XML-escaped string values for the given property.
    Raises an AssertionError if the property doesn't exist.

    Arg:
      property: string

    Returns:
      list of strings
    """
    assert self.has_key(property)
    xml = []

    values = self[property]
    if not isinstance(values, list):
      values = [values]

    for val in values:
      if hasattr(val, 'ToXml'):
        xml.append(val.ToXml())
      else:
        if val is None:
          xml.append('')
        else:
          xml.append(saxutils.escape(unicode(val)))

    return xml

  def ToPb(self):
    """Converts this Entity to its protocol buffer representation.

    Returns:
      entity_pb.Entity
    """
    return self._ToPb(False)

  def _ToPb(self, mark_key_as_saved=True):
    """Converts this Entity to its protocol buffer representation. Not
    intended to be used by application developers.

    Returns:
      entity_pb.Entity
    """

    pb = entity_pb.EntityProto()
    pb.mutable_key().CopyFrom(self.key()._ToPb())
    last_path = pb.key().path().element_list()[-1]
    if mark_key_as_saved and last_path.has_name() and last_path.has_id():
      last_path.clear_id()

    group = pb.mutable_entity_group()
    if self.__key.has_id_or_name():
      root = pb.key().path().element(0)
      group.add_element().CopyFrom(root)

    properties = self.items()
    properties.sort()
    for (name, values) in properties:
      properties = datastore_types.ToPropertyPb(name, values)
      if not isinstance(properties, list):
        properties = [properties]

      for prop in properties:
        if (prop.meaning() in datastore_types._RAW_PROPERTY_MEANINGS or
            name in self.unindexed_properties()):
          pb.raw_property_list().append(prop)
        else:
          pb.property_list().append(prop)

    if pb.property_size() > _MAX_INDEXED_PROPERTIES:
      raise datastore_errors.BadRequestError(
          'Too many indexed properties for entity %r.' % self.key())

    return pb

  @staticmethod
  def FromPb(pb):
    """Static factory method. Returns the Entity representation of the
    given protocol buffer (datastore_pb.Entity).

    Args:
      pb: datastore_pb.Entity or str encoding of a datastore_pb.Entity

    Returns:
      Entity: the Entity representation of pb
    """
    if isinstance(pb, str):
      real_pb = entity_pb.EntityProto()
      real_pb.ParseFromString(pb)
      pb = real_pb

    return Entity._FromPb(pb, require_valid_key=False)

  @staticmethod
  def _FromPb(pb, require_valid_key=True):
    """Static factory method. Returns the Entity representation of the
    given protocol buffer (datastore_pb.Entity). Not intended to be used by
    application developers.

    The Entity PB's key must be complete. If it isn't, an AssertionError is
    raised.

    Args:
      # a protocol buffer Entity
      pb: datastore_pb.Entity

    Returns:
      # the Entity representation of the argument
      Entity
    """
    assert pb.key().path().element_size() > 0

    last_path = pb.key().path().element_list()[-1]
    if require_valid_key:
      assert last_path.has_id() ^ last_path.has_name()
      if last_path.has_id():
        assert last_path.id() != 0
      else:
        assert last_path.has_name()
        assert last_path.name()

    unindexed_properties = [p.name() for p in pb.raw_property_list()]

    if pb.key().has_name_space():
      namespace = pb.key().name_space()
    else:
      namespace = ''
    e = Entity(unicode(last_path.type().decode('utf-8')),
               unindexed_properties=unindexed_properties,
               _app=pb.key().app(), namespace=namespace)
    ref = e.__key._Key__reference
    ref.CopyFrom(pb.key())

    temporary_values = {}

    for prop_list in (pb.property_list(), pb.raw_property_list()):
      for prop in prop_list:
        try:
          value = datastore_types.FromPropertyPb(prop)
        except (AssertionError, AttributeError, TypeError, ValueError), e:
          raise datastore_errors.Error(
            'Property %s is corrupt in the datastore. %s: %s' %
            (e.__class__, prop.name(), e))

        multiple = prop.multiple()
        if multiple:
          value = [value]

        name = prop.name()
        cur_value = temporary_values.get(name)
        if cur_value is None:
          temporary_values[name] = value
        elif not multiple:
          raise datastore_errors.Error(
            'Property %s is corrupt in the datastore; it has multiple '
            'values, but is not marked as multiply valued.' % name)
        else:
          cur_value.extend(value)

    for name, value in temporary_values.iteritems():
      decoded_name = unicode(name.decode('utf-8'))

      datastore_types.ValidateReadProperty(decoded_name, value)

      dict.__setitem__(e, decoded_name, value)

    return e


class Query(dict):
  """A datastore query.

  (Instead of this, consider using appengine.ext.gql.Query! It provides a
  query language interface on top of the same functionality.)

  Queries are used to retrieve entities that match certain criteria, including
  app id, kind, and property filters. Results may also be sorted by properties.

  App id and kind are required. Only entities from the given app, of the given
  type, are returned. If an ancestor is set, with Ancestor(), only entities
  with that ancestor are returned.

  Property filters are used to provide criteria based on individual property
  values. A filter compares a specific property in each entity to a given
  value or list of possible values.

  An entity is returned if its property values match *all* of the query's
  filters. In other words, filters are combined with AND, not OR. If an
  entity does not have a value for a property used in a filter, it is not
  returned.

  Property filters map filter strings of the form '<property name> <operator>'
  to filter values. Use dictionary accessors to set property filters, like so:

  > query = Query('Person')
  > query['name ='] = 'Ryan'
  > query['age >='] = 21

  This query returns all Person entities where the name property is 'Ryan',
  'Ken', or 'Bret', and the age property is at least 21.

  Another way to build this query is:

  > query = Query('Person')
  > query.update({'name =': 'Ryan', 'age >=': 21})

  The supported operators are =, >, <, >=, and <=. Only one inequality
  filter may be used per query. Any number of equals filters may be used in
  a single Query.

  A filter value may be a list or tuple of values. This is interpreted as
  multiple filters with the same filter string and different values, all ANDed
  together. For example, this query returns everyone with the tags "google"
  and "app engine":

  > Query('Person', {'tag =': ('google', 'app engine')})

  Result entities can be returned in different orders. Use the Order()
  method to specify properties that results will be sorted by, and in which
  direction.

  Note that filters and orderings may be provided at any time before the query
  is run. When the query is fully specified, Run() runs the query and returns
  an iterator. The query results can be accessed through the iterator.

  A query object may be reused after it's been run. Its filters and
  orderings can be changed to create a modified query.

  If you know how many result entities you need, use Get() to fetch them:

  > query = Query('Person', {'age >': 21})
  > for person in query.Get(4):
  >   print 'I have four pints left. Have one on me, %s!' % person['name']

  If you don't know how many results you need, or if you need them all, you
  can get an iterator over the results by calling Run():

  > for person in Query('Person', {'age >': 21}).Run():
  >   print 'Have a pint on me, %s!' % person['name']

  Get() is more efficient than Run(), so use Get() whenever possible.

  Finally, the Count() method returns the number of result entities matched by
  the query. The returned count is cached; successive Count() calls will not
  re-scan the datastore unless the query is changed.
  """
  ASCENDING = datastore_pb.Query_Order.ASCENDING
  DESCENDING = datastore_pb.Query_Order.DESCENDING

  ORDER_FIRST = datastore_pb.Query.ORDER_FIRST
  ANCESTOR_FIRST = datastore_pb.Query.ANCESTOR_FIRST
  FILTER_FIRST = datastore_pb.Query.FILTER_FIRST

  OPERATORS = {'<':  datastore_pb.Query_Filter.LESS_THAN,
               '<=': datastore_pb.Query_Filter.LESS_THAN_OR_EQUAL,
               '>':  datastore_pb.Query_Filter.GREATER_THAN,
               '>=': datastore_pb.Query_Filter.GREATER_THAN_OR_EQUAL,
               '=':  datastore_pb.Query_Filter.EQUAL,
               '==': datastore_pb.Query_Filter.EQUAL,
               }
  INEQUALITY_OPERATORS = frozenset(['<', '<=', '>', '>='])
  FILTER_REGEX = re.compile(
    '^\s*([^\s]+)(\s+(%s)\s*)?$' % '|'.join(OPERATORS.keys()),
    re.IGNORECASE | re.UNICODE)

  __kind = None
  __app = None
  __namespace = None
  __orderings = None
  __cached_count = None
  __hint = None
  __ancestor = None
  __compile = None

  __cursor = None

  __filter_order = None
  __filter_counter = 0

  __inequality_prop = None
  __inequality_count = 0

  def __init__(self, kind=None, filters={}, _app=None, keys_only=False,
               compile=True, cursor=None, namespace=None, **kwds):
    """Constructor.

    Raises BadArgumentError if kind is not a string. Raises BadValueError or
    BadFilterError if filters is not a dictionary of valid filters.

    Args:
      # kind is required. filters is optional; if provided, it's used
      # as an initial set of property filters. keys_only defaults to False.
      kind: string
      filters: dict
      keys_only: boolean
      namespace: string
    """

    _namespace = kwds.pop('_namespace', None)
    if kwds:
      raise datastore_errors.BadArgumentError(
          'Excess keyword arguments ' + repr(kwds))

    if namespace is None:
      namespace = _namespace
    elif _namespace is not None:
        raise datastore_errors.BadArgumentError(
            "Must not set both _namespace and namespace parameters.")

    if kind is not None:
      datastore_types.ValidateString(kind, 'kind',
                                     datastore_errors.BadArgumentError)

    self.__kind = kind
    self.__orderings = []
    self.__filter_order = {}
    self.update(filters)

    self.__app = datastore_types.ResolveAppId(_app)
    self.__namespace = datastore_types.ResolveNamespace(namespace)
    self.__keys_only = keys_only
    self.__compile = compile
    self.__cursor = cursor

  def Order(self, *orderings):
    """Specify how the query results should be sorted.

    Result entities will be sorted by the first property argument, then by the
    second, and so on. For example, this:

    > query = Query('Person')
    > query.Order('bday', ('age', Query.DESCENDING))

    sorts everyone in order of their birthday, starting with January 1.
    People with the same birthday are sorted by age, oldest to youngest.

    The direction for each sort property may be provided; if omitted, it
    defaults to ascending.

    Order() may be called multiple times. Each call resets the sort order
    from scratch.

    If an inequality filter exists in this Query it must be the first property
    passed to Order. Any number of sort orders may be used after the
    inequality filter property. Without inequality filters, any number of
    filters with different orders may be specified.

    Entities with multiple values for an order property are sorted by their
    lowest value.

    Note that a sort order implies an existence filter! In other words,
    Entities without the sort order property are filtered out, and *not*
    included in the query results.

    If the sort order property has different types in different entities - ie,
    if bob['id'] is an int and fred['id'] is a string - the entities will be
    grouped first by the property type, then sorted within type. No attempt is
    made to compare property values across types.

    Raises BadArgumentError if any argument is of the wrong format.

    Args:
      # the properties to sort by, in sort order. each argument may be either a
      # string or (string, direction) 2-tuple.

    Returns:
      # this query
      Query
    """
    orderings = list(orderings)

    for (order, i) in zip(orderings, range(len(orderings))):
      if not (isinstance(order, basestring) or
              (isinstance(order, tuple) and len(order) in [2, 3])):
        raise datastore_errors.BadArgumentError(
          'Order() expects strings or 2- or 3-tuples; received %s (a %s). ' %
          (order, typename(order)))

      if isinstance(order, basestring):
        order = (order,)

      datastore_types.ValidateString(order[0], 'sort order property',
                                     datastore_errors.BadArgumentError)
      property = order[0]

      direction = order[-1]
      if direction not in (Query.ASCENDING, Query.DESCENDING):
        if len(order) == 3:
          raise datastore_errors.BadArgumentError(
            'Order() expects Query.ASCENDING or DESCENDING; received %s' %
            str(direction))
        direction = Query.ASCENDING

      if (self.__kind is None and
          (property != datastore_types._KEY_SPECIAL_PROPERTY or
          direction != Query.ASCENDING)):
        raise datastore_errors.BadArgumentError(
            'Only %s ascending orders are supported on kindless queries' %
            datastore_types._KEY_SPECIAL_PROPERTY)

      orderings[i] = (property, direction)

    if (orderings and self.__inequality_prop and
        orderings[0][0] != self.__inequality_prop):
      raise datastore_errors.BadArgumentError(
        'First ordering property must be the same as inequality filter '
        'property, if specified for this query; received %s, expected %s' %
        (orderings[0][0], self.__inequality_prop))

    self.__orderings = orderings
    return self

  def Hint(self, hint):
    """Sets a hint for how this query should run.

    The query hint gives us information about how best to execute your query.
    Currently, we can only do one index scan, so the query hint should be used
    to indicates which index we should scan against.

    Use FILTER_FIRST if your first filter will only match a few results. In
    this case, it will be most efficient to scan against the index for this
    property, load the results into memory, and apply the remaining filters
    and sort orders there.

    Similarly, use ANCESTOR_FIRST if the query's ancestor only has a few
    descendants. In this case, it will be most efficient to scan all entities
    below the ancestor and load them into memory first.

    Use ORDER_FIRST if the query has a sort order and the result set is large
    or you only plan to fetch the first few results. In that case, we
    shouldn't try to load all of the results into memory; instead, we should
    scan the index for this property, which is in sorted order.

    Note that hints are currently ignored in the v3 datastore!

    Arg:
      one of datastore.Query.[ORDER_FIRST, ANCESTOR_FIRST, FILTER_FIRST]

    Returns:
      # this query
      Query
    """
    if hint not in [self.ORDER_FIRST, self.ANCESTOR_FIRST, self.FILTER_FIRST]:
      raise datastore_errors.BadArgumentError(
        'Query hint must be ORDER_FIRST, ANCESTOR_FIRST, or FILTER_FIRST.')

    self.__hint = hint
    return self

  def Ancestor(self, ancestor):
    """Sets an ancestor for this query.

    This restricts the query to only return result entities that are descended
    from a given entity. In other words, all of the results will have the
    ancestor as their parent, or parent's parent, or etc.

    Raises BadArgumentError or BadKeyError if parent is not an existing Entity
    or Key in the datastore.

    Args:
      # the key must be complete
      ancestor: Entity or Key

    Returns:
      # this query
      Query
    """
    self.__ancestor = _GetCompleteKeyOrError(ancestor)
    return self

  def IsKeysOnly(self):
    """Returns True if this query is keys only, false otherwise."""
    return self.__keys_only

  def GetCompiledCursor(self):
    try:
      compiled_cursor = self.__last_iterator.GetCompiledCursor(self)
      if not compiled_cursor:
        raise AttributeError()
    except AttributeError:
      raise AssertionError('No cursor available, either this query has not '
                           'been executed or there is no compilation '
                           'available for this kind of query')
    return compiled_cursor

  def GetCompiledQuery(self):
    try:
      if not self.__compiled_query:
        raise AttributeError()
    except AttributeError:
      raise AssertionError('No compiled query available, either this query has '
                           'not been executed or there is no compilation '
                           'available for this kind of query')
    return self.__compiled_query

  def Run(self, **kwargs):
    """Runs this query.

    If a filter string is invalid, raises BadFilterError. If a filter value is
    invalid, raises BadValueError. If an IN filter is provided, and a sort
    order on another property is provided, raises BadQueryError.

    If you know in advance how many results you want, use Get() instead. It's
    more efficient.

    Args:
      limit: integer, limit for the query.
      offset: integer, offset for the query.
      prefetch_count: integer, number of results to return in the first query.
      next_count: number of results to return in subsequent next queries.
      rpc: datastore.RPC to use for this request.

    Returns:
      # an iterator that provides access to the query results
      Iterator
    """
    return self._Run(**kwargs)

  def _Run(self, limit=None, offset=None,
           prefetch_count=None, next_count=None, **kwargs):
    """Runs this query, with an optional result limit and an optional offset.

    Identical to Run, with the extra optional limit, offset, prefetch_count,
    next_count parameters. These parameters must be integers >= 0.

    This is not intended to be used by application developers. Use Get()
    instead!

    Args:
      limit: integer, limit for the query.
      offset: integer, offset for the query.
      prefetch_count: integer, number of results to return in the first query.
      next_count: number of results to return in subsequent next queries.
      rpc: datastore.RPC to use for this request.
    """
    rpc = GetRpcFromKwargs(kwargs)
    request = self._ToPb(limit, offset, prefetch_count)

    if rpc:
      rpc_clone = rpc.clone()
    else:
      rpc_clone = None

    try:
      result = _MakeSyncCall('datastore_v3', 'RunQuery', request,
                             datastore_pb.QueryResult(), rpc)
    except apiproxy_errors.ApplicationError, err:
      try:
        raise _ToDatastoreError(err)
      except datastore_errors.NeedIndexError, exc:
        yaml = datastore_index.IndexYamlForQuery(
          *datastore_index.CompositeIndexForQuery(request)[1:-1])
        raise datastore_errors.NeedIndexError(
          str(exc) + '\nThis query needs this index:\n' + yaml)

    if result.has_cursor() and not result.cursor().app():
      result.mutable_cursor().set_app(self.__app)

    if result.has_compiled_query():
      self.__compiled_query = result.compiled_query()
    else:
      self.__compiled_query = None

    self.__last_iterator = Iterator(
        result, query_request_pb=request, batch_size=next_count, rpc=rpc_clone)
    return self.__last_iterator

  def Get(self, limit, offset=0, **kwargs):
    """Fetches and returns a maximum number of results from the query.

    This method fetches and returns a list of resulting entities that matched
    the query. If the query specified a sort order, entities are returned in
    that order. Otherwise, the order is undefined.

    The limit argument specifies the maximum number of entities to return. If
    it's greater than the number of remaining entities, all of the remaining
    entities are returned. In that case, the length of the returned list will
    be smaller than limit.

    The offset argument specifies the number of entities that matched the
    query criteria to skip before starting to return results.  The limit is
    applied after the offset, so if you provide a limit of 10 and an offset of 5
    and your query matches 20 records, the records whose index is 0 through 4
    will be skipped and the records whose index is 5 through 14 will be
    returned.

    The results are always returned as a list. If there are no results left,
    an empty list is returned.

    If you know in advance how many results you want, this method is more
    efficient than Run(), since it fetches all of the results at once. (The
    datastore backend sets the the limit on the underlying
    scan, which makes the scan significantly faster.)

    Args:
      # the maximum number of entities to return
      int or long
      # the number of entities to skip
      int or long
      rpc: datastore.RPC to use for this request.

    Returns:
      # a list of entities
      [Entity, ...]
    """
    rpc = GetRpcFromKwargs(kwargs)

    if not isinstance(limit, (int, long)) or limit <= 0:
      raise datastore_errors.BadArgumentError(
        'Argument to Get named \'limit\' must be an int greater than 0; '
        'received %s (a %s)' % (limit, typename(limit)))

    if not isinstance(offset, (int, long)) or offset < 0:
      raise datastore_errors.BadArgumentError(
          'Argument to Get named \'offset\' must be an int greater than or '
          'equal to 0; received %s (a %s)' % (offset, typename(offset)))

    return self._Run(
        limit=limit, offset=offset, prefetch_count=limit, **kwargs)._Get(limit)

  def Count(self, limit=None, **kwargs):
    """Returns the number of entities that this query matches. The returned
    count is cached; successive Count() calls will not re-scan the datastore
    unless the query is changed.

    Args:
      limit, a number. If there are more results than this, stop short and
      just return this number. Providing this argument makes the count
      operation more efficient.
      rpc: datastore.RPC to use for this request.

    Returns:
      The number of results.
    """
    rpc = GetRpcFromKwargs(kwargs)
    if self.__cached_count:
      return self.__cached_count

    try:
      req = self._ToPb(limit=limit)
      req.clear_compile()
      resp = _MakeSyncCall('datastore_v3', 'Count',
                           req,
                           api_base_pb.Integer64Proto(),
                           rpc)
    except apiproxy_errors.ApplicationError, err:
      raise _ToDatastoreError(err)
    else:
      self.__cached_count = resp.value()

    return self.__cached_count

  def __iter__(self):
    raise NotImplementedError(
      'Query objects should not be used as iterators. Call Run() first.')

  def __setitem__(self, filter, value):
    """Implements the [] operator. Used to set filters.

    If the filter string is empty or not a string, raises BadFilterError. If
    the value is not a supported type, raises BadValueError.
    """
    if isinstance(value, tuple):
      value = list(value)

    datastore_types.ValidateProperty(' ', value, read_only=True)
    match = self._CheckFilter(filter, value)
    property = match.group(1)
    operator = match.group(3)

    dict.__setitem__(self, filter, value)

    if operator in self.INEQUALITY_OPERATORS:
      if self.__inequality_prop is None:
        self.__inequality_prop = property
      else:
        assert self.__inequality_prop == property
      self.__inequality_count += 1

    if filter not in self.__filter_order:
      self.__filter_order[filter] = self.__filter_counter
      self.__filter_counter += 1

    self.__cached_count = None

  def setdefault(self, filter, value):
    """If the filter exists, returns its value. Otherwise sets it to value.

    If the property name is the empty string or not a string, raises
    BadPropertyError. If the value is not a supported type, raises
    BadValueError.
    """
    datastore_types.ValidateProperty(' ', value)
    self._CheckFilter(filter, value)
    self.__cached_count = None
    return dict.setdefault(self, filter, value)

  def __delitem__(self, filter):
    """Implements the del [] operator. Used to remove filters.
    """
    dict.__delitem__(self, filter)
    del self.__filter_order[filter]
    self.__cached_count = None

    match = Query.FILTER_REGEX.match(filter)
    property = match.group(1)
    operator = match.group(3)

    if operator in self.INEQUALITY_OPERATORS:
      assert self.__inequality_count >= 1
      assert property == self.__inequality_prop
      self.__inequality_count -= 1
      if self.__inequality_count == 0:
        self.__inequality_prop = None

  def update(self, other):
    """Updates this query's filters from the ones in other.

    If any filter string is invalid, raises BadFilterError. If any value is
    not a supported type, raises BadValueError.
    """
    for filter, value in other.items():
      self.__setitem__(filter, value)

  def copy(self):
    """The copy method is not supported.
    """
    raise NotImplementedError('Query does not support the copy() method.')

  def _CheckFilter(self, filter, values):
    """Type check a filter string and list of values.

    Raises BadFilterError if the filter string is empty, not a string, or
    invalid. Raises BadValueError if the value type is not supported.

    Args:
      filter: String containing the filter text.
      values: List of associated filter values.

    Returns:
      re.MatchObject (never None) that matches the 'filter'. Group 1 is the
      property name, group 3 is the operator. (Group 2 is unused.)
    """
    try:
      match = Query.FILTER_REGEX.match(filter)
      if not match:
        raise datastore_errors.BadFilterError(
          'Could not parse filter string: %s' % str(filter))
    except TypeError:
      raise datastore_errors.BadFilterError(
        'Could not parse filter string: %s' % str(filter))

    property = match.group(1)
    operator = match.group(3)
    if operator is None:
      operator = '='

    if isinstance(values, tuple):
      values = list(values)
    elif not isinstance(values, list):
      values = [values]
    if isinstance(values[0], datastore_types._RAW_PROPERTY_TYPES):
      raise datastore_errors.BadValueError(
        'Filtering on %s properties is not supported.' % typename(values[0]))

    if operator in self.INEQUALITY_OPERATORS:
      if self.__inequality_prop and property != self.__inequality_prop:
        raise datastore_errors.BadFilterError(
          'Only one property per query may have inequality filters (%s).' %
          ', '.join(self.INEQUALITY_OPERATORS))
      elif len(self.__orderings) >= 1 and self.__orderings[0][0] != property:
        raise datastore_errors.BadFilterError(
          'Inequality operators (%s) must be on the same property as the '
          'first sort order, if any sort orders are supplied' %
          ', '.join(self.INEQUALITY_OPERATORS))

    if (self.__kind is None and
        property != datastore_types._KEY_SPECIAL_PROPERTY):
      raise datastore_errors.BadFilterError(
          'Only %s filters are allowed on kindless queries.' %
          datastore_types._KEY_SPECIAL_PROPERTY)

    if property in datastore_types._SPECIAL_PROPERTIES:
      if property == datastore_types._KEY_SPECIAL_PROPERTY:
        for value in values:
          if not isinstance(value, Key):
            raise datastore_errors.BadFilterError(
              '%s filter value must be a Key; received %s (a %s)' %
              (datastore_types._KEY_SPECIAL_PROPERTY, value, typename(value)))

    return match

  def _ToPb(self, limit=None, offset=None, count=None):
    """Converts this Query to its protocol buffer representation. Not
    intended to be used by application developers. Enforced by hiding the
    datastore_pb classes.

    Args:
      # an upper bound on the number of results returned by the query.
      limit: int
      # number of results that match the query to skip.  limit is applied
      # after the offset is fulfilled
      offset: int
      # the requested initial batch size
      count: int

    Returns:
      # the PB representation of this Query
      datastore_pb.Query

    Raises:
      BadRequestError if called inside a transaction and the query does not
      include an ancestor.
    """

    if not self.__ancestor and _CurrentTransactionKey():
      raise datastore_errors.BadRequestError(
        'Only ancestor queries are allowed inside transactions.')

    pb = datastore_pb.Query()
    _MaybeSetupTransaction(pb, [self.__ancestor])

    if self.__kind is not None:
      pb.set_kind(self.__kind.encode('utf-8'))
    pb.set_keys_only(bool(self.__keys_only))
    if self.__app:
      pb.set_app(self.__app.encode('utf-8'))
    datastore_types.SetNamespace(pb, self.__namespace)
    if self.__compile:
      pb.set_compile(True)
    if limit is not None:
      pb.set_limit(limit)
    if offset is not None:
      pb.set_offset(offset)
    if count is not None:
      pb.set_count(count)
    if self.__ancestor:
      pb.mutable_ancestor().CopyFrom(self.__ancestor._Key__reference)

    if ((self.__hint == self.ORDER_FIRST and self.__orderings) or
        (self.__hint == self.ANCESTOR_FIRST and self.__ancestor) or
        (self.__hint == self.FILTER_FIRST and len(self) > 0)):
      pb.set_hint(self.__hint)

    ordered_filters = [(i, f) for f, i in self.__filter_order.iteritems()]
    ordered_filters.sort()

    for i, filter_str in ordered_filters:
      if filter_str not in self:
        continue

      values = self[filter_str]
      match = self._CheckFilter(filter_str, values)
      name = match.group(1)

      props = datastore_types.ToPropertyPb(name, values)
      if not isinstance(props, list):
        props = [props]

      op = match.group(3)
      if op is None:
        op = '='

      for prop in props:
        filter = pb.add_filter()
        filter.set_op(self.OPERATORS[op])
        filter.add_property().CopyFrom(prop)

    for property, direction in self.__orderings:
      order = pb.add_order()
      order.set_property(property.encode('utf-8'))
      order.set_direction(direction)

    if self.__cursor:
      pb.mutable_compiled_cursor().CopyFrom(self.__cursor);

    return pb


def AllocateIds(model_key, size, **kwargs):
  """Allocates a range of IDs of size for the key defined by model_key

  Allocates a range of IDs in the datastore such that those IDs will not
  be automatically assigned to new entities. You can only allocate IDs
  for model keys from your app. If there is an error, raises a subclass of
  datastore_errors.Error.

  Args:
    model_key: Key or string to serve as a model specifying the ID sequence
               in which to allocate IDs
    size: integer, number of IDs to allocate.
    rpc: datastore.RPC to use for this request.

  Returns:
    (start, end) of the allocated range, inclusive.
  """
  rpc = GetRpcFromKwargs(kwargs)
  keys, multiple = NormalizeAndTypeCheckKeys(model_key)

  if len(keys) > 1:
    raise datastore_errors.BadArgumentError(
        'Cannot allocate IDs for more than one model key at a time')

  if size > _MAX_ID_BATCH_SIZE:
    raise datastore_errors.BadArgumentError(
        'Cannot allocate more than %s ids at a time' % _MAX_ID_BATCH_SIZE)
  if size <= 0:
    raise datastore_errors.BadArgumentError(
        'Cannot allocate less than 1 id')

  req = datastore_pb.AllocateIdsRequest()
  req.mutable_model_key().CopyFrom(keys[0]._ToPb())
  req.set_size(size)

  try:
    resp = _MakeSyncCall('datastore_v3', 'AllocateIds', req,
                         datastore_pb.AllocateIdsResponse(), rpc)
  except apiproxy_errors.ApplicationError, err:
    raise _ToDatastoreError(err)

  return resp.start(), resp.end()


class MultiQuery(Query):
  """Class representing a query which requires multiple datastore queries.

  This class is actually a subclass of datastore.Query as it is intended to act
  like a normal Query object (supporting the same interface).

  Does not support keys only queries, since it needs whole entities in order
  to merge sort them. (That's not true if there are no sort orders, or if the
  sort order is on __key__, but allowing keys only queries in those cases, but
  not in others, would be confusing.)
  """

  def __init__(self, bound_queries, orderings):
    if len(bound_queries) > MAX_ALLOWABLE_QUERIES:
      raise datastore_errors.BadArgumentError(
          'Cannot satisfy query -- too many subqueries (max: %d, got %d).'
          ' Probable cause: too many IN/!= filters in query.' %
          (MAX_ALLOWABLE_QUERIES, len(bound_queries)))

    for query in bound_queries:
      if query.IsKeysOnly():
        raise datastore_errors.BadQueryError(
            'MultiQuery does not support keys_only.')

    self.__bound_queries = bound_queries
    self.__orderings = orderings
    self.__compile = False

  def __str__(self):
    res = 'MultiQuery: '
    for query in self.__bound_queries:
      res = '%s %s' % (res, str(query))
    return res

  def Get(self, limit, offset=0, **kwargs):
    """Get results of the query with a limit on the number of results.

    Args:
      limit: maximum number of values to return.
      offset: offset requested -- if nonzero, this will override the offset in
              the original query
      rpc: datastore.RPC to use for this request.

    Returns:
      A list of entities with at most "limit" entries (less if the query
      completes before reading limit values).
    """
    rpc = GetRpcFromKwargs(kwargs)
    count = 1
    result = []

    iterator = self.Run(rpc=rpc)

    try:
      for i in xrange(offset):
        val = iterator.next()
    except StopIteration:
      pass

    try:
      while count <= limit:
        val = iterator.next()
        result.append(val)
        count += 1
    except StopIteration:
      pass
    return result

  class SortOrderEntity(object):
    """Allow entity comparisons using provided orderings.

    The iterator passed to the constructor is eventually consumed via
    calls to GetNext(), which generate new SortOrderEntity s with the
    same orderings.
    """

    def __init__(self, entity_iterator, orderings):
      """Ctor.

      Args:
        entity_iterator: an iterator of entities which will be wrapped.
        orderings: an iterable of (identifier, order) pairs. order
          should be either Query.ASCENDING or Query.DESCENDING.
      """
      self.__entity_iterator = entity_iterator
      self.__entity = None
      self.__min_max_value_cache = {}
      try:
        self.__entity = entity_iterator.next()
      except StopIteration:
        pass
      else:
        self.__orderings = orderings

    def __str__(self):
      return str(self.__entity)

    def GetEntity(self):
      """Gets the wrapped entity."""
      return self.__entity

    def GetNext(self):
      """Wrap and return the next entity.

      The entity is retrieved from the iterator given at construction time.
      """
      return MultiQuery.SortOrderEntity(self.__entity_iterator,
                                        self.__orderings)

    def CmpProperties(self, that):
      """Compare two entities and return their relative order.

      Compares self to that based on the current sort orderings and the
      key orders between them. Returns negative, 0, or positive depending on
      whether self is less, equal to, or greater than that. This
      comparison returns as if all values were to be placed in ascending order
      (highest value last).  Only uses the sort orderings to compare (ignores
       keys).

      Args:
        that: SortOrderEntity

      Returns:
        Negative if self < that
        Zero if self == that
        Positive if self > that
      """
      if not self.__entity:
        return cmp(self.__entity, that.__entity)

      for (identifier, order) in self.__orderings:
        value1 = self.__GetValueForId(self, identifier, order)
        value2 = self.__GetValueForId(that, identifier, order)

        result = cmp(value1, value2)
        if order == Query.DESCENDING:
          result = -result
        if result:
          return result
      return 0

    def __GetValueForId(self, sort_order_entity, identifier, sort_order):
      value = _GetPropertyValue(sort_order_entity.__entity, identifier)
      if isinstance(value, list):
        entity_key = sort_order_entity.__entity.key()
        if (entity_key, identifier) in self.__min_max_value_cache:
          value = self.__min_max_value_cache[(entity_key, identifier)]
        elif sort_order == Query.DESCENDING:
          value = min(value)
        else:
          value = max(value)
        self.__min_max_value_cache[(entity_key, identifier)] = value

      return value

    def __cmp__(self, that):
      """Compare self to that w.r.t. values defined in the sort order.

      Compare an entity with another, using sort-order first, then the key
      order to break ties. This can be used in a heap to have faster min-value
      lookup.

      Args:
        that: other entity to compare to
      Returns:
        negative: if self is less than that in sort order
        zero: if self is equal to that in sort order
        positive: if self is greater than that in sort order
      """
      property_compare = self.CmpProperties(that)
      if property_compare:
        return property_compare
      else:
        return cmp(self.__entity.key(), that.__entity.key())

  def Run(self, **kwargs):
    """Return an iterable output with all results in order.

    Merge sort the results. First create a list of iterators, then walk
    though them and yield results in order.
    """
    rpc = GetRpcFromKwargs(kwargs)
    results = []
    count = 1
    log_level = logging.DEBUG - 1
    for bound_query in self.__bound_queries:
      logging.log(log_level, 'Running query #%i' % count)
      if rpc:
        rpc_clone = rpc.clone()
      else:
        rpc_clone = None
      results.append(bound_query.Run(rpc=rpc_clone))
      count += 1

    def IterateResults(results):
      """Iterator function to return all results in sorted order.

      Iterate over the array of results, yielding the next element, in
      sorted order. This function is destructive (results will be empty
      when the operation is complete).

      Args:
        results: list of result iterators to merge and iterate through

      Yields:
        The next result in sorted order.
      """
      result_heap = []
      for result in results:
        heap_value = MultiQuery.SortOrderEntity(result, self.__orderings)
        if heap_value.GetEntity():
          heapq.heappush(result_heap, heap_value)

      used_keys = set()

      while result_heap:
        top_result = heapq.heappop(result_heap)

        results_to_push = []
        if top_result.GetEntity().key() not in used_keys:
          yield top_result.GetEntity()
        else:
          pass

        used_keys.add(top_result.GetEntity().key())

        results_to_push = []
        while result_heap:
          next = heapq.heappop(result_heap)
          if cmp(top_result, next):
            results_to_push.append(next)
            break
          else:
            results_to_push.append(next.GetNext())
        results_to_push.append(top_result.GetNext())

        for popped_result in results_to_push:
          if popped_result.GetEntity():
            heapq.heappush(result_heap, popped_result)

    return IterateResults(results)

  def Count(self, limit=None, **kwargs):
    """Return the number of matched entities for this query.

    Will return the de-duplicated count of results.  Will call the more
    efficient Get() function if a limit is given.

    Args:
      limit: maximum number of entries to count (for any result > limit, return
      limit).
      rpc: datastore.RPC to use for this request.

    Returns:
      count of the number of entries returned.
    """
    rpc = GetRpcFromKwargs(kwargs)
    if limit is None:
      count = 0
      for i in self.Run(rpc=rpc):
        count += 1
      return count
    else:
      return len(self.Get(limit, rpc=rpc))

  def GetCompiledCursor(self):
    raise AssertionError('No cursor available for a MultiQuery (queries '
                         'using "IN" or "!=" operators)')

  def GetCompiledQuery(self):
    raise AssertionError('No compilation available for a MultiQuery (queries '
                         'using "IN" or "!=" operators)')

  def __setitem__(self, query_filter, value):
    """Add a new filter by setting it on all subqueries.

    If any of the setting operations raise an exception, the ones
    that succeeded are undone and the exception is propagated
    upward.

    Args:
      query_filter: a string of the form "property operand".
      value: the value that the given property is compared against.
    """
    saved_items = []
    for index, query in enumerate(self.__bound_queries):
      saved_items.append(query.get(query_filter, None))
      try:
        query[query_filter] = value
      except:
        for q, old_value in itertools.izip(self.__bound_queries[:index],
                                           saved_items):
          if old_value is not None:
            q[query_filter] = old_value
          else:
            del q[query_filter]
        raise

  def __delitem__(self, query_filter):
    """Delete a filter by deleting it from all subqueries.

    If a KeyError is raised during the attempt, it is ignored, unless
    every subquery raised a KeyError. If any other exception is
    raised, any deletes will be rolled back.

    Args:
      query_filter: the filter to delete.

    Raises:
      KeyError: No subquery had an entry containing query_filter.
    """
    subquery_count = len(self.__bound_queries)
    keyerror_count = 0
    saved_items = []
    for index, query in enumerate(self.__bound_queries):
      try:
        saved_items.append(query.get(query_filter, None))
        del query[query_filter]
      except KeyError:
        keyerror_count += 1
      except:
        for q, old_value in itertools.izip(self.__bound_queries[:index],
                                           saved_items):
          if old_value is not None:
            q[query_filter] = old_value
        raise

    if keyerror_count == subquery_count:
      raise KeyError(query_filter)

  def __iter__(self):
    return iter(self.__bound_queries)



class Iterator(object):
  """An iterator over the results of a datastore query.

  Iterators are used to access the results of a Query. An iterator is
  obtained by building a Query, then calling Run() on it.

  Iterator implements Python's iterator protocol, so results can be accessed
  with the for and in statements:

  > it = Query('Person').Run()
  > for person in it:
  >   print 'Hi, %s!' % person['name']
  """
  def __init__(self, query_result_pb, batch_size=None, rpc=None,
               query_request_pb=None):
    """Constructor.

    kwargs gets stored and passed on to Next calls made by this iterator.
    """
    self.__cursor = query_result_pb.cursor()
    self.__keys_only = query_result_pb.keys_only()
    self.__batch_size = batch_size
    self.__rpc = rpc

    self.__results_since_prev = 0
    self.__prev_compiled_cursor = None
    self.__next_compiled_cursor = None

    if query_request_pb and query_result_pb.has_compiled_cursor():
      if query_request_pb.has_compiled_cursor():
        self.__next_compiled_cursor = query_request_pb.compiled_cursor()
      else:
        self.__next_compiled_cursor = datastore_pb.CompiledCursor()
      self.__buffer = self._ProcessQueryResult(query_result_pb)
      self.__results_since_prev = query_request_pb.offset()
    else:
      self.__buffer = self._ProcessQueryResult(query_result_pb)

  def _Get(self, count):
    """Gets the next count result(s) of the query.

    Not intended to be used by application developers. Use the python
    iterator protocol instead.

    This method uses _Next to returns the next entities or keys from the list of
    matching results. If the query specified a sort order, results are returned
    in that order. Otherwise, the order is undefined.

    The argument, count, specifies the number of results to return. However, the
    length of the returned list may be smaller than count. This is the case only
    if count is greater than the number of remaining results.

    The results are always returned as a list. If there are no results left,
    an empty list is returned.

    Args:
      # the number of results to return; must be >= 1
      count: int or long

    Returns:
      # a list of entities or keys
      [Entity or Key, ...]
    """
    entity_list = self._Next(count)
    while len(entity_list) < count:
      next_results = self._Next(count - len(entity_list))
      if not next_results:
        break
      entity_list += next_results
    return entity_list;

  def _Next(self, count=None):
    """Returns the next batch of results.

    Not intended to be used by application developers. Use the python
    iterator protocol instead.

    Values are returned in the order they are recieved from the datastore.

    If there are values in the internal buffer they are returned, otherwise a
    single RPC is run in an attempt to fulfill the request.

    The optional argument, count, specifies the number of results to return.
    However, the length of the returned list may be smaller than count. This is
    the case if:
     - the local buffer has results and count is greater than the number of
       results in the buffer.
     - count is greater than the number of remaining results
     - the size of the remaining results exceeds the RPC buffer limit
    Use _Get to ensure all possible entities are retrieved.

    When count is None, if there are items in the local buffer, they are
    all returned, otherwise the datastore backend is allowed to decide how many
    entities to send.

    The internal buffer is also used by the next() method so it is best not to
    mix _Next() and next().

    The results are always returned as a list. If there are results left, at
    least one result will be returned in this list. If there are no results
    left, an empty list is returned.

    Args:
      # the number of results to return; must be >= 1
      count: int or long or None

    Returns:
      # a list of entities or keys
      [Entity or Key, ...]
    """
    if count is not None and (not isinstance(count, (int, long)) or count <= 0):
      raise datastore_errors.BadArgumentError(
        'Argument to _Next must be an int greater than 0; received %s (a %s)' %
        (count, typename(count)))

    if self.__buffer:
      if count is None:
        entity_list = self.__buffer
        self.__buffer = []
      elif count <= len(self.__buffer):
        entity_list = self.__buffer[:count]
        del self.__buffer[:count]
      else:
        entity_list = self.__buffer
        self.__buffer = []
      self.__results_since_prev += len(entity_list)
      return entity_list


    if not self.__more_results:
      return []

    req = datastore_pb.NextRequest()
    if count is not None:
      req.set_count(count)
    if self.__next_compiled_cursor:
      req.set_compile(True)
    req.mutable_cursor().CopyFrom(self.__cursor)
    try:
      rpc = self.__rpc
      if rpc:
        self.__rpc = rpc.clone()

      result = _MakeSyncCall('datastore_v3', 'Next', req,
                             datastore_pb.QueryResult(), rpc)
    except apiproxy_errors.ApplicationError, err:
      raise _ToDatastoreError(err)

    return self._ProcessQueryResult(result)

  def _ProcessQueryResult(self, result):
    """Returns all results from datastore_pb.QueryResult and updates
    self.__more_results

    Not intended to be used by application developers. Use the python
    iterator protocol instead.

    The results are always returned as a list. If there are no results left,
    an empty list is returned.

    Args:
      # the instance of datastore_pb.QueryResult to be stored
      result: datastore_pb.QueryResult

    Returns:
      # a list of entities or keys
      [Entity or Key, ...]
    """
    if self.__next_compiled_cursor and result.has_compiled_cursor():
      self.__prev_compiled_cursor = self.__next_compiled_cursor
      self.__next_compiled_cursor = result.compiled_cursor()
      self.__results_since_prev = 0

    self.__more_results = result.more_results()

    if self.__keys_only:
      return [Key._FromPb(e.key()) for e in result.result_list()]
    else:
      return [Entity._FromPb(e) for e in result.result_list()]

  def GetCompiledCursor(self, query):
    if not self.__buffer:
      return self.__next_compiled_cursor
    elif not self.__results_since_prev:
      return self.__prev_compiled_cursor
    elif self.__prev_compiled_cursor:
      request = query._ToPb(limit=1, offset=self.__results_since_prev, count=0)
      request.mutable_compiled_cursor().CopyFrom(self.__prev_compiled_cursor)
      rpc = self.__rpc
      if rpc:
        self.__rpc = rpc.clone()
      try:
        result = _MakeSyncCall('datastore_v3', 'RunQuery', request,
                               datastore_pb.QueryResult(), rpc)
      except apiproxy_errors.ApplicationError, err:
        raise _ToDatastoreError(err)
      return result.compiled_cursor()
    else:
      return None

  def next(self):
    if not self.__buffer:
      self.__buffer = self._Next(self.__batch_size)
    try:
      result = self.__buffer.pop(0)
    except IndexError:
      raise StopIteration
    self.__results_since_prev += 1
    return result

  def __iter__(self): return self

class _Transaction(object):
  """Encapsulates a transaction currently in progress.

  If we've sent a BeginTransaction call, then handle will be a
  datastore_pb.Transaction that holds the transaction handle.

  If we know the entity group for this transaction, it's stored in the
  entity_group attribute, which is set by RunInTransaction().

  modified_keys is a set containing the Keys of all entities modified (ie put
  or deleted) in this transaction. If an entity is modified more than once, a
  BadRequestError is raised.
  """
  def __init__(self):
    """Initializes modified_keys to the empty set."""
    self.handle = None
    self.entity_group = None
    self.modified_keys = None
    self.modified_keys = set()


def RunInTransaction(function, *args, **kwargs):
  """Runs a function inside a datastore transaction.

     Runs the user-provided function inside transaction, retries default
     number of times.

    Args:
    # a function to be run inside the transaction
    function: callable
    # positional arguments to pass to the function
    args: variable number of any type

  Returns:
    the function's return value, if any

  Raises:
    TransactionFailedError, if the transaction could not be committed.
  """
  return RunInTransactionCustomRetries(
      DEFAULT_TRANSACTION_RETRIES, function, *args, **kwargs)


def RunInTransactionCustomRetries(retries, function, *args, **kwargs):
  """Runs a function inside a datastore transaction.

  Runs the user-provided function inside a full-featured, ACID datastore
  transaction. Every Put, Get, and Delete call in the function is made within
  the transaction. All entities involved in these calls must belong to the
  same entity group. Queries are not supported.

  The trailing arguments are passed to the function as positional arguments.
  If the function returns a value, that value will be returned by
  RunInTransaction. Otherwise, it will return None.

  The function may raise any exception to roll back the transaction instead of
  committing it. If this happens, the transaction will be rolled back and the
  exception will be re-raised up to RunInTransaction's caller.

  If you want to roll back intentionally, but don't have an appropriate
  exception to raise, you can raise an instance of datastore_errors.Rollback.
  It will cause a rollback, but will *not* be re-raised up to the caller.

  The function may be run more than once, so it should be idempotent. It
  should avoid side effects, and it shouldn't have *any* side effects that
  aren't safe to occur multiple times. This includes modifying the arguments,
  since they persist across invocations of the function. However, this doesn't
  include Put, Get, and Delete calls, of course.

  Example usage:

  > def decrement(key, amount=1):
  >   counter = datastore.Get(key)
  >   counter['count'] -= amount
  >   if counter['count'] < 0:    # don't let the counter go negative
  >     raise datastore_errors.Rollback()
  >   datastore.Put(counter)
  >
  > counter = datastore.Query('Counter', {'name': 'foo'})
  > datastore.RunInTransaction(decrement, counter.key(), amount=5)

  Transactions satisfy the traditional ACID properties. They are:

  - Atomic. All of a transaction's operations are executed or none of them are.

  - Consistent. The datastore's state is consistent before and after a
  transaction, whether it committed or rolled back. Invariants such as
  "every entity has a primary key" are preserved.

  - Isolated. Transactions operate on a snapshot of the datastore. Other
  datastore operations do not see intermediated effects of the transaction;
  they only see its effects after it has committed.

  - Durable. On commit, all writes are persisted to the datastore.

  Nested transactions are not supported.

  Args:
    # number of retries
    retries: integer
    # a function to be run inside the transaction
    function: callable
    # positional arguments to pass to the function
    args: variable number of any type

  Returns:
    the function's return value, if any

  Raises:
    TransactionFailedError, if the transaction could not be committed.
  """

  if _CurrentTransactionKey():
    raise datastore_errors.BadRequestError(
      'Nested transactions are not supported.')

  if retries < 0:
    raise datastore_errors.BadRequestError(
      'Number of retries should be non-negative number.')

  tx_key = None

  try:
    tx_key = _NewTransactionKey()
    tx = _Transaction()
    _txes[tx_key] = tx

    for i in range(0, retries + 1):
      tx.modified_keys.clear()

      try:
        result = function(*args, **kwargs)
      except:
        original_exception = sys.exc_info()

        if tx.handle:
          try:
            _MakeSyncCall('datastore_v3', 'Rollback',
                          tx.handle, api_base_pb.VoidProto())
          except:
            exc_info = sys.exc_info()
            logging.info('Exception sending Rollback:\n' +
                         ''.join(traceback.format_exception(*exc_info)))

        type, value, trace = original_exception
        if type is datastore_errors.Rollback:
          return
        else:
          raise type, value, trace

      if tx.handle:
        try:
          _MakeSyncCall('datastore_v3', 'Commit',
                        tx.handle, datastore_pb.CommitResponse())
        except apiproxy_errors.ApplicationError, err:
          if (err.application_error ==
              datastore_pb.Error.CONCURRENT_TRANSACTION):
            logging.warning('Transaction collision for entity group with '
                            'key %r. Retrying...', tx.entity_group)
            tx.handle = None
            tx.entity_group = None
            continue
          else:
            raise _ToDatastoreError(err)

      return result

    raise datastore_errors.TransactionFailedError(
      'The transaction could not be committed. Please try again.')

  finally:
    if tx_key in _txes:
      del _txes[tx_key]
    del tx_key


def _MaybeSetupTransaction(request, keys):
  """Begins a transaction, if necessary, and populates it in the request.

  If we're currently inside a transaction, this records the entity group,
  checks that the keys are all in that entity group, creates the transaction
  PB, and sends the BeginTransaction. It then populates the transaction handle
  in the request.

  Raises BadRequestError if the entity has a different entity group than the
  current transaction.

  Args:
    request: GetRequest, PutRequest, DeleteRequest, or Query
    keys: sequence of Keys

  Returns:
    _Transaction if we're inside a transaction, otherwise None
  """
  assert isinstance(request, (datastore_pb.GetRequest, datastore_pb.PutRequest,
                              datastore_pb.DeleteRequest, datastore_pb.Query,
                              taskqueue_service_pb.TaskQueueAddRequest,
                              )), request.__class__
  tx_key = None

  try:
    tx_key = _CurrentTransactionKey()
    if tx_key:
      tx = _txes[tx_key]

      groups = [k.entity_group() for k in keys]
      if tx.entity_group:
        expected_group = tx.entity_group
      elif groups:
        expected_group = groups[0]
      else:
        expected_group = None

      for group in groups:
        if (group != expected_group or







            (not group.has_id_or_name() and group is not expected_group)):
          raise _DifferentEntityGroupError(expected_group, group)

        if not tx.entity_group and group.has_id_or_name():
          tx.entity_group = group

      if not tx.handle:
        req = datastore_pb.BeginTransactionRequest()
        if keys:
          req.set_app(keys[0].app())
        else:
          assert isinstance(request, taskqueue_service_pb.TaskQueueAddRequest)
          req.set_app(os.environ['APPLICATION_ID'])
        assert req.app()

        tx.handle = _MakeSyncCall('datastore_v3', 'BeginTransaction',
                                  req, datastore_pb.Transaction())

        if not tx.handle.app():
          tx.handle.set_app(req.app())

      request.mutable_transaction().CopyFrom(tx.handle)

      return tx

  finally:
    del tx_key


def _DifferentEntityGroupError(a, b):
  """Raises a BadRequestError that says the given entity groups are different.

  Includes the two entity groups in the message, formatted more clearly and
  concisely than repr(Key).

  Args:
    a, b are both Keys that represent entity groups.
  """
  def id_or_name(key):
    if key.name():
      return 'name=%r' % key.name()
    else:
      return 'id=%r' % key.id()

  raise datastore_errors.BadRequestError(
    'Cannot operate on different entity groups in a transaction: '
    '(kind=%r, %s) and (kind=%r, %s).' % (a.kind(), id_or_name(a),
                                          b.kind(), id_or_name(b)))


def _FindTransactionFrameInStack():
  """Walks the stack to find a RunInTransaction() call.

  Returns:
    # this is the RunInTransactionCustomRetries() frame record, if found
    frame record or None
  """
  frame = sys._getframe()
  filename = frame.f_code.co_filename

  frame = frame.f_back.f_back
  while frame:
    if (frame.f_code.co_filename == filename and
        frame.f_code.co_name == 'RunInTransactionCustomRetries'):
      return frame
    frame = frame.f_back

  return None

_CurrentTransactionKey = _FindTransactionFrameInStack

_NewTransactionKey = sys._getframe


def _GetCompleteKeyOrError(arg):
  """Expects an Entity or a Key, and returns the corresponding Key. Raises
  BadArgumentError or BadKeyError if arg is a different type or is incomplete.

  Args:
    arg: Entity or Key

  Returns:
    Key
  """
  if isinstance(arg, Key):
    key = arg
  elif isinstance(arg, basestring):
    key = Key(arg)
  elif isinstance(arg, Entity):
    key = arg.key()
  elif not isinstance(arg, Key):
    raise datastore_errors.BadArgumentError(
      'Expects argument to be an Entity or Key; received %s (a %s).' %
      (arg, typename(arg)))
  assert isinstance(key, Key)

  if not key.has_id_or_name():
    raise datastore_errors.BadKeyError('Key %r is not complete.' % key)

  return key


def _GetPropertyValue(entity, property):
  """Returns an entity's value for a given property name.

  Handles special properties like __key__ as well as normal properties.

  Args:
    entity: datastore.Entity
    property: str; the property name

  Returns:
    property value. For __key__, a datastore_types.Key.

  Raises:
    KeyError, if the entity does not have the given property.
  """
  if property in datastore_types._SPECIAL_PROPERTIES:
    assert property == datastore_types._KEY_SPECIAL_PROPERTY
    return entity.key()
  else:
    return entity[property]


def _AddOrAppend(dictionary, key, value):
  """Adds the value to the existing values in the dictionary, if any.

  If dictionary[key] doesn't exist, sets dictionary[key] to value.

  If dictionary[key] is not a list, sets dictionary[key] to [old_value, value].

  If dictionary[key] is a list, appends value to that list.

  Args:
    dictionary: a dict
    key, value: anything
  """
  if key in dictionary:
    existing_value = dictionary[key]
    if isinstance(existing_value, list):
      existing_value.append(value)
    else:
      dictionary[key] = [existing_value, value]
  else:
    dictionary[key] = value


def _ToDatastoreError(err):
  """Converts an apiproxy.ApplicationError to an error in datastore_errors.

  Args:
    err: apiproxy.ApplicationError

  Returns:
    a subclass of datastore_errors.Error
  """
  return _DatastoreExceptionFromErrorCodeAndDetail(err.application_error,
                                                   err.error_detail)


def _DatastoreExceptionFromErrorCodeAndDetail(error, detail):
  """Converts a datastore_pb.Error into a datastore_errors.Error.

  Args:
    error: A member of the datastore_pb.Error enumeration.
    detail: A string providing extra details about the error.

  Returns:
    A subclass of datastore_errors.Error.
  """
  exception_class = {
      datastore_pb.Error.BAD_REQUEST: datastore_errors.BadRequestError,
      datastore_pb.Error.CONCURRENT_TRANSACTION:
      datastore_errors.TransactionFailedError,
      datastore_pb.Error.INTERNAL_ERROR: datastore_errors.InternalError,
      datastore_pb.Error.NEED_INDEX: datastore_errors.NeedIndexError,
      datastore_pb.Error.TIMEOUT: datastore_errors.Timeout,
  }.get(error, datastore_errors.Error)

  if detail is None:
    return exception_class()
  else:
    return exception_class(detail)
