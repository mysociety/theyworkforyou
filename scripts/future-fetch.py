#!/usr/bin/python
# encoding: utf-8

import json
import os
import sys
import re
import urllib2
import MySQLdb

import datetime

# Set up commonlib pylib
package_dir = os.path.abspath(os.path.split(__file__)[0])
sys.path.append(os.path.normpath(package_dir + "/../commonlib/pylib"))

# And from that, get the config
from mysociety import config
config.set_file(os.path.abspath(package_dir + "/../conf/general"))

# And now we have config, find parlparse
sys.path.append(os.path.normpath(config.get('PWMEMBERS') + '../pyscraper'))
# This name matching could be done a lot better
from resolvemembernames import memberList
from lords.resolvenames import lordsList

CALENDAR_LINK = 'https://whatson.parliament.uk/%(place)s/%(iso)s/'
CALENDAR_BASE = 'https://whatson-api.parliament.uk/calendar/events/list.json?queryParameters.startDate=%(date)s'

positions = {}


def fetch_url(date):
    data = CALENDAR_BASE % {'date': date}
    data = urllib2.urlopen(data)
    data = json.load(data)
    return data

def get_calendar_events():
    date = datetime.date.today()
    data = fetch_url(date)
    data = sorted(data, key=lambda x: x['StartDate'] + x['StartTime'])
    for event in data:
        yield Entry(event)


class Entry(object):
    id = None
    modified = None
    deleted = 0
    link_calendar = None
    link_external = ''
    body = 'uk'
    chamber = None
    event_date = None
    time_start = None
    time_end = None
    committee_name = ''
    debate_type = ''
    title = ''
    witnesses = None
    witnesses_str = ''
    location = ''

    def __init__(self, event):
        house = event['House'] # Lords / Commons / Joint
        chamber = event['Type'] # Select & Joint Committees, General Committee, Grand Committee, Main Chamber, Westminster Hall

        if chamber == 'Select & Joint Committees':
            house_url = 'committees'
            if house == 'Joint':
                self.chamber = 'Joint Committee'
            else:
                self.chamber = '%s: Select Committee' % house
        else:
            self.chamber = '%s: %s' % (house, chamber)
            house_url = house.lower()

        # Two separate ID flows, for committees and not, it appears
        # We only have the one primary key
        self.id = event['Id']
        if house_url == 'committees':
            self.id += 1000000

        self.event_date = event['StartDate'][0:10]
        self.time_start = event['StartTime'] or None
        self.time_end = event['EndTime'] or None
        self.link_calendar = CALENDAR_LINK % {'place': house_url, 'iso': self.event_date}

        if event['Category'] == "Prime Minister's Question Time":
            self.debate_type = 'Oral questions'
            self.title = event['Category']
        else:
            self.debate_type = event['Category']
            self.title = event['Description'] or ''

        committee = event['Committee']
        if committee:
            self.committee_name = committee['Description'] or ''
            subject = (committee['Inquiries'] or [{}])[0].get('Description')
            if subject and self.title:
                self.title += ': ' + subject
            elif subject:
                self.title = subject

        self.people = []
        for member in event['Members']:
            id = str(member['Id'])
            match = memberList.match_by_mnis(id, self.event_date)
            if not match:
                match = lordsList.match_by_mnis(id, self.event_date)
            if match:
		self.people.append(
		    int(match['id'].replace('uk.org.publicwhip/person/', ''))
		    )

        self.witnesses = []
        witnesses_str = []
        for activity in event['EventActivities'] or []:
            for attendee in activity['Attendees']:
                m = re.match(r'\b(\w+ \w+ MP)', attendee)
                if m:
                    mp = m.group(1)
                    id, name, cons = memberList.matchfullnamecons(
                        mp, None, self.event_date
                    )
                    if id:
                        pid = int(id.replace('uk.org.publicwhip/person/', ''))
                        mp_link = '<a href="/mp/?p=%d">%s</a>' % (pid, mp)
                        self.witnesses.append(pid)
                        witnesses_str.append(attendee.replace(mp, mp_link))
                        continue
                witnesses_str.append(attendee)
        self.witnesses_str = '\n'.join(witnesses_str)

        self.location = event['Location'] or ''

    def get_tuple(self):
        return (
            self.id, self.deleted,
            self.link_calendar, self.link_external,
            self.body, self.chamber,
            self.event_date, self.time_start, self.time_end,
            self.committee_name,
            self.debate_type,
            self.title,
            self.witnesses_str,
            self.location,
            )

    def add(self):
        # TODO This function needs to insert into Xapian as well, or store to
        # insert in one go at the end
        db_cursor.execute("""INSERT INTO future (
            id, modified, deleted,
            link_calendar, link_external,
            body, chamber,
            event_date, time_start, time_end,
            committee_name, debate_type,
            title, witnesses, location
        ) VALUES (
            %s, now(), %s,
            %s, %s,
            %s, %s,
            %s, %s, %s,
            %s, %s,
            %s, %s, %s
        )""", self.get_tuple()
                          )

        self.update_people(delete_old=False)

    def update_people(self, delete_old=True):
        new_people = [(self.id, person, 0) for person in self.people]
        new_witnesses = [(self.id, witness, 1) for witness in self.witnesses]

        if delete_old:
            db_cursor.execute(
                'DELETE FROM future_people where calendar_id = %s',
                (self.id,))

        db_cursor.executemany(
            '''INSERT INTO future_people(calendar_id, person_id, witness)
                  VALUES (%s, %s, %s)''',
            new_people + new_witnesses
            )

    def update(self):
        event_tuple = self.get_tuple()

        db_cursor.execute(
            """
          UPDATE future SET
            modified = now(),
            deleted = %s,
            link_calendar = %s,
            link_external = %s,
            body = %s,
            chamber = %s,
            event_date = %s,
            time_start = %s,
            time_end = %s,
            committee_name = %s,
            debate_type = %s,
            title = %s,
            witnesses = %s,
            location = %s
          WHERE
            id = %s
            """,
            event_tuple[1:] + (self.id,)
            )

        self.update_people()

db_connection = MySQLdb.connect(
    host=config.get('TWFY_DB_HOST'),
    db=config.get('TWFY_DB_NAME'),
    user=config.get('TWFY_DB_USER'),
    passwd=config.get('TWFY_DB_PASS'),
    charset='utf8',
    )

db_cursor = db_connection.cursor()



# Get the id's of entries from the future as the database sees it.
# We'll delete ids from here as we go, and what's left will be things
# which are no longer in Future Business.
db_cursor.execute('select id from future where event_date > CURRENT_DATE()')
old_entries = set(db_cursor.fetchall())

for new_entry in get_calendar_events():
    id = new_entry.id
    event_date = new_entry.event_date
    positions[event_date] = positions.setdefault(event_date, 0) + 1

    row_count = db_cursor.execute(
        '''SELECT id, deleted,
           link_calendar, link_external,
           body, chamber,
           event_date, time_start, time_end,
           committee_name, debate_type,
           title, witnesses, location
         FROM future
         WHERE id=%s''',
        (id,)
        )

    if row_count:
        # We have seen this event before. TODO Compare with current entry,
        # update db and Xapian if so
        old_row = db_cursor.fetchone()

        # For some reason the time fields come out as timedelta rather that
        # time, so need converting.
        old_tuple = \
            old_row[0:6] + \
            (old_row[6].isoformat(), ) + \
            ((datetime.datetime.min + old_row[7]).time().isoformat() if old_row[7] is not None else None,) + \
            ((datetime.datetime.min + old_row[8]).time().isoformat() if old_row[8] is not None else None,) + \
            old_row[9:]

        new_tuple = new_entry.get_tuple()

        if old_tuple != new_tuple:
            new_entry.update()

        old_entries.discard((long(id),))
    else:
        new_entry.add()

    db_cursor.execute(
        'UPDATE future SET pos=%s WHERE id=%s', (positions[event_date], id)
        )

db_cursor.executemany(
    'UPDATE future SET deleted=1 WHERE id=%s', tuple(old_entries)
    )

db_connection.commit()
