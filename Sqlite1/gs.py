#
# Gramps - a GTK+/GNOME based genealogy program
#
# Copyright (C) 2013 william.bell@frog.za.net
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# References
# http://docs.python.org/2/library/xml.dom.minidom.html
# http://docs.python.org/2/library/xml.etree.elementtree.html#module-xml.etree.ElementTree

import sys
import getopt
import gzip
import xml.etree.cElementTree as ET
import sqlite3 as sqlite
import sys; sys.stdout = sys.stderr
import os

person_map = {}
note_map = {}
media_map = {}
repositories_map = {}
sources_map = {}
citation_map = {}
events_map = {}
family_map = {}
places_map = {}

attribute_types = {
    'Unknown':-1,
    'Custom':0,
    'Caste':1,
    'Description':2,
    'Identification Number':3,
    'National Origin':4,
    'Number of Children':5,
    'Social Security Number':6,
    'Nickname':7,
    'Cause':8,
    'Agency':9,
    'Age':10,
    "Father Age":11,
    "Mother Age":12,
    'Witness':13,
    'Time':14,
    'Blood Group':32}

repository_types = {
    'Unknown':-1,
    'Custom':0,
    'Library':1,
    'Cemetery':2,
    'Church':3,
    'Archive':4,
    'Album':5,
    'Web site':6,
    'Bookstore':7,
    'Collection':8,
    'Safe':9}

media_types = {
    'Unknown':-1,
    'Custom':0,
    'Audio':1,
    'Book':2,
    'Card':3,
    'Electronic':4,
    'Fiche':5,
    'Film':6,
    'Magazine':7,
    'Manuscript':8,
    'Map':9,
    'Newspaper':10,
    'Photo':11,
    'Tombstone':12,
    'Video':13}

event_types = {
    'Unknown':-1,
    'Custom':0,
    'Marriage':1,
    'Marriage Settlement':2,
    'Marriage License':3,
    'Marriage Contract':4,
    'Marriage Banns':5,
    'Engagement':6,
    'Divorce':7,
    'Divorce Filing':8,
    'Annulment':9,
    'Alternate Marriage':10,
    'Adopted':11,
    'Birth':12,
    'Death':13,
    'Adult Christening':14,
    'Baptism':15,
    'Bar Mitzvah':16,
    'Bas Mitzvah':17,
    'Blessing':18,
    'Burial':19,
    'Cause Of Death':20,
    'Census':21,
    'Christening':22,
    'Confirmation':23,
    'Cremation':24,
    'Degree':25,
    'Education':26,
    'Elected':27,
    'Emigration':28,
    'First Communion':29,
    'Immigration':30,
    'Graduation':31,
    'Medical Information':32,
    'Military Service':33,
    'Naturalization':34,
    'Nobility Title':35,
    'Number of Marriages':36,
    'Occupation':37,
    'Ordination':38,
    'Probate':39,
    'Property':40,
    'Religion':41,
    'Residence':42,
    'Retirement':43,
    'Will':44}

url_types = {
    'Unknown':-1,
    'Custom':0,
    'E-mail':1,
    'Web Home':2,
    'Web Search':3,
    'FTP':4}

date_types = {
    'regular':0,
    'before':1,
    'after':2,
    'about':3,
    'range':4,
    'span':5,
    'text only':6}

date_quality = {
    'regular':0,
    'estimated':1,
    'calculated':2}

calendar_types = {
    "Gregorian":0,
    "Julian":1,
    "Hebrew":2,
    "French Republican":3,
    "Persian":4,
    "Islamic":5,
    "Swedish":6}

name_types = {
    'Unknown':-1,
    'Custom':0,
    'Also Known As':1,
    'Birth Name':2,
    'Married Name':3
}


child_relationship_types = {
    'None':0,
    'Birth':1,
    'Adopted':2,
    'Stepchild':3,
    'Sponsored':4,
    'Foster':5,
    'Unknown':6,
    'Custom':7}

def makeDB(db):
    print('Creating database')

    db.query("""CREATE TABLE note (
                  gid TEXT PRIMARY KEY,
                  text TEXT,
                  preformatted BOOLEAN,
                  change INTEGER,
                  private BOOLEAN);""")

    db.query("""CREATE TABLE note_ref (
                 gid TEXT,
                 note_gid TEXT);""")

    db.query("""CREATE TABLE media (
                 gid TEXT PRIMARY KEY,
                 path TEXT,
                 mime TEXT,
                 description TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE media_ref (
                 gid TEXT,
                 media_gid TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE repository (
                 gid TEXT,
                 name TEXT,
                 the_type INTEGER,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE repository_ref (
                 gid TEXT,
                 repository_gid TEXT,
                 callno TEXT,
                 medium INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE url (
                 gid TEXT,
                 path TEXT,
                 description TEXT,
                 the_type INTEGER,
                 private BOOLEAN);
                 """)

    db.query("""CREATE TABLE source (
                 gid TEXT PRIMARY KEY,
                 title TEXT,
                 author TEXT,
                 pubinfo TEXT,
                 abbrev TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE source_ref (
                 gid TEXT,
                 source_gid,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE citation (
                 gid TEXT PRIMARY KEY,
                 confidence INTEGER,
                 page TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE citation_ref (
                 gid TEXT,
                 citation_gid,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE event (
                 gid TEXT PRIMARY KEY,
                 the_type INTEGER,
                 description TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE date (
              gid TEXT PRIMARY KEY,
              calendar INTEGER,
              the_type INTEGER,
              quality INTEGER,
              date1 TEXT,
              date2 TEXT);""")

    db.query("""CREATE TABLE attribute (
                 gid TEXT,
                 the_type TEXT,
                 value TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE person (
                  gid TEXT PRIMARY KEY,
                  gender INTEGER,
                  change INTEGER,
                  private BOOLEAN);""")

    db.query("""CREATE TABLE family (
                 gid TEXT,
                 father_gid TEXT,
                 mother_gid TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE event_ref (
                 gid TEXT,
                 event_gid TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE name (
                  gid TEXT,
                  the_type INTEGER,
                  primary_name BOOLEAN,
                  first_name TEXT,
                  suffix TEXT,
                  title TEXT,
                  group_as TEXT,
                  sort_as INTEGER,
                  display_as INTEGER,
                  call TEXT,
                  nick TEXT,
                  famnick TEXT,
                  change INTEGER,
                  private BOOLEAN);""")

    db.query("""CREATE TABLE surname (
                  gid TEXT,
                  surname TEXT,
                  prefix TEXT,
                  primary_surname BOOLEAN,
                  origin_type INTEGER,
                  connector TEXT);""")

    db.query("""CREATE TABLE child_ref (
                 gid TEXT,
                 child_gid TEXT,
                 frel INTEGER,
                 mrel INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE place (
                 gid TEXT PRIMARY KEY,
                 title TEXT,
                 long TEXT,
                 lat TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE location (
                 gid TEXT,
                 street TEXT,
                 locality TEXT,
                 city TEXT,
                 county TEXT,
                 state TEXT,
                 country TEXT,
                 postal TEXT,
                 phone TEXT,
                 parish TEXT);""")

    db.query("""CREATE TABLE place_ref (
                 gid TEXT,
                 place_gid TEXT);""")


    db.db.commit()

class Database(object):
    """
    The db connection.
    """
    def __init__(self, database):
        self.batch = False
        self.database = database
        self.db = sqlite.connect(self.database)
        self.cursor = self.db.cursor()

    def query(self, q, *args):
        args = list(args)
        #python2
        #for i in range(len(args)):
        #    if isinstance(args[i], str):
        #        args[i] = unicode(args[i])
        if q.strip().upper().startswith("DROP"):
            try:
                self.cursor.execute(q, args)
                self.db.commit()
            except:
                "WARN: no such table to drop: '%s'" % q
        else:
            try:
                #print q
                #print args
                self.cursor.execute(q, args)
                if not self.batch:
                    self.db.commit()
            except (getopt.GetoptError) as s:
                print(s)
                print("ERROR: query :", q)
                print("ERROR: values:", args)
                raise
            return self.cursor.fetchall()

    def close(self):
        """ Closes and writes out tables """
        self.cursor.close()
        self.db.close()

def usage():
    print("""Usage: gramps_to_sqlite [OPTION]...
Convert the gramps xml database to sqlite database

-i INPUT                     Use INPUT as input "data.gramps" file
-o OUTPUT                    Output SQLite file, gramps1.db if unspecified.
--help                       Display this help and exit

Report bugs to <william.bell@frog.za.net>.""")

def export_notes(db, notes_node):
    print ('Exporting notes')
    db.batch = 1
    for note_node in notes_node:
        _private = 0
        _preformat = 0
        _change = 0
        for att in note_node.attrib:
            if att == 'handle':
                _handle = note_node.get(att)
            elif att == 'id':
                _id = note_node.get(att)
            elif att == 'change':
                _change = note_node.get(att)
            elif att == 'format':
                _preformat = note_node.get(att)
            elif att == 'priv':
                _private = note_node.get(att)
        note_map[_handle] = _id
        for child in note_node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'text':
                _text = child.text
        db.query("""INSERT into note (
                gid,
                text,
                preformatted,
                change,
                private) values (?, ?, ?, ?, ?);
                """,
                _id, _text, _preformat, _change, _private)
    db.db.commit()

def do_note_ref(db, gid, child):
    #print (gid, ref_gid)
    for att in child.attrib:
        if att == 'hlink':
            _hlink = child.get(att)
            _note_gid = note_map[_hlink]

    db.query("""INSERT into note_ref (
            gid,
            note_gid) values (?, ?);
            """,
            gid, _note_gid)

def export_places(db, places_node):
    print ('Exporting places')
    for node in places_node:
        _private = 0
        _change = 0
        _long = 0
        _lat = 0
        _title = ''
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        places_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'ptitle':
                _title = child.text
            elif _child == 'noteref':
                do_note_ref(db, _id, child)
            elif _child == 'coord':
                for att in child.attrib:
                    if att == 'long':
                        _long = child.get(att)
                    elif att == 'lat':
                        _lat = child.get(att)
            elif _child == 'location':
                do_location(db, _id, child)
            elif _child == 'url':
                do_url(db, _id, child)

        db.query("""INSERT into place (
                gid,
                title,
                long,
                lat,
                change,
                private) values (?, ?, ?, ?, ?, ?);
                """,
                _id, _title, _long, _lat, _change, _private)
    db.db.commit()

def do_places_ref(db, gid, child):
    #print (gid, ref_gid)
    for att in child.attrib:
        if att == 'hlink':
            _hlink = child.get(att)
            _place_gid = places_map[_hlink]

    db.query("""INSERT into place_ref (
            gid,
            place_gid) values (?, ?);
            """,
            gid, _place_gid)

def do_location(db, gid, child):
    _street = ''
    _locality = ''
    _city = ''
    _county = ''
    _state = ''
    _country = ''
    _postal = ''
    _phone = ''
    _parish = ''

    for att in child.attrib:
        if att == 'street':
            _street = child.get(att)
        elif att == 'locality':
            _locality = child.get(att)
        elif att == 'city':
            _city = child.get(att)
        elif att == 'county':
            _county = child.get(att)
        elif att == 'state':
            _state = child.get(att)
        elif att == 'country':
            _country = child.get(att)
        elif att == 'postal':
            _postal = child.get(att)
        elif att == 'phone':
            _phone = child.get(att)
        elif att == 'parish':
            _parish = child.get(att)

    db.query("""INSERT into location (
            gid,
            street,
            locality,
            city,
            county,
            state,
            country,
            postal,
            phone,
            parish) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
            """,
            gid, _street, _locality, _city, _county, _state, _country, _postal, _phone, _parish)

def do_media_ref(db, gid, media_node):
    _private = 0
    for att in media_node.attrib:
        if att == 'hlink':
            _hlink = media_node.get(att)
            _gid = media_map[_hlink]
        elif att == 'priv':
            _private = media_node.get(att)

    #print (gid, _gid)
    db.query("""INSERT into media_ref (
            gid,
            media_gid,
            private) values (?, ?, ?);
            """,
            gid, _gid, _private)

def do_repo_ref(db, gid, repository_node):
    _private = 0
    _callno = ''
    for att in repository_node.attrib:
        if att == 'hlink':
            _hlink = repository_node.get(att)
            _gid = repositories_map[_hlink]
        elif att == 'callno':
            _callno = repository_node.get(att)
        elif att == 'medium':
            _medium = repository_node.get(att)
            _media_id = media_types[_medium]
        elif att == 'priv':
            _private = repository_node.get(att)

    db.query("""INSERT into repository_ref (
            gid,
            repository_gid,
            callno,
            medium,
            private) values (?, ?, ?, ?, ?);
            """,
            gid, _gid, _callno, _media_id, _private)

def do_url(db, gid, child):
    _description = ''
    _private = 0
    for att in child.attrib:
        if att == "href":
            _href = child.get(att)
        elif att == "type":
            _url_type = child.get(att)
        elif att == 'description':
            _description = child.get(att)
        elif att == 'priv':
            _private = child.get(att)

    if _url_type == 'Web Home':
        _type = 1
    elif _url_type == 'E-mail':
        _type = 2
    else:
        _type = 0

    db.query("""INSERT into url (
            gid,
            path,
            description,
            the_type,
            private) values (?, ?, ?, ?, ?);
            """,
            gid, _href, _description, _type, _private)

def do_date(db, gid, child):
    _description = ''
    _date2 = 0
    _calendar_type = 0
    _date_type = 0
    _date_quality = 0

    for att in child.attrib:
        if att == "val" or att == "start":
            _date1 = child.get(att)
        elif att == "type":
            _type = child.get(att)
            _date_type = date_types[_type]
        elif att == 'quality':
            _quality = child.get(att)
            _date_quality = date_quality[_quality]
        elif att == cformat:
            _calendar = child.get(att)
            _calendar_type = calendar_types[_calendar]
        elif att == 'stop':
            _date2 = child.get(att)

    db.query("""INSERT into date (
            gid,
            calendar,
            the_type,
            quality,
            date1,
            date2) values (?, ?, ?, ?, ?, ?);
            """,
            gid, _calendar_type, _date_type, _date_quality, _date1, _date2)

def do_citation_ref(db, gid, child):
    _private = 0
    for att in child.attrib:
        if att == "hlink":
            _hlink = child.get(att)
            _id = citation_map[_hlink]
        elif att == 'priv':
            _private = repository_node.get(att)

    db.query("""INSERT into citation_ref (
            gid,
            citation_gid,
            private) values (?, ?, ?);
            """,
            gid, _id, _private)

def export_media(db, media_node):
    print ('Exporting media')
    for node in media_node:
        _private = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        media_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'file':
                for att in child.attrib:
                    if att == "src":
                        _src = child.get(att)
                    elif att == "mime":
                        _mime = child.get(att)
                    elif att == "description":
                        _description = child.get(att)
            elif _child == 'noteref':
                do_note_ref(db, _id, child)
            #elif _child == 'attribute':
            #    type value


        db.query("""INSERT into media (
                gid,
                path,
                mime,
                description,
                change,
                private) values (?, ?, ?, ?, ?, ?);
                """,
                _id, _src, _mime, _description, _change, _private)
    db.db.commit()

def export_repositories(db, repository_node):
    print ('Exporting repositories')
    for node in repository_node:
        _private = 0
        _type = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        repositories_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'rname':
                _name = child.text
            elif _child == 'type':
                _type = repository_types[child.text]
            elif _child == 'url':
                do_url(db, _id, child)
            elif _child == 'noteref':
                do_note_ref(db, _id, child)
            elif _child == 'address':
                do_address(db, _id, child)
            #elif _child == 'attribute':
            #    type value

        db.query("""INSERT into repository (
                gid,
                name,
                the_type,
                change,
                private) values (?, ?, ?, ?, ?);
                """,
                _id, _name, _type, _change, _private)
    db.db.commit()

def do_address(db, gid, address_node):
    _street = ''
    _locality = ''
    _city = ''
    _county = ''
    _state = ''
    _country = ''
    _postal = ''
    _phone = ''
    _parish = ''

    for node in address_node:
        _child = node.tag.split('}', 1)[-1]
        if _child == 'street':
            _street = node.text
        elif _child == 'locality':
            _locality = node.text
        elif _child == 'city':
            _city = node.text
        elif _child == 'county':
            _county = node.text
        elif _child == 'state':
            _state = node.text
        elif _child == 'country':
            _country = node.text
        elif _child == 'postal':
            _postal = node.text
        elif _child == 'phone':
            _phone = node.text
        elif _child == 'parish':
            _parish = node.text
        elif _child == 'dateval':
            _dateval = 'todo get dateval val attribute'

    db.query("""INSERT into location (
            gid,
            street,
            locality,
            city,
            county,
            state,
            country,
            postal,
            phone,
            parish) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
            """,
            gid, _street, _locality, _city, _county, _state, _country, _postal, _phone, _parish)

def export_sources(db, sources_node):
    print ('Exporting sources')
    for node in sources_node:
        _private = 0
        _type = 0
        _author = ''
        _pubinfo = ''
        _abbrev = ''
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        sources_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'stitle':
                _title = child.text
            elif _child == 'sauthor':
                _author = child.text
            elif _child == 'spubinfo':
                _pubinfo = child.text
            elif _child == 'sabbrev':
                _abbrev = child.text
            elif _child == 'reporef':
                do_repo_ref(db, _id, child)
            elif _child == 'objref':
                do_media_ref(db, _id, child)
            #elif _child == 'data_item': todo
            elif _child == 'noteref':
                do_note_ref(db, _id, child)

        db.query("""INSERT into source (
                gid,
                title,
                author,
                pubinfo,
                abbrev,
                change,
                private) values (?, ?, ?, ?, ?, ?, ?);
                """,
                _id, _title, _author, _pubinfo, _abbrev, _change, _private)
    db.db.commit()

def export_citations(db, citations_node):
    print ('Exporting citations')
    for node in citations_node:
        _private = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        citation_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            #print (child.text)
            if _child == 'page':
                _page = child.text
            elif _child == 'confidence':
                _confidence = child.text
            elif _child == 'sourceref':
                do_source_ref(db, _id, child)
                for att in child.attrib:
                    if att == 'hlink':
                        _hlink = child.get(att)
            elif _child == 'noteref':
                do_note_ref(db, _id, child)

        db.query("""INSERT into citation (
                gid,
                confidence,
                page,
                change,
                private) values (?, ?, ?, ?, ?);
                """,
                _id, _confidence, _page, _change, _private)
    db.db.commit()

def do_source_ref(db, gid, sourceref_node):
    _private = 0
    for att in sourceref_node.attrib:
        if att == 'hlink':
            _hlink = sourceref_node.get(att)
            _gid = sources_map[_hlink]
        elif att == 'priv':
            _private = eventref_node.get(att)

    #print (gid, _gid)
    db.query("""INSERT into source_ref (
            gid,
            source_gid,
            private) values (?, ?, ?);
            """,
            gid, _gid, _private)

def export_events(db, events_node):
    print ('Exporting events')
    for node in events_node:
        _private = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        events_map[_handle] = _id
        _description = ''
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            #print (child.text)
            if _child == 'type':
                _type = child.text
                the_type = event_types[_type]
            elif _child == 'description':
                _description = child.text
            elif _child == 'attribute':
                do_attributes(db, _id, child)
            elif _child == 'dateval':
                do_date(db, _id, child)
            elif _child == 'noteref':
                do_note_ref(db, _id, child)
            elif _child == 'citationref':
                do_citation_ref(db, _id, child)
            elif _child == 'objref':
                do_media_ref(db, _id, child)
            elif _child == 'place':
                do_places_ref(db, _id, child)

        db.query("""INSERT into event (
                gid,
                the_type,
                description,
                change,
                private) values (?, ?, ?, ?, ?);
                """,
                _id, the_type, _description, _change, _private)
    db.db.commit()

def do_attributes(db, gid, child):
    _private = 0

    for att in child.attrib:
        if att == "type":
            _type = child.get(att)
            _the_type = attribute_types[_type]
        elif att == 'value':
            _value = child.get(att)
        elif att == 'priv':
            _private = child.get(att)
    #todo attribute notes

    db.query("""INSERT into attribute (
            gid,
            the_type,
            value,
            private) values (?, ?, ?, ?);
            """,
            gid, _the_type, _value, _private)

def do_event_ref(db, gid, eventref_node):
    _private = 0
    for att in eventref_node.attrib:
        if att == 'hlink':
            _hlink = eventref_node.get(att)
            _gid = events_map[_hlink]
        elif att == 'priv':
            _private = eventref_node.get(att)

    #print (gid, _gid)
    db.query("""INSERT into event_ref (
            gid,
            event_gid,
            private) values (?, ?, ?);
            """,
            gid, _gid, _private)

def do_surname(db, gid, child):
    #print('do surname', gid)
    _prefix = ''
    _surname = child.text
    _prim = 1
    _connector = ''
    for att in child.attrib:
        if att == "derivation":
            _derivation = child.get(att)
        elif att == 'prefix':
            _prefix = child.get(att)
        elif att == 'prim':
            _prim = child.get(att)
        elif att == 'connector':
            _connector = child.get(att)

    db.query("""INSERT into surname (
            gid,
            surname,
            prefix,
            primary_surname,
            connector) values (?, ?, ?, ?, ?);
            """,
            gid, _surname, _prefix, _prim, _connector)

def do_name(db, gid, name_node):
    _private = 0
    _primary = 1
    _first = ''
    _call = ''
    _nick = ''
    _suffix = ''
    for att in name_node.attrib:
        if att == 'type':
            _type = name_node.get(att)
            _name_type = name_types[_type]
        elif att == 'priv':
            _private = name_node.get(att)
        elif att == 'alt':
            _alt = name_node.get(att)
            if _alt == '1':
                _primary = 0

    for child in name_node:
        _child = child.tag.split('}', 1)[-1]
        #print(_child)
        if _child == 'first':
            _first = child.text
        elif _child == 'call':
            _call = child.text
        elif _child == 'title':
            _title = child.text
        elif _child == 'nick':
            _nick = child.text
        elif _child == 'suffix':
            _suffix = child.text
        elif _child == 'surname':
            do_surname(db, gid, child)
        #elif _child == 'noteref': todo

    db.query("""INSERT into name (
            gid,
            the_type,
            primary_name,
            first_name,
            suffix,
            call,
            nick,
            private) values (?, ?, ?, ?, ?, ?, ?, ?);
            """,
            gid, _name_type, _primary, _first, _suffix, _call, _nick, _private)

def export_people(db, people_node):
    print ("Exporting people")
    for node in people_node:
        _private = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'change':
                _change = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
        person_map[_handle] = _id
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'gender':
                _gender = child.text
            elif _child == 'attribute':
                do_attributes(db, _id, child)
            elif _child == 'url':
                do_url(db, _id, child)
            elif _child == 'eventref':
                do_event_ref(db, _id, child)
            elif _child == 'objref':
                do_media_ref(db, _id, child)
            elif _child == 'childof':
                for att in child.attrib:
                    if att == 'hlink':
                        _hlink = child.get(att)
                        _childof = family_map[_hlink]
            elif _child == 'parentin':
                for att in child.attrib:
                    if att == 'hlink':
                        _hlink = child.get(att)
                        _parentin = family_map[_hlink]
            elif _child == 'name':
                do_name(db, _id, child)
            elif _child == 'citationref':
                do_citation_ref(db, _id, child)
            elif _child == 'noteref':
                do_note_ref(db, _id, child)

        db.query("""INSERT into person (
                gid,
                gender,
                change,
                private) values (?, ?, ?, ?);
                """,
                _id, _gender, _change, _private)

    db.db.commit()

def do_family_map(family_node):
    print ("mapping families")
    for node in family_node:
        _private = 0
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'private':
                _private = node.get(att)
        #print(_handle, _id)
        family_map[_handle] = _id

def do_child_ref(db, gid, node):
    _private = 0
    _mrel = 1
    _frel = 1
    for att in node.attrib:
        if att == 'hlink':
            _hlink = node.get(att)
            _child_gid = person_map[_hlink]
        elif att == 'mrel':
            _mrelText = node.get(att)
            _mrel = child_relationship_types[_mrelText]
        elif att == 'frel':
            _frelText = node.get(att)
            _frel = child_relationship_types[_frelText]
        elif att == 'priv':
            _private = node.get(att)

    #print (gid, _child_gid)
    db.query("""INSERT into child_ref (
            gid,
            child_gid,
            frel,
            mrel,
            private) values (?, ?, ?, ?, ?);
            """,
            gid, _child_gid, _frel, _mrel, _private)

def export_family(db, family_node):
    print ("Exporting families")
    for node in family_node:
        _private = 0
        _father = ''
        _mother = ''
        for att in node.attrib:
            if att == 'handle':
                _handle = node.get(att)
            elif att == 'id':
                _id = node.get(att)
            elif att == 'priv':
                _private = node.get(att)
            elif att == 'change':
                _change = node.get(att)
        for child in node:
            _child = child.tag.split('}', 1)[-1]
            if _child == 'father':
                for att in child.attrib:
                    if att == 'hlink':
                        _hlink = child.get(att)
                        _father = person_map[_hlink]
            elif _child == 'mother':
                 for att in child.attrib:
                    if att == 'hlink':
                        _hlink = child.get(att)
                        _mother = person_map[_hlink]
            elif _child == 'eventref':
                do_event_ref(db, _id, child)
            elif _child == 'childref':
                do_child_ref(db, _id, child)

        db.query("""INSERT into family (
                    gid,
                    father_gid,
                    mother_gid,
                    change,
                    private) values (?, ?, ?, ?, ?);
                    """,
                    _id, _father, _mother, _change, _private)
    db.db.commit()

def load_gramps(db, fn):
    family_node = None
    print('input file:', fn)
    f = gzip.open(fn, 'r')
    tree = ET.parse(f)
    f.close()
    root = tree.getroot()
#    namespace = root.tag.split('{', 1)[-1]
#    namespace = root.tag[root.tag.find('{')+1:root.tag.rfind('}')]
    namespace = root.tag[:root.tag.rfind('}')+1]
    print(namespace)
    peop = tree.findall('events')
    for child in root:
        cld = child.tag.split('}', 1)[-1]
        print("tag: %s" % cld)
        if cld == "people":
            people_node = child
        elif cld == "notes":
            notes_node = child
        elif cld == "objects":
            media_node = child
        elif cld == "repositories":
            repositories_node = child
        elif cld == 'sources':
            sources_node = child
        elif cld == 'citations':
            citations_node = child
        elif cld == 'events':
            events_node = child
        elif cld == 'families':
            family_node = child
        elif cld == 'places':
            places_node = child

    export_notes(db, notes_node)
    export_media(db, media_node)
    export_places(db, places_node)
    export_repositories(db, repositories_node)
    export_sources(db, sources_node)
    export_citations(db, citations_node)
    export_events(db, events_node)
    if family_node is not None:
        do_family_map(family_node)
    export_people(db, people_node)
    if family_node is not None:
        export_family(db, family_node)
    db.db.commit()
#    people = root.findall('./')
#    for person in people:
#        print person.tag, person.attrib

    return root

def main(args):

    input_fn = ''
    output_fn = 'gramps1.db'

    try:
        (opts, args) = getopt.getopt(args, 'i:o:', ['help'])
    except (getopt.GetoptError) as s:
        print(s)
        usage()
        sys.exit(1)

    for (o, a) in opts:
        if o == '--help':
            usage()
            sys.exit(0)
        elif o == '-i':
            input_fn = a
        elif o == '-o':
            output_fn = a

    if input_fn == '':
        usage();
        sys.exit(0)

    print ('convert xml to sqlite')
    os.remove(output_fn)
    print('Creating empty database')
    db = Database(output_fn)
    db.batch = 1
    makeDB(db)
    p = load_gramps(db, input_fn)

if __name__ == '__main__':
    main(sys.argv[1:])
