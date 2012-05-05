<?php
/*
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
	function do_date($year, $month, $day, $modifier, $quality)
	{
		$res = '';

		switch($quality)
		{
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

		switch($modifier)
		{
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

		if ($year > 0)
		{
			$res = $res.$year;
			if ($month > 0)
			{
				if ($month < 10)
					$res = $res."-0".$month;
				else
					$res = $res."-".$month;
				if ($day > 0)
				{
					if ($day < 10)
						$res = $res."-0".$day;
					else
						$res = $res."-".$day;
				}
			}
		}
		return $res;
	}

	function do_source($db, $gid)
	{
		// do place
		$img = '';
		$result = $db->query(
			"select
				S.title,
				S.author,
				S.pubinfo,
				S.abbrev
			from source S
			where S.private = 0
				and S.gid = '".$gid."'");
		if ($row = $result->fetch())
		{
			$title = $row['title'];

			if ($title != "" && !is_null($title))
				print("<h3>".$title."</h3>\n");

			print("<p><span class=\"name\">Source ID:</span> <span class=\"value\">".$gid."</span></p>\n");

			$author = $row['author'];
			if ($author != "" && !is_null($author))
				print("<p><span class=\"name\">Author:</span> <span class=\"value\">".$author."</span></p>\n");

			$city = $row['pubinfo'];
			if ($city != "" && !is_null($city))
				print("<p><span class=\"name\">Publication Information:</span> <span class=\"value\">".$pubinfo."</span></p>\n");

			$abbrev = $row['abbrev'];
			if ($county != "" && !is_null($county))
				print("<p><span class=\"name\">Abbreviation:</span> <span class=\"value\">".$abbrev."</span></p>\n");
		}
	}

	function do_notes($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				text,
				note_type1
			from note N
			inner join note_ref R
			on R.note_gid = N.gid
			where R.gid = '".$gid."'
				and private  = 0");

		for($i=1; $row = $result->fetch(); $i++)
		{
			if ($i == 1)
			{
				print("\n<h3>Notes</h3>\n");
			}

			print("<p><span class=\"value\">".$i.'. '.$row['text']."</span></p>\n");
		}
		unset($row);
	}

	function do_gallery($db, $gid)
	{
		unset($result);
		$img = '';
		$result = $db->query(
			"select
				path,
				desc
			from media_ref MR
			inner join media M
				on M.gid = MR.media_gid
				and M.private = MR.private
			where MR.gid = '".$gid."'
				and MR.private = 0");
		for($i=1; $row = $result->fetch(); $i++)
		{
			if ($i == 1)
			{
				print("\n<h3>Gallery</h3>\n");
			}
			$pic = $row['path'];
			$pic = substr($pic, strrpos($pic, "/"));
			$img = "<p><img src=\"pics".$pic."\" alt=\"".$row['desc']."\" /></p>";
			print($img);
		}
		unset($row);
	}

	function do_person_reference($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				SR.source_gid,
				SR.gid,
				N.first_name,
				S.surname
			from source_ref SR
			inner join name N
				on N.gid = SR.gid
				and N.private = 0
			left join surname S
				on N.gid = S.gid
			where SR.private = 0
				and SR.source_gid = '".$gid."'");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
			{
				print("\n<h3>References</h3>\n");
			}
			$ref_gid = $row['gid'];
			$descr = "<a href=\"person.php?gid=".$ref_gid."\">".$row['first_name'].' '.$row['surname']."</a>";

			print("<p>".$descr."</p>\n");
		}
		unset($row);
	}

	function do_event_reference($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				SR.source_gid,
				EN.gid,
				EN.first_name||' '||ES.surname as name,
				E.the_type0,
				E.description,
				D.year1,
				D.month1,
				D.day1,
				D.modifier,
				D.quality
			from source_ref SR
			inner join event E
				on E.gid = SR.gid
				and E.private = 0
			left join event_ref ER
				on E.gid = ER.event_gid
				and ER.private = 0
			left join name EN
				on EN.gid = ER.gid
				and EN.private = 0
			left join surname ES
				on EN.gid = ES.gid
			left join date D
				on E.gid = D.gid
			where  SR.private = 0
				and SR.source_gid = '".$gid."'");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
			{
				print("\n<h3>References</h3>\n");
			}
			$date = do_date($row['year1'], $row['month1'], $row['day1'], $row['modifier'], $row['quality']);
			switch($row['the_type0'])
			{
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
					$eventtype = "Unknown event ".$row['the_type0'];
			}
			$ref_gid = $row['gid'];
			$descr = "<a href=\"person.php?gid=".$ref_gid."\">".$row['name']."</a>";

			print("<p>".$date." ".$eventtype." ".$descr."</p>\n");
		}
		unset($row);
	}

	function do_family_reference($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				SR.source_gid,
				SR.gid,
				F.father_gid,
				FN.first_name||' '||FS.surname as FName,
				F.mother_gid,
				MN.first_name||' '||MS.surname as MName,
				D.year1,
				D.month1,
				D.day1,
				D.modifier,
				D.quality
			from source_ref SR
			inner join family F
				on F.gid = SR.gid
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
			left join date D
				on F.gid = D.gid
			where  SR.private = 0
				and SR.source_gid = '".$gid."'");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
			{
				print("\n<h3>References</h3>\n");
			}
			$date = do_date($row['year1'], $row['month1'], $row['day1'], $row['modifier'], $row['quality']);
			$eventtype = "Family";
			$ref_gid = $row['gid'];
			$descr = "<a href=\"person.php?gid=".$row['father_gid']."\">".$row['FName']."</a> and <a href=\"person.php?gid=".$row['mother_gid']."\">".$row['MName']."</a>";

			print("<p>".$date." ".$eventtype." ".$descr."</p>\n");
		}
		unset($row);
	}

	require_once 'template.php';
	echo head('Source', '');
	try
	{
		$gid = $_GET["gid"];
		//open the database
		$db = new PDO('sqlite:../../.sqlite/gramps.db');

		do_source($db, $gid);

		do_notes($db, $gid);

		do_gallery($db, $gid);

		do_person_reference($db, $gid);

		do_event_reference($db, $gid);

		// close the database connection
		$db = NULL;
	}
	catch(PDOException $e)
	{
		print 'Exception : '.$e->getMessage();
	}
	echo foot();
?>
