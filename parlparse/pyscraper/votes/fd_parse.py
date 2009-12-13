# vim:sw=8:ts=8:et:nowrap

# This is an experimental library to try to assist parsing complicated and
# untidy files. It could be done with a large regular expression, but
# experience shows that it then becomes unmanageably difficult.

import sys
import re
import xml.dom
import datetime
import copy
import string


# Globals

# I haven't tried to allow more than one instance of this module to run
# concurrently. 

# A dictionary of parsers defined by DEFINE. 
# Key, is the name of the parser, the value is a DEFINE object (see below).

standard_parsers={}

# The encoding to use when doing str() conversion of objects -- and therefore
# of printing. Internally unicode strings should be used.

encoding='UTF-8'

# These are imported so that they can be used in evaluate. The way expressions
# are handled could be improved by taking tighter control over the namespsace
# available. This could be used to avoid this kind of clumsy importation.

from fd_core import fromiso, nextday, nextdayinstance, daynum, monthnum, cardinalnum, Template

# utility functions

def sub(template):
	'''sub substitutes expressions of the form %(thing) into a template.

	The idea of sub is to make patterns easier to read. Rather than splitting patterns up, or repeating large incomprehensible regular expressions, sub creates a parser object from a regular expression but substitutes the parser thing for %(thing).

	template is a string containing instances of %(thing)
	values is a dictionary containing thing as a key pointing to a Parse object.

	A Parse object is constructed from the template as a SEQuence of Pattern objects, interspersed with DEFINE objects for each %(thing). %(thing) is replaced by DEFINE('thing', values[thing]).

	If %(thingN) is used, where N is a number, DEFINE('thingN'...) is used.'''
 
	pos=0
	s=[]
	accumulator=''
	for mobj in re.finditer('%\((?P<word>[a-zA-Z]*)(?P<no>\d*)\)', template):		
		parser_name=mobj.group('word')
		try:
			p=standard_parsers[parser_name]
			
		except KeyError:
			raise Exception, "Cannot find standard parser %s in %s" % (parser_name, template)
#		print pos, accumulator, s, parser_name, mobj.start(), mobj.end()
		newp=DEFINE(
			p.name+mobj.group('no'),
			p.parser,fragment=p.fragment)

		accumulator=accumulator+template[pos:mobj.start()]
		if newp.fragment:
			accumulator=accumulator+newp.match.pattern
		else:
			if len(accumulator)>0:		
				s.append(pattern(accumulator))
				accumulator=''
			s.append(newp)
		pos=mobj.end()

	if pos < len(template):
		accumulator=accumulator+template[pos:]
		s.append(pattern(accumulator))

	S=SEQ(*s)

	return S


def evaluate(template, env):
	'''Evaluates a Template object, treating $var as a variable to be looked up in env, which is a dictionary of variable:values.'''
	
	valstring=template.substitute(env)
	try:
		value=eval(valstring)
	except (SyntaxError, NameError, ValueError, AttributeError), arg:
		raise Exception, "%s[%s] in %s (before substitution) %s (after substitution" % (sys.exc_info()[0], arg, template, repr(valstring))
	return value

def clean(s):
	'''clean removes certain HTML tags etc from a string

	In order to generate valid XML, there can be no tags etc in the
	value of an attribute.'''

	s=s.replace('<i>','')
	s=s.replace('</i>','')
	
	s=s.replace('&nbsp;',' ')
	s=s.replace('&','&amp;')

	#s=s.replace('\x99','&#214')

	return s

def debug(s):
	#print s
	pass

def str_flatten(l, divider=''):
	'''str_flatten concatenates a list of strings into a single string'''

	return divider.join(l)

# Result classes

def trunc(s):
	LIM=128
	if len(s) > LIM:
		return s[:128] + '...'
	else:
		return s

class Result:
	'''The base class for a result returned by a parser.'''

	def __init__(self, force=False, label='Result'):
		self.force=force
		self.label=label
		self.success=True
		self.matched_string=''
		self.delta=None

#	def text(self):
#		return "text no longer supported"
#		#str_flatten(self.values)
#
	def __repr__(self):
		f=self.force and 'force=True' or ''
		return "%s(%s)" % (self.label, f)

	def __unicode__(self):
		return repr(self)

	def __str__(self):
		u=unicode(self)
		return u.encode(encoding)

class CompoundResult(Result):
	def __init__(self, result, label='CompoundResult'):
		Result.__init__(self, result.force, label)
		self.result=result
		self.success=result.success

		if result.matched_string:
			self.matched_string=result.matched_string
		else:
			self.matched_string=''

		if result.delta:
			self.delta=result.delta

class DefineResult(CompoundResult):
	def __init__(self, result, name, expand):
		CompoundResult.__init__(self, result, 'DefineResult')
		self.name=name
		self.expand=expand

	def __unicode__(self):
		if self.expand:
			return unicode(self.result)
		elif self.success:
			return u"%s" %  self.name
		else:
			return u"*DEFINE(%s)\n\t%s" % (self.name, unicode(self.result).replace('\n','\n\t'))

class Failure(Result):
	def __init__(self):
		Result.__init__(self, label='Failure')
		self.success=False

class FastResult(Result):
	def __init__(self, parser, matched_string=''):
		Result.__init__(self, label='FastResult')
		self.matched_string=matched_string
		self.delta=NOP()		
		self.parser=parser

# Need to get indenting of the following right:

	def __unicode__(self):
		return u'Matched string [%s] using:\n\t%s' % (self.matched_string, unicode(self.parser).replace('\n','\n\t'))

class PatternResult(Result):
	def __init__(self, parser, s, env, m, success=True):
		Result.__init__(self, label='PatternResult')
		self.success=success
		self.match=m
		self.parser=parser
		self.failurestring=s
		self.env=env
		if self.success:
			self.matched_string=m.matched_string()
		else:
			self.matched_string=''
		self.delta=NOP()


	def __repr__(self):
		return 'PatternResult(%s, %s, %s)' % (repr(trunc(self.failurestring)), self.env, repr(self.parser))

	def __unicode__(self):
		if self.success:
			return u'%s' % self.parser
		else:
 			return u'*%s, failed on the string:\n\t%s' % (self.parser, repr(trunc(self.failurestring)))

#class PatternFailure(Failure):
#	def __init__(self,s,env,m):
#		Failure.__init__(self)
#		self.match=m
#		self.failurestring=s
#		self.env=env
#
#	def text(self):
#		return 'pattern: %s string=(%s)' % (self.match.pattern, self.failurestring[:128])
#
#	def __repr__(self):
#		return 'PatternFailure(%s, %s, %s)' % (repr(trunc(self.failurestring)), self.env, repr(self.match))
#
#	def __unicode__(self):
#		return 'Mismatch between:\n\t%s\n\t%s\n' % (repr(trunc(self.failurestring)), repr(self.match.pattern))
#

class SeqResult(Result):
	def __init__(self, parser, children):
		Result.__init__(self)
		self.length=len(children)
		self.final=children[self.length-1]
		self.children=children
		self.force=self.final.force
		self.success=self.final.success
		self.parser=parser
		if self.success:
			self.delta=DeltaList([r.delta for r in self.children[0:self.length-1]])
		self.matched_string=str_flatten([child.matched_string for child in children])


	def __repr__(self):
		return "SeqResult(%s)" % (repr(self.children))

	def __unicode__(self):
#		children=self.children[0:self.len-1]
#		children=self.goodchildren+[u"*" + unicode(self.final)]
		if self.success:
			return u"%s" % self.parser
		else:
			return "*SEQ(\n%s)"  % (str_flatten(['\t' + unicode(a).replace('\n','\n\t')+ '\n' for a in self.children])) 

class SeqFailure(Failure):
	def __init__(self, children, pos, result):
		Failure.__init__(self)
		self.pos=pos
		self.result=result
		self.children=children
		self.force=result.force

	def text(self):
		return 'sequence(%s): position=%s(\n%s\n)' % (len(self.children), self.pos,self.result.text())

	def __repr__(self):
		return "SeqFailure(%s, %s, %s)" % (repr(self.children), repr(self.pos), repr(self.result))

	def __unicode__(self):
		children=self.children[0:self.pos-1]
		children=children+[u"*" + unicode(self.result)]
		return "SEQ(\n%s)"  % (str_flatten(['\t' + unicode(a).replace('\n','\n\t')+ '\n' for a in children])) 


class OrFailure(Failure):
	def __init__(self, failures):
		Failure.__init__(self)
		self.failures=failures
		self.force=reduce(lambda a,b: a or b, [x.force for x in failures])

	def text(self):
		return 'or: %s ' % str_flatten([x.text() +'\n' for x in self.failures])

	def __repr__(self):
		return("OrFailure(%s)" % (repr(self.failures)))

	def __unicode__(self):
		return "OR(\n%s\n\t)" % (str_flatten(['\t' + unicode(a).replace('\n','\n\t')+ '\n' for a in self.failures]))

# deprecated because IF is deprecated
class IfFailure(Failure):
	def __init__(self,failure):
		Failure.__init__(self)
		self.failure=failure
		self.force=failure.force

	def text(self):
		return 'IF: %s\n' % self.failure.text()

class SimpleResult(Result):
	'''A sub-class of Result, which makes no changes to the objects being output, matches no strings.'''
	def __init__(self, parser, description, success=True):
		Result.__init__(self, label=parser.tag)
		self.description=description
		self.success=success
		self.parser=parser

	def __unicode__(self):
		return self.description

class ObjectResult(SimpleResult):
	def __init__(self, parser, delta, description):
		SimpleResult.__init__(self, parser, description)
		self.delta=delta

#class StopFailure(Failure):
#	def __init__(self,reason):
#		Failure.__init__(self)
#		self.reason=reason
#
#	def text(self):
#		return 'stop: %s' % self.reason
#	

# Need to print out a list of successes and failures (but that would mean
# we would be testing until over and over again [yes]. That would surely go in a trace(?). Perhaps a number could be printed up in a shorter form.
# Nevertheless we need to have a list of all results (good and bad), of the form# loop untilf loop untilf loop until (or might end loopf or untilf loop).

class AnyResult(Failure):
	def __init__(self, parser, results):
		Result.__init__(self, label='AnyResult')
		self.results=results
#
		self.length=len(results)
		self.final=results[self.length-1]
		if self.final.force:
			self.success=False
			self.force=True
#
#		self.failure=failure
#		self.untilfailure=untilfailure
#		self.force=failure.force or untilfailure.force

		if self.success:
			self.delta=DeltaList([child.delta for child in filter(lambda x:x.success, results)])
		self.matched_string=str_flatten([child.matched_string for child in results])

	def __unicode__(self):
		loop_results=[self.results[n] for n in range(1,self.length,2)]
		prefix=self.success and '' or '**'
		return "%sANY(\n%s)"  % (prefix, str_flatten(['\t' + unicode(a).replace('\n','\n\t')+ '\n' for a in loop_results])) 

#		return "ANY(\n\t%s\nuntil\n\t%s\n)" % (self.failure, self.untilfailure)

class AnyFailure(Failure):
	def __init__(self, failure, untilfailure):
		Failure.__init__(self)
		self.failure=failure
		self.untilfailure=untilfailure
		self.force=failure.force or untilfailure.force

	def text(self):
		return 'any:\n(any)failure:\n%s\n(any)untilfailure:\n%s\n\n' % (self.failure.text(),self.untilfailure.text())

	def __unicode__(self):
		return "ANY(\n\t%s\nuntil\n\t%s\n)" % (self.failure, self.untilfailure)


class PossiblyResult(CompoundResult):
	def __init__(self, result, matched_string=''):
		CompoundResult.__init__(self, result, label='PossiblyResult')
		self.success=True and not self.force
		if matched_string:
			self.matched_string=matched_string
				
	def __unicode__(self):
		prefix=self.result.success and '' or '*'
		forced=self.force and '** (forced)' or ''
		return '%sPOSSIBLY(\n\t%s\n)%s' % (prefix, self.result, forced)

#class PossiblyFailure(Failure):
#	def __init__(self, result):
#		Failure.__init__(self)
#		self.force=True
#		self.result=result
#
#	def text(self):
#		return 'Possibly(forced):\n%s' % self.result.text()
#
#	def __unicode__(self):
#		return '*POSSIBLY(\n\t%s\n)' % self.result

class ExpressionFailure(Failure):
	'''This is a generic class used when eval is called on an expression
	but raises an exception.'''

	def __init__(self, parser, expression, reason):
		Failure.__init__(self)
		self.expression=expression
		self.reason=reason
		self.parser=parser
		self.force=True

	def __unicode__(self):
		return "*%s\n\tEvaluation of the following expression failed:\n\t%s\n\tBecause\n\t%s" % (self.parser, repr(self.expression), self.reason)

class Success(Result):
	def __init__(self, delta, s=''):
		'''
		delta - the change to the XML hierarchy caused by the success
		s - the string that was matched.
		'''

		Result.__init__(self)
		self.success=True
		self.delta=delta
		self.label='Success'
		self.matched_string=s

	def __repr__(self):
		return "%s(%s, %s)" % (self.label, repr(self.delta), repr(self.matched_string))

	def __unicode__(self):
		return "%s(%s)" % (self.label, self.delta)

	def verbose(self):
		return "%s\n%%%%matched:\%s" % (unicode(self), repr(self.matched))

class Delta:
	def __init__(self):
		self.type='ROOT'

	def toplevel(self, toplevelname='toplevel'):
		self.dom=xml.dom.getDOMImplementation()
		self.document=self.dom.createDocument('http://www.publicwhip.org.uk/votes', toplevelname, None)
		self.rootnode=self.document.firstChild
		
		return self.rootnode

class NOP(Delta):
	def apply(self, current):
		return current

	def __repr__(self):
		return "NOP()"

	def __unicode__(self):
		return "NOP()"

class TopLevel(Delta):
	def __init__(self, toplevelname):
		self.dom=xml.dom.getDOMImplementation()
		self.document=self.dom.createDocument('http://www.publicwhip.org.uk/votes', toplevelname, None)
		self.rootnode=self.document.firstChild
		self.type='toplevel'

	def root(self):
		return self.rootnode

	def doc(self):
		return self.document

class DeltaList(Delta):
	def __init__(self, deltalist):
		self.type='list'
		self.deltalist=deltalist

	def apply(self, current):
		for delta in self.deltalist:
			current=delta.apply(current)
		return current

	def __repr__(self):
		return "DeltaList(%s)" % str_flatten([repr(a) for a in self.deltalist], ', ')

	def __unicode__(self):
		return repr(self)

class StartElement(Delta):
	'''Adds an element below the current element and moves to it'''
	def __init__(self, name, attributes={}):
		self.type='start'
		self.name=name
		self.attributes=attributes

	def apply(self, current):
		if not current:
			current=self.toplevel(self.name)
			newelement=current
		else:
			newelement=current.ownerDocument.createElement(self.name)
			current.appendChild(newelement)

		for name in self.attributes:
			value=self.attributes[name]
			if len(value)>0:
				newelement.setAttribute(name, self.attributes[name])
			else:
				raise Exception, "Illegal attempt to pass zero length attribute to StartElement\nAttribute name=%s" % name

		return newelement

	def __repr__(self):
		return "StartElement(%s, %s)" % (repr(self.name), repr(self.attributes))

	def __unicode__(self):
		return repr(self)

class AddAttribute(Delta):
	'''Adds an attribute to the current element.

	Zero length values are not permitted.'''

	def __init__(self, name, value):
		if not value or len(value)==0:
			raise Exception, "Attempting to add attribute (%s) with no or zero length value" % name
		self.type='addattribute'
		self.name=name
		self.value=value

	def apply(self, current):
		if not current:
			raise Exception, "No current node to which to add attribute (%s, %s)" % (self.name, self.value)
		current.setAttribute(self.name, self.value)
		return current

	def __repr__(self):
		return "AddAttribute(%s, %s)" % (repr(self.name), repr(self.value))

class EndElement(Delta):
	'''Moves up to the parent of the current element.'''

	def __init__(self, name):
		self.type='endelement'
		self.name=name

	def apply(self, current):
		if not current:
			raise Exception, "Attempted to end non-existant element %s" % self.name
		if current.tagName==self.name:
			return current.parentNode
		else:
			raise Exception, "Endelement %s used to close element %s" % (self.name, current.tagName)

	def __repr__(self):
		return "EndElement(%s)" % repr(self.name)

class Element(Delta):
	'''Creates a new element below the current element.'''

	def __init__(self, name, attributes={}):
		self.type='element'
		self.name=name
		self.attributes=attributes
		

	def apply(self, current):
		if not current:
			current=self.toplevel(self.name)
			newelement=current
		else:
			newelement=current.ownerDocument.createElement(self.name)
			current.appendChild(newelement)

		for name in self.attributes:
			newelement.setAttribute(name, self.attributes[name])

		return current


class TextElement(Delta):
	def __init__(self,text):
		self.type='text'
		self.text=text

	def apply(self, current):
		if not current:
			raise Exception, "Cannot have text element as top level node"
		newnode=current.ownerDocument.createTextNode(self.text)
		current.appendChild(newnode)
		return current

	def __unicode__(self):
		return 'TextElement(%s)' % self.text

	def __repr__(self):
		return 'TextElement(%s)' % repr(self.text)

# pattern operators

class Mapping:
	pass

class PosMapping(Mapping):
	def __init__(self, n, name):
		self.n=n
		self.name=name

	def __call__(self, match):
		s=match.mobj.groups()[self.n]
		if s:
			match.dict[self.name]=repr(s)

	def __unicode__(self):
		return "%s ==> %s" % (self.n, self.name)

	def __repr__(self):
		return "PosMapping(%s, %s)" % (repr(self.n), repr(self.name))

class SimpleMapping(Mapping):
	pass
	

class Match:
	'''A Match object matches a regular expression and may also assign names to some of the groups that have been matched.
	'''

	def __init__(self, pattern='', flags=0, breaking=False):
		self.pattern=pattern
		self.flags=flags
		self.breaking=breaking
		try:
			self.compile()
		except re.error, explanation:
			raise Exception, "regular expression error (%s) in (%s, %s)" % (explanation, self.pattern, self.flags)
		self.mappings=[]
		self.dict={}
		self.mobj=None

	def __repr__(self):
		return u"Match('%s', %s, %s)" % (self.pattern, self.flags, self.breaking)

	def __unicode__(self):
		if self.flags>0:
			return "%s(?%s)" % (self.pattern, Match.flag2str(self.flags))
		else:
			return "%s" % self.pattern

	def compile(self):
		self.prog=re.compile(self.pattern, self.flags)

	def match(self, s):
		self.mobj=self.prog.match(s)
		if self.mobj:
			self.dict=dict([(key, value) for (key, value) in self.mobj.groupdict().iteritems()])
			for t in self.mappings:
				t(self)
		else:
			self.dict={}

		return self.mobj

	def start(self):
		if self.mobj:
			return self.mobj.start()
		else:
			raise Exception, "Attempt to find start() of unused Match"
	def end(self):
		if self.mobj:
			return self.mobj.end()
		else:
			raise Exception, "Attempt to find end() of unused Match"
	def matched_string(self):
		if self.mobj:
			return self.mobj.group()
		else:
			raise Exception, "Attempt to find end()matching string of unused Match"


	def query(self):
		newmatch=Match(
			"(%s)?" % self.pattern,
			self.flags,
			True
			)
		return newmatch

	def star(self):
		newmatch=Match(
			"(%s)*" % self.pattern,
			self.flags,
			True
			)

		return newmatch

	@classmethod
	def join(cls, first, second, f=lambda x,y: x+y):
		groups1=first.prog.groupindex
		overlap=set(groups1) & set(second.prog.groupindex)
		pattern1=first.pattern
		mappings=copy.deepcopy(first.mappings)
		for gname in overlap:
			pattern1=pattern1.replace('(?P<%s>' % gname, '(', 1)
			mappings.append(PosMapping(groups1[gname]-1, gname))
		newmatch=Match(
			f(pattern1, second.pattern),
			first.flags & second.flags,
			first.breaking & second.breaking,
			)
	
		newmatch.mappings=mappings
		return newmatch
	
	@classmethod
	def mkflatten(cls, f):
		return lambda a, b: cls.genflatten(a, b, f) 

	@classmethod
	def flatten(cls, a, b):
		return cls.genflatten(a, b)

	@classmethod	
	def genflatten(cls, a, b, f=lambda x,y: x+y):
		if not a or not b:
			return None
		else:
			if a.breaking or b.breaking:
				return None
			else:
				return cls.join(a,b,f)
	@classmethod
	def flag2str(cls, flag):
		s=''
		for (value, letter) in [
			(re.IGNORECASE, 'i'),
			(re.DOTALL, 's')
			]:
			if flag & value == flag:
				s=s+letter
	
		return s

class NullBreakingMatch(Match):
	def __init__(self):
		Match.__init__(self, '', 0, True)

class Parse:
	'''Base class for all parser objects.

	A parser object may be called on a (string, environment) pair and returns a tuple of (string, environment, Result).

'''

	def __init__(self, tag, match=None, breaking=False, str_extra='', repr_extra='', **args):
		'''
		tag - the name of the parser
		match - if the effect of the parser is equivalent to a Match object
		breaking - if the parser's match should not be concatenated with a succeeding parser
		str_extra - is used in pretty printing and is an additional string to be printed after the objects arguments in **args.
		'''

		self.fragment=None
		self.atomic=True
		self.tag=tag
		self.args=args
		self.argstring=str_flatten(['%s=%s' % (key, repr(value)) for (key, value) in args.iteritems()], divider=', ')
		self.str_extra=str_extra
		self.repr_extra=repr_extra

		self.match=match

		# by default a Parse object does nothing. Query whether it should apply the match (whatever it is). This would mean having the fastcall method at this level of the hierarchy.

		def anonId(s, env):
			return (s, env, Success(NOP, ''))

		self.callme=anonId

	def __repr__(self):
		return '%s(%s)' % (self.tag, concatenate(self.argstring, self.repr_extra, ', '))

	def __unicode__(self):
		return '%s(%s%s)' % (self.tag, self.argstring, self.str_extra )

	def __str__(self):
		u=unicode(self)
		return u.encode(encoding)

#	def string(self):
#		return self.tag	

	def __call__(self, s, env={}):
		return self.callme(s, env)		


class Pattern(Parse):
	def __init__(self, tag, match):
		Parse.__init__(self, 
			tag, 
			match, 
			str_extra=match.pattern, 
			repr_extra=repr(match)
			)

		def anonPattern(s, env):
			mobj=self.match.match(s)
			if mobj:

				t=s[self.match.end():]
				env.update(self.match.dict)
				return (t, env, PatternResult(self, s, env, match))
			else:
				return (s, env, PatternResult(self, s, env, match, success=False))


		self.callme=anonPattern
		

def concatenate(a, b, divider=''):
	if len(a) > 0 and len(b) > 0:
		return a + divider + b
	else: 
		return a + b

class ParseConstructor(Parse):
	'''A Parse Constructor builds a parser on top of a list of child parsers. 
	ParseConstructor does not attempt to build a Match object from the Match objects of its children.'''

	def __init__(
		self,
		tag,
		children=[],
		match=None,
		breaking=False,
		**args
		):

		self.children=list(children)
		self.args=args

		child_repr=str_flatten([repr(a) for a in self.children], divider=', ')
		child_str='\n'+str_flatten(['\t' + unicode(a).replace('\n','\n\t')+ '\n' for a in self.children])

		Parse.__init__(self, 
			tag, 
			match, 
			breaking, 
			repr_extra=child_repr,
			str_extra=child_str,
			**args
		)

	def prepend(self, p):
		self.children=[p]+self.children
		self._rebuild()

	def append(self, p):
		self.children.append(p)
		self._rebuild()


class Compound(ParseConstructor):
	'''A Compound object is an extension of a ParseConstructor which attempts to create a Match object that is equivalent to the objects effect if possible.'''

	def __init__(
		self, 
		tag, 
		children=[], 
		match=None,
		breaking=False, 
		flatten=Match.flatten,
		**args
		):

		ParseConstructor.__init__(self, tag, children, match, breaking, **args)

		self.flatten=flatten
		if not self.match:
			self.synthetic=True
		else:
			self.synthetic=False

		# This will have to be a lot cleverer. It will do for now (just).
		#if not self.match and len(children)>0:
			#self.match=reduce(flatten, [a.match for a in self.children])
		self._rebuild()

		def fastcall(s, env):

			if self.match:
				mobj=self.match.match(s)
		
				if mobj:
					result=FastResult(self, self.match.matched_string())
					t=s[self.match.end():]
					env.update(self.match.dict)
					return (t, env, result)
				else:
					return self.callme(s, env)
					
			else:
				# if there is no equivalent pattern, do it
				a=self.callme(s, env)
				return a
				
		self.fastcall=fastcall

	def _rebuild(self):
		if self.synthetic and len(self.children)>0:
			self.match=reduce(self.flatten, [a.match for a in self.children])

	def __call__(self, s, env={}):
		return self.fastcall(s, env)


class DEFINE(Compound):
	def __init__(self, 
		name, 
		parser, 
		breaking=False, 
		fragment=False,
		expand=False
		):

		if parser.match:
			match=Match('(?P<%s>%s)' % (name, parser.match.pattern), parser.match.flags, parser.match.breaking)
		else:
			match=None	
		Compound.__init__(self, 
			'DEFINE', 
			[parser], 
			match, 
			breaking, 
			name=name, 
			fragment=fragment,
			expand=expand)

		self.fragment=fragment
		self.name=name
		self.parser=parser
		self.expand=expand
	
		def anonDEFINE(s, env):
			# call parser and define name
			(t, env, result)=parser(s, env)
			if result.success:
				env[self.name]=s[:len(s)-len(t)]

			return(t, env, DefineResult(result, name, self.expand))

		self.callme=anonDEFINE
		standard_parsers[name]=self

	def __unicode__(self):
		if self.expand:
			return Compound.__unicode__(self)
		else:
			return self.name

class SET(Parse):
	def __init__(self, a, b):
		Parse.__init__(self, 'SET') 
		self.a=a
		self.template=Template(b)

		def anonSET(s, env):
			value=evaluate(self.template, env)
			env[a]=value

			return (s, env, ObjectResult(self, NOP(), 'SET(%s=%s)' % (a, value)))
	
		self.callme=anonSET

	def __repr__(self):
		return "SET(%s, %s)" % (self.a, self.template.template)

	def __unicode__(self):
		return self.__repr__()

def FORCE(f):
	return f

#def FORCE(f):
#	def anonFORCE(s, env):
#		(s1,env1,result)=f(s,env)
#		if not result.success:
#			result.force=True
#
#		return (s1,env1,result)
#
#	return anonFORCE

class ANY(Compound):
	'''repeatedly attemps f, until no more string is consumed, or f fails

	ANY always succeeds, unless there is an otherwise clause, which is tried
	after ANY is attempted unless the until clause matches.'''

	def __init__(self, f, until=None, otherwise=None):
		if f.match:
			match=Match('(%s)*' % f.match.pattern, f.match.flags, True)
		else:
			match=None
		
		Compound.__init__(self, 
			'ANY', 
			children=[f], 
			match=match, 
			breaking=True)		

		def anonANY(s,env):
			results=[]
	
			if until:
				(s1,env1,untilresult)=until(s,env)
				results.append(untilresult)
				if untilresult.success or untilresult.force:
					return (s1,env1,AnyResult(self, results))
			else:

###This is a bit clumsy but is meant to be there. It puts in a dummy
###succesful completetion, for the purposes of pretty printing. Query whether
###some other way of doing this ought to work.

				results.append(Success(NOP()))
	
			(s1,env1,result)=f(s,env)
			results.append(result)
			while len(s1) < len(s) and result.success:
                        # need to make sure there is consistency with use of s,s1
				s=s1
				env=env1
				if until:
					(s1,env1,untilresult)=until(s,env)
					results.append(untilresult)

					if untilresult.success or untilresult.force:
						return (s1, env1, AnyResult(self, results))
				else:
					results.append(Success(NOP()))	

				# apply loop check
				(s1,env1,result)=f(s,env)
				results.append(result)
			
			# results should now have a list of results in it.

			if otherwise:
				(s1, env1, result)=otherwise(s1,env1)
				results.append(result)

			return (s1, env1, AnyResult(self, results))

		self.callme=anonANY

class OR(Compound):
	def __init__(self, *args):
		'''each argument is tried in order until one is found that succeeds.'''

		Compound.__init__(self, 'OR', children=list(args), flatten=Match.mkflatten(lambda x, y: "%s|%s" % (x, y)), breaking=True)

		def anonOR(s,env):
			debug('OR')
			failures=[]
			for f in args:
				(s1,env1,result)=f(s,env)
				if result.success:
					return (s1,env1,result)
				else:
					failures.append(result)
				if result.force:
					break
	
			return (s1,env1,OrFailure(failures))
	
		self.callme=anonOR

class SEQ(Compound):
	def __init__(self, *args):
		'''each argument is tried in order, all must succeed for SEQ to succeed.'''
		Compound.__init__(self,'SEQ',children=list(args))

		def anonSEQ(s,env):
			debug('SEQ (length=%s)' % len(self.children))
			original_string=s
#			values=[]
#			pos=1
			results=[]
			for l in self.children:
				#debug("****(pos=%s)\n##string:\n%s\n##env:\n%s\n##values:\n%s" % (pos,s[:64],env,values))

				(s,env,result)=l(s,env)
				results.append(result)
				if not result.success:
					break
#				values.append(result.delta)
#				pos=pos+1

	
			#debug('endSEQ success=%s value=%s\n========' % (result.success, values))
	
#			if result.success:
#				return (s, env, Success(DeltaList(values)))
#			else:
#				return (original_string, env, SeqFailure(self.children, pos, result))

			return (s, env, SeqResult(self, results))
		
		self.callme=anonSEQ

#def SEQ(*args):
#	'''each argument is tried in order, all must succeed for SEQ to succeed.'''
#
#	def anonSEQ(s,env):
#		debug('SEQ (length=%s)' % len(args))
#		original_string=s
#		values=[]
#		pos=1
#		for l in args:
#			debug("****(pos=%s)\n##string:\n%s\n##env:\n%s\n##values:\n%s" % (pos,s[:64],env,values))
#			(s,env,result)=l(s,env)
#			if not result.success:
#				break
#			values.append(result.delta)
#			pos=pos+1
#
#		debug('endSEQ success=%s value=%s\n========' % (result.success, values))
#
#		if result.success:
#			return (s, env, Success(DeltaList(values)))
#		else:
#			return (original_string, env, SeqFailure(len(args), pos,result))
#	
#	return anonSEQ
#
def IF(condition, ifsuccess):
	'''IF'''

	def anonIF(s, env):
		(s1,env1,result1)=condition(s,env)
		if result1.success:
			(s,env,result2)=ifsuccess(s1,env1)
			values=[result1.delta, result2.delta]
			if result2.success:
				return (s,env,Success(DeltaList(values)))
			else:
				return (s,env,IfFailure(result2))
		else:
			return (s,env,IfFailure(result1))

	return anonIF		


class POSSIBLY(Compound):
	def __init__(self, f):
		'''POSSIBLY always succeeds, unless f is forced'''

		# Need to force breaking on POSSIBLY's 
		# Can we do this with a pattern?

		Compound.__init__(self, 
			'POSSIBLY', 
			children=[f], 
			breaking=True)

		if f.match:
			self.match=f.match.query()
		
		def anonPOSSIBLY(s,env):
			(s1,env1,result)=f(s,env)
			if result.success or result.force:
				return (s1, env1, PossiblyResult(result))
			else:
#				return (s, env, PossiblyResult(NOP()))
				return (s, env, PossiblyResult(result))

		self.callme=anonPOSSIBLY

class NULL(Parse):
	def __init__(self):
		Parse.__init__(self, 'NULL')
		self.callme=lambda s, env: (s, env, Success(NOP()))
	


class BREAK(Parse):
	def __init__(self):
		Parse.__init__(self, 'BREAK', breaking=True)


#TODO call needs to be changed to the new evaluation style
class CALL(ParseConstructor):
	'''CALL runs a parser on an evaluated string and then discards
	any environment changes made by that parser. Any changes to the 
	object stream continue. Variables may be passed back using the 
	passback attribute.'''

	def __init__(self, callstring='', f=NULL(), passback={}):
	
		ParseConstructor.__init__(self, 'CALL', children=[f], callstring=callstring, passback=passback)
		self.callstring=callstring
		self.f=f
		self.passback=passback

		def anonCALL(s,env):
	
			substring=evaluate(Template(callstring), env)
			local_env=env.copy()
			(s1, env1, result)=f(unicode(substring), local_env)
	
			if result.success:
				for key, newkey in passback.iteritems():

##change. Posibly need to change this to make sure all is OK.
					env[newkey]=local_env[key]
			return (s, env, result)
	
		self.callme=anonCALL


class pattern(Pattern):
	def __init__(self, pattern, flags=re.IGNORECASE, debug=False):

		Pattern.__init__(self, 
			'pattern', 
			Match(pattern, flags)
			)
		

def tagpatterns(first, tags, padding, last):
	s='('
	e='('
	if padding:
		s=s+padding+'|'
		e=e+padding+'|'
	lt=len(tags)
	for i in range(lt):
		s='%s<%s[^>]*?>' % (s, tags[i])
		e='%s</%s>' % (e, tags[i])
		if i==lt-1:
			s='%s)*' % s
			e='%s)*' % e
		else:
			s='%s|' % s
			e='%s|' % e
	if lt==0:
		s=s+')'
		e=e+')'
	
	return(first+s, last+e)

def tagged(first='',tags=[],p='',padding=None, last=''):
	(first, last)=tagpatterns(first, tags, padding, last)

	return pattern(first+p+last)
		

# Don't make [] optional because they are significant all too often.
standard_punctuation=['.',';',',',':']

def prep_plaintext(text,strings={},punctuation=standard_punctuation, opttags=[]):

#	stringdict=dict([(v,lambda s:x) for (v,x) in strings.iteritems()])
#	stringdict.update(standard_patterns)
	for punc in punctuation:
		text=text.replace(punc,'('+punc+')?')

	text=text.replace('.','\.')
	text=text.replace(']','\]')
	text=text.replace('[','\[')
	
	for tag in opttags:
		text=text.replace('<%s>' % tag, '(<%s>)?' % tag)
		text=text.replace('</%s>' % tag, '(</%s>)?' % tag)

	text=sub(text)

	return text

def plaintext(text,strings={},punctuation=standard_punctuation, opttags=[], debug=False):

	return prep_plaintext(text,strings,punctuation, opttags=[])


def plaintextpar(text, strings={}, punctuation=standard_punctuation):
	(first, last)=tagpatterns(
		first='\s*',
		tags=['p','ul','br'],
		padding='\s',
		last='')
	s=prep_plaintext(text)
	s.prepend(pattern(first))
	s.append(pattern(last))
	return s


# Construction of objects.

def OBJECT(name, body='', **attributes):
	return SEQ(
		DEBUG('object name=%s' % name),
		START(name),
		ATTRIBUTES(_attrdict=attributes),
		OUT(body),
		END(name))

def ELEMENT(name, body='', groupstring=None, attrlit={}, **attributes):
	'''Creates an output element.

	The value of body is used as the body of the element.
	If groupstring is set, it is treated as a string containing explicit value definitions, such as attribute="value", these are translated into attributes of the element.
	Remaining arguments are treated as names of attributes, with values being evaluated to give attribute values in the final element.
	'''
	attributes.update(attrlit)
	return SEQ(
		DEBUG('element name=%s' % name), 
		START(name), 
		ATTRIBUTES(
			groupstring=groupstring,
			_attrdict=attributes), 
		OUT(body), 
		END(name))


def AttributesToDeltaList(env, attributes, groupstring=None):
	'''Creates a list of AddAttributes.

	groupstring is used where a collection of attributes have been
	read from an HTML file. 
	'''

	deltalist=[]
	for key in attributes:
#		deltalist.append(AddAttribute(key, eval(string.Template(attributes[key]).substitute(env))))
		value=evaluate(Template(attributes[key]), env)
		deltalist.append(AddAttribute(key, unicode(value)))

# Need to get evaluation of groupstrings right

	if groupstring:
#		group=eval(env[groupstring])
		group=env[groupstring]
		pairs=re.findall('\s*([^\s"]+)="([^"]*)"',group)
		for (name, value) in pairs:
			deltalist.append(AddAttribute(name, value))

	return deltalist


# Needs a __repr__ and __unicode__, but I am not sure how to do it.

class ATTRIBUTES(Parse):
	def __init__(self,  groupstring=None, _attrdict=None, **attributes):
		Parse.__init__(self, 'ATTRIBUTES')

		if _attrdict:
			def anonATTRIBUTES(s,env):
				deltalist=AttributesToDeltaList(env, _attrdict, groupstring)	
				return (s, env, ObjectResult(self, DeltaList(deltalist), 'ATTRIBUTES()'))
		
		else:
			def anonATTRIBUTES(s,env):
				deltalist=AttributesToDeltaList(env, attributes, groupstring)	
				return (s, env, ObjectResult(self, DeltaList(deltalist), 'ATTRIBUTES()'))
		
		self.callme=anonATTRIBUTES

class START(Parse):
	def __init__(self, name, groupstring=None, **attributes):
		Parse.__init__(self, 'START')
		self.name=name


		def anonSTART(s,env):
			
			deltalist=AttributesToDeltaList(env, attributes, groupstring)
	
			elementlist=DeltaList([StartElement(name)] + deltalist)
	
			return (s,env,ObjectResult(self, elementlist, 'START(%s)' % self.name))
	
		self.callme=anonSTART

		def __unicode__(self):
			if self.groupstring:
				arg=' %s' % self.groupstring
			else:
				arg=''
			return "START(%s%s)" % (self.name, arg)

class END(Parse):
	def __init__(self, name):
		Parse.__init__(self, 'END')
		def anonEND(s,env):
			return (s,env,ObjectResult(self, EndElement(name), 'END(%s)' % name))
	
		self.callme=anonEND


class OUT(Parse):
	def __init__(self, outexpr):
		Parse.__init__(self, 'OUT', outexpr=outexpr)
		self.template=Template(outexpr)
		def anonOUT(s,env):
			if len(outexpr)==0:
				value=''
			else:
				value=evaluate(self.template, env)
				value=clean(unicode(value))
			
			result=ObjectResult(self, TextElement(value), 'OUT(%s)' % value)
	
			return (s,env,result)
	
		self.callme=anonOUT

	def __unicode__(self):
		return('OUT(%s)' % self.template)
	
class DEBUG(Parse):
	def __init__(self, t, fail=False):
		Parse.__init__(self, 'DEBUG', Match())
		def anonDEBUG(s,env):
			
			#print 'debug: %s' % t
	
			if fail:
				return (s, env, Failure())
			else:
				return (s,env,Success(NOP()))
	
		self.callme=anonDEBUG

class TRACE(Parse):
	def __init__(self, cond=False, envlength=48, slength=256, vals=[], fail=False):
		Parse.__init__(self, 'TRACE', Match())

		def anonTRACE(s,env):
# During development testing, turn off all traces
			cond=False
			if cond:
				if len(vals)>0:
					print ('--------\nTrace:\ns=%s\nenv=%s\n' % (s[:slength],unicode(env)[:envlength])).encode('UTF-8')
				for v in vals:
					if env.has_key(v):
						print ('%s=%s' % (v,env[v])).encode('UTF-8')
					else:
						print ('unknown key %s' % v).encode('UTF-8')
				print '--------\n'
	
			if fail:
				return (s, env, Failure())
			else:
				return (s,env,Success(NOP()))
		self.callme=anonTRACE	

class STOP(Parse):
	def __init__(self, t=''):	
		Parse.__init__(self, 'STOP')
		def anonSTOP(s,env):
			return (s,env, SimpleResult(self, u'stop: %s' % t,  success=False))
#			return (s,env,StopFailure(t))

		self.callme=anonSTOP		
