#!/usr/bin/env python
#
# Copyright 2007 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

"""Validation tools for generic object structures.

This library is used for defining classes with constrained attributes.
Attributes are defined on the class which contains them using validators.
Although validators can be defined by any client of this library, a number
of standard validators are provided here.

Validators can be any callable that takes a single parameter which checks
the new value before it is assigned to the attribute.  Validators are
permitted to modify a received value so that it is appropriate for the
attribute definition.  For example, using int as a validator will cast
a correctly formatted string to a number, or raise an exception if it
can not.  This is not recommended, however.  the correct way to use a
validator that ensure the correct type is to use the Type validator.

This validation library is mainly intended for use with the YAML object
builder.  See yaml_object.py.
"""





import re

import google
import yaml


class Error(Exception):
  """Base class for all package errors."""


class AttributeDefinitionError(Error):
  """An error occurred in the definition of class attributes."""


class ValidationError(Error):
  """Base class for raising exceptions during validation."""

  def __init__(self, message, cause=None):
    """Initialize exception."""
    if hasattr(cause, 'args') and cause.args:
      Error.__init__(self, message, *cause.args)
    else:
      Error.__init__(self, message)
    self.message = message
    self.cause = cause

  def __str__(self):
    return str(self.message)


class MissingAttribute(ValidationError):
  """Raised when a required attribute is missing from object."""


def AsValidator(validator):
  """Wrap various types as instances of a validator.

  Used to allow shorthand for common validator types.  It
  converts the following types to the following Validators.

    strings -> Regex
    type -> Type
    collection -> Options
    Validator -> Its self!

  Args:
    validator: Object to wrap in a validator.

  Returns:
    Validator instance that wraps the given value.

  Raises:
    AttributeDefinitionError if validator is not one of the above described
    types.
  """
  if isinstance(validator, (str, unicode)):
    return Regex(validator, type(validator))
  if isinstance(validator, type):
    return Type(validator)
  if isinstance(validator, (list, tuple, set)):
    return Options(*tuple(validator))
  if isinstance(validator, Validator):
    return validator
  else:
    raise AttributeDefinitionError('%s is not a valid validator' %
                                   str(validator))


class Validated(object):
  """Base class for other classes that require validation.

  A class which intends to use validated fields should sub-class itself from
  this class.  Each class should define an 'ATTRIBUTES' class variable which
  should be a map from attribute name to its validator.  For example:

    class Story(Validated):
      ATTRIBUTES = {'title': Type(str),
                    'authors': Repeated(Type(str)),
                    'isbn': Optional(Type(str)),
                    'pages': Type(int),
                    }

  Attributes that are not listed under ATTRIBUTES work like normal and are
  not validated upon assignment.
  """

  ATTRIBUTES = None

  def __init__(self, **attributes):
    """Constructor for Validated classes.

    This constructor can optionally assign values to the class via its
    keyword arguments.

    Raises:
      AttributeDefinitionError when class instance is missing ATTRIBUTE
      definition or when ATTRIBUTE is of the wrong type.
    """
    if not isinstance(self.ATTRIBUTES, dict):
      raise AttributeDefinitionError(
          'The class %s does not define an ATTRIBUTE variable.'
          % self.__class__)

    for key in self.ATTRIBUTES.keys():
      object.__setattr__(self, key, self.GetAttribute(key).default)

    self.Set(**attributes)

  @classmethod
  def GetAttribute(self, key):
    """Safely get the underlying attribute definition as a Validator.

    Args:
      key: Name of attribute to get.

    Returns:
      Validator associated with key or attribute value wrapped in a
      validator.
    """
    return AsValidator(self.ATTRIBUTES[key])

  def Set(self, **attributes):
    """Set multiple values on Validated instance.

    This method can only be used to assign validated methods.

    Args:
      attributes: Attributes to set on object.

    Raises:
      ValidationError when no validated attribute exists on class.
    """
    for key, value in attributes.iteritems():
      if key not in self.ATTRIBUTES:
        raise ValidationError('Class \'%s\' does not have attribute \'%s\''
                               % (self.__class__, key))
      setattr(self, key, value)

  def CheckInitialized(self):
    """Checks that all required fields are initialized.

    Since an instance of Validated starts off in an uninitialized state, it
    is sometimes necessary to check that it has been fully initialized.
    The main problem this solves is how to validate that an instance has
    all of its required fields set.  By default, Validator classes do not
    allow None, but all attributes are initialized to None when instantiated.

    Raises:
      Exception relevant to the kind of validation.  The type of the exception
      is determined by the validator.  Typically this will be ValueError or
      TypeError.
    """
    for key in self.ATTRIBUTES.iterkeys():
      try:
        self.GetAttribute(key)(getattr(self, key))
      except MissingAttribute, e:
        e.message = "Missing required value '%s'." % key
        raise e


  def __setattr__(self, key, value):
    """Set attribute.

    Setting a value on an object of this type will only work for attributes
    defined in ATTRIBUTES.  To make other assignments possible it is necessary
    to override this method in subclasses.

    It is important that assignment is restricted in this way because
    this validation is used as validation for parsing.  Absent this restriction
    it would be possible for method names to be overwritten.

    Args:
      key: Name of attribute to set.
      value: Attributes new value.

    Raises:
      ValidationError when trying to assign to a value that does not exist.
    """

    if key in self.ATTRIBUTES:
      value = self.GetAttribute(key)(value)
      object.__setattr__(self, key, value)
    else:
      raise ValidationError('Class \'%s\' does not have attribute \'%s\''
                            % (self.__class__, key))

  def __str__(self):
    """Formatted view of validated object and nested values."""
    return repr(self)

  def __repr__(self):
    """Formatted view of validated object and nested values."""
    values = [(attr, getattr(self, attr)) for attr in self.ATTRIBUTES]
    dent = '    '
    value_list = []
    for attr, value in values:
      value_list.append('\n%s%s=%s' % (dent, attr, value))

    return "<%s %s\n%s>" % (self.__class__.__name__, ' '.join(value_list), dent)

  def __eq__(self, other):
    """Equality operator.

    Comparison is done by comparing all attribute values to those in the other
    instance.  Objects which are not of the same type are not equal.

    Args:
      other: Other object to compare against.

    Returns:
      True if validated objects are equal, else False.
    """
    if type(self) != type(other):
      return False
    for key in self.ATTRIBUTES.iterkeys():
      if getattr(self, key) != getattr(other, key):
        return False
    return True

  def __ne__(self, other):
    """Inequality operator."""
    return not self.__eq__(other)

  def __hash__(self):
    """Hash function for using Validated objects in sets and maps.

    Hash is done by hashing all keys and values and xor'ing them together.

    Returns:
      Hash of validated object.
    """
    result = 0
    for key in self.ATTRIBUTES.iterkeys():
      value = getattr(self, key)
      if isinstance(value, list):
        value = tuple(value)
      result = result ^ hash(key) ^ hash(value)
    return result

  @staticmethod
  def _ToValue(validator, value):
    """Convert any value to simplified collections and basic types.

    Args:
      validator: An instance of Validator that corresponds with 'value'.
        May also be 'str' or 'int' if those were used instead of a full
        Validator.
      value: Value to convert to simplified collections.

    Returns:
      The value as a dictionary if it is a Validated object.
      A list of items converted to simplified collections if value is a list
        or a tuple.
      Otherwise, just the value.
    """
    if isinstance(value, Validated):
      return value.ToDict()
    elif isinstance(value, (list, tuple)):
      return [Validated._ToValue(validator, item) for item in value]
    else:
      if isinstance(validator, Validator):
        return validator.ToValue(value)
      return value

  def ToDict(self):
    """Convert Validated object to a dictionary.

    Recursively traverses all of its elements and converts everything to
    simplified collections.

    Returns:
      A dict of all attributes defined in this classes ATTRIBUTES mapped
      to its value.  This structure is recursive in that Validated objects
      that are referenced by this object and in lists are also converted to
      dicts.
    """
    result = {}
    for name, validator in self.ATTRIBUTES.iteritems():
      value = getattr(self, name)
      if not(isinstance(validator, Validator) and value == validator.default):
        result[name] = Validated._ToValue(validator, value)
    return result

  def ToYAML(self):
    """Print validated object as simplified YAML.

    Returns:
      Object as a simplified YAML string compatible with parsing using the
      SafeLoader.
    """
    return yaml.dump(self.ToDict(),
                     default_flow_style=False,
                     Dumper=yaml.SafeDumper)



class Validator(object):
  """Validator base class.

  Though any callable can be used as a validator, this class encapsulates the
  case when a specific validator needs to hold a particular state or
  configuration.

  To implement Validator sub-class, override the validate method.

  This class is permitted to change the ultimate value that is set to the
  attribute if there is a reasonable way to perform the conversion.
  """

  expected_type = object

  def __init__(self, default=None):
    """Constructor.

    Args:
      default: Default assignment is made during initialization and will
        not pass through validation.
    """
    self.default = default

  def __call__(self, value):
    """Main interface to validator is call mechanism."""
    return self.Validate(value)

  def Validate(self, value):
    """Override this method to customize sub-class behavior.

    Args:
      value: Value to validate.

    Returns:
      Value if value is valid, or a valid representation of value.
    """
    return value

  def ToValue(self, value):
    """Convert 'value' to a simplified collection or basic type.

    Subclasses of Validator should override this method when the dumped
    representation of 'value' is not simply <type>(value) (e.g. a regex).

    Args:
      value: An object of the same type that was returned from Validate().

    Returns:
      An instance of a builtin type (e.g. int, str, dict, etc).  By default
      it returns 'value' unmodified.
    """
    return value


class Type(Validator):
  """Verifies property is of expected type.

  Can optionally convert value if it is not of the expected type.

  It is possible to specify a required field of a specific type in shorthand
  by merely providing the type.  This method is slightly less efficient than
  providing an explicit type but is not significant unless parsing a large
  amount of information:

    class Person(Validated):
      ATTRIBUTES = {'name': unicode,
                    'age': int,
                    }

  However, in most instances it is best to use the type constants:

    class Person(Validated):
      ATTRIBUTES = {'name': TypeUnicode,
                    'age': TypeInt,
                    }
  """

  def __init__(self, expected_type, convert=True, default=None):
    """Initialize Type validator.

    Args:
      expected_type: Type that attribute should validate against.
      convert: Cause conversion if value is not the right type.
        Conversion is done by calling the constructor of the type
        with the value as its first parameter.
    """
    super(Type, self).__init__(default)
    self.expected_type = expected_type
    self.convert = convert

  def Validate(self, value):
    """Validate that value is correct type.

    Args:
      value: Value to validate.

    Returns:
      None if value is None, value if value is of correct type, converted
      value if the validator is configured to convert.

    Raises:
      ValidationError if value is not of the right type and validator
      is not configured to convert.
    """
    if not isinstance(value, self.expected_type):
      if value is not None and self.convert:
        try:
          return self.expected_type(value)
        except ValueError, e:
          raise ValidationError('Type conversion failed for value \'%s\'.'
                                % value,
                                e)
        except TypeError, e:
          raise ValidationError('Expected value of type %s, but got \'%s\'.'
                                % (self.expected_type, value))
      else:
        raise MissingAttribute('Missing value is required.')
    else:
      return value


TYPE_BOOL = Type(bool)
TYPE_INT = Type(int)
TYPE_LONG = Type(long)
TYPE_STR = Type(str)
TYPE_UNICODE = Type(unicode)
TYPE_FLOAT = Type(float)


class Options(Validator):
  """Limit field based on pre-determined values.

  Options are used to make sure an enumerated set of values are the only
  one permitted for assignment.  It is possible to define aliases which
  map multiple string values to a single original.  An example of usage:

    class ZooAnimal(validated.Class):
      ATTRIBUTES = {
        'name': str,
        'kind': Options('platypus',                   # No aliases
                        ('rhinoceros', ['rhino']),    # One alias
                        ('canine', ('dog', 'puppy')), # Two aliases
                        )
  """

  def __init__(self, *options, **kw):
    """Initialize options.

    Args:
      options: List of allowed values.
    """
    if 'default' in kw:
      default = kw['default']
    else:
      default = None

    alias_map = {}
    def AddAlias(alias, original):
      """Set new alias on alias_map.

      Raises:
        AttributeDefinitionError when option already exists or if alias is
        not of type str..
      """
      if not isinstance(alias, str):
        raise AttributeDefinitionError(
            'All option values must be of type str.')
      elif alias in alias_map:
        raise AttributeDefinitionError(
            "Option '%s' already defined for options property." % alias)
      alias_map[alias] = original

    for option in options:
      if isinstance(option, str):
        AddAlias(option, option)

      elif isinstance(option, (list, tuple)):
        if len(option) != 2:
          raise AttributeDefinitionError("Alias is defined as a list of tuple "
                                         "with two items.  The first is the "
                                         "original option, while the second "
                                         "is a list or tuple of str aliases.\n"
                                         "\n  Example:\n"
                                         "      ('original', ('alias1', "
                                         "'alias2'")
        original, aliases = option
        AddAlias(original, original)
        if not isinstance(aliases, (list, tuple)):
          raise AttributeDefinitionError('Alias lists must be a list or tuple')

        for alias in aliases:
          AddAlias(alias, original)

      else:
        raise AttributeDefinitionError("All options must be of type str "
                                       "or of the form (str, [str...]).")
    super(Options, self).__init__(default)
    self.options = alias_map

  def Validate(self, value):
    """Validate options.

    Returns:
      Original value for provided alias.

    Raises:
      ValidationError when value is not one of predefined values.
    """
    if value is None:
      raise ValidationError('Value for options field must not be None.')
    value = str(value)
    if value not in self.options:
      raise ValidationError('Value \'%s\' not in %s.'
                            % (value, self.options))
    return self.options[value]


class Optional(Validator):
  """Definition of optional attributes.

  Optional values are attributes which can be set to None or left
  unset.  All values in a basic Validated class are set to None
  at initialization.  Failure to assign to non-optional values
  will result in a validation error when calling CheckInitialized.
  """

  def __init__(self, validator, default=None):
    """Initializer.

    This constructor will make a few guesses about the value passed in
    as the validator:

      - If the validator argument is a type, it automatically creates a Type
        validator around it.

      - If the validator argument is a list or tuple, it automatically
        creates an Options validator around it.

    Args:
      validator: Optional validation condition.

    Raises:
      AttributeDefinitionError if validator is not callable.
    """
    self.validator = AsValidator(validator)
    self.expected_type = self.validator.expected_type
    self.default = default

  def Validate(self, value):
    """Optionally require a value.

    Normal validators do not accept None.  This will accept none on
    behalf of the contained validator.

    Args:
      value: Value to be validated as optional.

    Returns:
      None if value is None, else results of contained validation.
    """
    if value is None:
      return None
    return self.validator(value)


class Regex(Validator):
  """Regular expression validator.

  Regular expression validator always converts value to string.  Note that
  matches must be exact.  Partial matches will not validate.  For example:

    class ClassDescr(Validated):
      ATTRIBUTES = { 'name': Regex(r'[a-zA-Z_][a-zA-Z_0-9]*'),
                     'parent': Type(type),
                     }

  Alternatively, any attribute that is defined as a string is automatically
  interpreted to be of type Regex.  It is possible to specify unicode regex
  strings as well.  This approach is slightly less efficient, but usually
  is not significant unless parsing large amounts of data:

    class ClassDescr(Validated):
      ATTRIBUTES = { 'name': r'[a-zA-Z_][a-zA-Z_0-9]*',
                     'parent': Type(type),
                     }

    # This will raise a ValidationError exception.
    my_class(name='AName with space', parent=AnotherClass)
  """

  def __init__(self, regex, string_type=unicode, default=None):
    """Initialized regex validator.

    Args:
      regex: Regular expression string to use for comparison.

    Raises:
      AttributeDefinitionError if string_type is not a kind of string.
    """
    super(Regex, self).__init__(default)
    if (not issubclass(string_type, basestring) or
        string_type is basestring):
      raise AttributeDefinitionError(
          'Regex fields must be a string type not %s.' % str(string_type))
    if isinstance(regex, basestring):
      self.re = re.compile('^%s$' % regex)
    else:
      raise AttributeDefinitionError(
          'Regular expression must be string.  Found %s.' % str(regex))

    self.expected_type = string_type

  def Validate(self, value):
    """Does validation of a string against a regular expression.

    Args:
      value: String to match against regular expression.

    Raises:
      ValidationError when value does not match regular expression or
      when value does not match provided string type.
    """
    if issubclass(self.expected_type, str):
      cast_value = TYPE_STR(value)
    else:
      cast_value = TYPE_UNICODE(value)

    if self.re.match(cast_value) is None:
      raise ValidationError('Value \'%s\' does not match expression \'%s\''
                            % (value, self.re.pattern))
    return cast_value


class _RegexStrValue(object):
  """Simulates the regex object to support recomplation when necessary.

  Used by the RegexStr class to dynamically build and recompile regular
  expression attributes of a validated object.  This object replaces the normal
  object returned from re.compile which is immutable.

  When the value of this object is a string, that string is simply used as the
  regular expression when recompilation is needed.  If the state of this object
  is a list of strings, the strings are joined in to a single 'or' expression.
  """

  def __init__(self, attribute, value):
    """Initialize recompilable regex value.

    Args:
      attribute: Attribute validator associated with this regex value.
      value: Initial underlying python value for regex string.  Either a single
        regex string or a list of regex strings.
    """
    self.__attribute = attribute
    self.__value = value
    self.__regex = None

  def __AsString(self, value):
    """Convert a value to appropriate string.

    Returns:
      String version of value with all carriage returns and line feeds removed.
    """
    if issubclass(self.__attribute.expected_type, str):
      cast_value = TYPE_STR(value)
    else:
      cast_value = TYPE_UNICODE(value)

    cast_value = cast_value.replace('\n', '')
    cast_value = cast_value.replace('\r', '')
    return cast_value

  def __BuildRegex(self):
    """Build regex string from state.

    Returns:
      String version of regular expression.  Sequence objects are constructed
      as larger regular expression where each regex in the list is joined with
      all the others as single 'or' expression.
    """
    if isinstance(self.__value, list):
      value_list = self.__value
      sequence = True
    else:
      value_list = [self.__value]
      sequence = False

    regex_list = []
    for item in value_list:
      regex_list.append(self.__AsString(item))

    if sequence:
      return '|'.join('(?:%s)' % item for item in regex_list)
    else:
      return regex_list[0]

  def __Compile(self):
    """Build regular expression object from state.

    Returns:
      Compiled regular expression based on internal value.
    """
    regex = self.__BuildRegex()
    try:
      return re.compile(regex)
    except re.error, e:
      raise ValidationError('Value \'%s\' does not compile: %s' % (regex, e), e)

  @property
  def regex(self):
    """Compiled regular expression as described by underlying value."""
    return self.__Compile()

  def match(self, value):
    """Match against internal regular expression.

    Returns:
      Regular expression object built from underlying value.
    """
    return re.match(self.__BuildRegex(), value)

  def Validate(self):
    """Ensure that regex string compiles."""
    self.__Compile()

  def __str__(self):
    """Regular expression string as described by underlying value."""
    return self.__BuildRegex()

  def __eq__(self, other):
    """Comparison against other regular expression string values."""
    if isinstance(other, _RegexStrValue):
      return self.__BuildRegex() == other.__BuildRegex()
    return str(self) == other

  def __ne__(self, other):
    """Inequality operator for regular expression string value."""
    return not self.__eq__(other)


class RegexStr(Validator):
  """Validates that a string can compile as a regex without errors.

  Use this validator when the value of a field should be a regex.  That
  means that the value must be a string that can be compiled by re.compile().
  The attribute will then be a compiled re object.
  """

  def __init__(self, string_type=unicode, default=None):
    """Initialized regex validator.

    Raises:
      AttributeDefinitionError if string_type is not a kind of string.
    """
    if default is not None:
      default = _RegexStrValue(self, default)
      re.compile(str(default))
    super(RegexStr, self).__init__(default)
    if (not issubclass(string_type, basestring) or
        string_type is basestring):
      raise AttributeDefinitionError(
          'RegexStr fields must be a string type not %s.' % str(string_type))

    self.expected_type = string_type

  def Validate(self, value):
    """Validates that the string compiles as a regular expression.

    Because the regular expression might have been expressed as a multiline
    string, this function also strips newlines out of value.

    Args:
      value: String to compile as a regular expression.

    Raises:
      ValueError when value does not compile as a regular expression.  TypeError
      when value does not match provided string type.
    """
    if isinstance(value, _RegexStrValue):
      return value
    value = _RegexStrValue(self, value)
    value.Validate()
    return value

  def ToValue(self, value):
    """Returns the RE pattern for this validator."""
    return str(value)


class Range(Validator):
  """Validates that numbers fall within the correct range.

  In theory this class can be emulated using Options, however error
  messages generated from that class will not be very intelligible.
  This class essentially does the same thing, but knows the intended
  integer range.

  Also, this range class supports floats and other types that implement
  ordinality.

  The range is inclusive, meaning 3 is considered in the range
  in Range(1,3).
  """

  def __init__(self, minimum, maximum, range_type=int, default=None):
    """Initializer for range.

    Args:
      minimum: Minimum for attribute.
      maximum: Maximum for attribute.
      range_type: Type of field.  Defaults to int.
    """
    super(Range, self).__init__(default)
    if not isinstance(minimum, range_type):
      raise AttributeDefinitionError(
          'Minimum value must be of type %s, instead it is %s (%s).' %
          (str(range_type), str(type(minimum)), str(minimum)))
    if not isinstance(maximum, range_type):
      raise AttributeDefinitionError(
          'Maximum value must be of type %s, instead it is %s (%s).' %
          (str(range_type), str(type(maximum)), str(maximum)))

    self.minimum = minimum
    self.maximum = maximum
    self.expected_type = range_type
    self._type_validator = Type(range_type)

  def Validate(self, value):
    """Validate that value is within range.

    Validates against range-type then checks the range.

    Args:
      value: Value to validate.

    Raises:
      ValidationError when value is out of range.  ValidationError when value
      is notd of the same range type.
    """
    cast_value = self._type_validator.Validate(value)
    if cast_value < self.minimum or cast_value > self.maximum:
      raise ValidationError('Value \'%s\' is out of range %s - %s'
                            % (str(value),
                               str(self.minimum),
                               str(self.maximum)))
    return cast_value


class Repeated(Validator):
  """Repeated field validator.

  Indicates that attribute is expected to be a repeated value, ie,
  a sequence.  This adds additional validation over just Type(list)
  in that it retains information about what can be stored in the list by
  use of its constructor field.
  """

  def __init__(self, constructor, default=None):
    """Initializer for repeated field.

    Args:
      constructor: Type used for verifying elements of sequence attribute.
    """
    super(Repeated, self).__init__(default)
    self.constructor = constructor
    self.expected_type = list

  def Validate(self, value):
    """Do validation of sequence.

    Value must be a list and all elements must be of type 'constructor'.

    Args:
      value: Value to validate.

    Raises:
      ValidationError if value is None, not a list or one of its elements is the
      wrong type.
    """
    if not isinstance(value, list):
      raise ValidationError('Repeated fields must be sequence, '
                            'but found \'%s\'.' % value)

    for item in value:
      if isinstance(self.constructor, Validator):
        item = self.constructor.Validate(item)
      elif not isinstance(item, self.constructor):
        raise ValidationError('Repeated items must be %s, but found \'%s\'.'
                              % (str(self.constructor), str(item)))

    return value
