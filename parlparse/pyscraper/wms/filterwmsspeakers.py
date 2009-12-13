# -*- coding: latin-1 -*-
# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string
from resolvemembernames import memberList
from splitheadingsspeakers import StampUrl

from miscfuncs import ApplyFixSubstitutions
from contextexception import ContextException

recomb = re.compile('((?:<stamp aname="[^"]*?"/>)?<b>(?:<stamp aname="[^"]*?"/>)*[^<]*</b>\s*:?)(?i)')
respeakervals = re.compile('(<stamp aname="[^"]*?"/>)?<b>(<stamp aname="[^"]*?"/>)*\s*([^:<(]*?):?\s*(?:\((.*?)\)?)?\s*:?\s*</b>(?i)')
remarginal = re.compile('<b>[^<]*</b>(?i)')

def FilterWMSSpeakers(fout, text, sdate):
        stampurl = StampUrl(sdate)

        for fss in recomb.split(text):
                stampurl.UpdateStampUrl(fss)

                # speaker detection
                speakerg = respeakervals.match(fss)
                if speakerg:
                        anamestamp = speakerg.group(1) or speakerg.group(2) or ""
                        spstr = string.strip(speakerg.group(3))
                        spstrbrack = speakerg.group(4)
                        if not spstr:
                                continue
                        try:
                                #print "spstr", spstr, ",", spstrbrack
                                result = memberList.matchwmsname(spstr, spstrbrack, sdate)
                        except Exception, e:
                                raise ContextException(str(e), stamp=stampurl, fragment=fss)

                        # put record in thisplace
                        spxm = '%s<speaker %s>%s</speaker>\n' % (anamestamp, result.encode("latin-1"), spstr)
                        fout.write(spxm)
                        continue

                # nothing detected
                # check if we've missed anything obvious
                if recomb.match(fss):
                        raise ContextException('regexpvals not general enough', fragment=fss, stamp=stampurl)
                if remarginal.search(fss):
                        raise ContextException(' marginal speaker detection case: %s' % remarginal.search(fss).group(0), fragment=fss, stamp=stampurl)

                fout.write(fss)

