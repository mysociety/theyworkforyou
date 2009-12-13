#!/usr/bin/env python2.4
# vim:sw=8:ts=8:et:nowrap

import sys
import os
import shutil
import string
import miscfuncs
import re
import tempfile
import optparse

# change current directory to pyscraper folder script is in
os.chdir(os.path.dirname(sys.argv[0]) or '.')

from resolvemembernames import memberList
toppath = miscfuncs.toppath

# File names of patch files
# this is horid since it shadows stuff that's done distributively in the scrapers
def GenPatchFileNames(typ, sdate):
	qfolder = toppath
	qfolder = os.path.join(qfolder, "cmpages")
	folder = os.path.join(qfolder, typ)

	# transform the typ into the file stub
	if typ == "wrans":
		stub = "answers"
	elif typ == "lordspages":
		stub = "daylord"
	elif typ == "westminhall":
		stub = "westminster"
	elif typ == "wms":
		stub = "ministerial"
	elif typ == "standing":
		stub = "standing"
        elif typ[0:9] == 'chgpages/':
                stub = re.sub('chgpages/', '', typ)
	else:
		stub = typ

	# lords case where we use the new top level patch directory
	pdire = os.path.join(toppath, "patches")
# all patches will be moved to where they belong
#	if typ != "lordspages":
#		pdire = "patches"  # as local directory
	pdire = os.path.join(pdire, typ)
	if not os.path.isdir(pdire):
		os.mkdir(pdire)

	patchfile = os.path.join(pdire, "%s%s.html.patch" % (stub, sdate))
	orgfile = os.path.join(folder, "%s%s.html" % (stub, sdate))
	tmpfile = tempfile.mktemp(".html", "patchtmp-%s%s-" % (stub, sdate), miscfuncs.tmppath)
	tmppatchfile = os.path.join(pdire, "%s%s.html.patch.new" % (stub, sdate))

	return patchfile, orgfile, tmpfile, tmppatchfile

# Launches editor on copy of file, and makes patch file of changes the user
# makes interactively
def RunPatchToolW(typ, sdate, stamp, frag):
	(patchfile, orgfile, tmpfile, tmppatchfile) = GenPatchFileNames(typ, sdate)

	shutil.copyfile(orgfile, tmpfile)
	if os.path.isfile(patchfile):
		print "Patching ", patchfile
		status = os.system('patch --quiet "%s" < "%s"' % (tmpfile, patchfile))

	# run the editor (first finding the line number to be edited)
	gp = 0
	finforlines = open(tmpfile, "r")
	rforlines = finforlines.read();
	finforlines.close()

	if stamp:
		aname = stamp.GetAName()
		ganamef = re.search(('<a name\s*=\s*"%s">([\s\S]*?)<a name(?i)' % aname), rforlines)
		if ganamef:
			gp = ganamef.start(1)
	else:
		ganamef = None

	if not frag:
		fragl = -1
	elif ganamef:
		fragl = string.find(ganamef.group(1), frag)
	else:
		fragl = string.find(rforlines, frag)
	if fragl != -1:
		gp += fragl

	gl = string.count(rforlines, '\n', 0, gp)
	gc = 0
	if gl:
		gc = gp - string.rfind(rforlines, '\n', 0, gp)
	#print "find loc codes ", gp, gl, gc

	if 1==0 and sys.platform == "win32":
		os.system('"C:\Program Files\ConTEXT\ConTEXT" %s /g%d:%d' % (tmpfile, gc + 1, gl + 1))
	else:
		# TODO add column support using gc + 1, if you can work out vim's syntax
		editor = os.getenv('EDITOR')
		if not editor:
			editor = 'vim'
		os.system('%s "%s" +%d' % (editor, tmpfile, gl + 1))


	# now create the diff file
	if os.path.isfile(tmppatchfile):
		os.remove(tmppatchfile)
	ern = os.system('diff -u "%s" "%s" > "%s"' % (orgfile, tmpfile, tmppatchfile))
	if ern == 2:
		print "Error running diff"
		sys.exit(1)
	os.remove(tmpfile)
	if os.path.isfile(patchfile):
		os.remove(patchfile)
	if os.path.getsize(tmppatchfile):
		os.rename(tmppatchfile, patchfile)
		print "Making patchfile ", patchfile


def RunPatchTool(typ, sdatext, ce):
	if not ce.stamp:
		print "No stamp available, so won't move your cursor to right place"
	else:
		assert ce.stamp.sdate[:10] == sdatext[:10]  # omitting the letter extension

	print "\nHit RETURN to launch your editor to make patches "
	sys.stdin.readline()
	RunPatchToolW(typ, sdatext, ce.stamp, ce.fragment)
	memberList.reloadXML()


# So it works from the command line
if __name__ == '__main__':
	parser=optparse.OptionParser()
	
	(options,args)=parser.parse_args()

	args=[sys.argv[0]]+args
	#print args

	if len(args) != 3:
                print """
This generates files for the patchfilter.py filter.

They are standard patch files which apply to the glued HTML files which we
download from Hansard.  Any special errors in Hansard are fixed by
these patches.

Run this tool like this:
  ./patchtool.py wrans 2004-03-25

This will launch your editor, and upon exit write out a patch of your changes
in the patches folder underneath this folder.  The original file is
untouched.  We consider the patches permanent data, so add them to CVS.
"""
 		sys.exit(1)
	RunPatchToolW(args[1], args[2], None, "")

