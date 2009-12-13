#!/usr/bin/python
# vim:sw=4:ts=4:et:nowrap

# CGI script for a REST based interface to the name matcher (and
# maybe later other things)

import sys
import cgi
import cgitb
cgitb.enable()

sys.path.append("../pyscraper/")
from resolvemembernames import memberList

# MP name match
def mp_full_cons_match(name, cons, date):
    (id, canon_name, canon_cons) = memberList.matchfullnamecons(name, cons, date)
    print "Content-Type: text/plain\n\n"
    if not id:
        print "ERROR"
        print "No match found"
    else:
        print "OK"
        print id
        print canon_name.encode("latin-1")
        print canon_cons.encode("latin-1")
        print memberList.membertoperson(id)

# Look up command
form = cgi.FieldStorage()
if form.has_key("command") and form["command"].value == "mp-full-cons-match":
    try:
        mp_full_cons_match(form["name"].value, form["constituency"].value, form["date"].value)
    except Exception, e:
        print "Content-Type: text/plain\n\n"
        print "ERROR"
        print str(e)

else:
    print """Content-Type: text/plain

rest.cgi - REST interface to parts of parlparse

Set the 'command' URL parameter to one of the functions described below.
Parameters are simple URL parameters.  The return value is in plain text. The
first line will contain just the word "OK" if the command succeeded, or "ERROR"
upon failure. The latin1 (ISO-8859-1) character set is used throughout.

Contact francis@flourish.org with any questions. If you'd like some more 
functions I can easily add them. For example to match constituencies, return
person ids and so on.

mp-full-cons-match
------------------

Finds MP identifier for a full name such as "Tony Blair", constituency name
and date when the MP was mentioned.  Put the values in 'name', 'constituency'
and 'date' URL parameters.  Returns value id, canonical name, canonical
constituency and person id on four lines.

http://ukparse.kforge.net/parlparse/rest.cgi?command=mp-full-cons-match&name=Tony%20Blair&constituency=Sedgefield&date=1997-09-01
http://ukparse.kforge.net/parlparse/rest.cgi?command=mp-full-cons-match&name=Sion%20Simon&date=2005-01-01&constituency=Birmingham%20Erdington

"""

