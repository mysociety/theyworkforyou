Gaebar (Google App Engine Backup and Restore) Beta 3
====================================================

A Naklab™ production sponsored by the <head> web conference - http://headconference.com

Copyright (c) 2009 Aral Balkan. http://aralbalkan.com

Released under the GNU GPL v3 License. See license.txt for the full license or read it here:
http://www.gnu.org/licenses/gpl-3.0-standalone.html


Downloading and Installing Gaebar
=================================

A. From an archive.
-------------------

You can get a .zip or .tar of Gaebar from:
http://github.com/aral/gaebar/tree/master

(Click on the Download link and choose your poison.)

Unzip the Gaebar archive to a folder called gaebar/ off the root of your Django project. You *must* place Gaebar at this location for the app to work properly.


B. From GitHub
--------------

You can install the latest Gaebar trunk into your projects from GitHub using Git.

(a) If you're using Git for your main project
---------------------------------------------

Add Gaebar to your project as a submodule:

git submodule add git://github.com/aral/gaebar-gaed.git 


(b) If you're not using Git for your main project
-------------------------------------------------

Clone Gaebar into a folder called gaebar off the root folder of your project: 

git clone git://github.com/aral/gaebar-gaed.git 

2. To check for updates, go into gaebar/ and git pull

(Don't forget to git commit your main project after you've updated Gaebar to a new version via git pull.)


Configuring your project to use Gaebar
======================================

*IMPORTANT* Patch your dev_appserver.py as per the instructions here: http://aralbalkan.com/1440 (and please star issue 616 if you'd like Google to fix this so we can remove this step: http://code.google.com/p/googleappengine/issues/detail?id=616). 

This is required in order to override some of the local dev server restrictions to allow automatic downloads of backups. Gaebar will not work unless you implement this patch.


1. Add to installed apps
------------------------

Add Gaebar to your list of INSTALLED_APPS in your application's settings.py file. e.g.

	INSTALLED_APPS = (
		# Other apps...
		'gaebar',
	)


2. Add to urls.py
-----------------

In your main urls.py, map the Gaebar app to the URL shown below. You *must* map Gaebar to the exact URL shown below or the app will not work. 

urlpatterns = patterns('',

	# ...other URLs

	url(r'^gaebar/', include('gaebar.urls')),
)

3. Add the static folder
------------------------

In your app.yaml file, add the following entry before any other static entries to map Gaebar's static files (images, js, etc.) correctly:

# Static: Gaebar
- url: /gaebar/static
  static_dir: gaebar/static

4. Add indices
--------------

If you are declaring your indices manually, add the following to your index.yaml file (or run Gaebar locally in the dev server so that the index is created for you automatically):

- kind: GaebarCodeShard
  properties:
  - name: backup
  - name: created_at

(Note: indices may take some time to create on the deployment environment. Until they are ready, backups will fail.)

5. Modify settings.py
---------------------

Modify settings.py to add the GAEBAR_LOCAL_URL, GAEBAR_SECRET_KEY, GAEBAR_SERVERS, and GAEBAR_SERVERS settings to your application.

GAEBAR_LOCAL_URL: Absolute URL of your local development server. Is used when
downloading your remote backup to your local machine.

GAEBAR_SECRET_KEY: A secret key that is used (a) to authenticate communication between your local deployment environment and the remote backup environment to facilitate the download of backups via urlfetch and (b) used during the restore process to authenticate the client.

GAEBAR_SERVERS: Dictionary of named servers. Not essential but makes it easy to identify the servers by name when backing up and restoring. Also makes it easier to identify which server you're running Gaebar on.

GAEBAR_MODELS: Tuple of models from your app that you want backed up.

Here is a sample batch of settings that you can use as a guide:

#
# Gaebar
#

GAEBAR_LOCAL_URL = 'http://localhost:8000'

GAEBAR_SECRET_KEY = 'change_this_to_something_random'

GAEBAR_SERVERS = {
	u'Deployment': u'http://www.myapp.com', 
	u'Staging': u'http://myappstaging.appspot.com', 
	u'Local Test': u'http://localhost:8080',
}

GAEBAR_MODELS = (
     (
          'app1.models', 
          (u'Profile', u'GoogleAccount', u'AllOtherTypes', u'PlasticMan'),
     ),
     (
          'app2.models', 
          (u'Simple',),
     ),
)


A note on templates: 
--------------------

If you get any template-related errors, try adding gaebar/templates to your TEMPLATE_DIRS settings variable.


How it works
============

Gaebar backs up the data in your datastore to Python code. It restores your data by running the generated Python code.

Since a backup is a long running process, and since Google App Engine doesn't support long-running processes, Gaebar fakes a long running process by breaking up the backup and restore processes into bite-sized chunks and repeatedly hitting the server via Ajax calls. 

By default, Gaebar backs up 5 rows at a time to avoid the short term CPU and 10-second call duration quotas and splits the generated code into code shards of approx. 300KB to avoid the 1MB limit on objects. You can change these defaults in the views.py file if your app has higher quotas and you want faster backups and restores. 

Gaebar only works with Django applications on Google App Engine. Both appenginepatch and App Engine Helper are supported.

Please test Gaebar out with sample data locally before testing it on your live app. We cannot be held responsible for any data loss or other damage that may arise from your use of Gaebar.


Usage
=====

A. To make a remote backup:
---------------------------

1. Deploy Gaebar, along with your application, to Google App Engine.

NOTE: When you're deploying, remember that you will also deploy any backups that are in the gaebar/backups folder. It's a good idea to move these elsewhere before deploying to create a new backup to reduce the number of files in your app. and only deploy with the backup you want to restore to reduce the number of files in your app so as not to hit the 1,000 file limit. You cannot harm the app by moving or deleting backup folders.

2. Hit the Gaebar index page from the Google App Engine deployment environment (e.g., http://myapp.appspot.com/gaebar/)

3. Click the Create New Backup button and wait.*

* Make sure that your local dev server is running so that your backup can be downloaded to your local machine.

Note: If your datastore has reference errors, i.e., non-existent references which would result in a 'ReferenceProperty failed to be resolved' error, the backup should fix those errors by ignoring those reference properties that do not exist. When you restore a Gaebar backup, those reference errors should no longer exist. 

Note: The largest datastore I've tested this with is for the <head> conference web site. The latest backup contained 18,955 rows stored in 223 code shards and resulted in a 35MB .datastore file when restored on the local development server (the restore process was left to run overnight due to the speed of the local datastore). Please send statistics of your backups and, of course, any errors you may encounter to aral@aralbalkan.com.

Note 2: If you'd like to get more meaningful exceptions, you may want to add DEBUG_PROPAGATE_EXCEPTIONS = True to your settings.py on the deployment server.


B. Restore data locally:
------------------------

You may want to restore data locally, on your development server, to test with real-world data while developing your application. To do this, 

1. Hit the Gaebar index page on your local machine (e.g., http://localhost:8000/gaebar/)

2. Select a backup to restore from the list.

3. Wait until the backup is restored.

*** Tip: *** The datastore and history are kept in temporary folders such as (on OS X) /var/folders/bz/bzDU030xHXK-jKYLMXnTzk+++TI/-Tmp-/. Before you do a restore, run ./manage.py flush to find out which folder they're kept in. Once you have that, copy those files to a safe place. This way, you can easily restore them if they are erased without having to go through the lengthy restore process again.

Also, realize that the datastore on the local SDK is _very_ slow. Launching the server with a large datastore will take a long time. Even doing a ./manage.py flush will take ages, and you can get the same result by simply deleting the datastore files from the temporary folder.


C. Restore data to a staging app:
---------------------------------

Although Google App Engine gives you basic versioning control during deployment, it doesn't provide a staging environment where you can test out updates with real data on the deployment server without your end users seeing.

With Gaebar, you can set up your own staging application. Simply set up a separate application, change the app name in app.yaml, and deploy. Then, backup your data from your main app. Copy the backup to the staging application and deploy the staging app again to upload your backups to the staging app. On the staging app, restore the data and you can test your latest changes with real data before exposing those changes to your users.

NOTE: When you're deploying, remember that you will also deploy any backups that are in the gaebar/backups folder. It's a good idea to only deploy with the backup you want to restore to reduce the number of files in your app so as not to hit the 1,000 file limit. You cannot harm the app by moving or deleting backup folders.


D. Restore data to your main app:
--------------------------------

If something happens to your main application, you can restore from a backup.

*** Please note that this will REPLACE the data in your main datastore *** THIS IS A DESTRUCTIVE OPERATION. Make sure you fully understand the ramifications before restoring on your deployed application. We take no responsibility for data loss or other damage caused by your use of this software.

NOTE: When you're deploying, remember that you will also deploy any backups that are in the gaebar/backups folder. It's a good idea to only deploy with the backup you want to restore to reduce the number of files in your app so as not to hit the 1,000 file limit. You cannot harm the app by moving or deleting backup folders.


A Note on restoring and existing data
=====================================

When restoring a row, Gaebar checks to see if the original key for the row that was backed up exists in the datastore. If it does, it removes it. 

This means that as long as you are restoring to the same datastore you backed up from or to an empty datastore, the datastore will not contain any duplicate entries after the restore process is complete.

To be extra safe, however, you may want to empty your existing datastore before restoring. 


Testing Gaebar locally:
=======================

You can test Gaebar locally to make sure that it works on your system by using one of the two test applications, gaebar-gaed or gaebar-aep.

The gaebar-gaed test app is built on Google App Engine Django (also known as Google App Engine Helper). It contains the Gaebar functional test suite. You can get it from:

http://github.com/aral/gaebar-gaed/tree/master

The gaebar-aep test app is built on app-engine-patch. It also contains the same functional test suite and you can get it from:

http://github.com/aral/gaebar-aep/tree/master

Look in the readme files in each project for instructions on how to set up and test them locally.


GIT NOTE FOR WINDOWS USERS:
===========================

I've successfully tested this with msysgit 1.5.6.1 (http://code.google.com/p/msysgit/). 

However, msysgit 1.6.0.2 appears to have a problem with submodules (see http://icanhaz.com/msysgitsubmoduleerroron1602).

You get the following error:

$ git submodule update
error: Entry 'readme.txt' would be overwritten by merge. Cannot merge.
Unable to checkout 'de51abeaa23173bbafe2313fd26d27fd6e032c31' in submodule path 'gaebar'

The workaround is to use msysgit 1.5.6.1 (the currently featured download on msysgit).


Known Issues
============

None.
