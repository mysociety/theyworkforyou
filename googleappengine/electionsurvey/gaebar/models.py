# This Python file uses the following encoding: utf-8

"""
	Gaebar (Google App Engine Backup and Restore) Beta 1

	A Naklab™ production sponsored by the <head> web conference - http://headconference.com

	Copyright (c) 2009 Aral Balkan. http://aralbalkan.com

	Released under the GNU GPL v3 License. See license.txt for the full license or read it here:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html

"""

from google.appengine.ext import db
from django.db import models

# NOTE: The restore entity is currently unused.
class GaebarRestore(db.Model):
	created_at = db.DateTimeProperty(auto_now_add=True)	
	modified_at = db.DateTimeProperty(auto_now=True)

	backup_key = db.StringProperty()

	current_row = db.IntegerProperty(default=0)
	num_rows = db.IntegerProperty(default=0)
	complete = db.BooleanProperty(default=False)

	current_model = db.StringProperty()
	current_index = db.IntegerProperty()
	

class GaebarBackup(db.Model):
	created_at = db.DateTimeProperty(auto_now_add=True)	
	modified_at = db.DateTimeProperty(auto_now=True)
	
	# The server that is being backed up.
	server_name = db.StringProperty()
	server_url = db.StringProperty()
	
	ordered_model_list = db.ListProperty(unicode)
	models_remaining_to_back_up = db.ListProperty(unicode)
	current_model = db.StringProperty()
	current_index = db.IntegerProperty()
	
	num_rows = db.IntegerProperty(default=0)
	
	num_shards = db.IntegerProperty(default=1)
	
	shard_row_limits = db.ListProperty(int)
	
	complete = db.BooleanProperty(default=False)
	
	# The created_at of the last-downloaded shard. This is used during the backup process 
	# while the shards are being downloaded to the local machine. 
	# (We can't use offsets because of the 1,000 limit on offsets and we can't just return 
	# the last created_at since we're already returning the content of the code shard in the call. 
	# We could inject the key into the returned content but that's going to be messier than storing
	# the last created_at in the datastore.) Storing this sortable field in the datastore
	# also means that we can resume downloading shards in case something goes
	# wrong (connection loss, etc.) in the backup process.
	last_downloaded_shard_created_at = db.DateTimeProperty()
	
	# code = db.TextProperty(default='')
	

class GaebarCodeShard(db.Model):
	created_at = db.DateTimeProperty(auto_now_add=True)	
	modified_at = db.DateTimeProperty(auto_now=True)
	
	backup = db.ReferenceProperty(GaebarBackup, collection_name="code_shards")
	code = db.TextProperty(default='')
	active = db.BooleanProperty(default=True)
	
	# Meta data
	start_row = db.IntegerProperty()
	end_row = db.IntegerProperty()
	models = db.ListProperty(unicode)
	models_start_row = db.ListProperty(int)
	models_end_row = db.ListProperty(int)
