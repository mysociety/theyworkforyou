# This Python file uses the following encoding: utf-8

"""
	Gaebar (Google App Engine Backup and Restore) Beta 1

	A Naklab™ production sponsored by the <head> web conference - http://headconference.com

	Copyright (c) 2009 Aral Balkan. http://aralbalkan.com

	Released under the GNU GPL v3 License. See license.txt for the full license or read it here:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html

"""

import os
import sys
import re
import pickle
import time
import datetime
import functools
import logging
import traceback

from django import http
from django.shortcuts import render_to_response
from django.conf import settings
from django.utils.http import urlquote
from django.utils import simplejson

from django.template import loader

from django.http import HttpResponse
from django.http import HttpResponseNotFound
from django.http import HttpResponseForbidden
from django.http import HttpResponseServerError
from django.http import HttpResponseRedirect
from django.http import Http404

from google.appengine.api import memcache
from google.appengine.ext import db
from google.appengine.api.datastore_errors import BadKeyError
from google.appengine.api import urlfetch
from google.appengine.api import users

# According to Guido in Rietveld, 
# DeadlineExceededError can live in two different places.
# TODO - Aral: Has this been fixed yet?
try:
  # When deployed
  from google.appengine.runtime import DeadlineExceededError
  from google.appengine.runtime import OverQuotaError
  from google.appengine.runtime import RequestTooLargeError
  from google.appengine.runtime import CapabilityDisabledError

except ImportError:

  # In the development server
  from google.appengine.runtime.apiproxy_errors import DeadlineExceededError
  from google.appengine.runtime.apiproxy_errors import OverQuotaError
  from google.appengine.runtime.apiproxy_errors import RequestTooLargeError
  from google.appengine.runtime.apiproxy_errors import CapabilityDisabledError


# This is the Gaebar apps models not the user's app's models.
from gaebar import models

# Load the all the model classes specified by the user
# for their app in the settings file.
for modelstuple in settings.GAEBAR_MODELS:
	model_package = modelstuple[0]
	model_classes = modelstuple[1]
	for model_class in model_classes:
		__import__(model_package, globals(), locals(), model_classes)



######################################################################
#
#	Constants and other globals
#
######################################################################

"""
	We limit shards to 300KB to be on the safe side 
	(far enough away from the 1MB limit in the datastore). 
	If you are storing large blobs in your entities, you may need to lower this.
	(The dev size is kept low so we can see shards in action without 
	populating the local datastore with too many entries.)
"""
#MAX_SHARD_SIZE = 2000 if IS_DEV else 300000
MAX_SHARD_SIZE = 300000

"""
	Number of rows to backup on each iteration of the backup process.
	5 appears to be the number at which we do not tax the deployment environment
	(we don't get any short term Over Quota errors when running this 
	with over 2,000 rows of data to backup over a period of several minutes.)
"""
#ROWS_TO_BACKUP_ON_EACH_ITERATION = 5
ROWS_TO_BACKUP_ON_EACH_ITERATION = 5

"""
	Regular expressions used by the backup and restore process (compile once)

	Used to find numeric IDs in old keys while updating old keys to new
	ones in the generated source code during the backup process.
"""
backup_update_key = r'(\d+?)(?:L)?,'	
backup_update_key_rc = re.compile(backup_update_key)

"""	
	Matches string representations of timestamps.
"""
timestamp_regexp = r'^(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)\.(\d*?)$'
timestamp_regexp_compiled = re.compile(timestamp_regexp)

"""
	App.yaml app name regexp
"""
# Note: Not searching for ^application since the default app.yaml that comes
# in appenginepatch has a few invisible characters before the application and fails
# the match.
# app_name_from_app_yaml_regexp = r'application: (.*?)\n'
# app_name_from_app_yaml_regexp_compiled = re.compile(app_name_from_app_yaml_regexp)


"""
	Other globals
"""

# Are we running on the local development server?
IS_DEV = False
if 'SERVER_SOFTWARE' in os.environ:
	IS_DEV = os.environ['SERVER_SOFTWARE'].startswith('Dev')

# Get the application name. Many thanks to yejun for showing me  
# how to get the application id on the App Engine Google Group.
application_name = os.environ.get('APPLICATION_ID') 

# logging.info('Application name: ')
# logging.info(application_name)


######################################################################
#
#	Decorators
#
######################################################################

def authorize(target_method):
	"""Authorization decorator"""
	
	def autorization_decorator(request, *args, **kwargs): 
		
		# Current user information.		
		user = users.get_current_user()

		# Is user logged in?
		if user == None:
			return HttpResponseForbidden('You must <a href="%s">sign in</a> to access this feature.' % users.create_login_url('/gaebar/'))
			
		# Is user admin?
		if not users.is_current_user_admin():
			return HttpResponseForbidden('You must be an administrator to access this feature. Click here to <a href="%s">sign out</a>.' % users.create_logout_url('/gaebar/'))
			
		# User is admin; authorization OK:
		# Try and call the requested view method. Very simple error handling.
		try:
			# For testing quota errors, enable the following line:
			# raise OverQuotaError
			return target_method(request, *args, **kwargs) 

		except DeadlineExceededError:
			logging.exception('DeadlineExceededError')
			return HttpResponseServerError('Deadline exceeded.')

		except OverQuotaError:
			logging.exception('OverQuotaError')
			return HttpResponseServerError('Over quota error.')

		except CapabilityDisabledError:
			logging.exception('CapabilityDisabledError')
			return HttpResponseServerError('Capability disabled error.')
					
		except RequestTooLargeError:
			logging.exception('RequestTooLargeError')
			return HttpResponseServerError('Request too large error.')
					
		except MemoryError:
			logging.exception('MemoryError')
			return HttpResponseServerError('Memory error.')
			
		except AssertionError:
			logging.exception('AssertionError')
			return HttpResponseServerError('Assetion error.')
			

	functools.update_wrapper(autorization_decorator, target_method) 

	return autorization_decorator



######################################################################
#
#	View methods
#
######################################################################

@authorize 
def index(request):
	
	"""List backups available for restore."""
		
	# Get the list of backups avaiable
	
	context={}
	
	if 'complete' in request.REQUEST:
		context['complete'] = True
		
	# Default backups folder -- will work everywhere except locally
	# on app-engine-patch projects (again, because the working folder is
	# common/appenginepatch/ instead of the app root.)
	backups_folder = 'gaebar/backups/'
	
	# App engine patch?
	if not os.path.exists('gaebar'):
		# Yep
		backups_folder = '../../gaebar/backups/'

	# logging.info('**** Backups folder is ' + backups_folder)

	current_host = request.get_host()

	# Make sure that the current host isn't the local server (so that user doesn't
	# accidentally backup the local server).
	if current_host in settings.GAEBAR_LOCAL_URL:
		context['running_on_local_server'] = True
		context['current_host'] = "local development"

	else:
		# Check if the host is in the settings file, and, if so, use the nice name for it	
		servers = settings.GAEBAR_SERVERS
		for server in servers:
			url = servers[server]
			if current_host in url:
				current_host = server	
	
	
		context['current_host'] = current_host
	
	
	folder_names = [x for x,y,z in os.walk(backups_folder)]
	folders = [z for x,y,z in os.walk(backups_folder)]

	folder_info = []
	if (len(folders) > 1):

		for i in range(len(folders)):
			if i == 0: continue
			num_files = len(folders[i])
			try:
				folder_name_index = folder_names[i].index('backup_')
			except ValueError:
				continue # It's some other folder, not a backup

			folder_name = folder_names[i][folder_name_index:]
		
			# TODO: We need to store host information for backups with the backup
			# so we know where to go to re-download stuff if any fail.
			metadata_url = request.get_host() + '/gaebar/metadata/' + urlquote(make_timestamp_from_safe_file_name(folder_name)) + '/' + urlquote(settings.SECRET_KEY) + '/'
		
			# e.g., details = ['backup', '2008', '09', '21', 'at', '10', '15', '47', '931862']
			details = folder_name.split('_')
			details_object = dict(year=details[1], month=details[2], day=details[3], hour=details[5], minutes=details[6], seconds=details[7], ms=details[8])
		
			pretty_name = details_object['year'] + '/' + details_object['month'] + '/' + details_object['day'] + ' at ' + details_object['hour'] + ':' + details_object['minutes'] + ':' + details_object['seconds']
		
			info_object = dict(name=folder_name, num_files=num_files, metadata_url=metadata_url, details=details_object, pretty_name=pretty_name)
			folder_info.append(info_object)
	
		folder_info.reverse()
	else:
		# No backups
		context['no_backups'] = True
		
	context['folder_info'] = folder_info	
	
	# Servers
	servers = settings.GAEBAR_SERVERS
	keys = servers.keys()
	keys.sort()	
	servers_dict = [dict(name=key, url=servers[key]) for key in keys]
	context['servers'] = servers_dict
	
	server_str = 'local development' if current_host in settings.GAEBAR_LOCAL_URL else current_host
	context['title'] = " Google App Engine Backup and Restore running on " + server_str + " server."
	
	return render_to_response('gaebar/index.html', context)
	
	


@authorize 
def backup_start(request):
	"""
	Starts a new datastore backup.

	"""
	
	current_host = request.get_host()

	server_name = ''
	server_url = ''

	# Make sure that the current host isn't the local server (so that user doesn't
	# accidentally backup the local server).
	if current_host in settings.GAEBAR_LOCAL_URL:
		error_message='Backup start called on local dev server - cannot backup the local server.'
		logging.error(error_message)
		result_dict = dict(error=True, error_message=error_message)
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 
		
	else:
		# Check if the host is in the settings file, and, if so, use the nice name for it	
		servers = settings.GAEBAR_SERVERS
		for server in servers:
			url = servers[server]
			if current_host in url:
				server_name = server
				server_url = url		

	# If the user has not setup the server in settings, use the host name.
	if server_name == '':
		server_name = 'Unknown server: ' + current_host 

	if server_url == '':
		server_url = 'http://' + current_host
		 
	logging.info('Gaebar backup started: ')
	logging.info('Server name: ' + server_name)
	logging.info('Server url: ' + server_url)	
				
	# Make sure no code shards were left over from a previous backup
	# (This can happen if the backup failed with an error.)
	active_code_shards = models.GaebarCodeShard.all().filter('active = ', True).fetch(100)
	for active_code_shard in active_code_shards:
		active_code_shard.active = False
		active_code_shard.put()
	
	backup = models.GaebarBackup()
	
	# Save the server details for the server we're backing up.
	backup.server_name = server_name
	backup.server_url = server_url

	# Current solution: Manual list of models stored in the settings file. 
	# Dynamically generating this list is possible but you also have to keep the models in 
	# source-order to maintain reference-order when restoring.
	models_ordered_list = []
	for models_tuple in settings.GAEBAR_MODELS:
		model_classes = models_tuple[1]
		for model_class in model_classes:
			models_ordered_list.append(model_class)
		
	# logging.info(models_ordered_list)
	
	current_model = models_ordered_list[0]
			
	backup.models_remaining_to_back_up = models_ordered_list
	backup.ordered_model_list = models_ordered_list
	backup.current_model = current_model
	backup.current_index = 0
	
	backup.put()

	# Create the first code shard (since the backup code may be 
	# greater than 1MB in size, we need to break it up into 
	# several entity instances in the datastore.)
	code_shard = models.GaebarCodeShard()
	code_shard.backup = backup
	code = code_shard.code
		
	#code = u'"""\n\tDatastore backup for %s\n\tStarted on: %s.\n"""\n\n' % (server_name, get_date_string())
	code = u'# coding: utf8\n"""\n\tDatastore backup for %s\n\tStarted on: %s.\n"""\n\n' % (server_name, get_date_string())
	code = add_code_shard_imports(code)

	code_shard.code = code
	code_shard.put()

	context=dict(last_key=0, new_backup=True, created_at=str(backup.created_at), all_models=backup.ordered_model_list)
	
	return backup_model(backup, context)



@authorize 
def backup_rows(request):
	"""
	Backs up a number of rows from the datastore.
	
	All return values are JSON-encoded HttpResponse[...] instances.
	
	"""
	
	# To test the 500 error resiliance, 
	# return HttpResponseServerError('Muhahaha!')
	
	context=dict()
	
	backup_key = ''
	if 'backup_key' in request.REQUEST:
		backup_key = request.REQUEST['backup_key']
	else:
		result_dict = dict(error=True, error_message='Backup key not provided in call to backup rows.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 

	if 'last_key' in request.REQUEST:
		last_key = request.REQUEST['last_key']
	else:
		result_dict = dict(error=True, error_message='Last key not provided in call to backup rows.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 

	try:
		backup = models.GaebarBackup.get(backup_key)
	except BadKeyError:
		logging.error('Error: backup with key backup_key not found in call to backup rows.')
		result_dict = dict(error=True, error_message='Backup with key backup_key not found in call to backup rows.')
		result_json = simplejson.dumps(result_dict)		
		return HttpResponseNotFound(result_json)
		
		
	code_shard = models.GaebarCodeShard().all().filter('active = ', True).get()
	
	models_list = backup.models_remaining_to_back_up
	
	current_model = backup.current_model
	current_index = backup.current_index

	kind_map = db._kind_map

	model_classes = []
	for model_name in kind_map:
		model_classes.append(kind_map[model_name])
		
	Model = kind_map[current_model]
		
	fields = Model.fields()
	
	# Forced to use GQL since __key__ is not recognized for order() in App Engine 1.1.7 - yay! Joy! etc.
	
	# logging.info('Last key:')
	# logging.info(last_key)
	
	if last_key == '0':
		# No last key, get the first N rows for this model
		# logging.info('NO LAST KEY, START FROM TOP')
		query = Model.gql('ORDER BY __key__')
	else:
		# Use the new sortable keys feature
		# logging.info('Has last key, using that...')
		# logging.info('===> ' + last_key)
		last_key_as_Key = db.Key(last_key)
		query = Model.gql('WHERE __key__ > :1 ORDER BY __key__', last_key_as_Key)
		
	rows = query.fetch(ROWS_TO_BACKUP_ON_EACH_ITERATION+1)

	# logging.info('Num rows:')
	# logging.info(len(rows))

	new_last_key = None
	last_iteration_for_model = True
	if len(rows) == ROWS_TO_BACKUP_ON_EACH_ITERATION+1:
		new_last_key = str(rows[ROWS_TO_BACKUP_ON_EACH_ITERATION].key())
		context['last_key'] = new_last_key
		last_iteration_for_model = False
		
		# logging.info('NOT last iteration for model...')
		# logging.info('New last key!')
		# logging.info(new_last_key)

	code = code_shard.code

	#if rows:
	# Go through the rows and save each one.
							
	counter = current_index
	for row in rows:

		# Check if there's enough space left in this code shard. 
		if len(code) >= MAX_SHARD_SIZE:
			# logging.info('Backup: current code shard is full, starting a new one...')

			# Update the metadata (end row for this model for this shard)
			update_code_shard_metadata(code_shard, backup, current_model)

			# Update the shard row limits on the backup so we don't have to 
			# generate these after the backup (CPU-heavy)
			backup.shard_row_limits.append(backup.num_rows - 1)

			close_code_shard(code_shard, backup, code)

			# Start the new code shard
			code_shard = models.GaebarCodeShard()
			code_shard.backup = backup
			code_shard.start_row = backup.num_rows
			#code = add_code_shard_imports('')
			code = add_code_shard_imports(u'# coding: utf8\n')			
			
			# Update the code shard count on the backup instance
			backup.num_shards += 1
		
		# Check if we've saved this model in this code shard
		# already and, if not, do so (metadata). 
		if not current_model in code_shard.models:
			# logging.info('ADDING::::: ' + current_model)
			code_shard.models.append(current_model)
			code_shard.models_start_row.append(backup.num_rows)
			code_shard.models_end_row.append(backup.num_rows)
		
		# Create the entity
		row_name = current_model.lower() + u'_' + unicode(counter)
		existing_key_name = row.key()
		key_id = row.key().id_or_name()
				
		key_repr = row.key().__repr__()
		
		# So that it doesn't get replaced in our regexp sweep later,
		# modify numeric indices so that they don't match the pattern
		# (couldn't find another way to do this since Python doesn't
		# support regular expression in negative lookbehinds.)
		# This will change, for example:
		#
		# datastore_types.Key.from_path('GoogleAccount', 2L, _app=u'si')
		#
		# to:
		#
		# datastore_types.Key.from_path('GoogleAccount', long(2L), _app=u'si')

		# key_repr_safe = backup_existing_key_hack_rc.sub(r'long(\1)', key_repr)
		
		code += u'def row_%d(app_name):\n' % backup.num_rows
				
		# Generate code: Check if entity already exists and delete it if it
		# does, by bypassing standard delete logic, because it may be overridden.
		code += u'\texisting_entity = %s.get(%s)\n' % (current_model, key_repr)
		code += u'\tif existing_entity:\n'
		#code += u'\t\texisting_entity.delete()\n'
		code += u'\t\t_delete(existing_entity)\n'
		
		# code += u'\t# Key id = ' + unicode(key_id) + '\n'
		# code += u'\t# Parent = ' + unicode(row.parent()) + '\n'
		# code += u'\t# Key repr = ' + unicode(key_repr) + '\n'

		##################################################################################
		#
		# Don't think this is going to work. Can't guarantee key ids... reverting to r.955
		# (see below)...
		#
		##################################################################################
		# Generate code: Create the new entity (entities are created 
		# differently based on whether they have key names or not).
		# key_name = None
		# if row.key().name():
		# 	# Row has a key name, use this when creating the new entity
		# 	key_name = row.key().name()
		# 	code += u'\t\t%s = %s(key_name="%s")\n' % (row_name, current_model, key_name)
		# else:
		# 	# Don't add a key name if the row didn't have one originally
		# 	code += u'\t\t%s = %s()\n' % (row_name, current_model)
		##################################################################################
	
		##################################################################################
		#
		# REVERT r.955
		#
		##################################################################################
		if row.key().name():
			key_name = row.key().name()
			# code += u'\t# Key has name: ' + row.key().name()
		else:
			key_name = u'id' + unicode(row.key().id())

		# Store the mapping from the old key to the new key
		# 
		# new_key_code = 'datastore_entities.Key.from_path()'
		# code += u'\t# (%s, )\n' % unicode(key_repr), 

		##################################################################################
		
		# Does this entity have a parent?
		parent_code = ''
		if row.parent():
			# Entity has a parent, maintain the ancestor relationship.
			parent = row.parent()
			parent_key = row.parent().key().__repr__()
			parent_key = update_keys(parent_key)
			
			# logging.info(parent_key)
			
			# parent_code = ', parent=%s' % parent_key
			parent_code = ', parent=_get(%s)' % parent_key
			# logging.info('Parent key found, adding %s ' % parent_key)
		
		
		# Create all of the properties first so that we can include them 
		# in the constructor (this is to support required properties which _must_ be 
		# included in the constructor). Thanks to Jonathan and Thomas for reporting this
		# to me (see http://aralbalkan.com/1784#comment-201846).

		# Property population code
		properties_code = ', '
			
		# Store fields with references separately as these will be 
		# handled in pass 2 of the restore process (so that we can guarantee
		# that all records exist in the datastore before they are referenced.)
		reference_fields_code = ', '
		
		# Populate the fields
		for field in fields:
			
			try:			
				raw_value = getattr(row, field)
				raw_value_type = type(raw_value)
				raw_value_type_name = raw_value_type.__name__

				# To test ReferenceProperty failed to be resolved error, uncomment the following line:
				# raise(db.Error('ReferenceProperty failed to be resolved error.'))

			except db.Error:
				# If a reference property doesn't exist, this will throw a 
				# ReferenceProperty failed to be resolved error. 
				# Catch this and make a note in the code but carry on trucking
				# as this is an issue with this particular datastore and 
				# it's not something we can fix.
				#
				# (Unfortunately, this will also catch other db errors as there
				# isn't a specific sub-class for ReferencePropertyFailedToBeResolvedError).
				# code += u"\t\t# Warning: Datastore error\n"
				datastore_error_message = u"%s: %s\n\n" % sys.exc_info()[:2]
				# code += u"\t\t# %s" % datastore_error_message
				context['datastore_error'] = True
				context['datastore_error_message'] = "Ignoring datastore error: %s" % datastore_error_message
				
				continue
			
			#
			# Check if the field is a reference value. If so, don't pickle
			# the actual reference entity but create code that can actually save
			# the reference at restore-time. We save references later as they
			# will be created in Pass 2 of the restore process.
			#
			
			stored = False
			
			if raw_value_type in model_classes:
				# 
				# Storing references as the actual datastore_entities.Key.from_path code.
				# If this ReferenceProperty contains a numeric ID for the old
				# datastore, it will be rewritten while writing out the references section.
				#
				reference_key = raw_value.key().__repr__()
				reference_fields_code += u'%s=%s, ' % (field, reference_key)		
				stored = True
				
			elif raw_value_type_name == 'list':
				if len(raw_value) > 0:
					list_type_name = type(raw_value[0]).__name__
					if list_type_name == 'Key':
						# ListPropert(db.Key) - list of keys (references) 
						# Rewrite the code to use the new ids if they contain numeric ids.	
						list_code = raw_value.__repr__()
						reference_fields_code += '%s=%s, ' % (field, list_code)
						stored = True		

			if not stored:	
				# Pickle and store (not a reference, list of keys, etc.)
				value = repr(pickle.dumps(raw_value))
				properties_code += u'%s=pickle.loads(%s), ' % (field, value)

		# Update any old datastore numeric keys in reference properties to 
		# their key_name equivalents for the new datastore.
		reference_fields_code = update_keys(reference_fields_code)			

		# Remove the trailing comma and space from the properties and reference field code strings.
		properties_code = properties_code[:-2]
		reference_fields_code = reference_fields_code[:-2]

		# OK, all properties are ready, write out the row's constructor to create the row.
		# code += u'\t%s = %s(key_name=%s%s%s%s)\n' % (row_name, current_model, key_name.__repr__(), properties_code, reference_fields_code, parent_code)
		code += u'\t%s = %s(key_name=u"%s"%s%s%s)\n' % (row_name, current_model, key_name, properties_code, reference_fields_code, parent_code)

		# Does this row belong to an Expando model? (It's OK to put Expoando properties after the 
		# constructor as they cannot be required.)
		if hasattr(row, '_dynamic_properties'):
			# Expando row, add the dynamic properties.
			# logging.info('Expando row found, pickling dynamic properties...')
			code += u'\t# Expando dynamic properties:\n'
			dynamic_properties = row._dynamic_properties
			for dynamic_property in dynamic_properties:
				value = repr(pickle.dumps(dynamic_properties[dynamic_property]))
				code += u'\t%s.%s = pickle.loads(%s)\n' % (row_name, dynamic_property, value)

		# Put the new row
		# code += u'\t%s.put()\n\n' % row_name
		# Put the new row bypassing standard put logic, because it may be overridden.
		code += u'\t_put(%s)\n\n' % row_name
						
		# Update the index counter for this model
		counter += 1
		
		# Update the global row count for this backup
		backup.num_rows += 1

	# Update the backup position
	backup.current_index = counter

	# And the current code shard
	code_shard.code = code
	
	try:
		code_shard.put()
	except db.InternalError:
		logging.warning('InternalError detected. on code_shard put. Trying twice more.')
		# Try twice more to see if the datastore error goes away.
		success = False
		for tries in range(2):
			try:
				code_shard.put()
				success = True
				break
			except db.InternalError:
				logging.warning('InternalError on code_shard put attempt ' + str(tries+1))
		if not success:
			logging.error('InternalError: could not put code_shard.')
			return helpers.render_response('500', first_level, second_level, context, 'HttpResponseServerError')
		
	# And continue backing up
	
	if not last_iteration_for_model:
		
		# Continue backup
		return backup_model(backup, context)
		
	else:
		
		# No rows left on the current model:
		# start on the next model, if there is one.

		# Update the metadata (end row for this model for this shard)
		# code_shard.models_end_row.append(backup.num_rows)
		update_code_shard_metadata(code_shard, backup, current_model)

		try:
			logging.info('Backup: model ' + current_model + ': done')
			code_shard.put()			
			
			models_list.remove(current_model)
			next_model = models_list[0]
			# logging.info('Backup: next model is ' + next_model)
			backup.current_model = next_model
			backup.current_index = 0
			
			# Signal that there is no last key for this model yet so that backup starts from the first row.
			context['last_key'] = 0
						
			return backup_model(backup, context)
			

		except IndexError:

			# That was the last model, backing up the rows is over.

			# Update the shard row limits on the backup so we don't have to 
			# generate these after the backup (CPU-heavy)
			backup.shard_row_limits.append(backup.num_rows - 1)
						
			# Close the code shard
			close_code_shard(code_shard, backup, code)

			# The backup is complete
			backup.complete=True
			backup.put()
			
			# Check if there was any data in the datastore (do not store empty backups)
			shard_row_limits = backup.shard_row_limits
			if len(shard_row_limits) == 1:
				if shard_row_limits[0] == -1:
					# Nothing was backed up
					result_json = simplejson.dumps(dict(empty_datastore=True))
					return HttpResponse(result_json)

			# Set the local and remote URLs
			remote_url = backup.server_url
			local_url = settings.GAEBAR_LOCAL_URL
			
			# logging.info('remote_url = ' + remote_url)
			# logging.info('local_url = ' + local_url)

			# TODO: Make the gaebar app name configurable
			download_url = local_url + '/gaebar/download-remote/?created_at=' + urlquote(backup.created_at) + '&num_shards=' + str(backup.num_shards) + '&url=' + remote_url

			context['url'] = download_url
			context['backup_key'] = backup_key
			
			# Backing up of data is done. Inform user and start
			# downloading it to their local machine.	
			# logging.info('Backup: completed backing up rows.')
			
			result_json = simplejson.dumps(dict(complete=True, download_url=download_url))
			return HttpResponse(result_json)
			



# Not using @authorize - runs on localhost.
def backup_local_download_remote_backup(request):
	"""
	Download a remote backup to the local machine.
	
	Arguments: created_at, num_shards.
	
	IMPORTANT: _This runs on localhost_ and gets called by the deployment
	environment (i.e., the local server must be running before you do 
	a remote backup to get this to work automatically.)
	
	Downloaded files are saved in the backups folder of your 
	Django project. 
	
	File structure:
	
	backups
	|
	|- __init__.py
	|_ <created_at>
	      |
	      |- __init__.py
	      |_ metadata.py
	      |_ shard0.py
	      |_ shard1.py
	      |_ shard<n>.py 
	"""

	# Depending on which app engine patch we're using, the cwd will be different. 
	# In appenginepatch, we'll be in /common/appenginepatch/ and will need to go to /../../gaebar/backups/
	# If I remember correctly, in AppEngine Helper, you should be in the root folder of your 
	# project so the backups will simply be in gaebar/backups/
	#
	# These are the only two configurations currently supported. If this breaks for you, let me know 
	# and I'll try and add support for your configuration too. (Or, if someone has a portable solution,
	# please let me know at aral@aralbalkan.com and I'll implement that.)	
	
	##############################################################
	# Monkeypatch: Remove the dev appserver restrictions
	# until we're done doing what we need to do.
	#
	# Note, requires dev_appserver.py to be patched as 
	# described at http://aralbalkan.com/1440
	#
	##############################################################
	from google.appengine.tools import dev_appserver
	
	# Add 'w' to the allowed modes
	OLD_ALLOWED_MODES = dev_appserver.FakeFile.ALLOWED_MODES
	dev_appserver.FakeFile.ALLOWED_MODES = frozenset(['r', 'rb', 'U', 'rU', 'w']) 
	
	# Add mkdir back to os
	os.mkdir = os.old_mkdir
	
	# Add chdir back to os
	os.chdir = os.old_chdir

	##################### End monkeypatches ########################
	
	if 'url' in request.REQUEST:
		host_url = request.REQUEST['url']
		
		# Make sure host URL starts with http:// protocol.
		if not host_url[0:7] == 'http://':
			host_url = 'http://' + host_url
		
		# logging.info('Host url: ' + host_url)
	else:
		return HttpResponse('Missing URL in local download of remote backup.')

	if 'created_at' in request.REQUEST:
		created_at = request.REQUEST['created_at']
		# logging.info('Created at: ' + created_at)
	else:
		return HttpResponse('Missing created at in local download of remote backup.')

	if 'num_shards' in request.REQUEST:
		num_shards = request.REQUEST['num_shards']
		# logging.info('Num shards: ' + num_shards)
	else:
		return HttpResponse('Missing num shards in local download of remote backup.')


	# Check that we're running locally. If not, exit 
	# (this is not currently meant for pulling backups from 
	# one server to the other. However, it could be used for that later on.)
	
	# TODO: Make this generic - not everyone will have IS_DEV in their settings. 
	if not IS_DEV:
		return HttpResponseForbidden('Please run locally.')


	# Check if we're running on appenginepatch
	cwd = os.getcwd()
	is_appenginepatch = (cwd[-14:] == 'appenginepatch')

	if is_appenginepatch:
		logging.info('Running on appenginepatch: Massaging the working directory...')
		os.chdir('../../')


	# Write the __init__.py in the backups package if it doesn't already exist
	top_package = 'gaebar/backups/__init__.py'
	if not os.path.exists(top_package):
		f = file(top_package, 'w')
		f.close()	
	
	# Check if the backup already exists. If it does, we fail.
	existing_backups = os.listdir('gaebar/backups')
		
	if make_safe_file_name_from_timestamp(created_at) in existing_backups:
		# TODO: Ask user if they want to replace it, etc.
		return HttpResponse('Sorry, backup %s is already on your local machine. Please delete it from your backups folder before downloading it again.' % created_at)

	# OK, backup does not already exist. Create its folder.
	backup_folder = 'gaebar/backups/' + make_safe_file_name_from_timestamp(created_at)
	os.mkdir(backup_folder)
	
	# logging.info('Backup folder created successfully.')

	# And add the __init__.py to it.
	f = file(backup_folder + '/__init__.py', 'w')
	f.close()
	
	# logging.info('Added package file.')

	# We use the Django secret key to authenticate with the remote download handler (shared secret).
	secret = urlquote(settings.GAEBAR_SECRET_KEY)
	
	# Get all the code shards and save them as files.
	result = ''
	shard_index = -1
	while result != 'NO_MORE_SHARDS':
		shard_index += 1
		# logging.info('Attempting to download the next code shard (number ' + str(shard_index) + ')...')
		download_url = host_url + '/gaebar/download-py/' + urlquote(created_at) + '/' + secret + '/'
		file_path = backup_folder + '/shard' + str(shard_index) + '.py'
		result = save_file_from_url(download_url, file_path)
				
		# Did the download fail?
		if result == 'DOWNLOAD_FAILED':
			return HttpResponseServerError('Sorry, downloading shard ' + str(shard_index) + ' failed after repeated retries. Backup failed.')
		
		
	# Get the metadata file:
	# http://localhost:8080/admin/backup/metadata/agJzaXIMCxIGQmFja3VwGAMM/
	metadata_url = host_url + '/gaebar/metadata/' + urlquote(created_at) + '/' + secret + '/'
	metadata_file_path = backup_folder + '/metadata.py'
	
	save_file_from_url(metadata_url, metadata_file_path)
	
	logging.info('Gaebar: Downloaded all files for current backup.')
	
	##############################################################
	# Monkeypatch: Reset the local appserver restrictions
	##############################################################
	
	# Remove 'w' from the allowed modes for file.
	dev_appserver.FakeFile.ALLOWED_MODES = OLD_ALLOWED_MODES
	
	# Remove mkdir from os
	del os.mkdir
	
	# Remove chdir from os
	del os.chdir

	##################### End monkeypatch resets ##################
	
	return HttpResponseRedirect('/gaebar/?complete=' + urlquote(make_safe_file_name_from_timestamp(created_at)))	



# No @authorize - see notes.
def backup_download_py(request, created_at='', secret=''):
	"""
	Download a python source file.
	
	This routine is meant to be called from the app. Does _not_ use the
	auth system but the Django secret key so we can use urlfetch.	
	
	Note: Stores the last downloaded shard in the backup entity.
	"""
	
	# logging.info('Backup download py called!')
	
	if secret == settings.GAEBAR_SECRET_KEY:	
		# Download a python file of the requested code shard.
		backup = models.GaebarBackup.all().filter('created_at = ', timestamp_to_datetime(created_at)).get()

		if not backup:
			raise Http404

		code_shards = backup.code_shards

		if backup.last_downloaded_shard_created_at == None:
			# This is the first shard download request for this backup: start from the first shard.
			# logging.info('First request to download a code shard for this backup!')
			query = code_shards.order('created_at')
		else:
			# Sort on created_at and get the next entity.
			# logging.info('Searching for the next code shard...')
			last_downloaded_shard_created_at = backup.last_downloaded_shard_created_at
			query = models.GaebarCodeShard.gql('WHERE backup = :1 AND created_at > :2 ORDER BY created_at', backup, last_downloaded_shard_created_at)

		# Get the code shard.
		code_shard_result = query.fetch(1)
		
		if code_shard_result:
			code_shard = code_shard_result[0]
			
			# logging.info('Code shard found - saving created at:')
			# logging.info(code_shard.created_at)
			
			# Save this as the last downloaded code shard.
			backup.last_downloaded_shard_created_at = code_shard.created_at
			backup.put()
			
		else:
			
			# logging.info('No more code shards!')
			
			# Signal to the client that there are no more code shards.
			response = HttpResponse('NO_MORE_SHARDS', 'text/plain')
			return response
		
		response = HttpResponse(code_shard.code, 'text/plain')
		response['Content-Disposition'] = 'attachment; filename=shard' + str(created_at) + '.py'
		return response
	else:
		return HttpResponseForbidden()
	
	
		
# No @authorize - see notes.
def backup_generate_metadata(request, created_at='', secret=''):
	"""
	Generates metadata code for the backup.
	
	Meant to be called by the local handler only with shared secret (not directly).
	"""
	
	if not secret == settings.GAEBAR_SECRET_KEY:	
		return HttpResponseForbidden()
	
	backup = models.GaebarBackup.all().filter('created_at = ', timestamp_to_datetime(created_at)).get()

	if not backup:
		raise Http404

	context = dict(backup = backup)

	response = HttpResponse(loader.render_to_string('gaebar/metadata.py', context), 'text/plain')
	response['Content-Disposition'] = 'attachment; filename=metadata.py'
	return response


#
#	Restore views
#
	
@authorize
def get_restore_info(request):
	"""
	Returns the folder name, secret string, and initial pass number and start row.
	
	The client will use this information to start calling backup_restore_row until
	the restore is complete.
	
	"""

	if 'folder_name' in request.REQUEST:
		folder_name = request.REQUEST['folder_name']
	else:
		result_dict = dict(error=True, error_message='Folder name was not provided in call to get restore info.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json)

	# logging.info('Restore start called for folder ' + folder_name)
	
	secret = urlquote(settings.GAEBAR_SECRET_KEY)	

	logging.info('Starting to restore ' + folder_name + '...')

	result_dict = dict(
		folder_name=folder_name, 
		secret=secret, 
		next_row_index=0,
		row_index=0,
		models={},
	)
	result_json = simplejson.dumps(result_dict)
	return HttpResponse(result_json)
			
		

# Does not use @authorize. See notes, below.
def backup_restore_row(request):
	"""	
	Restores a single row from the backup and recurses.
	
	Expects the following arguments in the request:
	
	secret: settings.GAEBAR_SECRET_KEY.
	folder_name: Name of backup folder to restore.
	row_index: The row index to back up next.
	
	Uses the Gaebar secret key from settings for authorization instead of the
	autorization decorator. Otherwise, auth would fail while in the middle of 
	a restore when the reference for the Admin's google account is broken.
	
	"""	
	
	if 'secret' in request.REQUEST:
		secret = request.REQUEST['secret']
		if not secret == settings.GAEBAR_SECRET_KEY:
			result_dict = dict(error=True, error_message='Security error.')
			result_json = simplejson.dumps(result_dict)	
			return HttpResponseForbidden(result_json)
	else:
		result_dict = dict(error=True, error_message='Row index not provided in call to restore rows.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 
	
	
	if 'folder_name' in request.REQUEST:
		folder_name = request.REQUEST['folder_name']
		
		# Find the folder path
		cwd = os.getcwd()
		# logging.info(cwd)

		# If we're running on AppEngineHelper, we should be in the root folder of the app 
		# (so do nothing). With appengine patch, we're in the common/appenginepatch/ folder 
		# and need to massage the working folder.
		folder_prefix = ''
		if os.path.exists('appenginepatcher'):
			# OK, we're running on appenginepatch, massage the working folder accordingly
			folder_prefix = '../../'

		# TODO: Hardcoded for appenginepatch - make it work on AppEngineHelper too
		# (we can't use chdir since we could be restoring on the deployment environment)
		folder_path = folder_prefix + 'gaebar/backups/' + folder_name

		# Does the folder exist?
		if not os.path.exists(folder_path):
			# No, return not found error.
			result_dict = dict(error=True, error_message='Folder path ' + str(folder_path) + 'not found.')
			result_json = simplejson.dumps(result_dict)
			return HttpResponseNotFound(result_json)
		
	else:
		# Folder name not provided.
		result_dict = dict(error=True, error_message='Folder name not provided in call to restore rows.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 

	if 'row_index' in request.REQUEST:
		row_index = int(request.REQUEST['row_index'])
	else:
		result_dict = dict(error=True, error_message='Row index not provided in call to restore rows.')
		result_json = simplejson.dumps(result_dict)
		return HttpResponseServerError(result_json) 	
	
	# logging.info('Restore row called - arguments OK.')
	
	# Load the metadata
	metadata_module_name = 'gaebar.backups.' + folder_name + '.metadata'
	
	#logging.info(metadata_module_name)
	
	metadata_module = import_module(metadata_module_name) #__import__(metadata_module_name)
	
	#logging.info(metadata_module)
	
	#metadata = eval('metadata_module.' + folder_name + '.metadata')
	backup = metadata_module.backup
	backup_shard_row_limits = backup['shard_row_limits']
	
	# Find the shard that this row is in
	shard_number = 0
	shard_found = False
	for shard_row_limit in backup_shard_row_limits:
		if row_index <= shard_row_limit:
			shard_found = True 
			break
		shard_number += 1
	
	if not shard_found:
		return HttpResponseServerError('Shard not found.')

	# Load the shard module
	shard_module_name = 'gaebar.backups.' + folder_name + '.shard' + str(shard_number)
	shard = import_module(shard_module_name) #__import__(shard_module_name)
	#shard_path = 'shard_module.' + folder_name + '.shard' + str(shard_number)
	#shard = eval(shard_path)

	#row_path = shard_path + '.row_' + str(row_index)
	row_function_name = 'row_' + str(row_index)
	row_function = getattr(shard, row_function_name) #eval(row_path)
	
	# Run the row
	row_function(application_name)
	
	# Check if the restore is over.
	if row_index == backup['num_rows'] - 1:

		# Restore is complete; take the user back to the backups list.
				
		# Return information 
		result_dict = dict(
			complete=True,
			folder_name=folder_name, 
			secret=secret, 
							
			# TODO: Save the server name in the metadata
			# backup_server_name=backup.server_name,
			
			created_at=backup['created_at'],
			models=backup['models'],
			num_rows=backup['num_rows'],
			num_shards=backup['num_shards'],
			shard_number=shard_number,
			
		)

		result_json = simplejson.dumps(result_dict)
		return HttpResponse(result_json)
			
	else:
	
		# Find the model currently being restored to display to user.
		current_model = ''
		models = shard.metadata['models']
		for model in models:
			limits = models[model]
			if row_index >= limits[0] and row_index <= limits[1]:
				current_model = model
				break
	
		next_row_index = row_index + 1
						
		# Return the information for the next call
		result_dict = dict(
			folder_name=folder_name, 
			secret=secret, 
			row_index=row_index,
			
			# TODO: Save the server name in the metadata
			# backup_server_name=backup['server_name'],

			next_row_index=next_row_index,
			model=current_model,
			created_at=backup['created_at'],
			models=backup['models'],
			num_rows=backup['num_rows'],
			num_shards=backup['num_shards'],
			shard_number=shard_number,
	
		)
		
		result_json = simplejson.dumps(result_dict)
		return HttpResponse(result_json)	

	
######################################################################
#
#	Helper methods
#
######################################################################

def backup_model(backup, context):
	"""Helper: Saves the current state of the backup and runs the next iteration."""
	backup.put()
	
	context['key'] = str(backup.key())
	context['current_model'] = backup.current_model
	context['current_index'] = backup.current_index
	context['num_rows'] = backup.num_rows
	context['num_shards'] = backup.num_shards
	context['num_models'] = len(backup.ordered_model_list)
	context['num_models_remaining'] = len(backup.models_remaining_to_back_up)
	context['num_models_done'] = len(backup.ordered_model_list) - len(backup.models_remaining_to_back_up)
	context['models_remaining'] = backup.models_remaining_to_back_up
	context['modified_at'] = str(backup.modified_at)
	
	result_json = simplejson.dumps(context)
	return HttpResponse(result_json)


def close_code_shard(code_shard, backup, code=None):
	"""Closes a code shard."""

	# Parameterize the application name in the code
	code = parameterize_app_name(code)

	# Save the old one and mark it as inactive
	code_shard.active = False
	
	# Generate metadata for this code shard
	# (to be used during restore.)
	end_row = backup.num_rows - 1
	metadata_end_row = unicode(end_row)
	if code_shard.start_row:
		metadata_start_row = unicode(code_shard.start_row)
	else:
		metadata_start_row = u'0'
	
	c = ", "
	metadata = u"\n\nmetadata = dict("
	metadata += u"start_row=" + metadata_start_row + c
	metadata += u"end_row = " + metadata_end_row + c
	metadata += ")\n"
	
	metadata += u"metadata['models'] = {"
	i = 0
	for model in code_shard.models:
		model_start_row = unicode(code_shard.models_start_row[i])
		model_end_row = unicode(code_shard.models_end_row[i])
		metadata += "'" + model + "': ("+ model_start_row +", " + model_end_row + ",), "
		i += 1
	metadata += "}\n"
	
	code += metadata
	
	code_shard.code = code
	code_shard.end_row = end_row
	code_shard.put()
	
	return True
	
	
def get_date_string():
	"""Helper: Returns a nicely formatted date string of the current date."""
	d = datetime.datetime.now()
	day = d.day
	if 4 <= day <= 20 or 24 <= day <= 30:
	    suffix = u'th'
	else:
	    suffix = [u'st', u'nd', u'rd'][day % 10 - 1]
	date_string = d.strftime('%A, %B ') + unicode(d.day) + suffix + ', ' + unicode(d.year)
	return date_string


def folder_exists(folder_name):
	"""Helper: Checks whether the passed folder exists."""
	folder_path = 'backups/' + folder_name
	# logging.info('Checking if ' + folder_path + 'exists...')
	return os.path.exists(folder_path)


def add_code_shard_imports(code):
	"""Helper: Adds imports that are common to all code shards."""
	code += u'import pickle\n'
	# code += u'from google.appengine.api.datastore import datastore_types\n'
	code += u'from google.appengine.api.datastore import datastore_types\nfrom google.appengine.ext.db import delete as _delete, put as _put, get as _get\n'
	# code += u'from lib.counter import Counter\n'

	# Import the models. Use the packages in the settings
	for model in settings.GAEBAR_MODELS:
		code += 'from ' + model[0] + ' import *\n'
	
	# Make it preeyti-like!
	code += '\n'
	
	return code
	

def get_timestamp_groups(timestamp):
	"""Helper: Returns matched groups from a timestamp."""
	return timestamp_regexp_compiled.match(timestamp).groups()


def make_safe_file_name_from_timestamp(timestamp):
	"""Helper: Returns a cross-platform file-system safe name from a datastore timestamp."""
	g = get_timestamp_groups(timestamp)
	file_name = 'backup_' + g[0] + '_' + g[1] + '_' + g[2] + '_at_' + g[3] + '_' + g[4] + '_' + g[5] + '_' + g[6]	
	return file_name


def make_timestamp_from_safe_file_name(file_name):
	"""Helper: Returns a datestore timestamp string from a safe file name."""
	#r'^(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)\.(\d*?)$'
	# ['backup', '2008', '08', '10', 'at', '14', '10', '08', '827815']
	g = file_name.split('_')
	timestamp = g[1] + '-' + g[2] + '-' + g[3] + ' ' + g[5] + ':' + g[6] + ':' + g[7] + '.' + g[8]
	return timestamp
	

def timestamp_to_datetime(timestamp):
	"""Helper: Returns a datetime object suitable for datastore quieries for the passed timestamp string (which must be in the format str(datetime_obj))."""
	g = get_timestamp_groups(timestamp)
	datetime_args = [int(x) for x in g]
	d = datetime.datetime(*datetime_args)
	return d


def save_file_from_url(url, file_path, num_tries = 0):
	"""
	Helper: Downloads a file from the passed url and saves it to the file at file_path.
	
	File should not already exist.
	
	Returns the content.
	"""
	
	# logging.info('About to fetch ' + url)
	# logging.info('Downloading and saving to ' + file_path)
	
	#url = u'http://localhost:8080/gaebar/download-py/2008-12-15%2019%3A30%3A02.236023/0/change_this_to_something_random/'
	#url = 'http://aralbalkan.com:80/'
	
	MAX_TRIES = 5;
	while num_tries < MAX_TRIES:
		try:
			# logging.info('Attempt # ' + str(num_tries+1))
			
			result = urlfetch.fetch(url)
					
			# To test shard download failure, uncomment the following line:
			# raise db.Error('dummy error')
			
			# Break out of the loop if we've made it this far; call is successful.
			break
			
		except:
			# In case there's a random error, retry until MAX_TRIES
			num_tries += 1
	else:
		logging.info('save_file_from_url Error: Tried ' + str(MAX_TRIES) + ' times and call is still failing. Backup failed.')
		return 'DOWNLOAD_FAILED'
		
	
	content = result.content
	
	if content == 'NO_MORE_SHARDS':
		return content
	
	# logging.info('Content:')
	# logging.info(content)
	
	f = file(file_path, 'w')
	f.write(content)
	f.close()
	
	return content	


def update_keys(code):
	"""
	Updates any numeric keys found in the code to use the new key_name format
	
	i.e., takes keys in the form 2L 343L etc. and makes them into 'id2', 'id343', etc.
	
	"""
	code = backup_update_key_rc.sub(r"'id\1',", code)
	return code
	
def parameterize_app_name(code):
	"""
	During backup, replaces any references to the app name with a variable. When
	restoring, the app name of the current app is passed to the restore function.
	This lets us restore the data to any application and thus create staging servers.
	
	"""
	code = code.replace("_app=u'%s'" % application_name, "_app=app_name")
	return code

def update_code_shard_metadata(code_shard, backup, current_model):
	if current_model in code_shard.models:
		current_model_index = code_shard.models.index(current_model)
		code_shard.models_end_row[current_model_index] = backup.num_rows - 1	
	return True


# From: http://stackoverflow.com/questions/211100/pythons-import-doesnt-work-as-expected
def import_module(name):
    mod = __import__(name)
    components = name.split('.')
    for comp in components[1:]:
        mod = getattr(mod, comp)
    return mod
