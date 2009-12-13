#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string
import StringIO

from contextexception import ContextException


# these types of stamps must be available in every question and batch.
# <stamp coldate="2003-11-17" colnum="518W"/>
# <page url="http://www.publications.parliament.uk/pa/cm200102/cmhansrd/vo020522/text/20522w01.htm">
# <stamp aname="40205-01_sbhd0"/>


restamps = re.compile('(<(?:stamp|page url)[^>]*>)')

# this class contains the running values for place identification as we scan through the file.
class StampUrl:
    def __init__(self, lsdate):
        self.sdate = lsdate

        # url of this record
        self.pageurl = ''
        # column number stamp
        self.stamp = ''
        # last time stamp
        self.timestamp = ''
        # last <a name=""> html code, for identifying major headings
        self.aname = ''

    def reps(self):
        col = re.search('colnum="(.*?)"', self.stamp)
        if col:
            col = col.group(1)
        anchor = self.GetAName()
        return "date:%s col:%s aname:%s" % (self.sdate, col or "[nocol]", anchor or "[noanchor]")

    def __repr__(self):
        return "<< StampURL %s >>" % self.reps()

    # extract the stamp codes from the text, and return the glued together text.
    def UpdateStampUrl(self, text):
        # remove the stamps from the text, checking for cases where we can glue back together.
        sp = restamps.split(text)
        for i in range(len(sp)):
            if re.match('<stamp [^>]*>', sp[i]):
                if re.match('<stamp time[^>]*>', sp[i]):
                    self.timestamp = sp[i]
                elif re.match('<stamp aname[^>]*>', sp[i]):
                    self.aname = sp[i]

                # this looks like the standard stamp
                elif re.match("<stamp parsemess[^>]*>", sp[i]):
                    self.stamp += sp[i] # appends it on
                else:
                    self.stamp = sp[i] # then over-rides any parsemesses

                sp[i] = ''

            elif re.match('<page url[^>]*>', sp[i]):
                self.pageurl = sp[i]
                sp[i] = ''

        # stick everything back together
        return string.join(sp, '')


    # extract a url and hash link to position in the web.
    def GetUrl(self):
        spurl = re.match('<page url="(.*?)"', self.pageurl).group(1)
        anamem = self.GetAName()
        if anamem:
            return '%s#%s' % (spurl, anamem)
        else:
            return spurl

    # extract anchor
    def GetAName(self):
        anamem = re.match('<stamp aname="(.*?)"/>', self.aname)
        if anamem:
            return anamem.group(1)
        elif self.aname:  # seems to be two versions of it
            return self.aname
        else:
            return None





# the following class is used to break the text into a list of triples; headings, unspokentext
# (pre-speaker), and list of speaker-pairs.
# where a speaker-pair is speaker and text below it.

# the fixed strings for piecing apart the text
# we need to split off tables because they often contain a heading type in them.
regsection1 = '<h\d><center>.*?\s*</center></h\d>'
regsection2 = '<h\d align="?center"?(?: style="text-transform:uppercase"| class="DebateType")?>.*?</h\d>'
regsection3 = '(?:<p class="tabletext">(?:<stamp[^>]*>)*)?(?:<br>)?<center>(?:<stamp[^>]*>)*<b>.*?</b></center>'  # this case of title is used when quoting inserted amendment text that has a title in it.  This is reaching the limit of this type of parsing
regsection4 = '<(?:p|br)>\s*<center(?: style="text-transform:uppercase")?>.*?</center>\s*<(?:p|br|page[^>]*)>'
regsection5 = '<h[34] align=left>.*?</h[34]>'
regparsermessage = '<parsemess.*?>' #'<parsemess-speech redirect="+-1"/>'
regspeaker = '<speaker [^>]*>.*?</speaker>'
regtable = '<table[^>]*>[\s\S]*?</table>'    # these have centres, so must be separated out

recomb = re.compile('(%s|%s|%s|%s|%s|%s|%s|%s)(?i)' % (regtable, regspeaker, regsection1, regsection2, regsection3, regsection4, regsection5, regparsermessage))

retableval = re.compile('(%s)(?i)' % regtable)
respeakerval = re.compile('<speaker ([^>]*)>.*?</speaker>')
resectiont1val = re.compile('<h\d><center>\s*(.*?)\s*</center></h\d>(?i)')
resectiont2val = re.compile('<h(\d) align="?center"?( style="text-transform:uppercase"| class="DebateType")?>\s*(.*?)\s*</h\d>(?i)')
resectiont3val = re.compile('(<p class="tabletext">(?:<stamp[^>]*>)*)?(?:<br>)?<center>(?:<stamp[^>]*>)*<b>(.*?)</b></center>(?i)')
resectiont4val = re.compile('<(?:p|br)>\s*<center(?: style="text-transform:uppercase")?>(.*?)</center>\s*<(?:p|br|page[^>]*)>(?i)')

# appears in today debates, and is getting a little too general (being able to mix/match b,i tags)
resectiont5val = re.compile('<h[34] align=left>(?:<[bi]>)?\s*(.*?)\s*(?:</[bi]>)?</h\d>(?i)')

reparsermessage = re.compile('<parsemess-misspeech type="(.*?)" redirect="(up|down|nowhere)"/>')


# These aren't actually headings, even though they are <H4><center>
renotheading = re.compile('>(?:\s*|(?:&nbsp;)*)(The .* (?:was|were) asked\s*(?:</i>)?(?:&#151;|-|--))\s*<')
# catch cases of the previous regexp not being broad enough
renotheadingmarg = re.compile('asked')

class SepHeadText:
    def EndSpeech(self):
        sptext = string.join(self.textl, '')
        if self.speaker != 'No one':
            self.shspeak.append((self.speaker, sptext))
            # Specifically "unknown" speakers e.g. "Several hon members rose" don't
            # have text in their "speech" bit.
            if re.match('(?:<[^>]*?>|\s)*$', sptext) and not re.match('speakerid="unknown"', self.speaker):
                print 'Warning:: Speaker with no text ' + self.speaker
                print self.heading

        elif not self.shspeak:
            self.unspoketext = sptext
        else:
            raise Exception, 'logic error in EndSpeech'

        self.speaker = 'No one'
        self.textl = [ ]

    # the pulling of unspoketext from previous heading is done in another pass
    # in filterdebatesections.py
    def EndHeading(self, nextheading, nextmajor=None):
        self.EndSpeech()

        if (self.heading == 'Initial') and self.shspeak:
            print 'Speeches without heading'

        # lost heading signals are found elswhere?

        # concatenate unspoken text with the title if it's a dangle outside heading
        # e.g. In 2003-01-15 we have heading "Birmingham Northern Relief Road "
        # with extra bit "(Low-noise Tarmac)" to pull in.
        if not re.match('(?:<[^>]*?>|\s)*$', self.unspoketext):
            # We deliberately don't put "." in to avoid matching "19." before paragraph starts
            gho = re.match('(\s*[()A-Za-z\-,\'\"/&#; 0-9]+)((?:<[^>]*?>|\s)*)$', self.unspoketext)
            if gho and not renotheadingmarg.search(self.unspoketext):
                self.heading = self.heading + ' ' + gho.group(1)
                self.heading = re.sub("\s+", " ", self.heading)
                #self.unspoketext = gho.group(2)
                # print "merged dangling heading %s" % (self.heading)
                if len(self.heading) > 100:
                    raise ContextException("Suspiciously long merged heading part - is it OK? %s" % self.heading, stamp=None, fragment=self.heading)

        self.shtext.append((self.heading, self.unspoketext, self.shspeak, self.major))

        self.heading = nextheading
        self.major = nextmajor
        self.unspoketext = ''    # for holding colstamps
        self.shspeak = [ ]


    def __init__(self, text):

        # this makes a list of pairs of headings and their following text
        self.shtext = [] # return value

        self.heading = 'Initial'
        self.major = None
        self.shspeak = []
        self.unspoketext = ''

        self.speaker = 'No one'
        self.textl = [ ]

        for fss in recomb.split(text):

            # stick tables back into the text
            if retableval.match(fss):
                self.textl.append(fss)
                continue

            # recognize a speaker type
            gspeaker = respeakerval.match(fss)
            if gspeaker:
                self.EndSpeech()
                self.speaker = gspeaker.group(1)
                continue

            # recognize parser-message headings
            # '<parsemess-misspeech type="(.*?)" redirect="(.*?)"/>'
            gparmess = reparsermessage.match(fss)
            if gparmess:

                # this is complex due to the heading speech structure we maintain at this point
                if re.search('heading', gparmess.group(1)):
                    self.EndHeading("-- Lost Heading --")  # this is used to avoid concatenation

                # missing a speech
                else:
                    assert re.match('speech|ques|reply', gparmess.group(1))
                    self.EndSpeech()

                    # this fills in a new speech that will be a placeholder
                    self.speaker = 'nospeaker="true" redirect="%s"' % gparmess.group(2)
                    if re.match('ques', gparmess.group(1)):
                        self.textl = [ "<wrans-question>NOTHING" ]
                    else:
                        self.textl = [ "NOTHING" ]
                continue

            # recognize a heading instance from the five kinds
            gheading = None
            gmajor = None
            gheadingMatch = resectiont1val.match(fss)
            if gheadingMatch:
                gheading = gheadingMatch.group(1)
            if not gheadingMatch:
                gheadingMatch = resectiont2val.match(fss)
                if gheadingMatch:
                    gheading = gheadingMatch.group(3)
                    gmajor = re.search('uppercase', gheadingMatch.group(2) or '') or gheadingMatch.group(1)=='2'
            if not gheadingMatch:
                gheadingMatch = resectiont3val.match(fss)
                if gheadingMatch:
                    if gheadingMatch.group(1):
                        fss = gheadingMatch.group(1) + ":TITLE3:" + gheadingMatch.group(2) # leave a signal
                        gheadingMatch = None
                    else:
                        gheading = gheadingMatch.group(2)
            if not gheadingMatch:
                gheadingMatch = resectiont4val.match(fss)
                if gheadingMatch:
                    gheading = gheadingMatch.group(1)
            if not gheadingMatch:
                gheadingMatch = resectiont5val.match(fss)
                if gheadingMatch:
                    gheading = gheadingMatch.group(1)
            
            # we have matched a heading thing
            if gheadingMatch and (not gheading or re.match('(?:<[^>]*?>|\s|&nbsp;)*$', gheading)):
                continue

            # we have matched a heading thing
            if gheading:
                # there's a negative regexp match (for "The ... was asked - " which
                # isn't a heading even though it looks like one).  Check we don't
                #  match it.
                negativematch = renotheading.search(fss)
                if not negativematch:
                    if renotheadingmarg.search(fss):
                        raise ContextException('"The ... was asked" match not broad enough: %s' % fss, stamp=None, fragment=fss)

                    # we are definitely a heading
                    self.EndHeading(gheading, gmajor)
                    continue

                fss = negativematch.group(1)


            # more plain text; throw back into the pot.
            if recomb.match(fss):
                print fss
                raise ContextException("vals matches not general enough (may be a programming error) %s" % fss, stamp=None, fragment=fss)
            self.textl.append(fss)

        self.EndHeading('No more')


# main function.
def SplitHeadingsSpeakers(text):
    sht = SepHeadText(text)
    return sht.shtext

