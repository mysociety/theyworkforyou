# This Python file uses the following encoding: utf-8

"""
	Gaebar (Google App Engine Backup and Restore) Beta 1

	A Naklab™ production sponsored by the <head> web conference - http://headconference.com

	Copyright (c) 2009 Aral Balkan. http://aralbalkan.com

	Released under the GNU GPL v3 License. See license.txt for the full license or read it here:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html

"""

import logging
from django.conf.urls.defaults import *

from gaebar import views as gaebar_views

urlpatterns = patterns('',

	url(r'backup-start', view=gaebar_views.backup_start, name='backup-start'),
	url(r'backup-rows', view=gaebar_views.backup_rows, name='backup-rows'),
	url(r'download-remote', view=gaebar_views.backup_local_download_remote_backup, name='download-remote'),
	url(r'download-py/(?P<created_at>.*?)/(?P<secret>.*?)/$', view=gaebar_views.backup_download_py, name='download-py'),
	url(r'metadata/(?P<created_at>.*?)/(?P<secret>.*?)/', view=gaebar_views.backup_generate_metadata, name='metadata'),
	url(r'get-restore-info/', view=gaebar_views.get_restore_info, name='get-restore-info'),
	
	url(r'restore-row/', view=gaebar_views.backup_restore_row),
	
    url(r'', view=gaebar_views.index, name='index'),	
)
