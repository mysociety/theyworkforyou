#!/bin/bash

export DJANGO_SETTINGS_MODULE=settings 
export PYTHONPATH=.
./google_appengine/remote_api_shell.py theyworkforyouelection /remote_api

