#!/usr/bin/python

import os
import sys
import re
from lxml import objectify
import MySQLdb

import datetime

# Set up commonlib pylib
package_dir = os.path.abspath(os.path.split(__file__)[0])
sys.path.append( os.path.normpath(package_dir + "/../commonlib/pylib") )

# And from that, get the config
from mysociety import config
config.set_file(os.path.abspath(package_dir + "/../conf/general"))

# And now we have config, find parlparse
sys.path.append( os.path.normpath(config.get('PWMEMBERS') + '../pyscraper') )
sys.path.append( os.path.normpath(config.get('PWMEMBERS') + '../pyscraper/lords') )
# This name matching could be done a lot better
from resolvemembernames import memberList
from resolvelordsnames import lordsList

CALENDAR_URL = 'http://services.parliament.uk/calendar/all.rss'

class Entry(object):                                                                                                                                                          
    id = None
    created = None
    deleted = 0
    link_calendar = None
    link_external = None
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

    def __init__(self, entry):
        self.id = entry.event.attrib['id']
        self.deleted = 0
        self.link_calendar = entry.guid
        self.link_external = entry.link
        chamber = entry.event.chamber.text.strip()
        self.chamber = '%s: %s' % (entry.event.house.text.strip(), chamber)
        self.event_date = entry.event.date
        self.time_start = getattr(entry.event, 'startTime', None)
        self.time_end = getattr(entry.event, 'endTime', None)

        committee_text = entry.event.comittee.text
        if committee_text:
            committee_text = committee_text.strip()
            if chamber in ('Select Committee', 'General Committee'):
                self.committee_name = committee_text
            else:
                self.debate_type = committee_text

        self.people = []

        title_text = entry.event.inquiry.text
        if title_text:
            m = re.search(' - ([^-]*)$', title_text)
            if m:
                person_texts = [x.strip() for x in m.group(1).split('/')]

                for person_text in person_texts:
                    id, name, cons = memberList.matchfullnamecons(m.group(1), None, self.event_date)
                    if not id:
                        try:
                            id = lordsList.GetLordIDfname(m.group(1), None, self.event_date)
                        except:
                            pass
                    if id:
                        self.people.append(int(memberList.membertoperson(id).replace('uk.org.publicwhip/person/', '')))

            if self.people:
                title_text = title_text.replace(' - ' + m.group(1), '')

            self.title = title_text.strip()

        self.witnesses = []
        witness_text = entry.event.witnesses.text
        if witness_text == 'This is a private meeting.':
            self.title = witness_text
        elif witness_text:
            self.witnesses_str = witness_text.strip()
            m = re.findall(r'\b(\w+ \w+ MP)', self.witnesses_str)
            for mp in m:
                id, name, cons = memberList.matchfullnamecons(mp, None, self.event_date)
                if not id: continue
                pid = int(memberList.membertoperson(id).replace('uk.org.publicwhip/person/', ''))
                mp_link = '<a href="/mp/?p=%d">%s</a>' % (pid, mp)
                self.witnesses.append(pid)
                self.witnesses_str = self.witnesses_str.replace(mp, mp_link)

        location_text = entry.event.location.text
        if location_text: self.location = location_text.strip()

    def get_tuple(self):
        return (
            self.id, self.deleted,
            self.link_calendar, self.link_external,
            self.body, self.chamber,
            self.event_date, self.time_start, self.time_end,
            self.committee_name, self.debate_type,
            self.title.encode('iso-8859-1', 'xmlcharrefreplace'),
            self.witnesses_str.encode('iso-8859-1', 'xmlcharrefreplace'),
            self.location.encode('iso-8859-1', 'xmlcharrefreplace'),
            )

    def add(self):
        # TODO This function needs to insert into Xapian as well, or store to insert in one go at the end
        db_cursor.execute("""INSERT INTO future (
            id, created, deleted, 
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
    )

db_cursor = db_connection.cursor()


parsed = objectify.parse(CALENDAR_URL)
root = parsed.getroot()

# Get the id's of entries from the future as the database sees it.
# We'll delete ids from here as we go, and what's left will be things
# which are no longer in Future Business.
db_cursor.execute('select id from future where event_date > CURRENT_DATE()')
old_entries = set(db_cursor.fetchall())

entries = root.channel.findall('item')
for entry in entries:
    id = entry.event.attrib['id']
    new_entry = Entry(entry)

    row_count = db_cursor.execute(
        '''SELECT id, deleted, 
           link_calendar, link_external,
           body, chamber, 
           event_date, time_start, time_end, 
           committee_name, debate_type, 
           title, witnesses, location
         FROM future 
         WHERE id=%s''', 
        id,
        )

    if row_count:
        # We have seen this event before. TODO Compare with current entry,
        # update db and Xapian if so
        old_row = db_cursor.fetchone()

        # For some reason the time fields come out as timedelta rather that time, so need converting.
        old_tuple = (str(old_row[0]),) + \
            old_row[1:6] + \
            (old_row[6].isoformat(), ) + \
            ((datetime.datetime.min + old_row[7]).time().isoformat() if old_row[7] is not None else None,) + \
            ((datetime.datetime.min + old_row[8]).time().isoformat() if old_row[8] is not None else None,) + \
            old_row[9:]
        
        new_tuple = new_entry.get_tuple()

        if old_tuple != new_entry.get_tuple():
            new_entry.update()

        old_entries.discard( (long(id),) )
    else:
        new_entry.add()

db_cursor.executemany('UPDATE future SET deleted=1 WHERE id=%s', tuple(old_entries))
