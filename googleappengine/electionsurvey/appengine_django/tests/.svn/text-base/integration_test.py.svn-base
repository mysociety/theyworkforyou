#!/usr/bin/python2.4
#
# Copyright 2008 Google Inc.
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

"""Tests that the core module functionality is present and functioning."""

import httplib
import logging
import os
import unittest
import sys
import threading

from django import http
from django import test
from django.test import client
from django.conf import settings

from google.appengine.tools import dev_appserver
from google.appengine.tools import dev_appserver_login

PORT = 8000
ROOT_PATH = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
APP_ID = 'google-app-engine-django'
LOGIN_URL = '/_ah/login'

# ########
# NOTE: All this stuff is expected to break with SDK updates
# TODO: get an interface for this into the SDK proper
# ########

def start_server(root_path=ROOT_PATH, port=PORT, app_id=APP_ID):
  dev_appserver.ApplicationLoggingHandler.InitializeTemplates(
      'HEADER', 'SCRIPT', 'MIDDLE', 'FOOTER')
  dev_appserver.SetupStubs(app_id,
                           login_url=LOGIN_URL,
                           datastore_path='/dev/null',
                           history_path='/dev/null',
                           blobstore_path='/dev/null',
                           clear_datastore=False)
  server = dev_appserver.CreateServer(ROOT_PATH,
                                      LOGIN_URL,
                                      port,
                                      '/unused/templates/path')

  server_thread = threading.Thread(target=server.serve_forever)
  server_thread.setDaemon(True)
  server_thread.start()
  return port

def RetrieveURL(method,
                host_port,
                relative_url,
                user_info=None,
                body=None,
                extra_headers=[]):
  """Access a URL over HTTP and returns the results.

  Args:
    method: HTTP method to use, e.g., GET, POST
    host_port: Tuple (hostname, port) of the host to contact.
    relative_url: Relative URL to access on the remote host.
    user_info: If not None, send this user_info tuple in an HTTP Cookie header
      along with the request; otherwise, no header is included. The user_info
      tuple should be in the form (email, admin) where:
        email: The user's email address.
        admin: True if the user should be an admin; False otherwise.
      If email is empty, it will be as if the user is not logged in.
    body: Request body to write to the remote server. Should only be used with
      the POST method any other methods that expect a message body.
    extra_headers: List of (key, value) tuples for headers to send on the
      request.

  Returns:
    Tuple (status, content, headers) where:
      status: HTTP status code returned by the remote host, e.g. 404, 200, 500
      content: Data returned by the remote host.
      headers: Dictionary mapping header names to header values (both strings).

    If an exception is raised while accessing the remote host, both status and
    content will be set to None.
  """
  url_host = '%s:%d' % host_port

  try:
    connection = httplib.HTTPConnection(url_host)

    try:
      connection.putrequest(method, relative_url)

      if user_info is not None:
        email, admin = user_info
        auth_string = '%s=%s' % (dev_appserver_login.COOKIE_NAME,
            dev_appserver_login.CreateCookieData(email, admin))
        connection.putheader('Cookie', auth_string)

      if body is not None:
        connection.putheader('Content-length', len(body))

      for key, value in extra_headers:
        connection.putheader(str(key), str(value))

      connection.endheaders()

      if body is not None:
        connection.send(body)

      response = connection.getresponse()
      status = response.status
      content = response.read()
      headers = dict(response.getheaders())

      return status, content, headers
    finally:
      connection.close()
  except (IOError, httplib.HTTPException, socket.error), e:
    logging.error('Encountered exception accessing HTTP server: %s', e)
    raise e

class AppEngineClientHandler(client.ClientHandler):
  def __init__(self, port):
    super(AppEngineClientHandler, self).__init__()
    self._port = port
    self._host = 'localhost'

  def __call__(self, environ):
    method = environ['REQUEST_METHOD']
    host_port = (self._host, self._port)
    relative_url = environ['PATH_INFO']
    if environ['QUERY_STRING']:
      relative_url += '?%s' % environ['QUERY_STRING']
    body = environ['wsgi.input'].read(environ.get('CONTENT_LENGTH', 0))
    headers = [] # Not yet supported
    status, content, headers = RetrieveURL(method,
                                           host_port,
                                           relative_url,
                                           body = body,
                                           extra_headers = headers)
    response = http.HttpResponse(content = content,
                                 status = status)
    for header, value in headers.iteritems():
      response[header] = value

    return response


class AppEngineClient(client.Client):
  def __init__(self, port, *args, **kw):
    super(AppEngineClient, self).__init__(*args, **kw)
    self.handler = AppEngineClientHandler(port=port)


class IntegrationTest(test.TestCase):
  """Tests that we can make a request."""

  def setUp(self):
    port = start_server()
    self.gae_client = AppEngineClient(port=port)


  def testBasic(self):
    """a request to the default page works in the dev_appserver"""
    rv = self.gae_client.get('/')
    self.assertEquals(rv.status_code, 200)
