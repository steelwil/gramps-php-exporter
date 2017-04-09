#
# Gramps - a GTK+/GNOME based genealogy program
#
# Copyright (C) 2017 william.bell@frog.za.net
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
import sqlite3
import sys; sys.stdout = sys.stderr
import os
import time
from time import gmtime, strftime
import datetime

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
    -1:'Unknown',
    0:'Custom',
    1:'Caste',
    2:'Description',
    3:'IDNO',
    4:'NATI',
    5:'Number of Children',
    6:'Social Security Number',
    7:'Nickname',
    8:'CAUS',
    9:'Agency',
    10:'Age',
    11:"Father Age",
    12:"Mother Age",
    13:'Witness',
    14:'Time',
    32:'FACT'}

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
    -1:'Unknown',
    0:'Custom',
    1:'Marriage',
    2:'Marriage Settlement',
    3:'Marriage License',
    4:'Marriage Contract',
    5:'Marriage Banns',
    6:'Engagement',
    7:'Divorce',
    8:'Divorce Filing',
    9:'Annulment',
    10:'Alternate Marriage',
    11:'Adopted',
    12:'BIRT',
    13:'DEAT',
    14:'Adult Christening',
    15:'BAPM',
    16:'Bar Mitzvah',
    17:'Bas Mitzvah',
    18:'Blessing',
    19:'BURI',
    20:'Cause Of Death',
    21:'Census',
    22:'Christening',
    23:'Confirmation',
    24:'Cremation',
    25:'_DEG',
    26:'Education',
    27:'Elected',
    28:'Emigration',
    29:'FCOM',
    30:'IMMI',
    31:'Graduation',
    32:'Medical Information',
    33:'_MILT',
    34:'Naturalization',
    35:'Nobility Title',
    36:'Number of Marriages',
    37:'OCCU',
    38:'Ordination',
    39:'Probate',
    40:'Property',
    41:'RELI',
    42:'RESI',
    43:'Retirement',
    44:'Will'}

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
    'Custom':7
}

def usage():
    print("""Usage: python3 gramps_to_sqlite [OPTION]...
Convert the gramps xml database to sqlite database

-i INPUT                     Use INPUT SQLite file, "gramps1.db" if unspecified
-o OUTPUT                    Output gedcom file, "gramps.ged" if unspecified.
--help                       Display this help and exit

Report bugs to <william.bell@frog.za.net>.""")

def do_date(in_datetime, dtype, qual):
    res = ""
    sd = ""
    if len(in_datetime) == 10:
        dt = datetime.datetime.strptime(in_datetime, '%Y-%m-%d').date()
        sd = dt.strftime("%d %b %Y").upper()
        if sd[0] == "0":
            sd = sd[1:]
    else:
        sd = in_datetime

    if qual == 1:
        res = "EST "
    if dtype == 3:
        res += "ABT "

    res += sd
    return res

def do_note(db, f, gid, lvl):
    cs = db.cursor()
    cs.execute("""
        SELECT
            note_gid
        FROM note_ref nf
        INNER JOIN note n
        ON n.gid = nf.note_gid
        WHERE nf.gid = '"""  + gid + "'")

    for rw in cs.fetchall():
        if rw is not None:
            if rw[0] is not None:
                f.write("%d NOTE @%s@\n" % (lvl, rw[0]))

def do_sources(db, f, gid):
    cs = db.cursor()
    cs.execute("""
        SELECT
            sr.source_gid,
            cr.citation_gid,
            c.page
        FROM
            citation_ref cr
        LEFT JOIN citation c ON
            c.gid = cr.citation_gid
        LEFT JOIN source_ref sr ON
            sr.gid = cr.citation_gid
        LEFT JOIN source s ON
            s.gid = sr.source_gid
        WHERE
            cr.gid = '""" + gid + "'")
    for rw in cs.fetchall():
        f.write("1 SOUR @%s@\n" % rw[0])
        if rw[2] is not None and len(rw[2]) > 1:
            f.write("2 PAGE %s\n" % (rw[2]))

def do_media(db, f, gid):
    cs = db.cursor()
    cs.execute("""
        SELECT
            m.path,
            m.mime AS the_type,
            m.description
        FROM
            media_ref mr
        LEFT JOIN media m ON
            m.gid = mr.media_gid
        WHERE
            mr.gid = '""" + gid + """'

        UNION ALL

        SELECT
            PATH,
            the_type,
            description
        FROM
            url
        WHERE
            gid = '""" + gid + "'")
    for rw in cs.fetchall():
        otype = ""
        if rw[1] == 0 or rw[1] == 1 or rw[1] == 2:
            otype = "URL"
        elif rw[1] == "image/jpeg":
            otype = "jpeg"
        else:
            otype = rw[1]

        f.write("1 OBJE\n2 FORM %s\n" % (otype))
        if rw[2] is not None and len(rw[2]) > 1:
            f.write("2 TITL %s\n" % (rw[2]))
        if rw[1] != 1 and rw[1] != 2 and rw[0][0] != '/':
            f.write("2 FILE /%s\n" % (rw[0]))
        else:
            f.write("2 FILE %s\n" % (rw[0]))


def do_place(db, f, gid):
    cs = db.cursor()
    cs.execute("""
        WITH PlaceHierarchy AS(
            -- Base case
            SELECT
                gid,
                place_gid,
                1 AS HierarchyLevel
            FROM
                place_ref pr
            WHERE
                gid = '""" + gid + """'
        UNION ALL -- Recursive step
            SELECT
                e.gid,
                e.place_gid,
                eh.HierarchyLevel + 1 AS HierarchyLevel
            FROM
                place_ref e
            INNER JOIN PlaceHierarchy eh ON
                e.gid = eh.place_gid
        ) SELECT
            h.gid,
            h.place_gid,
            p.title,
            p.type,
            p.long,
            p.lat,
            p.change
        FROM
            PlaceHierarchy h
        INNER JOIN place p ON
            p.gid = h.place_gid
        ORDER BY
            HierarchyLevel,
            h.gid,
            h.place_gid;""")
    name = ""
    loc = ""
    map = ""
    add = 0
    for rw in cs.fetchall():
        name += rw[2] + ", ";
        if map == "" and rw[5] is not None and len(rw[5]) > 1:
            lo = ""
            fval = 0.0
            try:
                fval = float(rw[4])
                if float(rw[4]) > 0:
                    lo = 'E' + rw[4]
                else:
                    lo = 'W' + rw[4][1:]
            except ValueError:
                lo = rw[4]

            try:
                fval = float(rw[5])
                if float(rw[5]) > 0:
                    la = 'N' + rw[5]
                else:
                    la = 'S' + rw[5][1:]
            except ValueError:
                la = rw[5]

            map = "3 MAP\n4 LATI %s\n4 LONG %s\n" % (la, lo)
        if rw[3] is not None and len(rw[3]) > 1:
            if add == 0:
                loc += "2 ADDR\n"
                add = 1
            if rw[3] == 'City' or rw[3] == 'Town':
                loc += "3 CITY %s\n" % (rw[2])
            elif rw[3] == 'Province':
                loc += "3 STAE %s\n" % (rw[2])
            elif rw[3] == 'Country':
                loc += "3 CTRY %s\n" % (rw[2])

    if len(name) > 1:
        f.write("2 PLAC %s\n" % (name[:-2]))
        if len(map) > 1:
            f.write(map)
        f.write(loc)

def do_event(db, f, gid, eventID):
    cs = db.cursor()
    cs.execute("""
        SELECT
            d.date1,
            d.the_type,
            d.quality,
            e.gid,
            e.description
        FROM
            event_ref er
        INNER JOIN event e ON
            e.gid = er.event_gid
        LEFT JOIN DATE d ON
            d.gid = e.gid
        WHERE
            e.the_type = """ + str(eventID) +
            " AND er.gid = '" + gid + "'")
    rw = cs.fetchone()
    if rw is not None:
        if rw[0] is not None:
            if rw[0].startswith('?') == False:
                f.write("1 %s\n2 DATE %s\n" % (event_types[eventID], do_date(rw[0], rw[1], rw[2])))
        elif rw[4] is not None and len(rw[4]) > 1:
            f.write("1 %s %s\n" % (event_types[eventID], rw[4]))
        else:
            f.write("1 %s\n" % event_types[eventID])

        do_place(db, f, rw[3])
        do_attribute(db, f, rw[3], 8, 2)
        do_note(db, f, rw[3], 2)

def do_attribute(db, f, gid, attributeID, lvl):
    cs = db.cursor()
    cs.execute("""
        SELECT
            the_type,
            value
        FROM
            attribute
        WHERE
            the_type = """ + str(attributeID) +
            " AND gid = '" + gid + "'")
    rw = cs.fetchone()
    if rw is not None:
        if rw[1] is not None and len(rw[1]) > 1:
            f.write("%d %s %s\n" % (lvl, attribute_types[attributeID], rw[1]))
        else:
            f.write("%d %s\n" % (lvl, attribute_types[attributeID]))
        if attributeID == 32:
            f.write("2 TYPE Blood Group\n")

def load_gramps(db, fn):
    family_node = None
    print('output file:', fn)
    f = open(fn, 'w')

    f.write("0 HEAD\n"
    "1 SOUR sqlite\n"
    "2 VERS 1.0.1\n"
    "2 NAME Gramps SQLite\n"
    "1 DATE " + time.strftime("%d %b %Y\n") +
    "2 TIME " + time.strftime("%H:%M\n") +
    "1 SUBM @SUBM@\n"
    "1 FILE /home/wil/gramps/Backups/20170326-recovery.ged\n"
    "1 COPR Copyright (c) 2017 William Bell.\n"
    "1 GEDC\n"
    "2 VERS 5.5.1\n"
    "2 FORM LINEAGE-LINKED\n"
    "1 CHAR UTF-8\n"
    "1 LANG English\n"
    "0 @SUBM@ SUBM\n"
    "1 NAME Bart Simpson\n"
    "1 ADDR 742 Evergreen Terrace\n"
    "2 CONT Springfield\n"
    "2 CONT undetermined\n"
    "2 CONT 0007\n"
    "2 CONT United States\n"
    "2 ADR1 742 Evergreen Terrace\n"
    "2 CITY Springfield\n"
    "2 STAE undetermined\n"
    "2 POST 0007\n"
    "2 CTRY United States\n"
    "1 PHON +01 11 555 5555\n"
    "1 EMAIL bart.simpson@fox.com\n")

    cur = db.cursor()
    cur.execute("""SELECT
                    gid,
                    gender,
                    [change],
                    private
                FROM
                    person
                ORDER BY
                    gid;""")

    # INDI
    for row in cur.fetchall():
        print(row)
        depth = 0
        f.write("%d @%s@ INDI\n" % (depth, row[0]))
        curname = db.cursor()
        curname.execute("""
            SELECT
                first_name,
                surname,
                the_type,
                n.call,
                n.nick,
                title
            FROM
                name n
            LEFT JOIN surname s ON
                s.gid = n.gid
                AND s.primary_surname = n.primary_name
            WHERE
                n.gid = '""" + row[0] + "'")
        rowname =  curname.fetchone()
        print(rowname)
        f.write("1 NAME %s /%s/\n" % (rowname[0], rowname[1]))
        if rowname[2] == 3:
            f.write("2 TYPE %s\n" % ("married"))
        f.write("2 GIVN %s\n" % (rowname[0]))
        f.write("2 SURN %s\n" % (rowname[1]))
        if rowname[4] is not None and len(rowname[4]) > 1:
            f.write("2 NICK %s\n" % (rowname[4]))
        if rowname[5] is not None and len(rowname[5]) > 1:
            f.write("2 NPFX %s\n" % (rowname[5]))
        sex = row[1]

        # NICK
        cs = db.cursor()
        cs.execute("""
            SELECT
                value
            FROM
                attribute
            WHERE
                the_type = 7
                AND gid = '""" + row[0] + "'")
        rw = cs.fetchone()
        if rw is not None:
            if rw[0] is not None:
                f.write("2 NICK %s\n" % (rw[0]))

        f.write("1 SEX %s\n" % (sex))
        curname.close()

        # BIRT
        do_event(db, f, row[0], 12)

        # DEAT
        do_event(db, f, row[0], 13)

        # BAPM
        do_event(db, f, row[0], 15)

        # RELI
        do_event(db, f, row[0], 41)

        # FCOM
        do_event(db, f, row[0], 29)

        # _MILT
        do_event(db, f, row[0], 33)

        # _DEG
        do_event(db, f, row[0], 25)

        # OCCU
        do_event(db, f, row[0], 37)

        # RESI
        do_event(db, f, row[0], 42)

        # IMMI
        do_event(db, f, row[0], 30)

        # BURI
        do_event(db, f, row[0], 19)

        # IDNO
        do_attribute(db, f, row[0], 3, 1)

        # FACT
        do_attribute(db, f, row[0], 32, 1)

        # FAMC
        cs = db.cursor()
        cs.execute("""
            SELECT
                cr.gid,
                cr.frel,
                cr.mrel
          FROM
                child_ref cr
            LEFT JOIN family f ON
                f.gid = cr.gid
            WHERE
                child_gid = '""" + row[0] + "'")
        rw = cs.fetchone()
        if rw is not None:
            if rw[0] is not None:
                if rw[1] == 2 or rw[2] == 2:
                    f.write("1 ADOP Y\n")
                    f.write("2 FAMC @%s@\n" % (rw[0]))
                    if rw[1] == 2 and rw[2] == 1:
                        f.write("3 ADOP HUSB\n")
                    elif rw[1] == 1 and rw[2] == 2:
                        f.write("3 ADOP WIFE\n")
                    else:
                        f.write("3 ADOP BOTH\n")
                else:
                    f.write("1 FAMC @%s@\n" % (rw[0]))

        # FAMS
        cs.execute("""
            SELECT
                gid
            FROM
                family
            WHERE
                father_gid = '"""  + row[0] + "'"
                "OR mother_gid = '" + row[0] + "'")
        for rw in cs.fetchall():
            if rw is not None:
                if rw[0] is not None:
                    f.write("1 FAMS @%s@\n" % (rw[0]))

        do_sources(db, f, row[0])

        do_media(db, f, row[0])

        do_note(db, f, row[0], 1)

        # CHAN
        dt = datetime.datetime.fromtimestamp(int(row[2])).strftime("%d %b %Y\n").upper()
        if dt[0] == "0":
            dt = dt[1:]
        f.write("1 CHAN\n"
        "2 DATE " + dt +
        "3 TIME " + datetime.datetime.fromtimestamp(int(row[2])).strftime("%H:%M\n"))

    # FAM
    cur.execute("""
        SELECT
            f.gid,
            f.father_gid,
            f.mother_gid,
            cr.child_gid
        FROM
            family f
        LEFT JOIN child_ref cr ON
            cr.gid = f.gid""")
    pfam = 'ZZZ'
    for row in cur.fetchall():
        if row[0] != pfam:
            pfam = row[0];
            f.write("0 @%s@ FAM\n" % (pfam))
            if row[1] is not None and len(row[1]) > 1:
                f.write("1 HUSB %s\n" % (row[1]))
            if row[2] is not None and len(row[2]) > 1:
                f.write("1 WIFE %s\n" % (row[2]))
            do_event(db, f, row[0], 1)
        elif row[3] is not None and len(row[2]) > 1:
            f.write("1 CHIL %s\n" % (row[3]))

    # SOUR
    cur.execute("""
        SELECT
            s.gid,
            title,
            rr.repository_gid,
            author,
            pubinfo,
            change
        FROM source s
        LEFT JOIN repository_ref rr
        ON rr.gid = s.gid""")
    for row in cur.fetchall():
        f.write("0 @%s@ SOUR\n" % (row[0]))
        f.write("1 TITL %s\n" % (row[1]))
        if row[3] is not None and len(row[3]) > 1:
            f.write("1 AUTH %s\n" % (row[3]))
        if row[2] is not None and len(row[2]) > 1:
            f.write("1 REPO @%s@\n" % (row[2]))

        # CHAN
        f.write("1 CHAN\n"
        "2 DATE " + datetime.datetime.fromtimestamp(int(row[5])).strftime("%d %b %Y\n").upper() +
        "3 TIME " + datetime.datetime.fromtimestamp(int(row[5])).strftime("%H:%M\n"))

    # REPO
    cur.execute("""
        SELECT
            r.gid,
            r.name,
            u.[path]
        FROM
            repository r
        INNER JOIN url u ON
            u.gid = r.gid;""")

    for row in cur.fetchall():
        f.write("0 @%s@ REPO\n" % (row[0]))
        f.write("1 NAME %s\n" % (row[1]))
        if row[2] is not None and len(row[2]) > 1:
            f.write("1 WWW %s\n" % (row[2]))

    # NOTE
    cur.execute("""
        SELECT
            gid,
            TEXT
        FROM
            note;""")

    for row in cur.fetchall():
        note = row[1].replace("\n", "\n1 CONT ")
        f.write("0 @%s@ NOTE %s\n" % (row[0], note))

    f.write("0 TRLR\n")
    f.close()

    return

def main(args):

    input_fn = 'gramps1.db'
    output_fn = 'gramps.ged'

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

    print ('convert sqlite to gedcom')
    if os.path.exists(output_fn):
        os.remove(output_fn)
    print('Reading in the database')
    db = sqlite3.connect(input_fn)
    p = load_gramps(db, output_fn)
    db.close()
    print ('done')

if __name__ == '__main__':
    main(sys.argv[1:])
