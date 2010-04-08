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

"""
In-memory persistent stub for the Python datastore API. Gets, queries,
and searches are implemented as in-memory scans over all entities.

Stores entities across sessions as pickled proto bufs in a single file. On
startup, all entities are read from the file and loaded into memory. On
every Put(), the file is wiped and all entities are written from scratch.
Clients can also manually Read() and Write() the file themselves.

Transactions are serialized through __tx_lock. Each transaction acquires it
when it begins and releases it when it commits or rolls back. This is
important, since there are other member variables like __tx_snapshot that are
per-transaction, so they should only be used by one tx at a time.
"""






import datetime
import logging
import md5
import os
import struct
import sys
import tempfile
import threading
import urllib
import warnings
try:
  from urlparse import parse_qsl
except ImportError:
  from cgi import parse_qsl

import cPickle as pickle

from google.appengine.api import api_base_pb
from google.appengine.api import apiproxy_stub
from google.appengine.api import apiproxy_stub_map
from google.appengine.api import datastore
from google.appengine.api import datastore_errors
from google.appengine.api import datastore_types
from google.appengine.api import users
from google.appengine.datastore import datastore_pb
from google.appengine.datastore import datastore_index
from google.appengine.runtime import apiproxy_errors
from google.net.proto import ProtocolBuffer
from google.appengine.datastore import entity_pb

try:
  __import__('google.appengine.api.labs.taskqueue.taskqueue_service_pb')
  taskqueue_service_pb = sys.modules.get(
      'google.appengine.api.labs.taskqueue.taskqueue_service_pb')
except ImportError:
  from google.appengine.api.taskqueue import taskqueue_service_pb

warnings.filterwarnings('ignore', 'tempnam is a potential security risk')


entity_pb.Reference.__hash__ = lambda self: hash(self.Encode())
datastore_pb.Query.__hash__ = lambda self: hash(self.Encode())
datastore_pb.Transaction.__hash__ = lambda self: hash(self.Encode())


_MAXIMUM_RESULTS = 1000


_MAX_QUERY_OFFSET = 1000


_MAX_QUERY_COMPONENTS = 100


_BATCH_SIZE = 20


_MAX_ACTIONS_PER_TXN = 5


_CURSOR_CONCAT_STR = '!CURSOR!'


class _StoredEntity(object):
  """Simple wrapper around an entity stored by the stub.

  Public properties:
    protobuf: Native protobuf Python object, entity_pb.EntityProto.
    encoded_protobuf: Encoded binary representation of above protobuf.
    native: datastore.Entity instance.
  """

  def __init__(self, entity):
    """Create a _StoredEntity object and store an entity.

    Args:
      entity: entity_pb.EntityProto to store.
    """
    self.protobuf = entity

    self.encoded_protobuf = entity.Encode()

    self.native = datastore.Entity._FromPb(entity)


class _Cursor(object):
  """A query cursor.

  Public properties:
    cursor: the integer cursor
    count: the original total number of results
    keys_only: whether the query is keys_only
    app: the app for which this cursor was created

  Class attributes:
    _next_cursor: the next cursor to allocate
    _next_cursor_lock: protects _next_cursor
    _offset: the internal index for where we are in the results
    _limit: the limit on the original query, used to track remaining results
  """
  _next_cursor = 1
  _next_cursor_lock = threading.Lock()

  def __init__(self, query, results, order_compare_entities):
    """Constructor.

    Args:
      query: the query request proto
      # the query results, in order, such that results[self.offset+1] is
      # the next result
      results: list of datastore.Entity
      order_compare_entities: a __cmp__ function for datastore.Entity that
        follows sort order as specified by the query
    """

    offset = 0
    cursor_entity = None
    if query.has_compiled_cursor() and query.compiled_cursor().position_list():
      (cursor_entity, inclusive) = self._DecodeCompiledCursor(
          query, query.compiled_cursor())
      offset += _Cursor._GetCursorOffset(results, cursor_entity, inclusive,
                                         order_compare_entities)

    if query.has_offset():
      offset += query.offset()

    if offset > 0:
      self.__last_result = results[offset - 1]
    else:
      self.__last_result = cursor_entity

    if query.has_limit():
      self.__results = results[offset:offset + query.limit()]
    else:
      self.__results = results[offset:]

    self.__query = query
    self.__offset = 0

    self.app = query.app()
    self.keys_only = query.keys_only()
    self.count = len(self.__results)
    self.cursor = self._AcquireCursorID()

  def _AcquireCursorID(self):
    """Acquires the next cursor id in a thread safe manner.
    """
    self._next_cursor_lock.acquire()
    try:
      cursor_id = _Cursor._next_cursor
      _Cursor._next_cursor += 1
    finally:
      self._next_cursor_lock.release()
    return cursor_id

  @staticmethod
  def _GetCursorOffset(results, cursor_entity, inclusive, compare):
    """Converts a cursor entity into a offset into the result set even if the
    cursor_entity no longer exists.

    Args:
      cursor_entity: the decoded datastore.Entity from the compiled query
      inclusive: boolean that specifies if to offset past the cursor_entity
      compare: a function that takes two datastore.Entity and compares them
    Returns:
      the integer offset
    """
    lo = 0
    hi = len(results)
    if inclusive:
      while lo < hi:
        mid = (lo + hi) // 2
        if compare(results[mid], cursor_entity) < 0:
           lo = mid + 1
        else:
          hi = mid
    else:
      while lo < hi:
        mid = (lo + hi) // 2
        if compare(cursor_entity, results[mid]) < 0:
          hi = mid
        else:
          lo = mid + 1
    return lo

  def _ValidateQuery(self, query, query_info):
    """Ensure that the given query matches the query_info.

    Args:
      query: datastore_pb.Query instance we are chacking
      query_info: datastore_pb.Query instance we want to match

    Raises BadRequestError on failure.
    """
    error_msg = 'Cursor does not match query: %s'
    exc = datastore_errors.BadRequestError
    if query_info.filter_list() != query.filter_list():
      raise exc(error_msg % 'filters do not match')
    if query_info.order_list() != query.order_list():
      raise exc(error_msg % 'orders do not match')

    for attr in ('ancestor', 'kind', 'name_space', 'search_query'):
      query_info_has_attr = getattr(query_info, 'has_%s' % attr)
      query_info_attr = getattr(query_info, attr)
      query_has_attr = getattr(query, 'has_%s' % attr)
      query_attr = getattr(query, attr)
      if query_info_has_attr():
        if not query_has_attr() or query_info_attr() != query_attr():
          raise exc(error_msg % ('%s does not match' % attr))
      elif query_has_attr():
        raise exc(error_msg % ('%s does not match' % attr))

  def _MinimalQueryInfo(self, query):
    """Extract the minimal set of information for query matching.

    Args:
      query: datastore_pb.Query instance from which to extract info.

    Returns:
      datastore_pb.Query instance suitable for matching against when
      validating cursors.
    """
    query_info = datastore_pb.Query()
    query_info.set_app(query.app())

    for filter in query.filter_list():
      query_info.filter_list().append(filter)
    for order in query.order_list():
      query_info.order_list().append(order)

    if query.has_ancestor():
      query_info.mutable_ancestor().CopyFrom(query.ancestor())

    for attr in ('kind', 'name_space', 'search_query'):
      query_has_attr = getattr(query, 'has_%s' % attr)
      query_attr = getattr(query, attr)
      query_info_set_attr = getattr(query_info, 'set_%s' % attr)
      if query_has_attr():
        query_info_set_attr(query_attr())

    return query_info

  def _DecodeCompiledCursor(self, query, compiled_cursor):
    """Converts a compiled_cursor into a cursor_entity.

    Returns:
      (cursor_entity, inclusive): a datastore.Entity and if it should be
      included in the result set.
    """
    assert len(compiled_cursor.position_list()) == 1

    position = compiled_cursor.position(0)
    entity_pb = datastore_pb.EntityProto()
    (query_info_encoded, entity_encoded) = position.start_key().split(
        _CURSOR_CONCAT_STR, 1)
    query_info_pb = datastore_pb.Query()
    query_info_pb.ParseFromString(query_info_encoded)
    self._ValidateQuery(query, query_info_pb)

    entity_pb.ParseFromString(entity_encoded)
    return (datastore.Entity._FromPb(entity_pb, True),
            position.start_inclusive())

  def _EncodeCompiledCursor(self, query, compiled_cursor):
    """Converts the current state of the cursor into a compiled_cursor

    Args:
      query: the datastore_pb.Query this cursor is related to
      compiled_cursor: an empty datstore_pb.CompiledCursor
    """
    if self.__last_result:
      position = compiled_cursor.add_position()
      query_info = self._MinimalQueryInfo(query)
      start_key = _CURSOR_CONCAT_STR.join((
          query_info.Encode(),
          self.__last_result.ToPb().Encode()))
      position.set_start_key(str(start_key))
      position.set_start_inclusive(False)
    elif self.__query.has_compiled_cursor:
      compiled_cursor.CopyFrom(self.__query.compiled_cursor())

  def PopulateQueryResult(self, result, count, compile=False):
    """Populates a QueryResult with this cursor and the given number of results.

    Args:
      result: datastore_pb.QueryResult
      count: integer of how many results to return
      compile: boolean, whether we are compiling this query
    """
    if count > _MAXIMUM_RESULTS:
      count = _MAXIMUM_RESULTS

    result.mutable_cursor().set_app(self.app)
    result.mutable_cursor().set_cursor(self.cursor)
    result.set_keys_only(self.keys_only)

    results = self.__results[self.__offset:self.__offset + count]
    count = len(results)
    if count:
      self.__offset += count
      self.__last_result = results[count - 1]

    results_pbs = [r._ToPb() for r in results]
    result.result_list().extend(results_pbs)

    result.set_more_results(self.__offset < self.count)
    if compile:
      self._EncodeCompiledCursor(self.__query, result.mutable_compiled_cursor())


class DatastoreFileStub(apiproxy_stub.APIProxyStub):
  """ Persistent stub for the Python datastore API.

  Stores all entities in memory, and persists them to a file as pickled
  protocol buffers. A DatastoreFileStub instance handles a single app's data
  and is backed by files on disk.
  """

  _PROPERTY_TYPE_TAGS = {
    datastore_types.Blob: entity_pb.PropertyValue.kstringValue,
    bool: entity_pb.PropertyValue.kbooleanValue,
    datastore_types.Category: entity_pb.PropertyValue.kstringValue,
    datetime.datetime: entity_pb.PropertyValue.kint64Value,
    datastore_types.Email: entity_pb.PropertyValue.kstringValue,
    float: entity_pb.PropertyValue.kdoubleValue,
    datastore_types.GeoPt: entity_pb.PropertyValue.kPointValueGroup,
    datastore_types.IM: entity_pb.PropertyValue.kstringValue,
    int: entity_pb.PropertyValue.kint64Value,
    datastore_types.Key: entity_pb.PropertyValue.kReferenceValueGroup,
    datastore_types.Link: entity_pb.PropertyValue.kstringValue,
    long: entity_pb.PropertyValue.kint64Value,
    datastore_types.PhoneNumber: entity_pb.PropertyValue.kstringValue,
    datastore_types.PostalAddress: entity_pb.PropertyValue.kstringValue,
    datastore_types.Rating: entity_pb.PropertyValue.kint64Value,
    str: entity_pb.PropertyValue.kstringValue,
    datastore_types.Text: entity_pb.PropertyValue.kstringValue,
    type(None): 0,
    unicode: entity_pb.PropertyValue.kstringValue,
    users.User: entity_pb.PropertyValue.kUserValueGroup,
    }

  WRITE_ONLY = entity_pb.CompositeIndex.WRITE_ONLY
  READ_WRITE = entity_pb.CompositeIndex.READ_WRITE
  DELETED = entity_pb.CompositeIndex.DELETED
  ERROR = entity_pb.CompositeIndex.ERROR

  _INDEX_STATE_TRANSITIONS = {
    WRITE_ONLY: frozenset((READ_WRITE, DELETED, ERROR)),
    READ_WRITE: frozenset((DELETED,)),
    ERROR: frozenset((DELETED,)),
    DELETED: frozenset((ERROR,)),
  }

  def __init__(self,
               app_id,
               datastore_file,
               history_file=None,
               require_indexes=False,
               service_name='datastore_v3',
               trusted=False):
    """Constructor.

    Initializes and loads the datastore from the backing files, if they exist.

    Args:
      app_id: string
      datastore_file: string, stores all entities across sessions.  Use None
          not to use a file.
      history_file: DEPRECATED. No-op.
      require_indexes: bool, default False.  If True, composite indexes must
          exist in index.yaml for queries that need them.
      service_name: Service name expected for all calls.
      trusted: bool, default False.  If True, this stub allows an app to
        access the data of another app.
    """
    super(DatastoreFileStub, self).__init__(service_name)


    assert isinstance(app_id, basestring) and app_id != ''
    self.__app_id = app_id
    self.__datastore_file = datastore_file
    self.SetTrusted(trusted)

    self.__entities = {}

    self.__schema_cache = {}

    self.__tx_snapshot = {}

    self.__tx_actions = []

    self.__queries = {}

    self.__transactions = set()

    self.__indexes = {}
    self.__require_indexes = require_indexes

    self.__query_history = {}

    self.__next_id = 1
    self.__next_tx_handle = 1
    self.__next_index_id = 1
    self.__id_lock = threading.Lock()
    self.__tx_handle_lock = threading.Lock()
    self.__index_id_lock = threading.Lock()
    self.__tx_lock = threading.Lock()
    self.__entities_lock = threading.Lock()
    self.__file_lock = threading.Lock()
    self.__indexes_lock = threading.Lock()

    self.Read()

  def Clear(self):
    """ Clears the datastore by deleting all currently stored entities and
    queries. """
    self.__entities = {}
    self.__queries = {}
    self.__transactions = set()
    self.__query_history = {}
    self.__schema_cache = {}

  def SetTrusted(self, trusted):
    """Set/clear the trusted bit in the stub.

    This bit indicates that the app calling the stub is trusted. A
    trusted app can write to datastores of other apps.

    Args:
      trusted: boolean.
    """
    self.__trusted = trusted

  def __ValidateAppId(self, app_id):
    """Verify that this is the stub for app_id.

    Args:
      app_id: An application ID.

    Raises:
      datastore_errors.BadRequestError: if this is not the stub for app_id.
    """
    assert app_id
    if not self.__trusted and app_id != self.__app_id:
      raise datastore_errors.BadRequestError(
          'app %s cannot access app %s\'s data' % (self.__app_id, app_id))

  def __ValidateKey(self, key):
    """Validate this key.

    Args:
      key: entity_pb.Reference

    Raises:
      datastore_errors.BadRequestError: if the key is invalid
    """
    assert isinstance(key, entity_pb.Reference)

    self.__ValidateAppId(key.app())

    for elem in key.path().element_list():
      if elem.has_id() == elem.has_name():
        raise datastore_errors.BadRequestError(
          'each key path element should have id or name but not both: %r' % key)

  def __ValidateTransaction(self, tx):
    """Verify that this transaction exists and is valid.

    Args:
      tx: datastore_pb.Transaction

    Raises:
      datastore_errors.BadRequestError: if the tx is valid or doesn't exist.
    """
    assert isinstance(tx, datastore_pb.Transaction)
    self.__ValidateAppId(tx.app())
    if tx not in self.__transactions:
      raise apiproxy_errors.ApplicationError(datastore_pb.Error.BAD_REQUEST,
                                             'Transaction %s not found' % tx)

  def _AppIdNamespaceKindForKey(self, key):
    """ Get (app, kind) tuple from given key.

    The (app, kind) tuple is used as an index into several internal
    dictionaries, e.g. __entities.

    Args:
      key: entity_pb.Reference

    Returns:
      Tuple (app, kind), both are unicode strings.
    """
    last_path = key.path().element_list()[-1]
    return (datastore_types.EncodeAppIdNamespace(key.app(), key.name_space()),
        last_path.type())

  def _StoreEntity(self, entity):
    """ Store the given entity.

    Args:
      entity: entity_pb.EntityProto
    """
    key = entity.key()
    app_kind = self._AppIdNamespaceKindForKey(key)
    if app_kind not in self.__entities:
      self.__entities[app_kind] = {}
    self.__entities[app_kind][key] = _StoredEntity(entity)

    if app_kind in self.__schema_cache:
      del self.__schema_cache[app_kind]

  READ_PB_EXCEPTIONS = (ProtocolBuffer.ProtocolBufferDecodeError, LookupError,
                        TypeError, ValueError)
  READ_ERROR_MSG = ('Data in %s is corrupt or a different version. '
                    'Try running with the --clear_datastore flag.\n%r')
  READ_PY250_MSG = ('Are you using FloatProperty and/or GeoPtProperty? '
                    'Unfortunately loading float values from the datastore '
                    'file does not work with Python 2.5.0. '
                    'Please upgrade to a newer Python 2.5 release or use '
                    'the --clear_datastore flag.\n')

  def Read(self):
    """ Reads the datastore and history files into memory.

    The in-memory query history is cleared, but the datastore is *not*
    cleared; the entities in the files are merged into the entities in memory.
    If you want them to overwrite the in-memory datastore, call Clear() before
    calling Read().

    If the datastore file contains an entity with the same app name, kind, and
    key as an entity already in the datastore, the entity from the file
    overwrites the entity in the datastore.

    Also sets __next_id to one greater than the highest id allocated so far.
    """
    if self.__datastore_file and self.__datastore_file != '/dev/null':
      for encoded_entity in self.__ReadPickled(self.__datastore_file):
        try:
          entity = entity_pb.EntityProto(encoded_entity)
        except self.READ_PB_EXCEPTIONS, e:
          raise datastore_errors.InternalError(self.READ_ERROR_MSG %
                                               (self.__datastore_file, e))
        except struct.error, e:
          if (sys.version_info[0:3] == (2, 5, 0)
              and e.message.startswith('unpack requires a string argument')):
            raise datastore_errors.InternalError(self.READ_PY250_MSG +
                                                 self.READ_ERROR_MSG %
                                                 (self.__datastore_file, e))
          else:
            raise

        self._StoreEntity(entity)

        last_path = entity.key().path().element_list()[-1]
        if last_path.has_id() and last_path.id() >= self.__next_id:
          self.__next_id = last_path.id() + 1

  def Write(self):
    """ Writes out the datastore and history files. Be careful! If the files
    already exist, this method overwrites them!
    """
    self.__WriteDatastore()

  def __WriteDatastore(self):
    """ Writes out the datastore file. Be careful! If the file already exist,
    this method overwrites it!
    """
    if self.__datastore_file and self.__datastore_file != '/dev/null':
      encoded = []
      for kind_dict in self.__entities.values():
        for entity in kind_dict.values():
          encoded.append(entity.encoded_protobuf)

      self.__WritePickled(encoded, self.__datastore_file)

  def __ReadPickled(self, filename):
    """Reads a pickled object from the given file and returns it.
    """
    self.__file_lock.acquire()

    try:
      try:
        if filename and filename != '/dev/null' and os.path.isfile(filename):
          return pickle.load(open(filename, 'rb'))
        else:
          logging.warning('Could not read datastore data from %s', filename)
      except (AttributeError, LookupError, ImportError, NameError, TypeError,
              ValueError, struct.error, pickle.PickleError), e:
        raise datastore_errors.InternalError(
          'Could not read data from %s. Try running with the '
          '--clear_datastore flag. Cause:\n%r' % (filename, e))
    finally:
      self.__file_lock.release()

    return []

  def __WritePickled(self, obj, filename, openfile=file):
    """Pickles the object and writes it to the given file.
    """
    if not filename or filename == '/dev/null' or not obj:
      return

    tmpfile = openfile(os.tempnam(os.path.dirname(filename)), 'wb')

    pickler = pickle.Pickler(tmpfile, protocol=1)
    pickler.fast = True
    pickler.dump(obj)

    tmpfile.close()

    self.__file_lock.acquire()
    try:
      try:
        os.rename(tmpfile.name, filename)
      except OSError:
        try:
          os.remove(filename)
        except:
          pass
        os.rename(tmpfile.name, filename)
    finally:
      self.__file_lock.release()

  def MakeSyncCall(self, service, call, request, response):
    """ The main RPC entry point. service must be 'datastore_v3'.
    """
    self.assertPbIsInitialized(request)
    super(DatastoreFileStub, self).MakeSyncCall(service,
                                                call,
                                                request,
                                                response)
    self.assertPbIsInitialized(response)

  def assertPbIsInitialized(self, pb):
    """Raises an exception if the given PB is not initialized and valid."""
    explanation = []
    assert pb.IsInitialized(explanation), explanation
    pb.Encode()

  def QueryHistory(self):
    """Returns a dict that maps Query PBs to times they've been run.
    """
    return dict((pb, times) for pb, times in self.__query_history.items()
                if pb.app() == self.__app_id)

  def _Dynamic_Put(self, put_request, put_response):
    if put_request.has_transaction():
      self.__ValidateTransaction(put_request.transaction())

    clones = []
    for entity in put_request.entity_list():
      self.__ValidateKey(entity.key())

      clone = entity_pb.EntityProto()
      clone.CopyFrom(entity)

      for property in clone.property_list() + clone.raw_property_list():
        if property.value().has_uservalue():
          uid = md5.new(property.value().uservalue().email().lower()).digest()
          uid = '1' + ''.join(['%02d' % ord(x) for x in uid])[:20]
          property.mutable_value().mutable_uservalue().set_obfuscated_gaiaid(
              uid)

      clones.append(clone)

      assert clone.has_key()
      assert clone.key().path().element_size() > 0

      last_path = clone.key().path().element_list()[-1]
      if last_path.id() == 0 and not last_path.has_name():
        self.__id_lock.acquire()
        last_path.set_id(self.__next_id)
        self.__next_id += 1
        self.__id_lock.release()

        assert clone.entity_group().element_size() == 0
        group = clone.mutable_entity_group()
        root = clone.key().path().element(0)
        group.add_element().CopyFrom(root)

      else:
        assert (clone.has_entity_group() and
                clone.entity_group().element_size() > 0)

    self.__entities_lock.acquire()

    try:
      for clone in clones:
        self._StoreEntity(clone)
    finally:
      self.__entities_lock.release()

    if not put_request.has_transaction():
      self.__WriteDatastore()

    put_response.key_list().extend([c.key() for c in clones])


  def _Dynamic_Get(self, get_request, get_response):
    if get_request.has_transaction():
      self.__ValidateTransaction(get_request.transaction())
      entities = self.__tx_snapshot
    else:
      entities = self.__entities

    for key in get_request.key_list():
      self.__ValidateAppId(key.app())
      app_kind = self._AppIdNamespaceKindForKey(key)

      group = get_response.add_entity()
      try:
        entity = entities[app_kind][key].protobuf
      except KeyError:
        entity = None

      if entity:
        group.mutable_entity().CopyFrom(entity)


  def _Dynamic_Delete(self, delete_request, delete_response):
    if delete_request.has_transaction():
      self.__ValidateTransaction(delete_request.transaction())

    self.__entities_lock.acquire()
    try:
      for key in delete_request.key_list():
        self.__ValidateAppId(key.app())
        app_kind = self._AppIdNamespaceKindForKey(key)
        try:
          del self.__entities[app_kind][key]
          if not self.__entities[app_kind]:
            del self.__entities[app_kind]

          del self.__schema_cache[app_kind]
        except KeyError:
          pass

        if not delete_request.has_transaction():
          self.__WriteDatastore()
    finally:
      self.__entities_lock.release()


  def _Dynamic_RunQuery(self, query, query_result):
    if query.has_transaction():
      self.__ValidateTransaction(query.transaction())
      if not query.has_ancestor():
        raise apiproxy_errors.ApplicationError(
          datastore_pb.Error.BAD_REQUEST,
          'Only ancestor queries are allowed inside transactions.')
      entities = self.__tx_snapshot
    else:
      entities = self.__entities

    app_id = query.app()
    namespace = query.name_space()
    self.__ValidateAppId(app_id)

    if query.has_offset() and query.offset() > _MAX_QUERY_OFFSET:
      raise apiproxy_errors.ApplicationError(
          datastore_pb.Error.BAD_REQUEST, 'Too big query offset.')

    num_components = len(query.filter_list()) + len(query.order_list())
    if query.has_ancestor():
      num_components += 1
    if num_components > _MAX_QUERY_COMPONENTS:
      raise apiproxy_errors.ApplicationError(
          datastore_pb.Error.BAD_REQUEST,
          ('query is too large. may not have more than %s filters'
           ' + sort orders ancestor total' % _MAX_QUERY_COMPONENTS))

    (filters, orders) = datastore_index.Normalize(query.filter_list(),
                                                  query.order_list())

    if self.__require_indexes:
      required, kind, ancestor, props, num_eq_filters = datastore_index.CompositeIndexForQuery(query)
      if required:
        required_key = kind, ancestor, props
        indexes = self.__indexes.get(app_id)
        if not indexes:
          raise apiproxy_errors.ApplicationError(
              datastore_pb.Error.NEED_INDEX,
              "This query requires a composite index, but none are defined. "
              "You must create an index.yaml file in your application root.")
        eq_filters_set = set(props[:num_eq_filters])
        remaining_filters = props[num_eq_filters:]
        for index in indexes:
          definition = datastore_index.ProtoToIndexDefinition(index)
          index_key = datastore_index.IndexToKey(definition)
          if required_key == index_key:
            break
          if num_eq_filters > 1 and (kind, ancestor) == index_key[:2]:
            this_props = index_key[2]
            this_eq_filters_set = set(this_props[:num_eq_filters])
            this_remaining_filters = this_props[num_eq_filters:]
            if (eq_filters_set == this_eq_filters_set and
                remaining_filters == this_remaining_filters):
              break
        else:
          raise apiproxy_errors.ApplicationError(
              datastore_pb.Error.NEED_INDEX,
              "This query requires a composite index that is not defined. "
              "You must update the index.yaml file in your application root.")

    try:
      query.set_app(app_id)
      datastore_types.SetNamespace(query, namespace)
      encoded = datastore_types.EncodeAppIdNamespace(app_id, namespace)
      if query.has_kind():
        results = entities[encoded, query.kind()].values()
        results = [entity.native for entity in results]
      else:
        results = []
        for key in entities:
          if key[0] == encoded:
            results += [entity.native for entity in entities[key].values()]
    except KeyError:
      results = []

    if query.has_ancestor():
      ancestor_path = query.ancestor().path().element_list()
      def is_descendant(entity):
        path = entity.key()._Key__reference.path().element_list()
        return path[:len(ancestor_path)] == ancestor_path
      results = filter(is_descendant, results)

    operators = {datastore_pb.Query_Filter.LESS_THAN:             '<',
                 datastore_pb.Query_Filter.LESS_THAN_OR_EQUAL:    '<=',
                 datastore_pb.Query_Filter.GREATER_THAN:          '>',
                 datastore_pb.Query_Filter.GREATER_THAN_OR_EQUAL: '>=',
                 datastore_pb.Query_Filter.EQUAL:                 '==',
                 }

    def has_prop_indexed(entity, prop):
      """Returns True if prop is in the entity and is indexed."""
      if prop in datastore_types._SPECIAL_PROPERTIES:
        return True
      elif prop in entity.unindexed_properties():
        return False

      values = entity.get(prop, [])
      if not isinstance(values, (tuple, list)):
        values = [values]

      for value in values:
        if type(value) not in datastore_types._RAW_PROPERTY_TYPES:
          return True
      return False

    for filt in filters:
      assert filt.op() != datastore_pb.Query_Filter.IN

      prop = filt.property(0).name().decode('utf-8')
      op = operators[filt.op()]

      filter_val_list = [datastore_types.FromPropertyPb(filter_prop)
                         for filter_prop in filt.property_list()]

      def passes_filter(entity):
        """Returns True if the entity passes the filter, False otherwise.

        The filter being evaluated is filt, the current filter that we're on
        in the list of filters in the query.
        """
        if not has_prop_indexed(entity, prop):
          return False

        try:
          entity_vals = datastore._GetPropertyValue(entity, prop)
        except KeyError:
          entity_vals = []

        if not isinstance(entity_vals, list):
          entity_vals = [entity_vals]

        for fixed_entity_val in entity_vals:
          for filter_val in filter_val_list:
            fixed_entity_type = self._PROPERTY_TYPE_TAGS.get(
              fixed_entity_val.__class__)
            filter_type = self._PROPERTY_TYPE_TAGS.get(filter_val.__class__)
            if fixed_entity_type == filter_type:
              comp = u'%r %s %r' % (fixed_entity_val, op, filter_val)
            elif op != '==':
              comp = '%r %s %r' % (fixed_entity_type, op, filter_type)
            else:
              continue

            logging.log(logging.DEBUG - 1,
                        'Evaling filter expression "%s"', comp)

            try:
              ret = eval(comp)
              if ret and ret != NotImplementedError:
                return True
            except TypeError:
              pass

        return False

      results = filter(passes_filter, results)

    for order in orders:
      prop = order.property().decode('utf-8')
      results = [entity for entity in results if has_prop_indexed(entity, prop)]

    def order_compare_entities(a, b):
      """ Return a negative, zero or positive number depending on whether
      entity a is considered smaller than, equal to, or larger than b,
      according to the query's orderings. """
      cmped = 0
      for o in orders:
        prop = o.property().decode('utf-8')

        reverse = (o.direction() is datastore_pb.Query_Order.DESCENDING)

        a_val = datastore._GetPropertyValue(a, prop)
        if isinstance(a_val, list):
          a_val = sorted(a_val, order_compare_properties, reverse=reverse)[0]

        b_val = datastore._GetPropertyValue(b, prop)
        if isinstance(b_val, list):
          b_val = sorted(b_val, order_compare_properties, reverse=reverse)[0]

        cmped = order_compare_properties(a_val, b_val)

        if o.direction() is datastore_pb.Query_Order.DESCENDING:
          cmped = -cmped

        if cmped != 0:
          return cmped

      if cmped == 0:
        return cmp(a.key(), b.key())

    def order_compare_properties(x, y):
      """Return a negative, zero or positive number depending on whether
      property value x is considered smaller than, equal to, or larger than
      property value y. If x and y are different types, they're compared based
      on the type ordering used in the real datastore, which is based on the
      tag numbers in the PropertyValue PB.
      """
      if isinstance(x, datetime.datetime):
        x = datastore_types.DatetimeToTimestamp(x)
      if isinstance(y, datetime.datetime):
        y = datastore_types.DatetimeToTimestamp(y)

      x_type = self._PROPERTY_TYPE_TAGS.get(x.__class__)
      y_type = self._PROPERTY_TYPE_TAGS.get(y.__class__)

      if x_type == y_type:
        try:
          return cmp(x, y)
        except TypeError:
          return 0
      else:
        return cmp(x_type, y_type)

    results.sort(order_compare_entities)

    clone = datastore_pb.Query()
    clone.CopyFrom(query)
    clone.clear_hint()
    if clone in self.__query_history:
      self.__query_history[clone] += 1
    else:
      self.__query_history[clone] = 1

    cursor = _Cursor(query, results, order_compare_entities)
    self.__queries[cursor.cursor] = cursor

    if query.has_count():
      count = query.count()
    elif query.has_limit():
      count = query.limit()
    else:
      count = _BATCH_SIZE

    cursor.PopulateQueryResult(query_result, count, compile=query.compile())

    if query.compile():
      compiled_query = query_result.mutable_compiled_query()
      compiled_query.set_keys_only(query.keys_only())
      compiled_query.mutable_primaryscan().set_index_name(query.Encode())

  def _Dynamic_Next(self, next_request, query_result):
    self.__ValidateAppId(next_request.cursor().app())

    cursor_handle = next_request.cursor().cursor()

    try:
      cursor = self.__queries[cursor_handle]
    except KeyError:
      raise apiproxy_errors.ApplicationError(
          datastore_pb.Error.BAD_REQUEST, 'Cursor %d not found' % cursor_handle)

    assert cursor.app == next_request.cursor().app()

    count = _BATCH_SIZE
    if next_request.has_count():
      count = next_request.count()
    cursor.PopulateQueryResult(query_result, count)

  def _Dynamic_Count(self, query, integer64proto):
    query_result = datastore_pb.QueryResult()
    self._Dynamic_RunQuery(query, query_result)
    cursor = query_result.cursor().cursor()
    integer64proto.set_value(min(self.__queries[cursor].count, _MAXIMUM_RESULTS))
    del self.__queries[cursor]

  def _Dynamic_BeginTransaction(self, request, transaction):
    self.__ValidateAppId(request.app())

    self.__tx_handle_lock.acquire()
    handle = self.__next_tx_handle
    self.__next_tx_handle += 1
    self.__tx_handle_lock.release()

    transaction.set_app(request.app())
    transaction.set_handle(handle)
    assert transaction not in self.__transactions
    self.__transactions.add(transaction)

    self.__tx_lock.acquire()
    snapshot = [(app_kind, dict(entities))
                for app_kind, entities in self.__entities.items()]
    self.__tx_snapshot = dict(snapshot)
    self.__tx_actions = []

  def _Dynamic_AddActions(self, request, _):
    """Associates the creation of one or more tasks with a transaction.

    Args:
      request: A taskqueue_service_pb.TaskQueueBulkAddRequest containing the
          tasks that should be created when the transaction is comitted.
    """


    if ((len(self.__tx_actions) + request.add_request_size()) >
        _MAX_ACTIONS_PER_TXN):
      raise apiproxy_errors.ApplicationError(
          datastore_pb.Error.BAD_REQUEST,
          'Too many messages, maximum allowed %s' % _MAX_ACTIONS_PER_TXN)

    new_actions = []
    for add_request in request.add_request_list():
      self.__ValidateTransaction(add_request.transaction())
      clone = taskqueue_service_pb.TaskQueueAddRequest()
      clone.CopyFrom(add_request)
      clone.clear_transaction()
      new_actions.append(clone)

    self.__tx_actions.extend(new_actions)

  def _Dynamic_Commit(self, transaction, transaction_response):
    self.__ValidateTransaction(transaction)

    self.__tx_snapshot = {}
    try:
      self.__WriteDatastore()

      for action in self.__tx_actions:
        try:
          apiproxy_stub_map.MakeSyncCall(
              'taskqueue', 'Add', action, api_base_pb.VoidProto())
        except apiproxy_errors.ApplicationError, e:
          logging.warning('Transactional task %s has been dropped, %s',
                          action, e)
          pass

    finally:
      self.__tx_actions = []
      self.__tx_lock.release()

  def _Dynamic_Rollback(self, transaction, transaction_response):
    self.__ValidateTransaction(transaction)

    self.__entities = self.__tx_snapshot
    self.__tx_snapshot = {}
    self.__tx_actions = []
    self.__tx_lock.release()

  def _Dynamic_GetSchema(self, req, schema):
    app_str = req.app()
    self.__ValidateAppId(app_str)

    namespace_str = req.name_space()
    app_namespace_str = datastore_types.EncodeAppIdNamespace(app_str,
                                                             namespace_str)
    kinds = []

    for app_namespace, kind in self.__entities:
      if (app_namespace != app_namespace_str or
          (req.has_start_kind() and kind < req.start_kind()) or
          (req.has_end_kind() and kind > req.end_kind())):
        continue

      app_kind = (app_namespace_str, kind)
      if app_kind in self.__schema_cache:
        kinds.append(self.__schema_cache[app_kind])
        continue

      kind_pb = entity_pb.EntityProto()
      kind_pb.mutable_key().set_app('')
      kind_pb.mutable_key().mutable_path().add_element().set_type(kind)
      kind_pb.mutable_entity_group()

      props = {}

      for entity in self.__entities[app_kind].values():
        for prop in entity.protobuf.property_list():
          if prop.name() not in props:
            props[prop.name()] = entity_pb.PropertyValue()
          props[prop.name()].MergeFrom(prop.value())

      for value_pb in props.values():
        if value_pb.has_int64value():
          value_pb.set_int64value(0)
        if value_pb.has_booleanvalue():
          value_pb.set_booleanvalue(False)
        if value_pb.has_stringvalue():
          value_pb.set_stringvalue('none')
        if value_pb.has_doublevalue():
          value_pb.set_doublevalue(0.0)
        if value_pb.has_pointvalue():
          value_pb.mutable_pointvalue().set_x(0.0)
          value_pb.mutable_pointvalue().set_y(0.0)
        if value_pb.has_uservalue():
          value_pb.mutable_uservalue().set_gaiaid(0)
          value_pb.mutable_uservalue().set_email('none')
          value_pb.mutable_uservalue().set_auth_domain('none')
          value_pb.mutable_uservalue().clear_nickname()
          value_pb.mutable_uservalue().clear_obfuscated_gaiaid()
        if value_pb.has_referencevalue():
          value_pb.clear_referencevalue()
          value_pb.mutable_referencevalue().set_app('none')
          pathelem = value_pb.mutable_referencevalue().add_pathelement()
          pathelem.set_type('none')
          pathelem.set_name('none')

      for name, value_pb in props.items():
        prop_pb = kind_pb.add_property()
        prop_pb.set_name(name)
        prop_pb.set_multiple(False)
        prop_pb.mutable_value().CopyFrom(value_pb)

      kinds.append(kind_pb)
      self.__schema_cache[app_kind] = kind_pb

    for kind_pb in kinds:
      kind = schema.add_kind()
      kind.CopyFrom(kind_pb)
      if not req.properties():
        kind.clear_property()

    schema.set_more_results(False)

  def _Dynamic_AllocateIds(self, allocate_ids_request, allocate_ids_response):
    model_key = allocate_ids_request.model_key()
    size = allocate_ids_request.size()

    self.__ValidateAppId(model_key.app())

    try:
      self.__id_lock.acquire()
      start = self.__next_id
      self.__next_id += size
      end = self.__next_id - 1
    finally:
     self.__id_lock.release()

    allocate_ids_response.set_start(start)
    allocate_ids_response.set_end(end)

  def _Dynamic_CreateIndex(self, index, id_response):
    self.__ValidateAppId(index.app_id())
    if index.id() != 0:
      raise apiproxy_errors.ApplicationError(datastore_pb.Error.BAD_REQUEST,
                                             'New index id must be 0.')
    elif self.__FindIndex(index):
      raise apiproxy_errors.ApplicationError(datastore_pb.Error.BAD_REQUEST,
                                             'Index already exists.')

    self.__index_id_lock.acquire()
    index.set_id(self.__next_index_id)
    id_response.set_value(self.__next_index_id)
    self.__next_index_id += 1
    self.__index_id_lock.release()

    clone = entity_pb.CompositeIndex()
    clone.CopyFrom(index)
    app = index.app_id()
    clone.set_app_id(app)

    self.__indexes_lock.acquire()
    try:
      if app not in self.__indexes:
        self.__indexes[app] = []
      self.__indexes[app].append(clone)
    finally:
      self.__indexes_lock.release()

  def _Dynamic_GetIndices(self, app_str, composite_indices):
    self.__ValidateAppId(app_str.value())
    composite_indices.index_list().extend(
      self.__indexes.get(app_str.value(), []))

  def _Dynamic_UpdateIndex(self, index, void):
    self.__ValidateAppId(index.app_id())
    stored_index = self.__FindIndex(index)
    if not stored_index:
      raise apiproxy_errors.ApplicationError(datastore_pb.Error.BAD_REQUEST,
                                             "Index doesn't exist.")
    elif (index.state() != stored_index.state() and
          index.state() not in self._INDEX_STATE_TRANSITIONS[stored_index.state()]):
      raise apiproxy_errors.ApplicationError(
        datastore_pb.Error.BAD_REQUEST,
        "cannot move index state from %s to %s" %
          (entity_pb.CompositeIndex.State_Name(stored_index.state()),
          (entity_pb.CompositeIndex.State_Name(index.state()))))

    self.__indexes_lock.acquire()
    try:
      stored_index.set_state(index.state())
    finally:
      self.__indexes_lock.release()

  def _Dynamic_DeleteIndex(self, index, void):
    self.__ValidateAppId(index.app_id())
    stored_index = self.__FindIndex(index)
    if not stored_index:
      raise apiproxy_errors.ApplicationError(datastore_pb.Error.BAD_REQUEST,
                                             "Index doesn't exist.")

    app = index.app_id()
    self.__indexes_lock.acquire()
    try:
      self.__indexes[app].remove(stored_index)
    finally:
      self.__indexes_lock.release()

  def __FindIndex(self, index):
    """Finds an existing index by definition.

    Args:
      definition: entity_pb.CompositeIndex

    Returns:
      entity_pb.CompositeIndex, if it exists; otherwise None
    """
    app = index.app_id()
    self.__ValidateAppId(app)
    if app in self.__indexes:
      for stored_index in self.__indexes[app]:
        if index.definition() == stored_index.definition():
          return stored_index

    return None
