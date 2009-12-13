#! /usr/bin/env python2.4

import re
import os
import glob
import sys
import tempfile
import shutil
import sets
import time

pardir = os.path.abspath(os.path.join(sys.path[0], '..'))
sys.path.append(pardir)
os.chdir(pardir)
from resolvemembernames import memberList
from contextexception import ContextException
from BeautifulSoup import BeautifulSoup
from patchtool import RunPatchTool
from xmlfilewrite import WriteXMLHeader
from standingutils import shortname_atts
import miscfuncs 
import codecs

from miscfuncs import FixHTMLEntities

streamWriter = codecs.lookup('utf-8')[-1]
sys.stdout = streamWriter(sys.stdout)

# data dir paths
toppath = miscfuncs.toppath
pwcmdirs = miscfuncs.pwcmdirs
pwxmldirs = miscfuncs.pwxmldirs
pwpatchesdirs = miscfuncs.pwpatchesdirs
tmppath = miscfuncs.tmppath


def debug(text, *args):
    """Print the text if the debug flag is set to True"""
    if debug_flag: 
        print text, args
    
def ApplyPatches(filein, fileout, patchfile):
    shutil.copyfile(filein, fileout)
    status = os.system("patch --quiet %s <%s" % (fileout, patchfile))
    if status == 0:
        return True
    print "blanking out failed patch %s" % patchfile
    print "---- This should not happen, therefore assert!"
    assert False

class StandingSoup(BeautifulSoup):
    
    # pre-parsing regex-based cleanup   
    myMassage = [
        
        # Remove gumph
        (re.compile('</?center>'), lambda match: ''),
        (re.compile('&nbsp;'), lambda match: ''),
        (re.compile('<(B|b)>House of Commons</(B|b)>.*?<(BODY|body)>'),  lambda match: ''),
        
        # Unconverted formatting from the document's previous encoding
        (re.compile('(<(H|h)5>)(<<\d+>)'), lambda match: match.group(3) + match.group(1)),
        (re.compile('<<(\d+)>'), lambda match: "<b>Column Number: %s</b></p>" % match.group(1)),
        
        # <UL> tags are abused 
        (re.compile('(<P>\s*</UL></FONT>)'), '</font></p><p>'),
        (re.compile('</UL></UL></UL>The following Members attended the Committee:'), '<p>The following Members attended the Committee:'),
        
        (re.compile('(<UL>)'), '<p>'),
        (re.compile('(</UL>)'), '</p>'),
        
        # Sometimes <a name=blah> tags are not getting closed
        (re.compile('(<a name="\d+">)(?!</a>)'), lambda match: match.group(1)+ '</a>'),
    
        # get rid of raw ampersands in the text
        (re.compile(' & '), ' &amp; '),
        (re.compile(' &c\.'), ' &amp;c.'),
        (re.compile('p&p'), 'p&amp;p'), # XXX!
        (re.compile('([A-Z0-9])&([A-Z0-9])'),lambda match: match.group(1) + '&amp;' + match.group(2) ),
 
        # Swap elements that are clearly the wrong way round
        (re.compile('(<b>Column Number: \d*?</b></p>)'), lambda match: match.group(1)+ '<p>'), 
        (re.compile('<p>\s*(<h\d[^>]*>)(?i)'), lambda match: match.group(1)),
        (re.compile('(<p[^>]*>)\s*((</(font|i|b|ul)>)+)'), lambda match: match.group(2) + match.group(1)),
        (re.compile('(<p[^>]*>)\s*(<b>)'), lambda match: match.group(2) + match.group(1)),
        (re.compile('((<(font|i|b)>)+)\s*(</p[^>]*>)'), lambda match: match.group(3) + match.group(1)),
        (re.compile('(<b>)\s*(<p[^>]*>)([^<]*?</b>)'), lambda match: match.group(2) + match.group(1) + match.group(3)),
        #(re.compile('(<P>)\s*(</UL></UL></UL>)'), lambda match: match.group(2) + match.group(1)),

        (re.compile('(<p[^>]*>[^<]*)<h4>(?i)'), lambda match: match.group(1) + '</p> <h4>'),
    ]    
    
class ParseCommittee:
    
    def id(self):
        """Return the current id counter for use in uniquely tagging speeches
        """
        return 'uk.org.publicwhip/standing/%s.%s.%s' % (self.sitting_part, self.idA, self.idB)

    def display_speech_tag(self):
        """Display a speech tag with the current speaker, timestamp and 
        column number and a unique id number
        """
        # set up the info
        speaker_str = 'nospeaker="true"'
        timestamp_str = ''
        col_str = ''
        if self.speaker: speaker_str = self.speaker     
        if self.timestamp: timestamp_str = ' time="%s"' % self.timestamp
        if self.column_number:  col_str = ' colnum="%d"' % self.column_number
            
        # increment the id
        self.idB += 1
                        
        # end any existing speech
        self.close_speech()     
        self.out.write('<speech id="%s" %s%s url="%s"%s>\n' % (self.id(), speaker_str, timestamp_str, self.url, col_str))
        self.in_speech= True
        
    def display_para(self, tag, indent=False, amendmentText=False):
        """Output a paragraph of text. 
        """ 
        # deal with the column numbers
        for col in tag.findAll('div', {'class':'Column'}):
            self.parse_column(''.join(col(text=True)))
            col.replaceWith("&nbsp;")

        for col in tag.findAll(text=re.compile('Column Number:\s*?(\d+)')):
            self.parse_column(col)
            col.replaceWith("&nbsp;")
          
        # timelines
        for time in tag.findAll('h5', {'class': None}):
            self.parse_timeline(time)  
            time.replaceWith(" ")  
        
        # don't include any h4 content - it will be handled
        # directly in the main parse_xxx_sitting_part function
        # XXX - nope, as this then loses e.g. in the Chair headings which call here
        ptext = ''.join(tag(text=True)) # [node for node in tag(text=True) if getattr(node.parent, 'name', 'None') != 'h4'])
        
        ptext = re.sub("\s+", " ", ptext).strip()
        if not ptext: return 
      
        indent_str = ''
        if indent: indent_str = ' class="indent"'
        
        if amendmentText:
            level = "unknown"
            if tag.get('class', None):
                mLevel = re.match("hs_AmendmentLevel(\d+)", tag['class'])
                if mLevel: level = mLevel.group(1)
            amendment_str = ' amendmenttext="true" amendmentlevel="%s"' % level
        else:
            amendment_str = ''
        
        mDivision = re.match('\[?\s*Division\s*No.\s*(\d+)?', ptext)
        if mDivision: 
            divNum = "unknown"
            if mDivision.group(1): divNum =  int(mDivision.group(1))
            self.parse_division(tag, divNum)
            return

        # get rid of empty nodes
        contents = [node for node in tag if ((not node.string) or node.string.strip())]    
        
        if len(contents) == 0: return 
        firstNode = contents[0]
        if getattr(firstNode, 'name', None) in ['a','b'] and ptext.find(':') != -1 and len(ptext.strip()) -1 > ptext.find(':') and len(contents)>1:
            # an amendment
            if re.match('Amendment proposed.*?', ptext):
                self.non_speech_text()
            # a vote
            elif re.match('The Committee divided.*?', ptext):
                self.non_speech_text()
            elif re.match('Question put,.*?', ptext):
                self.non_speech_text()
            # new question heading for oral evidence
            elif re.match('Q\d+', ptext):
                self.non_speech_text()
            else:
                # see if we can find a speaker
                speaker, bracket, text = self.parse_speaker(ptext) 
                self.speaker = memberList.matchcttedebatename(speaker, bracket, self.date, self.external_speakers)
                contents.remove(firstNode)
                self.display_speech_tag()
                self.out.write('<p%s%s>%s</p>\n' % (indent_str, amendment_str, text))
                return 
        
        # italicised text means no-one's speaking    
        elif getattr(firstNode, 'name', None) == 'i': 
            self.non_speech_text()
        
        # display the contents of the tag as a para
        text = self.render_node_list(contents)
        if not self.in_speech:
            self.display_speech_tag()
        if text: self.out.write('<p%s%s>%s</p>\n' % (indent_str, amendment_str, text))    
                
    def display_heading(self, text, type):
        """Output a major or minor heading
        """
        self.close_speech(type)
        # increment the section ID
        self.idA += 1
        # restart the speech counter
        self.idB = 0
        if not self.in_heading:
            timestamp_str = ''
            if self.timestamp: timestamp_str = ' time="%s"' % self.timestamp        
            self.out.write('<%s-heading id="%s"%s nospeaker="true" url="%s">%s' % (type, self.id(), timestamp_str, self.url, text))
            self.in_heading = type
        else:
            self.out.write(' - %s' % text)
    
    def display_chair(self, tag):
        """Output the text saying who's chairing - also set the 
        chairman so it can be used for disambiguation"""
        
        text = re.sub("\s+", " ", ''.join(tag(text=True)))
        mChair = re.match('\s*\[?\s?(.*?)\s*?in\s?the\s?Chair', text)
        if mChair:
            chair = self.split_on_caps(mChair.group(1))
            memberList.set_chairman(self.clean_text(chair))
        self.speaker = None
        self.display_speech_tag()
        self.display_para(tag)

    def display_committee(self, chairlist, memberlist, clerknames, all_attending=False):
        """Output a committee tag with chairs, members, clerks etc. The all_attending
        flag indicates that all members are in attendence"""  
        ctte_tag = []
        clerks = [self.clean_text(name) for name in clerknames.split(',') if name.strip()] 
        ctte_tag.append('<committee>\n')
        ctte_tag.append('<chairmen>\n')
        for chairman in chairlist: ctte_tag.append(chairman)
        ctte_tag.append('</chairmen>\n')
        for member in memberlist:
            (orig_name, membername, bracket, party, attending) = self.parse_member_tag(member)
            matchtext = memberList.matchcttename(membername, bracket, self.date)
            if bracket: orig_name = '%s <span class="italic">(%s)</span>' % (orig_name, bracket)
            if party: orig_name += ' %s' % party 
            ctte_tag.append(self.render_committee_member(matchtext, orig_name, all_attending or attending))
        for clerk in clerks: ctte_tag.append('<clerk>%s</clerk>\n' % clerk)
        ctte_tag.append('</committee>\n')
        self.out.write(''.join(ctte_tag))
      
    def display_witnesses(self, witnesslist):
        """Output a list of witnesses"""
        
	self.out.write('<witnesses>\n')
	for witness in witnesslist:
            self.out.write('<witness>%s</witness>\n'%(witness))
	self.out.write('</witnesses>\n')

    def display_division(self, num, counts):
        """Output a division tag with aye and no counts
        """
        self.close_speech()
        self.idB += 1
        self.out.write( '<divisioncount id="%s" divnumber="%s" ayes="%d" noes="%d" url="%s">\n' % ( self.id(), num, counts['ayes']['count'], counts['noes']['count'], self.url ) )
        self.out.write( '<mplist vote="aye">\n' )
        self.out.write( counts['ayes']['names'] )
        self.out.write( '</mplist>\n' )
        self.out.write( '<mplist vote="no">\n' )
        self.out.write( counts['noes']['names'] )
        self.out.write( '</mplist>\n' )
        if counts['abstains']['count']:
            self.out.write( '<mplist vote="abstains">\n' )
            self.out.write( counts['abstains']['names'] )
            self.out.write( '</mplist>\n' )
        self.out.write( '</divisioncount>\n' )
            
    def render_table(self, table):
        """Use some heuristics to figure out if a table is genuine data or just bad HTML
        and ignore or diaply it accordingly""" 
        tabletext = []
        prev = table.previous
        if getattr(prev, 'name', None) == 'a' and prev.get('name', None) == 'end':
             pass
        elif not table(text=re.compile('[a-z]')):
             pass
        elif table('font', {'size': '+3'} ):
             pass
        elif table('h1', {'align': 'left'} ):
             pass
        elif re.search('attended the Committee', (table.findPrevious(text=re.compile('[a-z]')) or '').strip()):
             pass
        elif len(table.findAllPrevious(text=re.compile('[a-z]'))) < 6:
             pass
        else: 
             tabletext.append('<data>')
             tabletext.append(str(table))
             tabletext.append('</data>')
             table.contents = []
             
        return ''.join(tabletext)  
        
    def render_committee_member(self, match_text, orig_text, attending):
         """Return the text for a committee member listing"""
         text = '<mpname'
         text += match_text  
         if attending: 
            text += 'attending="true"'
         else:
            text += 'attending="false"'
         text += '>%s</mpname>\n' % orig_text
         return text
     
    def render_node_list(self, nodelist):
        return ''.join(self.render_tree(nodelist, []))
        
    def render_tree(self, nodelist, textlist=[]):
        """Produce text for displaying a tree of nodes by 
        recursing over the elements"""
        for node in nodelist:
            if getattr(node, 'contents', None): # Tag, not String
                if node.name == 'table':
                    textlist.append(self.render_table(node))
                elif node.name == 'i':
                    textlist.append('<span class="italic">')
                    textlist = self.render_tree(node.contents, textlist)
                    textlist.append('</span>')
                elif node.name == 'font' and node.get('size') == '-1' :
                    textlist.append('<span class="indent">')
                    textlist = self.render_tree(node.contents, textlist)
                    textlist.append('</span>')
                else:
                    textlist = self.render_tree(node.contents, textlist)
            # empty tag
            elif (getattr(node, 'name', None)):
                pass
            else: 
                 textlist.append(re.sub('\s+', ' ', node)) # not clean_text as we don't want to strip spaces next to tags
        return textlist
        
    def add_text_to_votelist(self, node, votelist, stop_pattern):
        """Iterate through nodes in an old-style division, process the 
        text and add them to a list of votes"""
        
        while node.findNext(text=True) and not re.search(stop_pattern, node.findNext(text=True)):
            # get rid of whitespace and column numbers
            if node.strip() and not re.search('Column Number', node): 
                # sometimes content for one mp gets split over a couple of nodes
                # check for leading and trailing brackets and stick it back together
                if node.strip()[0] in ['(', ')']  or (votelist and votelist[-1][-1] == '('):
                    votelist[-1] += node.strip()
                else:
                    votelist.append(node.strip())
            node = node.findNext(text=True)
            
        return (node, votelist)
               
    def add_member_to_votecount(self, votecounts, vote_type, name):
        """Parse a member name from an old-style division, match it,
        and add the appropriate elements to a votecount data structure"""
        votecounts[vote_type]['count'] += 1
        (mpname, bracket) = self.reverse_name(name)
        mpname = self.split_on_caps(mpname)
        mpid = memberList.matchcttename(mpname, bracket, self.date)            
        votecounts[vote_type]['names'] += '\t<mpname %s vote="%s">%s</mpname>\n' % (mpid, votecounts[vote_type]['name'], name)        
        return votecounts
  
    def parse_old_division(self, tag, divisionNum):
        """Parse the old-style division listings"""
           
        # data structure for the results
        votecounts = self.vote_dict()
        
        debug("division %s" % divisionNum)
        # sometimes the votes are all in a <p> tag
        ayes = tag.findNextSibling('p')
        noes = ayes.findNextSibling('p')
        ayelist = ayes(text=True)  
        nolist = noes(text=True)
        ayevote = ''
        novote = ''
        # vote headers
        if ayelist: ayevote = ayelist[0].strip().lower()
        if nolist: novote = nolist[0].strip().lower()
        
        # Sometimes they have some arbitary mix of <p> tags and linebreaks
        if len(ayelist) == 1 and (len(nolist) in [0,1] or novote != 'noes'):
            ayelist = []
            nolist = []
            counter = ayes(text=True)[0]
            (counter, ayelist) = self.add_text_to_votelist(counter, ayelist, '^NOES$')
            counter = counter.findNext(text=True)
            noes = counter
            (counter, nolist) = self.add_text_to_votelist(counter, nolist, 'Question (accordingly|put)')
            # vote headers
            if len(ayelist) > 0:
                ayevote = ayelist[0].lower()
            else:
                ayevote = ''
            if len(nolist) > 0:
                novote = nolist[0].lower()    
            else:
                novote = ''        
        debug("AYES", ayelist)
        debug("NOES", nolist)  
        
        if ayevote != 'ayes': raise ContextException, "Bad division aye vote count heading: %s" % ayevote
        if novote != 'noes': raise ContextException, "Bad division no vote count heading: %s" % novote
        
        if len(ayelist[1:]) == 0 and len(nolist[1:]) == 0:
            raise ContextException, "No votes found in division %s" % divisionNum
        # process the ayes
        for aye in ayelist[1:]: 
            self.add_member_to_votecount(votecounts, ayevote, aye)
        # process the noes, filtering out abstensions    
        for no in nolist[1:]:  
            mAbstain = 'did not vote|NO VOTE|DID NOT VOTE'
            if re.search(mAbstain, no):
                self.add_member_to_votecount(votecounts, 'abstains', re.sub(mAbstain, '', no))
            else:
                self.add_member_to_votecount(votecounts, novote, no)
        # output the division
        self.display_division(divisionNum, votecounts)
        ayes.contents = []
        noes.contents = []
    
    def parse_division(self, tag, divisionNum):      
        """Parse a new style division listing"""
        
        votecounts = self.vote_dict()
        debug("division %s" % divisionNum)
        
        divisionHeader = tag.findNextSibling('h5', {'class' : "hs_DivListHeader" })
        if not divisionHeader: return self.parse_old_division(tag, divisionNum)
        
        # ayes header
        firstvote = ''.join(divisionHeader(text=True)).strip().lower()
        
        if not firstvote in ['ayes', 'noes']: raise ContextException, "Bad division vote count heading: %s" % firstvote
        
        node = divisionHeader.findNext('div')
        # get all the aye votes
        while getattr(node, 'name', None) != 'h5':
             if getattr(node,'name', None) == 'div' and node.get('class', None) == 'hs_Para' :
                ptext = re.sub("\s+", " ", ''.join(node(text=True))).strip()
                if ptext:
                    self.add_member_to_votecount(votecounts, firstvote, ptext)
                    node.contents = []  
             node = node.next
        
        # noes header    
        secondvote = node.b.string.strip().lower()
        if not firstvote in ['ayes', 'noes']: raise ContextException, "Bad division vote count heading: %s" % secondvote
        
        # no votes 
        node = node.next
        while(node):
            if getattr(node,'name', None) == 'div' and node.get('class', None) == 'hs_Para' and getattr(node, 'contents', None):
                contents = [subnode for subnode in node.contents if not subnode.string or subnode.string.strip()]
                if not contents:
                    node = node.next
                    continue
                if getattr( contents[0], 'name', None ) == 'i':
                    self.display_division(divisionNum, votecounts)  
                    return 
                else: 
                    ptext = " ".join( node( text=True ) ).strip()
                    # possible chair tie-break
                    if re.search('Chairman', ptext):
                        chairvote = None
                        if re.search('Ayes', ptext):
                            chairvote = "ayes"
                        elif re.search('Noes', ptext):
                            chairvote = "noes"
                        if not chairvote: raise ContextException, "Couldn't determine chairman's tie-breaking vote"
                        self.add_member_to_votecount(votecounts, chairvote, "Chairman")         
                    else:
                        self.add_member_to_votecount(votecounts, secondvote, ptext)              
                node.contents = []
            node = node.next
            
    def parse_chair_tag(self, tag):
        """Parse the new-style committee chair tag. Return the member's
        name and whether or not they're in attendence"""
        attending = False
        chairname = tag.findNext(text=re.compile('[a-z]'))
        chairname = self.split_on_caps(chairname)
        chairname = self.clean_text(chairname)
        # strip anything following a comma
        chairname = re.sub(",.*", "", chairname)
        if re.search("&(dagger|#134);", tag.previousSibling.string): attending = True
        return (chairname, attending)

    def parse_chair_string(self, text):
        """Parse the old-style committee chairman text. Return the member's
        name and whether or not they're in attendence"""
        attending = False
        newtext = re.sub('&(dagger|#134);', '', text)
        if newtext != text: attending = True
        return (self.clean_text(newtext), attending)
        
    def parse_member_tag(self, text):
        """Parse the new-style committee member tag. Return a tuple of:
        orig_name - name as it appears in text
        membername - name cleaned up and un-reversed
        bracket - any bracketed text (usually the constituency)
        memberparty - second bracketed text if present 
        attending - bookean indicating if the member is attending"""
        attending = False
        newtext = re.sub('&(dagger|#134);', '', text)
        if newtext != text: attending = True
        mMember = re.match('([^(]*?)\s*?(\([^)]*?\))?\s*?(\([^)]*?\))?\s*$', newtext)
        if not mMember: raise ContextException, "Couldn't parse committee member %s" % newtext 
        orig_name = mMember.group(1)
        bracket = mMember.group(2)
        memberparty = mMember.group(3)
        text_to_reverse = orig_name
        if bracket: text_to_reverse += bracket
        (membername, bracket) = self.reverse_name(text_to_reverse)
        if memberparty: memberparty = self.clean_text(memberparty)
        return (orig_name, membername, bracket, memberparty, attending) 
    
    def parse_h1_tag(self, tag):
        if tag.b:
            text = self.render_node_list(tag.b.contents)
        else:
            text = self.render_node_list(tag.contents)
        self.display_heading(text, 'major')
    
    def parse_column(self, text):
        """Extract a column number"""
        mNum = re.match('Column (N|n)umber: (\d+)', text)
        if not mNum: raise ContextException, "Couldn't set column number %s" % text
        self.column_number = int(mNum.group(2))
        debug("Column:", self.column_number)

    def parse_timeline(self, tag):
        """Extract a timestamp"""
        text = ''.join(tag(text=True))
        mTime = re.match('(\d\d?)\.?(\d\d)?\s*?(a|p)\.?m\.?', text)
        if mTime:
            hour = int(mTime.group(1))
            if hour<12 and mTime.group(3) == 'p':
                hour += 12
            self.timestamp = "%s:%s" % (hour, mTime.group(2) or '00')
        elif re.match('12\s*?noon|', text):
            self.timestamp = "12:00" 
        elif re.match('12\s*?midnight', text):
            self.timestamp = "00:00"
        else:
            raise ContextException, "Content other than timeline in expected timeline tag: %s" % tag.renderContents() 
        
             
    def parse_old_committee(self, tag):
        """Parse the old-style committee membership listing"""
        self.close_speech()
        chairlist = []
        memberlist = []
        clerks = ''
        in_committee = True
        extra_attendees = []
        
        # get rid of column numbers
        for col in tag.findAllNext(text=re.compile('Column Number')):
            self.parse_column(col)
            col.extract()
        
        members = tag.findAllNext(text=True)[1:] 
        members = [t.strip() for t in members if t and re.sub('&nbsp;', '',t).strip() ]
        if len(members) == 0: return 
        
        pChair = "\((Chairm(a|e)n\))?$"
        pClerks = "\s*?Committee\s*?Clerks"
        
        # chairman may not be marked - if we know it, we can mark it
        chairtext = memberList.matchcttename("Chairman", None, self.date)
        
        for member in members:
            if not member.strip(): continue
            if re.match('^\(|Chairm(a|e)n|\)|' + pClerks, member): continue
            if re.match('The following also attended.*?|Members attending pursuant', member):
                in_committee = False
            elif re.match('.*?,'  + pClerks, member) or re.match('.*?,$', member) and re.match(pClerks, members[members.index(member)+1]):
                clerks = re.sub(',' + pClerks,'', member)
                continue
            if in_committee:
                if re.match(".*?" + pChair, member):
                    chairman = re.sub(pChair, "", member)
                    chairman, bracket = self.reverse_name(chairman)
                    matchtext = memberList.matchcttename(chairman, bracket, self.date)
                    chairlist =  [self.render_committee_member(matchtext, chairman, attending=True) ]
                else:
                    memb, attending = self.parse_chair_string(member)
                    matchtext, bracket = self.reverse_name(memb)                    
                    matchtext = memberList.matchcttename(matchtext, bracket, self.date)
                    if matchtext == chairtext:
                        chairlist = [self.render_committee_member(matchtext, memb, attending=True) ]
                    else: 
                        if member: memberlist.append(member)
            else:
                extra_attendees.append('<p>%s</p>' % member)
        
        if not memberlist: raise ContextException, "Can't find committee members"
        if not chairlist:
            if chairtext: 
                chairlist = [self.render_committee_member(chairtext, memberList.get_chairman(), attending=True) ]
            else:
                raise ContextException, "Can't find committee chairman"
        
        self.display_committee(chairlist, memberlist, clerks, all_attending=True)
        if extra_attendees:
            self.non_speech_text()
            self.out.write(''.join(extra_attendees))
            debug("Extra attendees", extra_attendees)
        tag.contents = []
        for t in tag.findAllNext(text=True)[1:]: 
            t.parent.contents = []
                
    def parse_committee(self, tag):
        """Parse slightly-newer-style committee member list"""    
        self.close_speech()
        chairtext = None
        chairRe = "\s*Chairm(e|a)n:?"
        override_chair_attendance = False
        members = []
        clerks = []
        got_members = False
        # try using tags to break out  members
        candidates = tag.findAllNext({'p':True, 'h4':True}) 
        
        filtered_candidates = []
        # search until we find the end (markup is too variable)
        for candidate in candidates: 
            
            text = ''.join(candidate(text=True))
            if re.search('(A|a)ttended the Committee', text):
                got_members = True
                candidate.contents = []
                break
            filtered_candidates.append(candidate)
        
        # using tags didn't work, back to regex
        if not got_members:
            filtered_candidates = []
            tag = tag.nextSibling
            chairTag = tag.findNext(text=True)  
            while not re.search('(A|a)ttended the Committe(e)?', str(tag)):
                if str(tag).strip(): filtered_candidates.append(tag)
                tag = tag.nextSibling
            tag.extract()
            # reassemble text and split on the only delimiters - <br /> tags
            long = ''.join([str(x) for x in filtered_candidates])    
            long = re.sub('.*?</p>', '', long)    
            long = re.sub('</?i>|</?b>', '', long)    
            long = re.sub('<a name=".*?">', '', long)
            long = re.sub('</a>', '<br />', long)
            long = re.sub('</div>', '', long)
            elements = long.split('<br />')
            for element in elements:    
                if re.search('Committee Clerk', element):
                    clerks.append(element)
                else: 
                    if element.strip(): members.append(element)
        else:
            for candidate in filtered_candidates:     
                text = ''.join(candidate(text=True)).strip()
                if text:
                    if re.search('Committee Clerk', text):
                        clerks.append(text)
                    elif re.search('Column Number', text):
                        self.parse_column(text)
                    else: 
                        members.append(candidate)
            chairTag = members[0].findNext(text=re.compile('[a-z]'))  
            members = [''.join(member(text=True)).strip() for member in members[1:] if ''.join(member(text=True)).strip()]
           
        if not members: raise ContextException, "Can't find committee members"
     
        #chairman
        mChair = re.match(chairRe + ".*?", chairTag)
        if not mChair: raise ContextException, "Can't find committee chairman"
        if re.match(chairRe + "\s*$", chairTag):
            chairTag = chairTag.findNext(text=re.compile("[a-z]"))
            chairtext = re.sub(':', '', chairTag)
        else:
            chairtext = re.sub(chairRe, "", chairTag)
            
        while re.search("(&dagger;|&#134;|,|and)\s*?$",chairtext):
            chairTag = chairTag.findNext(text=re.compile("[a-z]"))
            chairtext += chairTag  
        chairs = []
        chairlist = re.split(',|and',chairtext)
        #If there's only one chair, their attendance is not 
        # marked with the usual dagger 
        if len(chairlist) == 1: override_chair_attendance = True
        for chair in chairlist:
            (chairname, attending) = self.parse_chair_string(chair)
            matchtext = memberList.matchcttename(chairname, None, self.date)
            chairs.append( self.render_committee_member(matchtext, chairname, override_chair_attendance or attending))

        # clerks
        clerknames = ''
        for clerk in clerks:
           clerknames += re.sub(',?\s*?Committee\s*?Clerk(s)?','',clerk)

        self.display_committee(chairs, members, clerknames)

        # get rid of the contents
        for element in filtered_candidates: element.contents = [] 
    
    def parse_new_committee(self, soup):
        """Parse new-style committee member list""" 
        # Committee description
        committee = soup.find("h4", { "class" : "hs_CLHeading" })
        if not committee: raise ContextException, "Couldn't find committee description"

        # find the chairmen
        chairmen = soup.find("div", { "class" : "hs_CLChairman" })
        if not chairmen: raise ContextException, "Couldn't find chairmen"    
        chairTags = chairmen.findAll('a')        
        if not chairTags:raise ContextException, "Couldn't populate chairmen"

        chairlist = []    
        for tag in chairTags:
            (chairname, attending) = self.parse_chair_tag(tag)
            if len(chairTags)==1: attending = True # They don't bother with a dagger if there's only one chairman
            matchtext = memberList.matchcttename(chairname, None, self.date)
            chairlist.append( self.render_committee_member(matchtext, chairname, attending))
                 
        # find the members
        cttememberTags = soup.findAll("div", { "class" : "hs_CLMember" })
        if not cttememberTags: raise ContextException, "Couldn't find committee members"
        memberlist = [''.join(memberTag(text=True)).strip() for memberTag in cttememberTags] 

        #find the clerks
        ctteclerkTags = soup.findAll("div", { "class" : "hs_CLClerks" })
        clerks = ''
        for clerkTag in ctteclerkTags: clerks += clerkTag.contents[0]
        
        self.display_committee(chairlist, memberlist, clerks)

        #find any witnesses
        ctteWitnessTag = committee.findNext("h4", {"class" : "hs_CLHeading"})
        if ctteWitnessTag and ''.join(ctteWitnessTag(text=True)).strip() == "Witnesses":
            ctteWitnesses = soup.findAll("div",{"class" : "hs_CLPara"})
	    witnesslist = [''.join(witnessTag(text=True)).strip() for witnessTag in ctteWitnesses]
            self.display_witnesses(witnesslist)
            self.external_speakers = True

    def parse_speaker(self, text):
        """Parse the speaker name from the beginning of a speech"""
        speaker, text = text.split(':', 1)
        speaker = self.clean_text(speaker)
        bracket = None
        mSpeaker =  re.match('\s*(.*?)\s*\((.*?)\)\s*(\(.*?\))?', speaker)
        if mSpeaker:
            speaker = mSpeaker.group(1)
            bracket = mSpeaker.group(2)
            bracket = self.split_on_caps(bracket)
        
	# questions are now part of the para
        mQuestion = re.match('(Q\s*?\d+)\s*(.*)', speaker)
        if mQuestion:
            question = mQuestion.group(1)
            speaker = mQuestion.group(2)
            self.close_speech()
            self.display_speech_tag()
            self.out.write('<p>%s</p>\n' % (question))
	speaker = self.split_on_caps(speaker)
        return (speaker, bracket,text)
    
    def parse_sitting_part(self, sitting_part):
        
        filename = os.path.join(pwcmdirs, 'standing', '%s.html' % sitting_part)
        patchfile = os.path.join(toppath, "patches", 'standing', '%s.html.patch' % sitting_part)
        
        # speech id parts
        self.idA = 0
        self.idB = 0
        atts = shortname_atts(sitting_part)
        self.sitting_part =   sitting_part
        self.committeedate =  atts['committeedate']
        self.letter =         atts['letter']
        self.sittingnumber =  atts['sittingnumber']
        self.sittingpart =    atts['sittingpart']
        self.date =           atts['date']
        self.column_number =  ''
        self.timestamp =      ''
        self.in_speech =      False
        self.in_heading =     False
        self.speaker =        None
        self.baseurl = 'http://www.publications.parliament.uk'
        self.external_speakers = False
        
        if os.path.isfile(patchfile):
            patchtempfilename = tempfile.mktemp("", "standing-applypatchtemp-", tmppath)
            ApplyPatches(filename, patchtempfilename, patchfile)
            filename = patchtempfilename

        fp = open(filename)
        text = fp.read()
        # pre-massage massage. Pull out the divisions and replace linebreaks 
        # with <br /> tags so that we can actually distinguish names
        if not re.search("hs_Para", text):
            patt = re.compile("((Committee (having )?divided|Question put(?! and agreed)).*?(Question|Amendment) (accordingly|put))", re.DOTALL)   
            text = re.sub(patt, self.replace_linebreaks, text)
        text = re.sub("\s+", " ", text)
        soup = StandingSoup(text,markupMassage=StandingSoup.myMassage)
        fp.close()
        
        # open the xml file
        self.out = open( os.path.join(pwxmldirs,'standing','%s.xml.new' % sitting_part), 'w')
        self.out = streamWriter(self.out)
        
        WriteXMLHeader(self.out, 'utf-8')
        self.out.write('<publicwhip>')
        
        # url that the content came from
        self.url = soup.pagex['url']        
        paras =  soup.findAll('div', {"class" :"hs_Para"})
        if paras:            
            self.parse_new_sitting_part(soup)
        else:
            self.parse_old_sitting_part(soup)
         
        # close any open speech
        self.close_speech()
        self.out.write('</publicwhip>\n')
        self.out.close()
  
        newfile = os.path.join(pwxmldirs,'standing','%s.xml' % sitting_part)
        if os.path.exists(newfile):
            os.unlink(newfile)
        os.rename( os.path.join(pwxmldirs,'standing','%s.xml.new' % sitting_part), newfile)
        
    def parse_new_sitting_part(self, soup):
        """Parse and convert a new-style (post 3/2006) Public Bill Committee transcript to XML"""                
        # Extract the bill title (first major heading) and the url for the bill
        url = None
        bill_title = soup.h1
        if bill_title.b:
            node = bill_title.b
        else:
            node = bill_title
        title = self.render_node_list(node.contents)
        plaintitle = ''.join(node(text=True))
        bill_link = soup.h3
        if bill_link:
            if bill_link.a:
                url = bill_link.a.get('href', None)
                url = re.sub('\s+', '', url)
            elif bill_link.parent.name == 'a':
                url = bill_link.parent.get('href', None)
                url = re.sub('\s+', '', url)
            bill_link.extract()
   
        bill_title.extract()
        # fall back to getting the title from the link
        if not plaintitle or not title:
            link_title = self.render_node_list(bill_link.contents)
            link_plain_title = ''.join(bill_link(text=True))
            if not link_title or not link_plain_title:
                raise ContextException, "PBC part is missing title"
            else: 
                title =  re.sub('\s*Committee\s*$', '', link_title)
                plaintitle = re.sub('\s*Committee\s*$', '', link_plain_title)
        if not url:
            raise ContextException, "No URL for bill found"
        url_str = '%s%s' % (url[0:7] != 'http://' and self.baseurl or '', url)
        self.out.write('<bill url="%s" title="%s">%s</bill>\n' % (url_str, plaintitle, title))
        
        self.parse_new_committee(soup)
                
        for tag in soup({'h1':True, 'h3':True, 'h4':True, 'div':True, 'page':True, 'a':True}):
            if tag.name == 'h1': 
                self.parse_h1_tag(tag)
            elif tag.name == 'page':
                self.url = tag['url']
            elif tag.name == 'a' and tag.get('name', None):
                self.url = self.url.split('#',1)[0]
                self.url = self.url + "#" + tag['name']
            else:  
                cssClass = tag.get('class', '')
                if (cssClass == 'Column'):
                    self.parse_column(''.join(tag(text=True)))
                elif (cssClass == 'hs_Timeline'):
                    self.parse_timeline(tag)
                elif (cssClass == 'hs_Para'):
                    self.display_para(tag) 
                elif (cssClass == 'hs_ParaIndent'):
                    self.display_para(tag, indent=True)
                elif (cssClass == 'hs_8Clause'):
                    self.display_heading(tag.string, "minor")
                elif (cssClass == 'hs_8ClauseQn'):
                    self.display_heading(tag.string, "minor")
                elif (cssClass == 'hs_8Question'):
                    self.display_heading(tag.string, "minor")
                elif (cssClass == 'hs_2BillTitle' or cssClass == 'hs_2DebBill'):
                    self.display_heading(tag.string, "minor")
                elif (cssClass == 'hs_76fChair'):
                    self.display_chair(tag)
                elif (cssClass == 'hs_7SmCapsHdg'):
                    self.speaker = None
                    self.display_speech_tag()
                    self.display_para(tag)
                elif (cssClass == 'hs_AmendmentHeading'):
                    self.display_heading(tag.string, "major")
                elif (cssClass in ['hs_AmendmentLevel0', 
                                   'hs_AmendmentLevel1',
                                   'hs_AmendmentLevel2',
                                   'hs_AmendmentLevel3', 
                                   'hs_AmendmentLevel4']):            
                    self.display_para(tag, indent=False, amendmentText=True)
                elif (cssClass == 'hs_brev'):
                    self.display_para(tag, indent=True)   
                #dealt with already
                elif (cssClass in ['hs_CLHeading', 'hs_CLHeading', 'hs_CLChairman', 'hs_CLMember', 'hs_CLMember', 'hs_CLClerks', 'hs_CLAttended']):
                    pass  
                #ignored
                elif (cssClass in ['hs_6fDate']):
                    pass
                else :
                    #print "NAME %s CLASS %s" % (tag.name, cssClass)
                    pass
    
    
    def parse_old_sitting_part(self, soup):
        """Parse and convert an older-style (1/2001-3/2006) Standing Committee transcript to XML"""     
        
        # Extract the bill title (first major heading) and the url for the bill
        urlnode = soup.find('a', href=re.compile('/cmbills/'))
        if urlnode:
            url = urlnode.get('href')
            title = self.render_node_list(urlnode)
            plaintitle = ''.join(urlnode(text=True))
            self.out.write('<bill url="%s%s" title="%s">%s</bill>\n' % (self.baseurl, url, plaintitle, title))

        #Tuesday 16 January 2001|Tuesday 16 January 2001(Afternoon)
        pDate = '\S*?\s+\d\d?\s+\S*?\s+\d\d\d\d(\(\S*?\))?'
        pTimeOfDay = '(\(Morning\)|\(Afternoon\))'
        
        tags = soup({'h1':True, 'h2':True, 'h3':True, 'h4':True, 'h5':True, 
                     'page':True, 'table':True, 'p': True, 'b':True })
        for tag in tags:
            text = ''.join(tag(text=True)).strip()
            if not text: continue
            if tag.name == 'b':
                if re.match('Column number: (\d+)', text): self.parse_column(text)
            elif tag.name == 'p':
                if tag.get('class', None):                        
                    if tag['class'] == 'smallcaps':
                        self.display_heading(text, "minor")
                    elif tag['class'] == 'class':
                        self.display_para(tag, indent=False, amendmentText=True)
                elif re.match("The Committee consisted of the following Members:\s*?$", text):
                    self.parse_committee(tag)
                elif re.match("The following (M|m)embers attended the Committ?ee:?", text):
                    self.parse_old_committee(tag)
                elif  re.match('Column (N|n)umber:? (\d+)', text):
                    self.parse_column(text)
                elif re.match('Examination of Witnesses', text):
                    self.external_speakers = True
                    self.display_heading(text, "minor")
                elif tag.get('align', None) and tag['align'] == 'center':
                    self.display_heading(text, "minor")
                else:
                    self.display_para(tag)
            if tag.name == 'h1':
                self.parse_h1_tag(tag)
            elif tag.name == 'h2':
                self.display_heading(text, 'major')
            elif tag.name == 'h3':
                if re.match(pDate, text) or re.match(pTimeOfDay, text):
                    pass
                elif re.search('in\s+the\s+Chair', text):
                    self.display_chair(tag)
                else:
                    self.display_heading(text, "major")
            elif tag.name == 'h4':
                if re.match(pDate, text) or re.match(pTimeOfDay, text):
                    pass
                elif re.search('in\s+the\s+Chair', text):
                    self.display_chair(tag)
                elif re.match('\s*\[?(New)?\s*(c|C)lause', text):
                    self.display_heading(text, "minor")
                elif re.match('\[?(New)?\s*Schedule', text):
                    self.display_heading(text, "minor")
                elif re.match('\[?Part', text):
                    self.display_heading(text, "minor")
                elif re.match('(\d\d?)\.?(\d\d)?\s*?(a|p)m', text) or re.match ('12\s*?noon|12\s*?midnight', text):
                    self.parse_timeline(tag)
                elif re.match('The Committee consisted of the following Members:', text):
                    self.parse_committee(tag)
                elif re.match('Examination of Witnesses', text):
                    self.external_speakers = True
                    self.display_heading(text, "minor")
                else:
                    self.display_heading(text, "minor")
            elif tag.name == 'h5':
                self.parse_timeline(tag)                
            elif tag.name == 'page':   
                self.url = tag['url']
            elif tag.name == 'table':
                self.out.write(self.render_table(tag))
                
    def replace_linebreaks(self, match):
        text = re.sub('\n', '<br />', match.group(1))
        return text
    
    def vote_dict(self):
        return {'ayes':     {'name': 'aye',
                             'count': 0,
                             'names': ''},
                'noes':     {'name': 'no',
                             'count': 0,
                             'names': ''},
                'abstains': {'name': 'abstain',
                             'count': 0,
                             'names': ''}}
    
    def close_speech(self, type=''):
        """Close any open speech or heading, set speaker to none"""
        if self.in_speech:
            self.out.write('</speech>\n')
            self.in_speech = False
        if self.in_heading and (type=='' or type!=self.in_heading):
            self.out.write('</%s-heading>\n' % self.in_heading)
            self.in_heading = False
        self.speaker = None

    def non_speech_text(self):
        """Close any open speech, set speaker to none, start a new speech tag"""
        self.close_speech()
        self.display_speech_tag()
    
    def clean_text(self, text):
        """Clean whitespace and strip"""
        text = re.sub('\s+', ' ', text)
        return text.strip()
    
    def split_on_caps(self, name):
        """Try and insert sensible whitespace into a munged name"""
        name = re.sub(r'([a-z])([A-Z])', r'\1 \2', name)
        name = re.sub(r'(Mac|Mc)\s([A-Z])', r'\1\2', name)
        name = re.sub(r'(\.)([A-Z])', r'\1 \2', name)
        return name
    
    def reverse_name(self, name):
        """Reverse a name in the form 'surname, firstname'"""
        bracket = None
        mName = re.match("^(.*?)\s*(\(.*?\))?$", name.strip())
        if mName:
            name = mName.group(1)
            bracket = mName.group(2)
        if bracket:
            bracket = self.clean_text(re.sub('\(|\)','',bracket))
        name = name.split(',')
        name.reverse()
        name = " ".join(name)        
        return (self.clean_text(name), bracket)
        
# Main function
quiet = False
force = False
patchtool = False
# verbose output
debug_flag = False

standing_dir = os.path.join(toppath, 'cmpages', 'standing')
standing_xml_dir = os.path.join(toppath, 'scrapedxml', 'standing')
if len(sys.argv)==2 and sys.argv[1] == '--patchtool':
    patchtool = True

# this code only works for transcripts from 2001 onwards
g = glob.glob(os.path.join(standing_dir, 'standing200*.html'))
g = [file for file in g if re.search('standing20(0[1-9]|[1-9][0-9])', file)]
g.sort()
parser = ParseCommittee()
for file in g:

    mnums = re.match(".*(standing)(.*?)([a-z]*)\.html$", file)
    sitting_part = mnums.group(1) + mnums.group(2) + mnums.group(3)
    patch_part = mnums.group(2) + mnums.group(3)
    outfile = os.path.join(standing_xml_dir, '%s.xml' % sitting_part)
    parsefile = ((not os.path.isfile(outfile)) or force or os.path.getmtime(file) > os.path.getmtime(outfile))
    
    while parsefile:
        try:
            print("Standing committees parsing %s..." % sitting_part)
            # raise ContextException, "One off"
            parser.parse_sitting_part(sitting_part)
            fil = open('%s/changedates.txt' % standing_xml_dir, 'a+')
            fil.write('%d,%s.xml\n' % (time.time(), sitting_part))
            fil.close()
            break
        except ContextException, ce:
            if patchtool:
                print "Problem parsing...", ce.description
                RunPatchTool('standing', patch_part, ce)
                continue
            elif quiet:
                debug(ce.description)
                debug("\tERROR! Failed on %s, quietly moving to next sitting part" % (sitting_part))
                break
            else:
                raise
