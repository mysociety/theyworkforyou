#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string
import cStringIO
import tempfile
import time
import shutil

import xml.sax
xmlvalidate = xml.sax.make_parser()

from filterwranscolnum import FilterWransColnum
from filterwransspeakers import FilterWransSpeakers
from filterwranssections import FilterWransSections

from filterwmscolnum import FilterWMSColnum
from filterwmsspeakers import FilterWMSSpeakers
from filterwmssections import FilterWMSSections

from filterdebatecoltime import FilterDebateColTime
from filterdebatespeakers import FilterDebateSpeakers
from filterdebatesections import FilterDebateSections

from lordsfiltercoltime import SplitLordsText
from lordsfiltercoltime import FilterLordsColtime
from lordsfilterspeakers import LordsFilterSpeakers
from lordsfiltersections import LordsFilterSections

from ni.parse import ParseDay as ParseNIDay

from contextexception import ContextException
from patchtool import RunPatchTool

from xmlfilewrite import CreateGIDs, CreateWransGIDs, WriteXMLHeader, WriteXMLspeechrecord
from gidmatching import FactorChanges, FactorChangesWrans

from resolvemembernames import memberList

import miscfuncs
from miscfuncs import AlphaStringToOrder


toppath = miscfuncs.toppath
pwcmdirs = miscfuncs.pwcmdirs
pwxmldirs = miscfuncs.pwxmldirs
pwpatchesdirs = miscfuncs.pwpatchesdirs


# master function which carries the glued pages into the xml filtered pages

# outgoing directory of scaped pages directories
# file to store list of newly done dates
changedatesfile = "changedates.txt"
tempfilename = tempfile.mktemp(".xml", "pw-filtertemp-", miscfuncs.tmppath)

# create the output directory
if not os.path.isdir(pwxmldirs):
    os.mkdir(pwxmldirs)


def ApplyPatches(filein, fileout, patchfile):
    # Apply the patch
    shutil.copyfile(filein, fileout)
    status = os.system("patch --quiet %s <%s" % (fileout, patchfile))
    if status == 0:
        return True
    print "blanking out failed patch %s" % patchfile
    print "---- This should not happen, therefore assert!"
    assert False

# the operation on a single file
def RunFilterFile(FILTERfunction, xprev, sdate, sdatever, dname, jfin, patchfile, jfout, bquietc):
    # now apply patches and parse
    patchtempfilename = tempfile.mktemp("", "pw-applypatchtemp-", miscfuncs.tmppath)

    if not bquietc:
        print "reading " + jfin

    # apply patch filter
    kfin = jfin
    if os.path.isfile(patchfile) and ApplyPatches(jfin, patchtempfilename, patchfile):
        kfin = patchtempfilename

    # read the text of the file
    ofin = open(kfin)
    text = ofin.read()
    ofin.close()

    # do the filtering according to the type.  Some stuff is being inlined here
    if dname == 'regmem' or dname == 'votes' or dname == 'ni':
        regmemout = open(tempfilename, 'w')
        FILTERfunction(regmemout, text, sdate, sdatever)  # totally different filter function format
        regmemout.close()
        # in win32 this function leaves the file open and stops it being renamed
        if sys.platform != "win32":
            xmlvalidate.parse(tempfilename) # validate XML before renaming
        if os.path.isfile(jfout):
            os.remove(jfout)
        os.rename(tempfilename, jfout)
        return

    safejfout = jfout
    assert dname in ('wrans', 'debates', 'wms', 'westminhall', 'lordspages')

    notus = False
    if sdate > '2006-05-07' and re.search('<notus-date', text):
        notus = True
        text = re.sub("\n", ' ', text)
        text = re.sub("</?notus-date[^>]*>", "", text)
        text = re.sub("\s*<meta[^>]*>\s*", "", text)
        text = re.sub('(<h5 align="left">)((?:<a name="(.*?)">)*)', r"\2\1", text) # If you can't beat them, ...
        text = re.sub("(<br><b>[^:<]*:\s*column\s*\d+(?:WH)?\s*</b>)(\s+)(?i)", r"\1<br>\2", text)
        text = re.sub("(\s+)(<b>[^:<]*:\s*column\s*\d+(?:WH)?\s*</b><br>)(?i)", r"\1<br>\2", text)
        # Lords, big overall replacements
        if dname == 'lordspages':
            text = re.sub('(<h5>)((?:<a name="(.*?)">(?:</a>)?)*)', r"\2\1", text) # If you can't beat them, ...
            text = re.sub('<columnNum><br />( |\xc2\xa0)<br />', '<br>&nbsp;<br>', text)
            text = re.sub('<br />( |\xc2\xa0)<br /></columnNum>', '<br>&nbsp;<br>', text)
            text = text.replace('<b align="center">', '<b>')
            text = text.replace('<br />', '<br>')
            text = text.replace('CONTENTS', 'CONTENTS\n')
            text = re.sub('</?small>', '', text)
            text = re.sub('<div class="amendment(?:_heading)?">', '', text)
            text = re.sub('</?div>', '', text)
            text = re.sub('</b></b>', '</b>', text)

    # Changes in 2008-09 session
    if sdate>'2008-12-01' and dname=='lordspages':
        text = re.sub('(?i)Asked By (<b>.*?)</b>', r'\1:</b>', text)
        text = re.sub('(?i)((?:Moved|Tabled) By (?:<a name="[^"]*"></a>)*)<b>(.*?)</b>', r'\1\2', text)
        text = re.sub('(?i)(Moved on .*? by )<b>(.*?)</b>', r'\1\2', text)

    if notus:
        # Some UTF-8 gets post-processed into nonsense
        # XXX - should probably be in miscfuncs.py/StraightenHTMLrecurse with other character set evil
        text = text.replace("\xe2\x22\xa2", "&trade;")
        text = text.replace("\xc2(c)", "&copy;")
        text = text.replace("\xc2(r)", "&reg;")
        text = text.replace("\xc21/4", "&frac14;")
        text = text.replace("\xc21/2", "&frac12;")
        text = text.replace("\xc23/4", "&frac34;")
        text = text.replace("\xc3\"", "&#279;")
        text = text.replace("\xc3 ", "&agrave;")
        text = text.replace("\xc3(c)", "&eacute;")
        text = text.replace("\xc3(r)", "&icirc;")
        text = text.replace("\xc31/4", "&uuml;")
        # And it's true UTF-8 since the start of the 2009 session, let's pretend it isn't.
        try:
            text = text.decode('utf-8').encode('ascii', 'xmlcharrefreplace')
        except:
            pass

    (flatb, gidname) = FILTERfunction(text, sdate)
    for i in range(len(gidname)):
        tempfilenameoldxml = None

        gidnam = gidname[i]
        if gidname[i] == 'lordswms':
            gidnam = 'wms'
        if gidname[i] == 'lordswrans':
            gidnam = 'wrans'
        CreateGIDs(gidnam, sdate, sdatever, flatb[i])
        jfout = safejfout
        if gidname[i] != 'lords':
            jfout = re.sub('(daylord|lordspages)', gidname[i], jfout)

        # wrans case is special, with its question-id numbered gids
        if dname == 'wrans':
            majblocks = CreateWransGIDs(flatb[i], (sdate + sdatever)) # combine the date and datever.  the old style gids stand on the paragraphs still
            bMakeOldWransGidsToNew = (sdate < "2005")

        fout = open(tempfilename, "w")
        WriteXMLHeader(fout);
        fout.write('<publicwhip scrapeversion="%s" latest="yes">\n' % sdatever)

        # go through and output all the records into the file
        if dname == 'wrans':
            for majblock in majblocks:
                WriteXMLspeechrecord(fout, majblock[0], bMakeOldWransGidsToNew, True)
                for qblock in majblock[1]:
                    qblock.WriteXMLrecords(fout, bMakeOldWransGidsToNew)
        else:
            for qb in flatb[i]:
                WriteXMLspeechrecord(fout, qb, False, False)
        fout.write("</publicwhip>\n\n")
        fout.close()

        # load in a previous file and over-write it if necessary
        if xprev:
            xprevin = xprev[0]
            if gidname[i] != 'lords':
                xprevin = re.sub('(daylord|lordspages)', gidname[i], xprevin)
            if os.path.isfile(xprevin):
                xin = open(xprevin, "r")
                xprevs = xin.read()
                xin.close()

                # separate out the scrape versions
                mpw = re.search('<publicwhip([^>]*)>\n([\s\S]*?)</publicwhip>', xprevs)
                if mpw.group(1):
                    re.match(' scrapeversion="([^"]*)" latest="yes"', mpw.group(1)).group(1) == xprev[1]
                # else it's old style xml files that had no scrapeversion or latest attributes
                if dname == 'wrans':
                    xprevcompress = FactorChangesWrans(majblocks, mpw.group(2))
                else:
                    xprevcompress = FactorChanges(flatb[i], mpw.group(2))

                tempfilenameoldxml = tempfile.mktemp(".xml", "pw-filtertempold-", miscfuncs.tmppath)
                foout = open(tempfilenameoldxml, "w")
                WriteXMLHeader(foout)
                foout.write('<publicwhip scrapeversion="%s" latest="no">\n' % xprev[1])
                foout.writelines(xprevcompress)
                foout.write("</publicwhip>\n\n")
                foout.close()

        # in win32 this function leaves the file open and stops it being renamed
        if sys.platform != "win32":
            xmlvalidate.parse(tempfilename) # validate XML before renaming

        # in case of error, an exception is thrown, so this line would not be reached
        # we rename both files (the old and new xml) at once

        if os.path.isfile(jfout):
            os.remove(jfout)
        if not os.path.isdir(os.path.dirname(jfout)):  # Lords output directories need making here
            os.mkdir(os.path.dirname(jfout))
        os.rename(tempfilename, jfout)

        # copy over onto old xml file
        if tempfilenameoldxml:
            if sys.platform != "win32":
                xmlvalidate.parse(tempfilenameoldxml) # validate XML before renaming
            assert os.path.isfile(xprevin)
            os.remove(xprevin)
            os.rename(tempfilenameoldxml, xprevin)

# hunt the patchfile
def findpatchfile(name, d1, d2):
    patchfile = os.path.join(d1, "%s.patch" % name)
    if not os.path.isfile(patchfile):
        patchfile = os.path.join(d2, "%s.patch" % name)
    return patchfile

# this works on triplets of directories all called dname
def RunFiltersDir(FILTERfunction, dname, options, forcereparse):
    # the in and out directories for the type
    pwcmdirin = os.path.join(pwcmdirs, dname)
    pwxmldirout = os.path.join(pwxmldirs, dname)
    # migrating to patches files stored in parldata, rather than in parlparse
    pwpatchesdir = os.path.join(pwpatchesdirs, dname)
    newpwpatchesdir = os.path.join(toppath, "patches", dname)

    # create output directory
    if not os.path.isdir(pwxmldirout):
        os.mkdir(pwxmldirout)

    # build up the groups of html files per day
    # scan through the directory and make a mapping of all the copies for each
    daymap = { }
    for ldfile in os.listdir(pwcmdirin):
        mnums = re.match("[a-z]*(\d{4}-\d\d-\d\d)([a-z]*)\.html$", ldfile)
        if mnums:
            daymap.setdefault(mnums.group(1), []).append((AlphaStringToOrder(mnums.group(2)), mnums.group(2), ldfile))
        elif os.path.isfile(os.path.join(pwcmdirin, ldfile)):
            print "not recognized file:", ldfile, " inn ", pwcmdirin

    # make the list of days which we will iterate through (in revers date order)
    daydates = daymap.keys()
    daydates.sort()
    daydates.reverse()

    # loop through file in input directory in reverse date order and build up the
    for sdate in daydates:
        newday = 0
        # skip dates outside the range specified on the command line
        if sdate < options.datefrom or sdate > options.dateto:
            continue

        fdaycs = daymap[sdate]
        fdaycs.sort()

        # detect if there is a change in date on any of them, which will
        # require forcr reparse on whole day to keep the "latest" flag up to date.
        # this is happening due to over-writes on the today pages
        bmodifiedoutoforder = None
        for fdayc in fdaycs:
            fin = fdayc[2]
            jfin = os.path.join(pwcmdirin, fdayc[2])
            jfout = os.path.join(pwxmldirout, re.match('(.*\.)html$', fin).group(1) + 'xml')
            patchfile = findpatchfile(fin, newpwpatchesdir, pwpatchesdir)
            if os.path.isfile(jfout):
                out_modified = os.stat(jfout).st_mtime
                in_modified = os.stat(jfin).st_mtime
                if in_modified > out_modified:
                    bmodifiedoutoforder = fin
                if patchfile and os.path.isfile(patchfile):
                    patch_modified = os.stat(patchfile).st_mtime
                    if patch_modified > out_modified:
                        bmodifiedoutoforder = fin
        if bmodifiedoutoforder:
            print "input or patch modified since output reparsing ", bmodifiedoutoforder


        # now we parse these files -- in order -- to accumulate their catalogue of diffs
        xprev = None # previous xml file from which we check against diffs, and its version string
        for fdayc in fdaycs:
            fin = fdayc[2]
            jfin = os.path.join(pwcmdirin, fin)
            sdatever = fdayc[1]

            # here we repeat the parsing and run the patchtool editor until this file goes through.
            # create the output file name
            jfout = os.path.join(pwxmldirout, re.match('(.*\.)html$', fin).group(1) + 'xml')
            patchfile = findpatchfile(fin, newpwpatchesdir, pwpatchesdir)

            # skip already processed files, if date is earler and it's not a forced reparse
            # (checking output date against input and patchfile, if there is one)
            bparsefile = not os.path.isfile(jfout) or forcereparse or bmodifiedoutoforder
            if dname == 'lordspages' and sdate == '2007-10-01' and not bmodifiedoutoforder and not forcereparse:
                bparsefile = False # No debates on 2007-10-01 lords

            while bparsefile:  # flag is being used acually as if bparsefile: while True:
                try:
                    RunFilterFile(FILTERfunction, xprev, sdate, sdatever, dname, jfin, patchfile, jfout, options.quietc)

                    # update the list of files which have been changed
                    # (don't see why it can't be determined by the modification time on the file)
                    # (-- because rsync is crap, and different computers have different clocks)
                    newlistf = os.path.join(pwxmldirout, changedatesfile)
                    fil = open(newlistf,'a+')
                    fil.write('%d,%s\n' % (time.time(), os.path.split(jfout)[1]))
                    fil.close()
                    break

                # exception cases which cause the loop to continue
                except ContextException, ce:
                    if options.patchtool:
                        # deliberately don't set options.anyerrors (as they are to fix it!)
                        print "runfilters.py", ce
                        RunPatchTool(dname, (sdate + sdatever), ce)
                        # find file again, in case new
                        patchfile = findpatchfile(fin, newpwpatchesdir, pwpatchesdir)
                        continue # emphasise that this is the repeat condition

                    elif options.quietc:
                        options.anyerrors = True
                        print ce.description
                        print "\tERROR! %s failed on %s, quietly moving to next day" % (dname, sdate)
                        newday = 1
                        # sys.exit(1) # remove this and it will continue past an exception (but then keep throwing the same tired errors)
                        break # leave the loop having not written the xml file; go onto the next day

                    # reraise case (used for parser development), so we can get a stackdump and end
                    else:
                        options.anyerrors = True
                        raise

            # endwhile
            if newday:
                break
            xprev = (jfout, sdatever)


# These text filtering functions filter twice through stringfiles,
# before directly filtering to the real file.
def RunWransFilters(text, sdate):
    si = cStringIO.StringIO()
    FilterWransColnum(si, text, sdate)
    text = si.getvalue()
    si.close()

    si = cStringIO.StringIO()
    FilterWransSpeakers(si, text, sdate)
    text = si.getvalue()
    si.close()

    flatb = FilterWransSections(text, sdate)
    return ([flatb], ["wrans"])


def RunDebateFilters(text, sdate):
    memberList.cleardebatehistory()

    si = cStringIO.StringIO()
    FilterDebateColTime(si, text, sdate, "debate")
    text = si.getvalue()
    si.close()

    si = cStringIO.StringIO()
    FilterDebateSpeakers(si, text, sdate, "debate")
    text = si.getvalue()
    si.close()

    flatb = FilterDebateSections(text, sdate, "debate")
    return ([flatb], ["debate"])


def RunWestminhallFilters(text, sdate):
    memberList.cleardebatehistory()

    si = cStringIO.StringIO()
    FilterDebateColTime(si, text, sdate, "westminhall")
    text = si.getvalue()
    si.close()

    si = cStringIO.StringIO()
    FilterDebateSpeakers(si, text, sdate, "westminhall")
    text = si.getvalue()
    si.close()

    flatb = FilterDebateSections(text, sdate, "westminhall")
    return ([flatb], ["westminhall"])

def RunWMSFilters(text, sdate):
    si = cStringIO.StringIO()
    FilterWMSColnum(si, text, sdate)
    text = si.getvalue()
    si.close()

    si = cStringIO.StringIO()
    FilterWMSSpeakers(si, text, sdate)
    text = si.getvalue()
    si.close()

    flatb = FilterWMSSections(text, sdate)
    return ([flatb], ["wms"])

# These text filtering functions filter twice through stringfiles,
# before directly filtering to the real file.
def RunLordsFilters(text, sdate):
    fourstream = SplitLordsText(text, sdate)
    #return ([], "lords")

    flatb = []
    gidnames = []

    # Debates section
    if fourstream[0]:
        si = cStringIO.StringIO()
        FilterLordsColtime(si, fourstream[0], sdate)
        text = si.getvalue()
        si.close()
        si = cStringIO.StringIO()
        LordsFilterSpeakers(si, text, sdate)
        text = si.getvalue()
        si.close()
        flatb.append(LordsFilterSections(text, sdate))
        gidnames.append("lords")

    # Written Ministerial Statements
    if fourstream[2]:
        text = fourstream[2]
        if sdate > '2008-12-01': # Can't see a better place for this...
            text = re.sub('<h3[^>]*><i>(?:<a name="[^"]*"></a>)*Statements?</i></h3>', '', text)
        si = cStringIO.StringIO()
        FilterLordsColtime(si, text, sdate)
        text = si.getvalue()
        si.close()
        si = cStringIO.StringIO()
        LordsFilterSpeakers(si, text, sdate)
        text = si.getvalue()
        si.close()
        wms = FilterWMSSections(text, sdate, True)
        if wms:
            flatb.append(wms)
            gidnames.append("lordswms")

    # Written Answers
    if fourstream[3]:
        text = fourstream[3]
        if sdate > '2008-12-01': # Can't see a better place for this...
            text = re.sub('<h3[^>]*><i>(?:<a name="[^"]*"></a>)*Questions?</i></h3>', '', text)
        si = cStringIO.StringIO()
        FilterLordsColtime(si, text, sdate)
        text = si.getvalue()
        si.close()
        si = cStringIO.StringIO()
        LordsFilterSpeakers(si, text, sdate)
        text = si.getvalue()
        si.close()
        wrans = FilterWransSections(text, sdate, lords=True)
        if wrans:
            flatb.append(wrans)
            gidnames.append("lordswrans")

    return (flatb, gidnames)

def RunNIFilters(fp, text, sdate, sdatever):
    parser = ParseNIDay()
    print "NI parsing %s..." % sdate
    parser.parse_day(fp, text, sdate + sdatever)

