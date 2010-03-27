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

"""Blobstore support classes.

Classes:

  DownloadRewriter:
    Rewriter responsible for transforming an application response to one
    that serves a blob to the user.

  CreateUploadDispatcher:
    Creates a dispatcher that is added to dispatcher chain.  Handles uploads
    by storing blobs rewriting requests and returning a redirect.
"""



import cgi
import cStringIO
import logging
import mimetools
import re

from google.appengine.api import apiproxy_stub_map
from google.appengine.api import blobstore
from google.appengine.api import datastore
from google.appengine.api import datastore_errors
from google.appengine.tools import dev_appserver_upload


UPLOAD_URL_PATH = '_ah/upload/'

UPLOAD_URL_PATTERN = '/%s(.*)' % UPLOAD_URL_PATH


def GetBlobStorage():
  """Get blob-storage from api-proxy stub map.

  Returns:
    BlobStorage instance as registered with blobstore API in stub map.
  """
  return apiproxy_stub_map.apiproxy.GetStub('blobstore').storage


def DownloadRewriter(response):
  """Intercepts blob download key and rewrites response with large download.

  Checks for the X-AppEngine-BlobKey header in the response.  If found, it will
  discard the body of the request and replace it with the blob content
  indicated.

  If a valid blob is not found, it will send a 404 to the client.

  If the application itself provides a content-type header, it will override
  the content-type stored in the action blob.

  Args:
  response: Response object to be rewritten.
  """
  blob_key = response.headers.getheader(blobstore.BLOB_KEY_HEADER)
  if blob_key:
    del response.headers[blobstore.BLOB_KEY_HEADER]

    try:
      blob_info = datastore.Get(
          datastore.Key.from_path(blobstore.BLOB_INFO_KIND, blob_key))

      response.body = GetBlobStorage().OpenBlob(blob_key)
      response.headers['Content-Length'] = str(blob_info['size'])
      if not response.headers.getheader('Content-Type'):
        response.headers['Content-Type'] = blob_info['content_type']
      response.large_response = True

    except datastore_errors.EntityNotFoundError:
      response.status_code = 500
      response.status_message = 'Internal Error'
      response.body = cStringIO.StringIO()

      if response.headers.getheader('status'):
        del response.headers['status']
      if response.headers.getheader('location'):
        del response.headers['location']
      if response.headers.getheader('content-type'):
        del response.headers['content-type']

      logging.error('Could not find blob with key %s.', blob_key)


def CreateUploadDispatcher(get_blob_storage=GetBlobStorage):
  """Function to create upload dispatcher.

  Returns:
    New dispatcher capable of handling large blob uploads.
  """
  from google.appengine.tools import dev_appserver

  class UploadDispatcher(dev_appserver.URLDispatcher):
    """Dispatcher that handles uploads."""

    def __init__(self):
      """Constructor.

      Args:
        blob_storage: A BlobStorage instance.
      """
      self.__cgi_handler = dev_appserver_upload.UploadCGIHandler(
          get_blob_storage())

    def Dispatch(self,
                 request,
                 outfile,
                 base_env_dict=None):
      """Handle post dispatch.

      This dispatcher will handle all uploaded files in the POST request, store
      the results in the blob-storage, close the upload session and transform
      the original request in to one where the uploaded files have external
      bodies.

      Returns:
        New AppServerRequest indicating request forward to upload success
        handler.
      """
      if base_env_dict['REQUEST_METHOD'] != 'POST':
        outfile.write('Status: 400\n\n')
        return

      upload_key = re.match(UPLOAD_URL_PATTERN, request.relative_url).group(1)
      try:
        upload_session = datastore.Get(upload_key)
      except datastore_errors.EntityNotFoundError:
        upload_session = None

      if upload_session:
        success_path = upload_session['success_path']

        upload_form = cgi.FieldStorage(fp=request.infile,
                                       headers=request.headers,
                                       environ=base_env_dict)

        try:
          mime_message_string = self.__cgi_handler.GenerateMIMEMessageString(
              upload_form)
          datastore.Delete(upload_session)
          self.current_session = upload_session

          header_end = mime_message_string.find('\n\n') + 1
          content_start = header_end + 1
          header_text = mime_message_string[:header_end]
          content_text = mime_message_string[content_start:]

          complete_headers = ('%s'
                              'Content-Length: %d\n'
                              '\n') % (header_text, len(content_text))

          return dev_appserver.AppServerRequest(
              success_path,
              None,
              mimetools.Message(cStringIO.StringIO(complete_headers)),
              cStringIO.StringIO(content_text),
              force_admin=True)
        except dev_appserver_upload.InvalidMIMETypeFormatError:
          outfile.write('Status: 400\n\n')
      else:
        logging.error('Could not find session for %s', upload_key)
        outfile.write('Status: 404\n\n')

    def EndRedirect(self, redirected_outfile, original_outfile):
      """Handle the end of upload complete notification.

      Makes sure the application upload handler returned an appropriate status
      code.
      """
      response = dev_appserver.RewriteResponse(redirected_outfile)
      logging.info('Upload handler returned %d', response.status_code)

      if (response.status_code in (301, 302, 303) and
          (not response.body or len(response.body.read()) == 0)):
        contentless_outfile = cStringIO.StringIO()
        contentless_outfile.write('Status: %s\n' % response.status_code)
        contentless_outfile.write(''.join(response.headers.headers))
        contentless_outfile.seek(0)
        dev_appserver.URLDispatcher.EndRedirect(self,
                                                contentless_outfile,
                                                original_outfile)
      else:
        logging.error(
            'Invalid upload handler response. Only 301, 302 and 303 '
            'statuses are permitted and it may not have a content body.')
        original_outfile.write('Status: 500\n\n')

  return UploadDispatcher()
