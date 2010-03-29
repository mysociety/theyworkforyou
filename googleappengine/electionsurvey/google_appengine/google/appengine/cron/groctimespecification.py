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


"""Implementation of scheduling for Groc format schedules.

A Groc schedule looks like '1st,2nd monday 9:00', or 'every 20 mins'. This
module takes a parsed schedule (produced by Antlr) and creates objects that
can produce times that match this schedule.

A parsed schedule is one of two types - an Interval or a Specific Time.
See the class docstrings for more.

Extensions to be considered:

  allowing a comma separated list of times to run
  allowing the user to specify particular days of the month to run
"""


import calendar
import datetime

try:
  import pytz
except ImportError:
  pytz = None

import groc

HOURS = 'hours'
MINUTES = 'minutes'

try:
  from pytz import NonExistentTimeError
  from pytz import AmbiguousTimeError
except ImportError:

  class NonExistentTimeError(Exception):
    pass

  class AmbiguousTimeError(Exception):
    pass


def GrocTimeSpecification(schedule, timezone=None):
  """Factory function.

  Turns a schedule specification into a TimeSpecification.

  Arguments:
    schedule: the schedule specification, as a string
    timezone: the optional timezone as a string for this specification.
        Defaults to 'UTC' - valid entries are things like 'Australia/Victoria'
        or 'PST8PDT'.
  Returns:
    a TimeSpecification instance
  """
  parser = groc.CreateParser(schedule)
  parser.timespec()

  if parser.getTokenStream().LT(1).getText():
    raise groc.GrocException(
        'Extra token %r' % parser.getTokenStream().LT(1).getText())

  if parser.period_string:
    return IntervalTimeSpecification(parser.interval_mins,
                                     parser.period_string,
                                     parser.synchronized)
  else:
    return SpecificTimeSpecification(parser.ordinal_set, parser.weekday_set,
                                     parser.month_set,
                                     parser.monthday_set,
                                     parser.time_string,
                                     timezone)


class TimeSpecification(object):
  """Base class for time specifications."""

  def GetMatches(self, start, n):
    """Returns the next n times that match the schedule, starting at time start.

    Arguments:
      start: a datetime to start from. Matches will start from after this time.
      n:     the number of matching times to return

    Returns:
      a list of n datetime objects
    """
    out = []
    for _ in range(n):
      start = self.GetMatch(start)
      out.append(start)
    return out

  def GetMatch(self, start):
    """Returns the next match after time start.

    Must be implemented in subclasses.

    Arguments:
      start: a datetime to start with. Matches will start from this time.

    Returns:
      a datetime object
    """
    raise NotImplementedError


class IntervalTimeSpecification(TimeSpecification):
  """A time specification for a given interval.

  An Interval type spec runs at the given fixed interval. It has three
  attributes:
  period - the type of interval, either 'hours' or 'minutes'
  interval - the number of units of type period.
  synchronized - whether to synchronize the times to be locked to a fixed
      period (midnight).
  """

  def __init__(self, interval, period, synchronized=False):
    super(IntervalTimeSpecification, self).__init__()
    if interval < 1:
      raise groc.GrocException('interval must be greater than zero')
    self.interval = interval
    self.period = period
    self.synchronized = synchronized
    if self.period == HOURS:
      self.seconds = self.interval * 3600
    else:
      self.seconds = self.interval * 60
    if self.synchronized:
      if (self.seconds > 86400) or ((86400 % self.seconds) != 0):
        raise groc.GrocException('can only use synchronized for periods that'
                                 ' divide evenly into 24 hours')

  def GetMatch(self, t):
    """Returns the next match after time 't'.

    Arguments:
      t: a datetime to start from. Matches will start from after this time.

    Returns:
      a datetime object
    """
    if not self.synchronized:
      return t + datetime.timedelta(seconds=self.seconds)
    else:
      daystart = t.replace(hour=0, minute=0, second=0, microsecond=0)
      dayseconds = (t - daystart).seconds
      delta = self.seconds - (dayseconds % self.seconds)
      return t + datetime.timedelta(seconds=delta)


class SpecificTimeSpecification(TimeSpecification):
  """Specific time specification.

  A Specific interval is more complex, but defines a certain time to run and
  the days that it should run. It has the following attributes:
  time     - the time of day to run, as 'HH:MM'
  ordinals - first, second, third &c, as a set of integers in 1..5
  months   - the months that this should run, as a set of integers in 1..12
  weekdays - the days of the week that this should run, as a set of integers,
             0=Sunday, 6=Saturday
  timezone - the optional timezone as a string for this specification.
             Defaults to UTC - valid entries are things like Australia/Victoria
             or PST8PDT.

  A specific time schedule can be quite complex. A schedule could look like
  this:
  '1st,third sat,sun of jan,feb,mar 09:15'

  In this case, ordinals would be {1,3}, weekdays {0,6}, months {1,2,3} and
  time would be '09:15'.
  """

  timezone = None

  def __init__(self, ordinals=None, weekdays=None, months=None, monthdays=None,
               timestr='00:00', timezone=None):
    super(SpecificTimeSpecification, self).__init__(self)
    if weekdays and monthdays:
      raise ValueError('cannot supply both monthdays and weekdays')
    if ordinals is None:
      self.ordinals = set(range(1, 6))
    else:
      self.ordinals = set(ordinals)

    if weekdays is None:
      self.weekdays = set(range(7))
    else:
      self.weekdays = set(weekdays)

    if months is None:
      self.months = set(range(1, 13))
    else:
      self.months = set(months)

    if not monthdays:
      self.monthdays = set()
    else:
      if max(monthdays) > 31 or min(monthdays) < 1:
        raise ValueError('invalid day of month')
      self.monthdays = set(monthdays)
    hourstr, minutestr = timestr.split(':')
    self.time = datetime.time(int(hourstr), int(minutestr))
    if timezone:
      if pytz is None:
        raise ValueError('need pytz in order to specify a timezone')
      self.timezone = pytz.timezone(timezone)

  def _MatchingDays(self, year, month):
    """Returns matching days for the given year and month.

    For the given year and month, return the days that match this instance's
    day specification, based on either (a) the ordinals and weekdays, or
    (b) the explicitly specified monthdays.  If monthdays are specified,
    dates that fall outside the range of the month will not be returned.

    Arguments:
      year: the year as an integer
      month: the month as an integer, in range 1-12

    Returns:
      a list of matching days, as ints in range 1-31
    """
    start_day, last_day = calendar.monthrange(year, month)
    if self.monthdays:
      return sorted([day for day in self.monthdays if day <= last_day])

    out_days = []
    start_day = (start_day + 1) % 7
    for ordinal in self.ordinals:
      for weekday in self.weekdays:
        day = ((weekday - start_day) % 7) + 1
        day += 7 * (ordinal - 1)
        if day <= last_day:
          out_days.append(day)
    return sorted(out_days)

  def _NextMonthGenerator(self, start, matches):
    """Creates a generator that produces results from the set 'matches'.

    Matches must be >= 'start'. If none match, the wrap counter is incremented,
    and the result set is reset to the full set. Yields a 2-tuple of (match,
    wrapcount).

    Arguments:
      start: first set of matches will be >= this value (an int)
      matches: the set of potential matches (a sequence of ints)

    Yields:
      a two-tuple of (match, wrap counter). match is an int in range (1-12),
      wrapcount is a int indicating how many times we've wrapped around.
    """
    potential = matches = sorted(matches)
    after = start - 1
    wrapcount = 0
    while True:
      potential = [x for x in potential if x > after]
      if not potential:
        wrapcount += 1
        potential = matches
      after = potential[0]
      yield (after, wrapcount)

  def GetMatch(self, start):
    """Returns the next time that matches the schedule after time start.

    Arguments:
      start: a UTC datetime to start from. Matches will start after this time

    Returns:
      a datetime object
    """
    start_time = start
    if self.timezone and pytz is not None:
      if not start_time.tzinfo:
        start_time = pytz.utc.localize(start_time)
      start_time = start_time.astimezone(self.timezone)
      start_time = start_time.replace(tzinfo=None)
    if self.months:
      months = self._NextMonthGenerator(start_time.month, self.months)
    while True:
      month, yearwraps = months.next()
      candidate_month = start_time.replace(day=1, month=month,
                                           year=start_time.year + yearwraps)

      day_matches = self._MatchingDays(candidate_month.year, month)

      if ((candidate_month.year, candidate_month.month)
          == (start_time.year, start_time.month)):
        day_matches = [x for x in day_matches if x >= start_time.day]
        while (day_matches and day_matches[0] == start_time.day
               and start_time.time() >= self.time):
          day_matches.pop(0)
      while day_matches:
        out = candidate_month.replace(day=day_matches[0], hour=self.time.hour,
                                      minute=self.time.minute, second=0,
                                      microsecond=0)
        if self.timezone and pytz is not None:
          try:
            out = self.timezone.localize(out, is_dst=None)
          except AmbiguousTimeError:
            out = self.timezone.localize(out)
          except NonExistentTimeError:
            for _ in range(24):
              out = out.replace(minute=1) + datetime.timedelta(minutes=60)
              try:
                out = self.timezone.localize(out)
              except NonExistentTimeError:
                continue
              break
          out = out.astimezone(pytz.utc)
        return out
