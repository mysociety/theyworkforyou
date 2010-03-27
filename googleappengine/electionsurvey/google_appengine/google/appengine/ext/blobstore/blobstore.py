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

"""A Python blobstore API used by app developers.

Contains methods uses to interface with Blobstore API.  Includes db.Model-like
class representing a reference to a very large BLOB.  Imports db.Key-like
class representing a blob-key.
"""



import cgi
import email

from google.appengine.api import blobstore
from google.appengine.api import datastore
from google.appengine.api import datastore_errors
from google.appengine.api import datastore_types
from google.appengine.ext import db

__all__ = ['BLOB_INFO_KIND',
           'BLOB_KEY_HEADER',
           'BlobFetchSizeTooLargeError',
           'BlobInfo',
           'BlobInfoParseError',
           'BlobKey',
           'BlobNotFoundError',
           'BlobReferenceProperty',
           'CreationFormatError',
           'DataIndexOutOfRangeError',
           'Error',
           'InternalError',
           'MAX_BLOB_FETCH_SIZE',
           'UPLOAD_INFO_CREATION_HEADER',
           'create_upload_url',
           'delete',
           'fetch_data',
           'get',
           'parse_blob_info']

Error = blobstore.Error
InternalError = blobstore.InternalError
BlobFetchSizeTooLargeError = blobstore.BlobFetchSizeTooLargeError
BlobNotFoundError = blobstore.BlobNotFoundError
CreationFormatError = blobstore.CreationFormatError
DataIndexOutOfRangeError = blobstore.DataIndexOutOfRangeError

BlobKey = blobstore.BlobKey
create_upload_url = blobstore.create_upload_url
delete = blobstore.delete


class BlobInfoParseError(Error):
  """CGI parameter does not contain valid BlobInfo record."""


BLOB_INFO_KIND = blobstore.BLOB_INFO_KIND
BLOB_KEY_HEADER = blobstore.BLOB_KEY_HEADER
MAX_BLOB_FETCH_SIZE = blobstore.MAX_BLOB_FETCH_SIZE
UPLOAD_INFO_CREATION_HEADER = blobstore.UPLOAD_INFO_CREATION_HEADER


class _GqlQuery(db.GqlQuery):
  """GqlQuery class that explicitly sets model-class.

  This does the same as the original db.GqlQuery class except that it does
  not try to find the model class based on the compiled GQL query.  The
  caller instead provides the query with a model class to use for construction.

  This class is required for compatibility with the current db.py query
  mechanism but will be removed in the future.  DO NOT USE.
  """

  def __init__(self, query_string, model_class, *args, **kwds):
    """Constructor.

    Args:
      query_string: Properly formatted GQL query string.
      model_class: Model class from which entities are constructed.
      *args: Positional arguments used to bind numeric references in the query.
      **kwds: Dictionary-based arguments for named references.
    """
    from google.appengine.ext import gql
    app = kwds.pop('_app', None)
    self._proto_query = gql.GQL(query_string, _app=app)
    super(db.GqlQuery, self).__init__(model_class)
    self.bind(*args, **kwds)


class BlobInfo(object):
  """Information about blobs in Blobstore.

  This is a db.Model-like class that contains information about blobs stored
  by an application.  Like db.Model, this class is backed by an Datastore
  entity, however, BlobInfo instances are read-only and have a much more
  limited interface.

  Each BlobInfo has a key of type BlobKey associated with it. This key is
  specific to the Blobstore API and is not compatible with db.get.  The key
  can be used for quick lookup by passing it to BlobInfo.get.  This
  key converts easily to a string, which is web safe and can be embedded
  in URLs.

  Properties:
    content_type: Content type of blob.
    creation: Creation date of blob, when it was uploaded.
    filename: Filename user selected from their machine.
    size: Size of uncompressed blob.

  All properties are read-only.  Attempting to assign a value to a property
  will raise NotImplementedError.
  """

  _unindexed_properties = frozenset()

  @property
  def content_type(self):
    return self.__get_value('content_type')

  @property
  def creation(self):
    return self.__get_value('creation')

  @property
  def filename(self):
    return self.__get_value('filename')

  @property
  def size(self):
    return self.__get_value('size')

  def __init__(self, entity_or_blob_key, _values=None):
    """Constructor for wrapping blobstore entity.

    The constructor should not be used outside this package and tests.

    Args:
      entity: Datastore entity that represents the blob reference.
    """
    if isinstance(entity_or_blob_key, datastore.Entity):
      self.__entity = entity_or_blob_key
      self.__key = BlobKey(entity_or_blob_key.key().name())
    elif isinstance(entity_or_blob_key, BlobKey):
      self.__entity = _values
      self.__key = entity_or_blob_key
    else:
      TypeError('Must provide Entity or BlobKey')

  @classmethod
  def from_entity(cls, entity):
    """Convert entity to BlobInfo.

    This method is required for compatibility with the current db.py query
    mechanism but will be removed in the future.  DO NOT USE.
    """
    return BlobInfo(entity)

  @classmethod
  def properties(cls):
    """Set of properties that belong to BlobInfo.

    This method is required for compatibility with the current db.py query
    mechanism but will be removed in the future.  DO NOT USE.
    """
    return set(('content_type', 'creation', 'filename', 'size'))

  def __get_value(self, name):
    """Get a BlobInfo value, loading entity if necessary.

    This method allows lazy loading of the underlying datastore entity.  It
    should never be invoked directly.

    Args:
      name: Name of property to get value for.

    Returns:
      Value of BlobInfo property from entity.
    """
    if self.__entity is None:
      self.__entity = datastore.Get(
          datastore_types.Key.from_path(self.kind(), str(self.__key)))
    try:
      return self.__entity[name]
    except KeyError:
      raise AttributeError(name)


  def key(self):
    """Get key for blob.

    Returns:
      BlobKey instance that identifies this blob.
    """
    return self.__key

  def delete(self):
    """Permanently delete blob from Blobstore."""
    delete(self.key())

  @classmethod
  def get(cls, blob_keys):
    """Retrieve BlobInfo by key or list of keys.

    Args:
      blob_keys: A key or a list of keys.  Keys may be instances of str,
      unicode and BlobKey.

    Returns:
      A BlobInfo instance associated with provided key or a list of BlobInfo
      instances if a list of keys was provided.  Keys that are not found in
      Blobstore return None as their values.
    """
    blob_keys = cls.__normalize_and_convert_keys(blob_keys)
    try:
      entities = datastore.Get(blob_keys)
    except datastore_errors.EntityNotFoundError:
      return None
    if isinstance(entities, datastore.Entity):
      return BlobInfo(entities)
    else:
      references = []
      for entity in entities:
        if entity is not None:
          references.append(BlobInfo(entity))
        else:
          references.append(None)
      return references

  @classmethod
  def all(cls):
    """Get query for all Blobs associated with application.

    Returns:
      A db.Query object querying over BlobInfo's datastore kind.
    """
    return db.Query(cls)

  @classmethod
  def __factory_for_kind(cls, kind):
    if kind == BLOB_INFO_KIND:
      return BlobInfo
    raise ValueError('Cannot query for kind %s' % kind)

  @classmethod
  def gql(cls, query_string, *args, **kwds):
    """Returns a query using GQL query string.

    See appengine/ext/gql for more information about GQL.

    Args:
      query_string: Properly formatted GQL query string with the
        'SELECT * FROM <entity>' part omitted
      *args: rest of the positional arguments used to bind numeric references
        in the query.
      **kwds: dictionary-based arguments (for named parameters).

    Returns:
      A gql.GqlQuery object querying over BlobInfo's datastore kind.
    """
    return _GqlQuery('SELECT * FROM %s %s'
                       % (cls.kind(), query_string),
                     cls,
                     *args,
                     **kwds)

  @classmethod
  def kind(self):
    """Get the entity kind for the BlobInfo.

    This method is required for compatibility with the current db.py query
    mechanism but will be removed in the future.  DO NOT USE.
    """
    return BLOB_INFO_KIND

  @classmethod
  def __normalize_and_convert_keys(cls, keys):
    """Normalize and convert all keys to BlobKey type.

    This method is based on datastore.NormalizeAndTypeCheck().

    Args:
      keys: A single key or a list/tuple of keys.  Keys may be a string
        or BlobKey

    Returns:
      Single key or list with all strings replaced by BlobKey instances.
    """
    if isinstance(keys, (list, tuple)):
      multiple = True
      keys = list(keys)
    else:
      multiple = False
      keys = [keys]

    for index, key in enumerate(keys):
      if not isinstance(key, (basestring, BlobKey)):
        raise datastore_errors.BadArgumentError(
            'Expected str or BlobKey; received %s (a %s)' % (
                key,
                datastore.typename(key)))
      keys[index] = datastore.Key.from_path(cls.kind(), str(key))

    if multiple:
      return keys
    else:
      return keys[0]


def get(blob_key):
  """Get a BlobInfo record from blobstore.

  Does the same as BlobInfo.get.
  """
  return BlobInfo.get(blob_key)


def parse_blob_info(field_storage):
  """Parse a BlobInfo record from file upload field_storage.

  Args:
    field_storage: cgi.FieldStorage that represents uploaded blob.

  Returns:
    BlobInfo record as parsed from the field-storage instance.
    None if there was no field_storage.

  Raises:
    BlobInfoParseError when provided field_storage does not contain enough
    information to construct a BlobInfo object.
  """
  if field_storage is None:
    return None

  field_name = field_storage.name

  def get_value(dict, name):
    value = dict.get(name, None)
    if value is None:
      raise BlobInfoParseError(
          'Field %s has no %s.' % (field_name, name))
    return value

  filename = get_value(field_storage.disposition_options, 'filename')
  blob_key = BlobKey(get_value(field_storage.type_options, 'blob-key'))

  upload_content = email.message_from_file(field_storage.file)
  content_type = get_value(upload_content, 'content-type')
  size = get_value(upload_content, 'content-length')
  creation_string = get_value(upload_content, UPLOAD_INFO_CREATION_HEADER)

  try:
    size = int(size)
  except (TypeError, ValueError):
    raise BlobInfoParseError(
        '%s is not a valid value for %s size.' % (size, field_name))

  try:
    creation = blobstore.parse_creation(creation_string)
  except CreationFormatError, e:
    raise BlobInfoParseError('Could not parse creation for %s: %s' % (
        field_name, str(e)))

  return BlobInfo(blob_key,
                  {'content_type': content_type,
                   'creation': creation,
                   'filename': filename,
                   'size': size,
                   })


class BlobReferenceProperty(db.Property):
  """Property compatible with db.Model classes.

  Add references to blobs to domain models using BlobReferenceProperty:

    class Picture(db.Model):
      title = db.StringProperty()
      image = blobstore.BlobReferenceProperty()
      thumbnail = blobstore.BlobReferenceProperty()

  To find the size of a picture using this model:

    picture = Picture.get(picture_key)
    print picture.image.size

  BlobInfo objects are lazily loaded so iterating over models with
  for BlobKeys is efficient, the following does not need to hit
  Datastore for each image key:

    list_of_untitled_blobs = []
    for picture in Picture.gql("WHERE title=''"):
      list_of_untitled_blobs.append(picture.image.key())
  """

  data_type = BlobInfo

  def get_value_for_datastore(self, model_instance):
    """Translate model property to datastore value."""
    blob_info = getattr(model_instance, self.name)
    if blob_info is None:
      return None
    return blob_info.key()

  def make_value_from_datastore(self, value):
    """Translate datastore value to BlobInfo."""
    if value is None:
      return None
    return BlobInfo(value)

  def validate(self, value):
    """Validate that assigned value is BlobInfo.

    Automatically converts from strings and BlobKey instances.
    """
    if isinstance(value, (basestring)):
      value = BlobInfo(BlobKey(value))
    elif isinstance(value, BlobKey):
      value = BlobInfo(value)
    return super(BlobReferenceProperty, self).validate(value)


def fetch_data(blob, start_index, end_index):
  """Fetch data for blob.

  Fetches a fragment of a blob up to MAX_BLOB_FETCH_SIZE in length.  Attempting
  to fetch a fragment that extends beyond the boundaries of the blob will return
  the amount of data from start_index until the end of the blob, which will be
  a smaller size than requested.  Requesting a fragment which is entirely
  outside the boundaries of the blob will return empty string.  Attempting
  to fetch a negative index will raise an exception.

  Args:
    blob: BlobInfo, BlobKey, str or unicode representation of BlobKey of
      blob to fetch data from.
    start_index: Start index of blob data to fetch.  May not be negative.
    end_index: End index (exclusive) of blob data to fetch.  Must be
      >= start_index.

  Returns:
    str containing partial data of blob.  If the indexes are legal but outside
    the boundaries of the blob, will return empty string.

  Raises:
    TypeError if start_index or end_index are not indexes.  Also when blob
      is not a string, BlobKey or BlobInfo.
    DataIndexOutOfRangeError when start_index < 0 or end_index < start_index.
    BlobFetchSizeTooLargeError when request blob fragment is larger than
      MAX_BLOB_FETCH_SIZE.
    BlobNotFoundError when blob does not exist.
  """
  if isinstance(blob, BlobInfo):
    blob = blob.key()
  return blobstore.fetch_data(blob, start_index, end_index)
