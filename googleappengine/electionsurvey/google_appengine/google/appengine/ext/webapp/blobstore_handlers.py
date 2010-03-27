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

"""Handler library for Blobstore API.

Contains handlers to help with uploading and downloading blobs.

  BlobstoreDownloadHandler: Has helper method for easily sending blobs
    to client.
  BlobstoreUploadHandler: Handler for receiving upload notification requests.
"""




import cgi

from google.appengine.ext import blobstore
from google.appengine.ext import webapp


_CONTENT_DISPOSITION_FORMAT = 'attachment; filename="%s"'

class BlobstoreDownloadHandler(webapp.RequestHandler):
  """Base class for creating handlers that may send blobs to users."""

  def send_blob(self, blob_key_or_info, content_type=None, save_as=None):
    """Send a blob-response based on a blob_key.

    Sets the correct response header for serving a blob.  If BlobInfo
    is provided and no content_type specified, will set request content type
    to BlobInfo's content type.

    Args:
      blob_key_or_info: BlobKey or BlobInfo record to serve.
      content_type: Content-type to override when known.
      save_as: If True, and BlobInfo record is provided, use BlobInfos
        filename to save-as.  If string is provided, use string as filename.
        If None or False, do not send as attachment.

      Raises:
        ValueError on invalid save_as parameter.
    """
    if isinstance(blob_key_or_info, blobstore.BlobInfo):
      blob_key = blob_key_or_info.key()
      blob_info = blob_key_or_info
    else:
      blob_key = blob_key_or_info
      blob_info = None

    self.response.headers[blobstore.BLOB_KEY_HEADER] = str(blob_key)

    if content_type:
      if isinstance(content_type, unicode):
        content_type = content_type.encode('utf-8')
      self.response.headers['Content-Type'] = content_type
    else:
      del self.response.headers['Content-Type']

    def send_attachment(filename):
      if isinstance(filename, unicode):
        filename = filename.encode('utf-8')
      self.response.headers['Content-Disposition'] = (
          _CONTENT_DISPOSITION_FORMAT % filename)

    if save_as:
      if isinstance(save_as, basestring):
        send_attachment(save_as)
      elif blob_info and save_as is True:
        send_attachment(blob_info.filename)
      else:
        if not blob_info:
          raise ValueError('Expected BlobInfo value for blob_key_or_info.')
        else:
          raise ValueError('Unexpected value for save_as')

    self.response.clear()


class BlobstoreUploadHandler(webapp.RequestHandler):
  """Base class for creation blob upload handlers."""

  def __init__(self):
    super(BlobstoreUploadHandler, self).__init__()
    self.__uploads = None

  def get_uploads(self, field_name=None):
    """Get uploads sent to this handler.

    Args:
      field_name: Only select uploads that were sent as a specific field.

    Returns:
      A list of BlobInfo records corresponding to each upload.
      Empty list if there are no blob-info records for field_name.
    """
    if self.__uploads is None:
      self.__uploads = {}
      for key, value in self.request.params.items():
        if isinstance(value, cgi.FieldStorage):
          if 'blob-key' in value.type_options:
            self.__uploads.setdefault(key, []).append(
                blobstore.parse_blob_info(value))

    if field_name:
      try:
        return list(self.__uploads[field_name])
      except KeyError:
        return []
    else:
      results = []
      for uploads in self.__uploads.itervalues():
        results += uploads
      return results
