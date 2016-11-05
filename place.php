<?php
/**
 Grampsphpexporter.- export gramps genealogy program to the web

 Copyright (C) 2012  William Bell <william.bell@frog.za.net>

    Grampsphpexporter is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Grampsphpexporter is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/

function doDate($date1, $modifier, $quality)
{
    $res = '';

    switch ($quality) {
    case 0:
        break; // regular
    case 1:
        $res = "Estimated ";
        break;
    case 2:
        $res = "Calculated ";
        break;
    default:
        $res = "Unknown quality ".$quality." ";
    }

    switch ($modifier) {
    case 0:
        break; // regular
    case 1:
        $res = $res."Before ";
        break;
    case 2:
        $res = $res."After ";
        break;
    case 3:
        $res = "Estimated "; // About
        break;
    case 4:
        $res = $res."Range ";
        break;
    case 5:
        $res = $res."Span ";
        break;
    case 6:
        break; // text only
    default:
        $res = $res."Unknown modifier ".$modifier." ";
    }

    $res = $res.$date1;
    return $res;
}

function doPlace($db, $gid)
{
    // do place
    $img = '';
    $result = $db->query(
        "select
            P.title,
            ltrim(P.long) as long,
            P.lat,
            U.path
        from place P
        left join url U
        on P.gid = U.gid
            and U.private = 0
        where P.private = 0
            and P.gid = '".$gid."'"
    );

    if ($row = $result->fetch()) {
        $title = $row['title'];

        if ($title != "" && !is_null($title)) {
            print("<h3>".$title."</h3>");
        }

        $long = $row['long'];
        $lat = $row['lat'];
        if ($long != "" && !is_null($long) && $long != 0) {
            print("<p><span class=\"name\">Location:</span> <span class=\"value\">".'<a href="http://maps.google.com/maps?q='.$lat.','.$long.'&amp;t=h&amp;z=6">'.$lat.', '.$long."</a></span></p>\n");
        }
    }
    // http://maps.google.com/maps?ll=56.948889,24.106389&t=h&z=5
    // http://maps.google.com/maps?ll=7.710556,50.338056&t=h&z=5
    // http://maps.google.com/maps?ll=50.338056,7.710556&t=h&z=12
}

function doUrl($db, $gid)
{
    unset($result);
    $result = $db->query(
        "select
            path,
            description,
            the_type
        from url
        where gid = '".$gid."'
            and private = 0"
    );

    for ($i=0; $row = $result->fetch(); $i++) {
        if ($i == 0) {
            print("\n<h3>Web Links</h3>\n");
        }
        switch ($row['the_type'])
        {
        case 1:
            $eventtype = "Web Page";
            $path = $row['path'];
            break;
        case 2:
            $eventtype = "e-mail";
            $path = "mailto:".$row['path'];
            break;
        }
        $desc = $row['description'];
        if ($desc == "" || is_null($desc)) {
            $desc = $row['path'];
        }

        print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\"><a href=\"".$path."\">".$desc."</a></span></p>\n");
    }
    unset($row);
}

function doNotes($db, $gid)
{
    unset($result);
    $result = $db->query(
        "select
            text,
            preformatted
        from note N
        inner join note_ref R
        on R.note_gid = N.gid
        where R.gid = '".$gid."'
            and private  = 0"
    );

    for ($i=1; $row = $result->fetch(); $i++) {
        if ($i == 1) {
            print("\n<h3>Notes</h3>\n");
        }

        print("<p><span class=\"value\">".$i.'. '.$row['text']."</span></p>\n");
    }
    unset($row);
}

function doEnclosedBy($db, $gid)
{
    unset($result);
    $result = $db->query(
        "select
            P.gid,
            P.title,
            P.type
        from place_ref PR
        inner join place P
        on P.gid = PR.place_gid
            and P.private = 0
        where PR.gid = '".$gid."'
        order by P.title"
    );

    for ($i=0; $row = $result->fetch(); $i++) {
        $ref_gid = $row['gid'];
        $descr = "";
        if ($i == 0) {
            print("\n<h3>Enclosed By</h3>\n");
        }
        $descr = "<a href=\"place.php?gid=".$ref_gid."\">".$row['title']."</a>";
        print("<p>The ".$row['type']." of ".$descr."</p>\n");
    }
    unset($row);
}

function doIncludes($db, $gid)
{
    unset($result);
    $result = $db->query(
        "select
            P.gid,
            P.title,
            P.type
        from place_ref PR
        inner join place P
        on P.gid = PR.gid
            and P.private = 0
        where PR.place_gid = '".$gid."'
        order by P.title"
    );

    for ($i=0; $row = $result->fetch(); $i++) {
        $ref_gid = $row['gid'];
        $descr = "";
        if ($i == 0) {
            print("\n<h3>Includes</h3>\n");
        }
        $descr = "<a href=\"place.php?gid=".$ref_gid."\">".$row['title']."</a>";
        print("<p>The ".$row['type']." of ".$descr."</p>\n");
    }
    unset($row);
}

function doReference($db, $gid)
{
    unset($result);
    $result = $db->query(
        "select
            PR.place_gid,
            PR.gid,
            ER.gid as ER_gid,
            N.first_name||' '||S.surname as Name,
            E.the_type as EventType,
            E.description,
            D.date1,
            D.the_type,
            D.quality,
            F.father_gid,
            FN.first_name||' '||FS.surname as FName,
            F.mother_gid,
            MN.first_name||' '||MS.surname as MName
        from place_ref PR
        inner join event E
        on E.gid = PR.gid
            and E.private = 0
        left join date D
            on E.gid = D.gid
        left join event_ref ER
            on E.gid = ER.event_gid
            and ER.private = 0
        left join name N
            on N.gid = ER.gid
            and N.private = 0
        left join surname S
            on N.gid = S.gid
        left join family F
            on F.gid = ER.gid
        left join name FN
            on FN.gid = F.father_gid
            and FN.private = 0
        left join surname FS
            on FS.gid = F.father_gid
        left join name MN
            on MN.gid = F.mother_gid
            and MN.private = 0
        left join surname MS
            on MS.gid = F.mother_gid
        where PR.place_gid = '".$gid."'
        order by D.date1"
    );

    for ($i=0; $row = $result->fetch(); $i++) {
        $ref_gid = $row['gid'];
        $descr = "";
        if ($i == 0) {
            print("\n<h3>References</h3>\n");
        }
        $date = "";
        if (!is_null($row['the_type'])) {
            $date = doDate($row['date1'], $row['the_type'], $row['quality']);
        }
        switch ($row['EventType']) {
        case 1:
            $eventtype = "Marriage";
            break;
        case 7:
            $eventtype = "Divorce";
            break;
        case 12:
            $eventtype = "Birth";
            break;
        case 13:
            $eventtype = "Death";
            break;
        case 15:
            $eventtype = "Baptism";
            break;
        case 19:
                $eventtype = "Burial";
            break;
        case 24:
            $eventtype = "Cremation";
            break;
        case 25:
            $eventtype = "Degree";
            break;
        case 28:
            $eventtype = "Emigration";
            break;
        case 29:
            $eventtype = "First Communion";
            break;
        case 30:
            $eventtype = "Immigration";
            break;
        case 33:
            $eventtype = "Military Service";
            break;
        case 37:
            $eventtype = "Occupation";
            break;
        case 41:
            $eventtype = "Religion";
            break;
        case 42:
            $eventtype = "Residence";
            break;
        default:
            $eventtype = "Unknown event ".$row['EventType'];
        }
        
        $ref_gid = $row['ER_gid'];
        $descr = "";
        if ($ref_gid[0] == 'I') {
            $descr = "<a href=\"person.php?gid=".$ref_gid."\">".$row['Name']."</a>";
        } else {
            $descr = "<a href=\"person.php?gid=".$row['father_gid']."\">".$row['FName']."</a> and <a href=\"person.php?gid=".$row['mother_gid']."\">".$row['MName']."</a>";
        }
        print("<p>".$date." ".$eventtype." ".$descr."</p>\n");
    }
    unset($row);
}

require_once 'template.php';
echo head('Place', '');
try
{
    $gid = substr($_GET["gid"], 0, 8);
    //open the database
    $db = new PDO('sqlite:../../.sqlite/gramps1.db');

    doPlace($db, $gid);

    doUrl($db, $gid);

    doNotes($db, $gid);

    doEnclosedBy($db, $gid);

    doIncludes($db, $gid);

    doReference($db, $gid);

    // close the database connection
    $db = null;
}
catch(PDOException $e)
{
    print 'Exception : '.$e->getMessage();
}
echo foot();
?>
