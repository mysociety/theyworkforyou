#! /usr/bin/env python2.4

# Renames a folder of MP photos from filenames containing their names to
# filenames containing their member id

import os
import re
import sys

sys.path.append("../pyscraper/")
from resolvemembernames import memberList

photodir = "/home/francis/devel/fawkes/www/docs/images/orig/"
photodate = "2004-04-13"

# Remove junk from directory
if os.path.exists(os.path.join(photodir, "Thumbs.db")):
    os.remove(os.path.join(photodir, "Thumbs.db"))

# Perform name matching
dir = os.listdir(photodir)
renamemap = {}
for file in dir:
    mfile = file
    if file == "drew-david_178.jpg":
        mfile = "drew_david_178.jpg"
    if file == "murphy_john_445.jpg":
        mfile = "murphy_jim_445.jpg"
    if file== "raynsford-nick_496.jpg":
        mfile = "raynsford_nick_496.jpg"
    if file== "robathan_robert_502.jpg":
        mfile = "robathan_andrew_502.jpg"

    match = re.match("([a-z_-]+)_([a-z-]+)(?:_(\d+))?_?.jpg", mfile) 
    assert match, "didn't match %s" % file
    (last, first, alienid) = match.groups()

    cons = None
    if file == "thomas_gareth_591.jpg":
        cons = "Clwyd West"
    if file == "thomas_gareth_r_592.jpg":
        cons = "Harrow West"
    if file == "wright_tony_w_654.jpg":
        cons = "Cannock Chase"
    if file == "wright_tony_653.jpg":
        cons = "Great Yarmouth"

    last = last.replace("_", " ")
    fullname = "%s %s" % (first, last)
    fullname = memberList.fixnamecase(fullname)
    (id, correctname, correctcons) = memberList.matchfullnamecons(fullname, cons, photodate)
    id = memberList.membertoperson(id)
    id = id.replace("uk.org.publicwhip/person/", "")

    renamemap[file] = "%s.jpg" % id

    # print file, renamemap[file]

assert len(renamemap.keys()) == 659, "got %d keys, not 659" % len(renamemap.keys())

# sys.exit(1)

# Do renaming
for name, newname in renamemap.iteritems():
    assert not os.path.exists(newname), "file %s already exists" % newname
    print name, "=>", newname
    os.rename(photodir + name, photodir + newname)

