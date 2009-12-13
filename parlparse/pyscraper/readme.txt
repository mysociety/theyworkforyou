Special tags you can put in patches:

<another-answer-to-follow>
    In Written Answers indicates there are multiple answers to one set of
    questions.  This happens rarely, so we explicitly flag it.

<broken-name>
    Put this next to an MP name that is completely ambiguous, can't be
    parsed or we're sure we don't want to parse yet.  Currently I use
    this if something is genuinely ambiguous.  Things like ministerial
    offices without other context information should go in member-alises.xml.

<ok-extra-qnum>
    Wrans stuff checks for extra question numbers of the form [87844], as
    this often picks up errors.  This marker says that a particular qnum
    is fine - it is a link to another question for example.

<wrans-question>
	This is a Written Answer question.  Normally the words "To ask" mark
	this.  When they are missing you can either add them in, or add this
	tag.

<explicit-end-division>
	Marks the end of a division listing, for when the parser is having
	trouble otherwise telling.


The following are for handling those cases where the a new download of 
the html has been parsed and the new xml doesn't match gidwise the old 
xml file.  

We adapt the bend the values of the new file so it matches the old file 
which there may already be links to.  


<parsemess-misspeech type="speech|heading" redirect="up|down|nowhere"/>
    Insert a place marker for a speech or heading that is missing 
    from the new version.  

<stamp parsemess-missgid="4"/>
    The gids are numbered from 0 onwards from the first new speech or 
    heading in a column.  Put this command before the speech numbered 
    4, say, and it will get the code 4-1 instead, and the fifth 
    speech will get labeled 4.  
    Extra speeches in the new xml are acceptable so long as the numbers 
    remain compatible with the old codes.  



<stamp parsemess-colnum="888 --or-- 888W"/>
    This resets the column number (use W or WS after the number if in
    written answers or westminster hall).  Please avoid destroying
    the structure of a column number that's already there (all the ul's and
    p's preceding and trailing it) or the regexp won't find it.
    You can put this message just before the speech that's gone into
    the wrong column and everything will be fine.

<stamp parsemess-colnumoffset="2 or 0 or -2"/>
    This offsets the column number by the specified amount until another
    offset changes it (probably back to 0). This is due to Hansard
    renumbering columns wholesale during the cm->vo transition, and me
    not wanting to add hundreds of parsemess-colnums. :)

<stamp parsemess-ignorenamemismatch="yes"/>
	In this column this signals the xmlwriter to ignore mismatches in the name 
	of the speakers when comparing records if it looks like there's 
	been a genuine correction in the versions

