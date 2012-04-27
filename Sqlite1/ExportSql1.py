# Gramps - a GTK+/GNOME based genealogy program
#
# Copyright (C) 2008 Douglas S. Blank <doug.blank@gmail.com>
# Modifications Copyright (C) 2012 W.G. Bell
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
# $Id: ExportSql1.py 619 2010-12-13 14:23:59Z dsblank $
#

"Export to SQLite Database keys"

person_map = {}
event_map = {}
source_map = {}
media_map = {}
place_map = {}
note_map = {}
tag_map = {}
#------------------------------------------------------------------------
#
# Standard Python Modules
#
#------------------------------------------------------------------------
from TransUtils import get_addon_translator
_ = get_addon_translator().ugettext
ngettext = get_addon_translator().ngettext
import sqlite3 as sqlite
import time
from ExportOptions import WriterOptionBox

#------------------------------------------------------------------------
#
# Set up logging
#
#------------------------------------------------------------------------
import logging
log = logging.getLogger(".ExportSql1")

#------------------------------------------------------------------------
#
# GRAMPS modules
#
#------------------------------------------------------------------------
from ExportOptions import WriterOptionBox
from Utils import create_id

#-------------------------------------------------------------------------
#
# Export functions
#
#-------------------------------------------------------------------------
def lookup(index, event_ref_list):
    """
    Get the unserialized event_ref in an list of them and return it.
    """
    if index < 0:
        return None
    else:
        count = 0
        for event_ref in event_ref_list:
            (private, note_list, attribute_list, ref, role) = event_ref
            if index == count:
                return ref
            count += 1
        return None

def makeDB(db):
    db.query("""drop table note_ref;""")
    db.query("""drop table note;""")
    db.query("""drop table person;""")
    db.query("""drop table event;""")
    db.query("""drop table family;""")
    db.query("""drop table repository;""")
    db.query("""drop table repository_ref;""")
    db.query("""drop table date;""")
    db.query("""drop table place_ref;""")
    db.query("""drop table place;""")
    db.query("""drop table source;""")
    db.query("""drop table media;""")
    db.query("""drop table name;""")
    db.query("""drop table surname;""")
    db.query("""drop table link;""")
    db.query("""drop table markup;""")
    db.query("""drop table event_ref;""")
    db.query("""drop table source_ref;""")
    db.query("""drop table child_ref;""")
    db.query("""drop table person_ref;""")
    db.query("""drop table lds;""")
    db.query("""drop table media_ref;""")
    db.query("""drop table address;""")
    db.query("""drop table location;""")
    db.query("""drop table attribute;""")
    db.query("""drop table url;""")
    db.query("""drop table datamap;""")
    db.query("""drop table tag;""")

    db.query("""CREATE TABLE note_ref (
                 gid CHARACTER(25),
                 note_gid CHARACTER(25));""")

    db.query("""CREATE TABLE note (
                  gid    CHARACTER(25) PRIMARY KEY,
                  text   TEXT,
                  format INTEGER,
                  note_type1   INTEGER,
                  note_type2   TEXT,
                  change INTEGER,
                  tags TEXT,
                  private BOOLEAN);""")

    db.query("""CREATE TABLE name (
                  gid CHARACTER(25),
                  primary_name BOOLEAN,
                  private BOOLEAN,
                  first_name TEXT,
                  suffix TEXT,
                  title TEXT,
                  name_type0 INTEGER,
                  name_type1 TEXT,
                  group_as TEXT,
                  sort_as INTEGER,
                  display_as INTEGER,
                  call TEXT,
                  nick TEXT,
                  famnick TEXT);""")

    db.query("""CREATE TABLE surname (
                  gid CHARACTER(25),
                  surname TEXT,
                  prefix TEXT,
                  primary_surname BOOLEAN,
                  origin_type0 INTEGER,
                  origin_type1 TEXT,
                  connector TEXT);""")

    db.query("""CREATE INDEX idx_surname_handle ON
                  surname(gid);""")

    db.query("""CREATE TABLE date (
                  gid CHARACTER(25) PRIMARY KEY,
                  calendar INTEGER,
                  modifier INTEGER,
                  quality INTEGER,
                  day1 INTEGER,
                  month1 INTEGER,
                  year1 INTEGER,
                  slash1 BOOLEAN,
                  day2 INTEGER,
                  month2 INTEGER,
                  year2 INTEGER,
                  slash2 BOOLEAN,
                  text TEXT,
                  sortval INTEGER,
                  newyear INTEGER);""")

    db.query("""CREATE TABLE person (
                  gid CHARACTER(25) PRIMARY KEY,
                  gender INTEGER,
                  death_ref_handle TEXT,
                  birth_ref_handle TEXT,
                  change INTEGER,
                  tags TEXT,
                  private BOOLEAN);""")

    db.query("""CREATE TABLE family (
                 gid CHARACTER(25),
                 father_gid CHARACTER(25),
                 mother_gid CHARACTER(25),
                 the_type0 INTEGER,
                 the_type1 TEXT,
                 change INTEGER,
                 tags TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE place (
                 gid CHARACTER(25) PRIMARY KEY,
                 title TEXT,
                 main_location CHARACTER(25),
                 long TEXT,
                 lat TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE place_ref (
                 gid CHARACTER(25),
                 place_gid CHARACTER(25));""")

    db.query("""CREATE TABLE event (
                 gid CHARACTER(25 )PRIMARY KEY,
                 the_type0 INTEGER,
                 the_type1 TEXT,
                 description TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE source (
                 gid CHARACTER(25) PRIMARY KEY,
                 title TEXT,
                 author TEXT,
                 pubinfo TEXT,
                 abbrev TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE media (
                 gid CHARACTER(25) PRIMARY KEY,
                 path TEXT,
                 mime TEXT,
                 desc TEXT,
                 change INTEGER,
                 tags TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE repository_ref (
                 handle CHARACTER(25) PRIMARY KEY,
                 ref CHARACTER(25),
                 call_number TEXT,
                 source_media_type0 INTEGER,
                 source_media_type1 TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE repository (
                 handle CHARACTER(25) PRIMARY KEY,
                 gid CHARACTER(25),
                 the_type0 INTEGER,
                 the_type1 TEXT,
                 name TEXT,
                 change INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE markup (
                 handle CHARACTER(25) PRIMARY KEY,
                 markup0 INTEGER,
                 markup1 TEXT,
                 value TEXT,
                 start_stop_list TEXT);""")

    db.query("""CREATE TABLE event_ref (
                 gid CHARACTER(25),
                 event_gid CHARACTER(25),
                 role0 INTEGER,
                 role1 TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE person_ref (
                 handle_from CHARACTER(25),
                 handle_to CHARACTER(25),
                 description TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE INDEX idx_person_ref_handle ON
                  person_ref(handle_from, handle_to);""")

    db.query("""CREATE TABLE source_ref (
                 gid CHARACTER(25),
                 source_gid CHARACTER(25),
                 confidence INTEGER,
                 page CHARACTER(25),
                 private BOOLEAN);""")

    db.query("""CREATE TABLE child_ref (
                 gid CHARACTER(25),
                 child_gid CHARACTER(25),
                 sortval INTEGER,
                 frel0 INTEGER,
                 frel1 CHARACTER(25),
                 mrel0 INTEGER,
                 mrel1 CHARACTER(25),
                 private BOOLEAN);""")

    db.query("""CREATE TABLE lds (
                 handle CHARACTER(25) PRIMARY KEY,
                 type INTEGER,
                 place CHARACTER(25),
                 famc CHARACTER(25),
                 temple TEXT,
                 status INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE media_ref (
                 gid CHARACTER(25),
                 media_gid CHARACTER(25),
                 role0 INTEGER,
                 role1 INTEGER,
                 role2 INTEGER,
                 role3 INTEGER,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE address (
                gid CHARACTER(25) PRIMARY KEY,
                private BOOLEAN);""")

    db.query("""CREATE TABLE location (
                 gid CHARACTER(25) PRIMARY KEY,
                 street TEXT,
                 locality TEXT,
                 city TEXT,
                 county TEXT,
                 state TEXT,
                 country TEXT,
                 postal TEXT,
                 phone TEXT,
                 parish TEXT);""")

    db.query("""CREATE TABLE attribute (
                 gid CHARACTER(25),
                 the_type0 INTEGER,
                 the_type1 TEXT,
                 value TEXT,
                 private BOOLEAN);""")

    db.query("""CREATE TABLE url (
                 ID INTEGER PRIMARY KEY,
                 ref_gid CHARACTER(25),
                 path TEXT,
                 desc TXT,
                 type0 INTEGER,
                 type1 TEXT,
                 private BOOLEAN);
                 """)

    db.query("""CREATE TABLE datamap (
                 handle CHARACTER(25) PRIMARY KEY,
                 key_field   TEXT,
                 value_field TXT);
                 """)

    db.query("""CREATE TABLE tag (
                 gid CHARACTER(25) PRIMARY KEY,
                 name TEXT,
                 color TEXT,
                 priority INTEGER,
                 change INTEGER);
                 """)

class Database(object):
    """
    The db connection.
    """
    def __init__(self, database):
        self.database = database
        self.db = sqlite.connect(self.database)
        self.cursor = self.db.cursor()

    def query(self, q, *args):
        args = list(args)
        for i in range(len(args)):
            if isinstance(args[i], str):
                args[i] = unicode(args[i])
        if q.strip().upper().startswith("DROP"):
            try:
                self.cursor.execute(q, args)
                self.db.commit()
            except:
                "WARN: no such table to drop: '%s'" % q
        else:
            try:
                self.cursor.execute(q, args)
                self.db.commit()
            except:
                print "ERROR: query :", q
                print "ERROR: values:", args
                raise
            return self.cursor.fetchall()

    def close(self):
        """ Closes and writes out tables """
        self.cursor.close()
        self.db.close()

def export_location_list(db, from_type, from_handle, locations):
    for location in locations:
        export_location(db, from_type, from_handle, location)

def export_url_list(db, from_type, from_handle, urls):
    for url in urls:
        # (False, u'http://www.gramps-project.org/', u'loleach', (0, u'kaabgo'))
        (private, path, desc, type) = url
        db.query("""insert INTO url (
                 ID,
                 ref_gid,
                 path,
                 desc,
                 type0,
                 type1,
                 private) VALUES (NULL, ?, ?, ?, ?, ?, ?);
                 """,
                 from_handle,
                 path,
                 desc,
                 type[0],
                 type[1],
                 private)

def export_person_ref_list(db, from_type, handle_from, person_ref_list):
    for person_ref in person_ref_list:
        (private,
         source_list,
         note_list,
         handle_to,
         desc) = person_ref
        db.query("""INSERT INTO person_ref (
                    handle_from,
                    handle_to,
                    description,
                    private) VALUES (?, ?, ?, ?);""",
                 handle_from,
                 handle_to,
                 desc,
                 private
                 )
        export_source_ref_list(db, "person_ref", handle_to, source_list)
        export_note_list(db, "note", handle_from, note_list)

def export_lds(db, from_type, from_handle, data):
    (lsource_list, lnote_list, date, type, place,
     famc, temple, status, private) = data
    lds_handle = create_id()
    db.query("""INSERT into lds (handle, type, place, famc, temple, status, private)
             VALUES (?,?,?,?,?,?,?);""",
             lds_handle, type, place, famc, temple, status, private)
    export_date(db, "lds", lds_handle, date)
    export_source_ref_list(db, "lds", lds_handle, lsource_list)
    export_note_list(db, "note", from_handle, note_list)

def export_source_ref(db, from_type, from_handle, source):
    (date, private, note_list, confidence, ref, page) = source
    global source_map
    # handle is source_ref handle
    # ref is source handle
    db.query("""INSERT into source_ref (
             gid,
             source_gid,
             confidence,
             page,
             private
             ) VALUES (?,?,?,?,?);""",
             from_handle,
             source_map[ref],
             confidence,
             page,
             private)
    export_date(db, "source_ref", from_handle, date)
    export_note_list(db, "note", from_handle, note_list)

def export_source(db, handle, gid, title, author, pubinfo, abbrev, change, private):
    global source_map
    source_map[handle] = gid
    db.query("""INSERT into source (
             gid,
             title,
             author,
             pubinfo,
             abbrev,
             change,
             private
             ) VALUES (?,?,?,?,?,?,?);""",
             gid,
             title,
             author,
             pubinfo,
             abbrev,
             change,
             private)

def export_note(db, data):
    (handle, gid, styled_text, format, note_type,
     change, tags, private) = data
    text, markup_list = styled_text
    global note_map
    db.query("""INSERT into note (
                  gid,
                  text,
                  format,
                  note_type1,
                  note_type2,
                  change,
                  tags,
                  private) values (?, ?, ?,
                                   ?, ?, ?, ?, ?);""",
             gid, text, format, note_type[0],
             note_type[1], change, ",".join(tags), private)
    note_map[handle] = gid
    for markup in markup_list:
        markup_code, value, start_stop_list = markup
        export_markup(db, "note", handle, markup_code[0], markup_code[1], value,
                      str(start_stop_list)) # Not normal form; use eval

def export_note_list(db, from_type, from_handle, notes):
    global note_map
    for note in notes:
        db.query("""insert INTO note_ref (
                 gid,
                 note_gid) VALUES (?, ?);
                 """,
                 from_handle,
                 note_map[note])


def export_markup(db, from_type, from_handle,  markup_code0, markup_code1, value,
                  start_stop_list):
    db.query("""INSERT INTO markup (
                 handle,
                 markup0,
                 markup1,
                 value,
                 start_stop_list) VALUES (?,?,?,?,?);""",
             from_handle, markup_code0, markup_code1, value,
             start_stop_list)

def export_event(db, data):
    (handle, gid, the_type, date, description, place_handle,
     source_list, note_list, media_list, attribute_list,
     change, private) = data
    global event_map
    global place_map
    db.query("""INSERT INTO event (
                 gid,
                 the_type0,
                 the_type1,
                 description,
                 change,
                 private) VALUES (?,?,?,?,?,?);""",
             gid,
             the_type[0],
             the_type[1],
             description,
             change,
             private)
    if place_handle != '':
        db.query("""INSERT INTO place_ref (
                 gid,
                 place_gid) VALUES (?,?);""",
                 gid,
                 place_map[place_handle])
    event_map[handle] = gid
    export_date(db, "event", gid, date)
    export_attribute_list(db, "event", gid, attribute_list)
    export_media_ref_list(db, "event", gid, media_list)
    export_source_ref_list(db, "event", gid, source_list)
    export_note_list(db, "note", gid, note_list)

def export_event_ref(db, from_type, from_handle, event_ref):
    (private, note_list, attribute_list, ref, role) = event_ref
    global event_map
    db.query("""insert INTO event_ref (
                 gid,
                 event_gid,
                 role0,
                 role1,
                 private) VALUES (?,?,?,?,?);""",
             from_handle,
             event_map[ref],
             role[0],
             role[1],
             private)
    export_attribute_list(db, "event_ref", from_handle, attribute_list)
    export_note_list(db, "note", from_handle, note_list)

def export_person(db, person):
    (handle,        #  0
     gid,          #  1
     gender,             #  2
     primary_name,       #  3
     alternate_names,    #  4
     death_ref_index,    #  5
     birth_ref_index,    #  6
     event_ref_list,     #  7
     family_list,        #  8
     parent_family_list, #  9
     media_list,         # 10
     address_list,       # 11
     attribute_list,     # 12
     urls,               # 13
     lds_ord_list,       # 14
     psource_list,       # 15
     pnote_list,         # 16
     change,             # 17
     tags,             # 18
     private,           # 19
     person_ref_list,    # 20
     ) = person
    global person_map
    person_map[handle] = gid
    #tag_map[tags] = gid
    db.query("""INSERT INTO person (
                  gid,
                  gender,
                  death_ref_handle,
                  birth_ref_handle,
                  change,
                  tags,
                  private) values (?, ?, NULL, NULL, ?, ?, ?);""",
             gid,
             gender,
             #NULL, lookup(death_ref_index, event_ref_list),
             #NULL, lookup(birth_ref_index, event_ref_list),
             change,
             ",".join(tags),
             private)

    # Event Reference information
    for event_ref in event_ref_list:
        export_event_ref(db, "person", gid, event_ref)
    export_media_ref_list(db, "person", gid, media_list)
    export_attribute_list(db, "person", gid, attribute_list)
    export_url_list(db, "person", gid, urls)
    export_person_ref_list(db, "person", gid, person_ref_list)
    export_source_ref_list(db, "person", gid, psource_list)
    export_note_list(db, "person", gid, pnote_list)

    # -------------------------------------
    # Address
    # -------------------------------------
    for address in address_list:
        export_address(db, "person", gid, address)

    # -------------------------------------
    # LDS ord
    # -------------------------------------
    for ldsord in lds_ord_list:
        export_lds(db, "person", gid, ldsord)

    # -------------------------------------
    # Names
    # -------------------------------------
    export_name(db, "person", gid, True, primary_name)
    map(lambda name: export_name(db, "person", gid, False, name),
        alternate_names)

def export_date(db, from_type, from_handle, data):
    if data is None: return
    (calendar, modifier, quality, dateval, text, sortval, newyear) = data
    if len(dateval) == 4:
        day1, month1, year1, slash1 = dateval
        day2, month2, year2, slash2 = 0, 0, 0, 0
    elif len(dateval) == 8:
        day1, month1, year1, slash1, day2, month2, year2, slash2 = dateval
    else:
        raise ("ERROR: date dateval format", dateval)
    db.query("""INSERT INTO date (
                  gid,
                  calendar,
                  modifier,
                  quality,
                  day1,
                  month1,
                  year1,
                  slash1,
                  day2,
                  month2,
                  year2,
                  slash2,
                  text,
                  sortval,
                  newyear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,
                                   ?, ?, ?, ?, ?, ?);""",
             from_handle, calendar, modifier, quality,
             day1, month1, year1, slash1,
             day2, month2, year2, slash2,
             text, sortval, newyear)
    # And finally, make a link from parent to new object

def export_surname(db, handle, surname_list):
    for data in surname_list:
        (surname, prefix, primary, origin_type, connector) = data
        db.query("""INSERT INTO surname (
                  gid,
                  surname,
                  prefix,
                  primary_surname,
                  origin_type0,
                  origin_type1,
                  connector) VALUES (?,?,?,?,?,?,?);""",
                 handle, surname, prefix, primary, origin_type[0],
                 origin_type[1], connector)

def export_name(db, from_type, from_handle, primary, data):
    if data:
        (private, source_list, note_list, date,
         first_name, surname_list, suffix, title,
         name_type,
         group_as, sort_as, display_as,
         call, nick, famnick) = data
        db.query("""INSERT into name (
                  gid,
                  primary_name,
                  private,
                  first_name,
                  suffix,
                  title,
                  name_type0,
                  name_type1,
                  group_as,
                  sort_as,
                  display_as,
                  call,
                  nick,
                  famnick
                    ) values (?, ?, ?, ?, ?, ?, ?,
                              ?, ?, ?, ?, ?, ?, ?);""",
                 from_handle, primary, private, first_name, suffix, title,
                 name_type[0], name_type[1], group_as,
                 sort_as, display_as, call, nick, famnick)
        export_surname(db, from_handle, surname_list)
        export_date(db, "name", from_handle, date)
        export_source_ref_list(db, "name", from_handle, source_list)
        export_note_list(db, "note", from_handle, note_list)

def export_attribute(db, from_type, from_handle, attribute):
    (private, source_list, note_list, the_type, value) = attribute
    db.query("""INSERT INTO attribute (
                 gid,
                 the_type0,
                 the_type1,
                 value,
                 private) VALUES (?,?,?,?,?);""",
             from_handle, the_type[0], the_type[1], value, private)
    export_source_ref_list(db, "attribute", from_handle, source_list)
    export_note_list(db, "note", from_handle, note_list)

def export_source_ref_list(db, from_type, from_handle, source_list):
    for source in source_list:
        export_source_ref(db, from_type, from_handle, source)

def export_media_ref_list(db, from_type, from_handle, media_list):
    for media in media_list:
        export_media_ref(db, from_type, from_handle, media)

def export_media_ref(db, from_type, from_handle, media):
    (private, source_list, note_list, attribute_list, ref, role) = media
    global media_map
    # handle is the media_ref handle
    # ref is the media handle
    if role is None:
        role = (-1, -1, -1, -1)
    db.query("""INSERT into media_ref (
                 gid,
                 media_gid,
                 role0,
                 role1,
                 role2,
                 role3,
                 private) VALUES (?,?,?,?,?,?,?);""",
             from_handle, media_map[ref], role[0], role[1], role[2], role[3], private)
    export_attribute_list(db, "media_ref", media_map[ref], attribute_list)
    export_source_ref_list(db, "media_ref", media_map[ref], source_list)
    export_note_list(db, "note", media_map[ref], note_list)

def export_attribute_list(db, from_type, from_handle, attr_list):
    for attribute in attr_list:
        export_attribute(db, from_type, from_handle, attribute)

def export_child_ref_list(db, from_type, from_handle, to_type, ref_list):
    global person_map
    order = 0.0
    for child_ref in ref_list:
        # family -> child_ref
        # (False, [], [], u'b305e96e39652d8f08c', (1, u''), (1, u''))
        (private, source_list, note_list, ref, frel, mrel) = child_ref
        db.query("""INSERT INTO child_ref (gid,
                     child_gid, sortval, frel0, frel1, mrel0, mrel1, private)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?);""",
                 from_handle, person_map[ref], order, frel[0], frel[1],
                 mrel[0], mrel[1], private)
        export_source_ref_list(db, "child_ref",from_handle, source_list)
        export_note_list(db, "note", from_handle, note_list)
        order += 1

def export_datamap_dict(db, from_type, from_handle, datamap):
    for key_field in datamap:
        value_field = datamap[key_field]
        db.query("""INSERT INTO datamap (
                      handle,
                      key_field,
                      value_field) values (?, ?, ?)""",
                 from_handle, key_field, value_field)

def export_address(db, from_type, from_handle, address):
    (private, asource_list, anote_list, date, location) = address
    db.query("""INSERT INTO address (
                gid,
                private) VALUES (?, ?);""", from_handle, private)
    export_location(db, "address", from_handle, location)
    export_date(db, "address", from_handle, date)
    export_source_ref_list(db, "address", from_handle, asource_list)

def export_location(db, from_type, from_handle, location):
    if location == None: return
    if len(location) == 8:
        (street, locality, city, county, state, country, postal, phone) = location
        parish = None
    elif len(location) == 2:
        ((street, locality, city, county, state, country, postal, phone), parish) = location
    else:
        print "ERROR: what kind of location is this?", location
        return
    db.query("""INSERT INTO location (
                 gid,
                 street,
                 locality,
                 city,
                 county,
                 state,
                 country,
                 postal,
                 phone,
                 parish) VALUES (?,?,?,?,?,?,?,?,?,?);""",
             from_handle, street, locality, city, county, state, country, postal, phone, parish)

def export_repository_ref_list(db, from_type, from_handle, reporef_list):
    for repo in reporef_list:
        (note_list,
         ref,
         call_number,
         source_media_type,
         private) = repo
        db.query("""insert INTO repository_ref (
                     handle,
                     ref,
                     call_number,
                     source_media_type0,
                     source_media_type1,
                     private) VALUES (?,?,?,?,?,?);""",
                 from_handle,
                 ref,
                 call_number,
                 source_media_type[0],
                 source_media_type[1],
                 private)
        export_note_list(db, "note", from_handle, note_list)

def exportData(database, filename, err_dialog=None, option_box=None,
               callback=None):
    if not callable(callback):
        callback = lambda (percent): None # dummy

    if option_box:
        option_box.parse_options()
        database = option_box.get_filtered_database(database)

    start = time.time()
    total = (len(database.get_note_handles()) +
             len(database.get_person_handles()) +
             len(database.get_event_handles()) +
             len(database.get_family_handles()) +
             len(database.get_repository_handles()) +
             len(database.get_place_handles()) +
             len(database.get_media_object_handles()) +
             len(database.get_tag_handles()) +
             len(database.get_source_handles()))
    count = 0.0

    db = Database(filename)
    makeDB(db)

    # ---------------------------------
    # Notes
    # ---------------------------------
    for note_handle in  database.iter_note_handles():
        data = database.get_note_from_handle(note_handle)
        if data is None:
            continue
        export_note(db, data.serialize())
        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Source
    # ---------------------------------
    for source_handle in database.iter_source_handles():
        source = database.get_source_from_handle(source_handle)
        if source is None:
            continue
        (handle, gid, title,
         author, pubinfo,
         note_list,
         media_list,
         abbrev,
         change, datamap,
         reporef_list,
         private) = source.serialize()

        export_source(db, handle, gid, title, author, pubinfo, abbrev, change, private)
        export_media_ref_list(db, "source", gid, media_list)
        export_datamap_dict(db, "source", gid, datamap)
        export_repository_ref_list(db, "source", gid, reporef_list)
        export_note_list(db, "note", gid, note_list)
        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Place
    # ---------------------------------
    for place_handle in database.iter_place_handles():
        place = database.get_place_from_handle(place_handle)
        if place is None:
            continue
        (handle, gid, title, long, lat,
         main_loc, alt_location_list,
         urls,
         media_list,
         source_list,
         note_list,
         change, private) = place.serialize()
        global place_map
        place_map[handle] = gid
        db.query("""INSERT INTO place (
                 gid,
                 title,
                 long,
                 lat,
                 change,
                 private) values (?,?,?,?,?,?);""",
                 gid, title, long, lat,
                 change, private)

        export_url_list(db, "place", gid, urls)
        export_media_ref_list(db, "place", gid, media_list)
        export_source_ref_list(db, "place", gid, source_list)

        # Main Location with parish:
        # No need; we have the handle, but ok:
        export_location(db, "place_main", gid, main_loc)
        # But we need to link these:
        export_location_list(db, "place_alt", gid, alt_location_list)
        export_note_list(db, "note", gid, note_list)

        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Media
    # ---------------------------------
    for media_handle in database.iter_media_object_handles():
        media = database.get_object_from_handle(media_handle)
        if media is None:
            continue
        (handle, gid, path, mime, desc,
         attribute_list,
         source_list,
         note_list,
         change,
         date,
         tags,
         private) = media.serialize()

        media_map[handle] = gid
        db.query("""INSERT INTO media (
            gid,
            path,
            mime,
            desc,
            change,
            tags,
            private) VALUES (?,?,?,?,?,?,?);""",
                 gid, path, mime, desc,
                 change, ",".join(tags), private)
        export_date(db, "media", gid, date)
        export_source_ref_list(db, "media", gid, source_list)
        export_attribute_list(db, "media", gid, attribute_list)
        export_note_list(db, "note", gid, note_list)
        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Tags
    # ---------------------------------
    for tag_handle in database.iter_tag_handles():
        tag_object = database.get_tag_from_handle(tag_handle)
        if tag_object is None:
            continue
        (handle, name, color, priority, change) = tag_object.serialize()
        db.query("""INSERT INTO tag (
            gid,
            name,
            color,
            priority,
            change) VALUES (?,?,?,?,?);""",
                 handle, name, color, priority, change)

    # ---------------------------------
    # Event
    # ---------------------------------
    for event_handle in database.iter_event_handles():
        data = database.get_event_from_handle(event_handle)
        if data is None:
            continue
        export_event(db, data.serialize())
        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Person
    # ---------------------------------
    for person_handle in database.iter_person_handles():
        person = database.get_person_from_handle(person_handle)
        if person is None:
            continue
        export_person(db, person.serialize())
        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Family
    # ---------------------------------
    for family_handle in database.iter_family_handles():
        family = database.get_family_from_handle(family_handle)
        if family is None:
            continue
        (handle, gid, father_handle, mother_handle,
         child_ref_list, the_type, event_ref_list, media_list,
         attribute_list, lds_seal_list, source_list, note_list,
         change, tags, private) = family.serialize()
        global person_map
        father_gid = father_handle
        if father_handle is not None:
           father_gid = person_map[father_handle]
        mother_gid = mother_handle
        if mother_handle is not None:
            mother_gid = person_map[mother_handle]
        # father_handle and/or mother_handle can be None
        db.query("""INSERT INTO family (
                 gid,
                 father_gid,
                 mother_gid,
                 the_type0,
                 the_type1,
                 change,
                 tags,
                 private) values (?,?,?,?,?,?,?,?);""",
                 gid, father_gid, mother_gid,
                 the_type[0], the_type[1], change, ",".join(tags),
                 private)

        export_child_ref_list(db, "family", gid, "child_ref", child_ref_list)
        export_attribute_list(db, "family", gid, attribute_list)
        export_source_ref_list(db, "family", gid, source_list)
        export_media_ref_list(db, "family", gid, media_list)
        export_note_list(db, "note", gid, note_list)

        # Event Reference information
        for event_ref in event_ref_list:
            export_event_ref(db, "family", gid, event_ref)

        # -------------------------------------
        # LDS
        # -------------------------------------
        for ldsord in lds_seal_list:
            export_lds(db, "family", handle, ldsord)

        count += 1
        callback(100 * count/total)

    # ---------------------------------
    # Repository
    # ---------------------------------
    for repository_handle in database.iter_repository_handles():
        repository = database.get_repository_from_handle(repository_handle)
        if repository is None:
            continue
        (handle, gid, the_type, name, note_list,
         address_list, urls, change, private) = repository.serialize()

        db.query("""INSERT INTO repository (
                 handle,
                 gid,
                 the_type0,
                 the_type1,
                 name,
                 change,
                 private) VALUES (?,?,?,?,?,?,?);""",
                 handle, gid, the_type[0], the_type[1],
                 name, change, private)

        export_url_list(db, "repository", gid, urls)
        export_note_list(db, "note", gid, note_list)

        for address in address_list:
            export_address(db, "repository", gid, address)

        count += 1
        callback(100 * count/total)

    total_time = time.time() - start
    msg = ngettext('Export Complete: %d second','Export Complete: %d seconds', total_time ) % total_time
    print msg
    return True

# Future ideas
# Also include meta:
#   Bookmarks
#   Header - researcher info
#   Name formats
#   Namemaps?
#   GRAMPS Version #, date, exporter
