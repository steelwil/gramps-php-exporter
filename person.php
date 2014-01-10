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
	$genderID = 2; // unknown gender
	$name;
	$gen = 0;

	function do_date($date1, $modifier, $quality)
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

		$res = $res.$date1;

		return $res;
	}

	function do_name($db, $gid)
	{
		// do media
		$img = '';
		$result = $db->query(
			"select
				path,
				description
			from media_ref MR
			inner join media M
				on M.gid = MR.media_gid
				and M.private = MR.private
			where MR.gid = '".$gid."'
				and MR.private = 0");
		if ($row = $result->fetch())
		{
			$pic = $row['path'];
			$pic = substr($pic, strrpos($pic, "/"));
			$img = "<p><img class=\"person\" src=\"pics".$pic."\" alt=\"".$row['desc']."\" /></p>";
		}


		$result = $db->query(
			"select
				S.gid as gid,
				P.gender as gender,
				N.first_name,
				S.surname,
				N.call,
				N.nick,
				N.private
			from person P
			inner join surname S
				on P.gid = S.gid
			inner join name N
				on N.gid = P.gid
				and primary_surname = 1
			where P.gid = '".$gid."'
				and P.private = 0
			order by N.first_name");
		for($i=0; $row = $result->fetch(); $i++)
		{
			GLOBAL $name;
			$surname = $row['surname'];
			$name = $row['first_name']." ".$surname;
			$private = $row['private'];

			if ($private == 1)
				$name = substr($name, 0, 1)." ".$surname;

			echo head('Bell Family Tree - '.$name, $surname);

			print("\n<h3>".$name."</h3>\n");
			if ($row['call'] != '')
				print("<p><span class=\"name\">Called:</span> <span class=\"value\">".$row['call']."</span></p>\n");
			if ($row['nick'] != '')
				print("<p><span class=\"name\">Nick:</span> <span class=\"value\">".$row['nick']."</span></p>\n");
			if ($img != '')
				print($img);
			$gender = 'Unknown';
			GLOBAL $genderID;
			$genderID = $row['gender'];
 			if ($genderID == 'F')
				$gender = "Female";
			else if ($genderID == 'M')
				$gender = "Male";
			else
				$gender = "Unknown";
			print("<p><span class=\"name\">Gender:</span> <span class=\"value\">".$gender."</span></p>\n");
			break;
		}
		unset($row);
	}

	function do_events($db, $gid)
	{
		$result = $db->query(
			"select E.the_type as EventType,
				D.date1,
				description,
				P.title,
				D.the_type,
				D.quality,
				P.gid
			from event_ref R
			inner join event E
				on E.gid = R.event_gid
				and E.private = 0
			left join date D
				on D.gid = R.event_gid
			left join place_ref PR
				on PR.gid = R.event_gid
			left join place P
				on P.gid = PR.place_gid
				and P.private = 0
			where R.private = 0
				and R.gid = '".$gid."'");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Events</h3>\n");

			switch($row['EventType'])
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
					$eventtype = "Unknown event ".$row['EventType'];
			}

			$date = do_date($row['date1'], $row['the_type'], $row['quality']);
			$descrip = $row['description'];
			if ($descrip == "Estimated death date" || $descrip == "Estimated birth date")
				$descrip = "";

			$description = $date.' '.$descrip;

			$place = $row['title'];
			if (!is_null($place))
			{
				$place_gid = $row['gid'];
				if ($description != "")
					$description = $description." at <a href=\"place.php?gid=".$place_gid."\">".$place."</a>";
				else
					$description = "<a href=\"place.php?gid=".$place_gid."\">".$place."</a>";
			}

			print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\">".$description."</span></p>\n");
		}
		unset($row);
	}

	function do_parents($db, $gid)
	{
		$relation[0]="(none)";
		$relation[1]=""; // birth
		$relation[2]="(adopted)";
		$relation[3]="(step)";
		$relation[4]="(sponsored)";
		$relation[5]="(foster)";
		$relation[6]="(custom)";
		$relation[7]="(unknown 7)";
		$relation[8]="(unknown 8)";

		$result = $db->query(
			"select
				CR.gid,
				F.gid as family_gid,
				father_gid,
				FN.first_name as FName,
				FS.surname as FSurname,
				FN.private as FPrivate,
				CR.frel,
				mother_gid,
				MN.first_name as MName,
				MS.surname as MSurname,
				MN.private as MPrivate,
				CR.mrel
			from child_ref CR
			inner join family F
				on F.gid = CR.gid
			left join name FN
				on FN.gid = father_gid
				and FN.primary_name = 1
			inner join surname FS
				on FS.gid = FN.gid
				and FS.primary_surname = 1
			left join name MN
				on MN.gid = mother_gid
				and MN.primary_name = 1
			inner join surname MS
				on MS.gid = MN.gid
				and MS.primary_surname = 1
			where CR.child_gid = '".$gid."'
				and CR.private  = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Parents</h3>\n");
			$rel = $row[frel];
			$relationship = $relation[$rel];
			$father = $row['father_gid'];
			$father_name = $row['FName'].' '.$row['FSurname'];
			if ($row['FPrivate'] == 1)
				$father_name = substr($row['FName'], 0, 1).' '.$row['FSurname'];
			if (!is_null($father))
				print("<p><span class=\"name\">Father:</span> <span class=\"value\"><a href=\"person.php?gid=".$father."\">".$father_name."</a> ".$relationship."</span></p>\n");
			else
				print("<p><span class=\"name\">Father:</span> <span class=\"value\">&nbsp;</span></p>\n");

			$rel = $row[mrel];
			$relationship = $relation[$rel];
			$mother = $row['mother_gid'];
			$mother_name = $row['MName'].' '.$row['MSurname'];
			if ($row['MPrivate'] == 1)
				$mother_name = substr($row['MName'], 0, 1).' '.$row['MSurname'];
			if (!is_null($mother))
				print("<p><span class=\"name\">Mother:</span> <span class=\"value\"><a href=\"person.php?gid=".$mother."\">".$mother_name."</a> ".$relationship."</span></p>\n");
			else
				print("<p><span class=\"name\">Mother:</span> <span class=\"value\">&nbsp;</span></p>\n");
		}
		unset($row);
		$result = $db->query(
			"select
				CR2.child_gid,
				P.gender as gender,
				N.first_name,
				S.surname,
				N.private
			from child_ref CR
			inner join child_ref CR2
			on CR.gid = CR2.gid
				and CR2.private = 0
			inner join name N
				on N.gid = CR2.child_gid
				and N.primary_name = 1
			inner join surname S
				on S.gid = N.gid
				and primary_surname = 1
			inner join person P
				on P.gid = CR2.child_gid
			where CR.child_gid = '".$gid."'
				and CR.private = 0");
		for($i=1; $row = $result->fetch(); $i++)
		{
			$child_gid = $row['child_gid'];
			if ($i == 1)
			{
				print("<p><span class=\"name\">Siblings:</span></p>\n");
			}
			$private = $row['private'];
			$name = "";

			$gender = $row['gender'];
			$first_name = $row['first_name'];

			if ($row['first_name'] == '')
			{
				if ($gender == 'F')
					$first_name = "Unknown Female";
				elseif ($gender == 'M')
					$first_name = "Unknown Male";
				else
					$first_name = "Unknown";
			}

			if ($private == 1)
			{
				$name = substr($first_name, 0, 1)." ".$row[surname];
			}
			else
			{
				$name = $first_name." ".$row[surname];
			}

			if ($child_gid == $gid)
				print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". ".$name."</span></p>\n");
			else
				print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$child_gid."\">".$name."</a></span></p>\n");
		}
		unset($row);
	}

	function do_family_events($db, $gid, $family_gid)
	{

		$result = $db->query(
			"select
				event_gid,
				E.the_type as EventType,
				date1,
				D.the_type,
				D.quality,
				E.description,
				P.title,
				P.gid
			from event_ref ER
			inner join event E
			on ER.event_gid = E.gid
				and E.private = 0
			left join place_ref PR
			on PR.gid = E.gid
			left join place P
			on P.gid = PR.place_gid
				and P.private = 0
			left join date D
			on ER.event_gid = D.gid
			where ER.private = 0
				and ER.gid = '".$family_gid."'");

		for($i=1; $row = $result->fetch(); $i++)
		{
			$type = $row['EventType'];
			$name = "";
			if ($type == 1)
				$name = 'married ';
			elseif ($type == 7)
				$name = 'divorced ';
			elseif ($type == 42)
				$name = 'residence ';
			$date = do_date($row['date1'], $row['the_type'], $row['quality']);

			$place = $row['title'];
			$name = $name.$date.' '.$row['description'];
			if (!is_null($place))
			{
				$place_gid = $row['gid'];
				$name = $name." at <a href=\"place.php?gid=".$place_gid."\">".$place."</a>";
			}
			print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$name."</span></p>\n");
		}
		unset($row);
	}

	function do_children($db, $gid, $family_gid)
	{

		$relation[0]="(none)";
		$relation[1]=""; // birth
		$relation[2]="(adopted)";
		$relation[3]="(step child)";
		$relation[4]="(sponsored)";
		$relation[5]="(foster child)";
		$relation[6]="(unknown)";
		$relation[7]="(unknown 7)";
		$relation[8]="(unknown 8)";
		GLOBAL $genderID;
		$result = $db->query(
			"select
				N.gid as person_gid,
				N.first_name,
				S.surname,
				N.private,
				R.frel,
				R.mrel
			from family F
			inner join child_ref R
				on R.gid = F.gid
			inner join name N
				on N.gid = R.child_gid
				and N.primary_name = 1
			inner join surname S
				on S.gid = N.gid
			where (F.father_gid = '".$gid."'
				or F.mother_gid = '".$gid."')
				and F.gid = '".$family_gid."'
				and F.private = 0");
		for($i=1; $row = $result->fetch(); $i++)
		{
			$relationship = "";
			if ($genderID == 'M')
				$rel = $row['mrel'];
			elseif ($genderID == 'F')
				$rel = $row['frel'];
			$relationship = $relation[$rel];
			$person_gid = $row['person_gid'];
			$name = $row['first_name'].' '.$row['surname'];
			if ($row['private'] == 1)
				$name = substr($row['first_name'], 0, 1).' '.$row['surname'];
			if ($i == 1)
			{
				print("<p><span class=\"name\">Children:</span></p>\n");
			}
			print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$person_gid."\"> ".$name."</a> ".$relationship."</span></p>\n");
		}
		unset($row);
	}

	function do_families($db, $gid)
	{
		$result = $db->query(
			"select
				F.gid as family_gid,
				N.gid as person_gid,
				N.first_name,
				S.surname,
				N.private
			from family F
			left join name N
				on (N.gid = F.mother_gid
				or N.gid = F.father_gid)
				and N.gid <> '".$gid."'
				and N.primary_name = 1
			inner join surname S
				on (S.gid = F.mother_gid
				or S.gid = F.father_gid)
				and S.gid <> '".$gid."'
				and primary_surname = 1
			where (F.father_gid = '".$gid."'
				or F.mother_gid = '".$gid."')
				and F.private = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Families</h3>\n");
			else
				print("\n<br />\n");

			GLOBAL $genderID;
			if ($genderID == 'M')
				$spouse = "Wife";
			else if ($genderID == 'F')
				$spouse = "Husband";
			else
				$spouse = "Spouse";
			$name = $row['first_name'].' '.$row['surname'];
			if ($row['private'] == 1)
				$name = substr($row['first_name'], 0, 1).' '.$row['surname'];
			if (is_null($name))
				print("<p><span class=\"name\">".$spouse.":</span> <span class=\"value\">&nbsp;</span></p>\n");
			else
				print("<p><span class=\"name\">".$spouse.":</span> <span class=\"value\"><a href=\"person.php?gid=".$row['person_gid']."\">".$name."</a></span></p>\n");
			do_family_events($db, $gid, $row['family_gid']);
			do_children($db, $gid, $row['family_gid']);
		}
		unset($row);
	}

	function do_attributes($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				the_type,
				value
			from attribute
			where gid = '".$gid."'
			and private = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Attributes</h3>\n");

			switch($row['the_type'])
			{
				case 0:
					$eventtype = "Custom";
					break;
				case 1:
					$eventtype = "Marriage";
					 break;
				case 3:
					$eventtype = "ID Number";
					 break;
				case 5:
					$eventtype = "Number of Children";
					break;
				case 7:
					$eventtype = "Nick Name";
					 break;
				case 10:
					$eventtype = "Age";
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
				case 30:
					$eventtype = "Immigration";
					 break;
				case 32:
					$eventtype = "Blood Group";
					 break;
				case 33:
					$eventtype = "Military Service";
					 break;
				case 37:
					$eventtype = "Occupation";
					 break;
				case 42:
					$eventtype = "Residence";
					 break;
				default:
					$eventtype = "Unknown attribute ".$row['the_type'];
			}

			print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\">".$row['value']."</span></p>\n");
		}
		unset($row);
	}

	function do_url($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				path,
				description,
				the_type
			from url
			where gid = '".$gid."'
				and private = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
			{
				print("\n<h3>Web Links</h3>\n");
			}
			switch($row['the_type'])
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
			if ($desc == "" || is_null($desc))
				$desc = $row['path'];

			print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\"><a href=\"".$path."\">".$desc."</a></span></p>\n");
		}
		unset($row);
	}

	function do_notes($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select text
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

	function do_references($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				S.title,
				CR.gid,
				CR.citation_gid,
				C.confidence,
				C.page
			from citation_ref CR
			left join citation C
			on citation_gid = C.gid
				and C.private = 0
			left join source_ref SR
			on SR.gid = C.gid
				and SR.private = 0
			inner join source S
			on S.gid = SR.source_gid
				and S.private = 0
			where CR.gid = '".$gid."'
			order by S.title");

		for($i=1; $row = $result->fetch(); $i++)
		{
			if ($i == 1)
			{
				print("\n<h3>References</h3>\n");
			}

			$page = $row['page'];
			if ($page == "" || is_null($page) == true)
				print("<p><span class=\"name\">".$i.'. '.$row['title']."</span></p>\n");
			else
				print("<p><span class=\"name\">".$i.'. '.$row['title'].", </span> <span class=\"value\">".$row['page']."</span></p>\n");
		}
		unset($row);
	}

	function do_father($db, $gid, $top)
	{
   		GLOBAL $gen;
		$result = $db->query(
			"select
				father_gid,
				FN.first_name,
				S.surname,
				FN.private
			from child_ref CR
			inner join family F
				on F.gid = CR.gid
			left join name FN
				on FN.gid = father_gid
				and FN.primary_name = 1
			inner join surname S
				on S.gid = FN.gid
			where CR.child_gid = '".$gid."'
				and CR.private  = 0");

		$row = $result->fetch();
		if ($row)
		{
			if (!is_null($row['father_gid']))
			{
				$name = $row['first_name'].' '.$row['surname'];
				if ($row['private'] == 1)
					$name = substr($row['first_name'], 0, 1).' '.$row['surname'];
				$res = $row['father_gid'];
				$left = 6 + 190*$gen;
				$height = (int)(242/pow(2,$gen-1)+1);
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; width: 16px\" class=\"line\"></div>\n");
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; height: ".$height."px\" class=\"line\"></div>\n");
				print("<div class=\"mbox AncCol".$gen."\" style=\"top: ".$top."px;\"><a href=\"person.php?gid=".$res."\">".$name."</a></div>\n");
			}
		}
		unset($row);
		return $res;
	}

	function do_mother($db, $gid, $top)
	{
   		GLOBAL $gen;
		$result = $db->query(
			"select
				mother_gid,
				MN.first_name,
				S.surname,
				MN.private
			from child_ref CR
			inner join family F
				on F.gid = CR.gid
			left join name MN
				on MN.gid = mother_gid
				and MN.primary_name = 1
			inner join surname S
				on S.gid = MN.gid
			where CR.child_gid = '".$gid."'
				and CR.private  = 0");

		$row = $result->fetch();
		if ($row)
		{
			if (!is_null($row['mother_gid']))
			{
				$name = $row['first_name'].' '.$row['surname'];
				if ($row['private'] == 1)
					$name = substr($row['first_name'], 0, 1).' '.$row['surname'];
				$res = $row['mother_gid'];
				$left = 6 + 190*$gen;
				$height = (int)(242/pow(2,$gen-1)+0.5);
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; width: 16px\" class=\"line\"></div>\n");
				print("<div style=\"top: ".($top+15-$height)."px; left: ".$left."px; height: ".$height."px\" class=\"line\"></div>\n");
				print("<div class=\"fbox AncCol".$gen."\" style=\"top: ".$top."px;\"><a href=\"person.php?gid=".$res."\">".$name."</a></div>\n");
			}
		}
		unset($row);
		return $res;
	}

	function do_ancestor($db, $gid, $top)
	{
   		GLOBAL $gen;
   		$ntop = (int)($top - 242/pow(2,$gen));
   		$gen++;
   		$has_ancestors = 0;
   		$agid = do_father($db, $gid, $ntop);
   		if (!is_null($agid))
   		{
			$has_ancestors = 1;
			if ($gen < 4)
			{
				do_ancestor($db, $agid, $ntop);
			}
		}
	   	$ntop = (int)($top + 242/pow(2,$gen-1)+0.5);
		$agid = do_mother($db, $gid, $ntop);
		if (!is_null($agid))
		{
			$has_ancestors = 1;
			if ($gen < 4)
			{
				do_ancestor($db, $agid, $ntop);
			}
		}
		if ($has_ancestors == 1)
		{
			$left = 6 + 190*$gen-15;
			print("<div style=\"top: ".($top+15)."px; left: ".$left."px; width: 16px\" class=\"line\"></div>\n");
		}

   		$gen--;
	}

  require_once 'template.php';
  try
  {
  	$gid = $_GET["gid"];
    //open the database
    $db = new PDO('sqlite:../../.sqlite/gramps1.db');

   	do_name($db, $gid);

	do_events($db, $gid);

	do_parents($db, $gid);

	do_families($db, $gid);

	do_attributes($db, $gid);

	do_url($db, $gid);

	do_notes($db, $gid);

	do_references($db, $gid);

	print("\n<h3>Ancestors</h3>\n<div style=\"position:relative;\">\n");
	GLOBAL $genderID;
   	GLOBAL $name;
   	GLOBAL $gen;
	$gen = 0;

	 if ($genderID == 'M')
		print("<div class=\"mbox AncCol0\"><a href=\"person.php?gid=".$gid."\">".$name."</a></div>\n");
	else if ($genderID == 'F')
		print("<div class=\"fbox AncCol0\"><a href=\"person.php?gid=".$gid."\">".$name."</a></div>\n");
	do_ancestor($db, $gid, 460);
	print("</div>\n");

    // close the database connection
    $db = NULL;
  }
  catch(PDOException $e)
  {
    print 'Exception : '.$e->getMessage();
  }
  echo foot();
?>
