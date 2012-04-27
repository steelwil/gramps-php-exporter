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

	function do_date($year, $month, $day)
	{
		$res = '';
		if ($year > 0)
		{
			$res = $year;
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

	function do_name($db, $gid)
	{
		// do media
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
		if ($row = $result->fetch())
		{
			$pic = $row['path'];
			$pic = substr($pic, strrpos($pic, "/"));
			$img = "<p><img class=\"person\" src=\"pics//".$pic."\" alt=\"".$row['desc']."\" /></p>";
		}


		$result = $db->query(
			"select
				S.gid as gid,
				P.gender as gender,
				N.first_name,
				S.surname
			from person P
			inner join surname S
				on P.gid = S.gid
			inner join name N
				on N.gid = P.gid
				and primary_surname = 1
			where P.gid = '".$gid."'
			order by N.first_name");
		for($i=0; $row = $result->fetch(); $i++)
	    {
	    	GLOBAL $name;
	    	$surname = $row['surname'];
	    	$name = $row['first_name']." ".$surname;
			echo head('Bell Family Tree - '.$name, $surname);

			print("\n<h3>".$name."</h3>\n");
			if ($img != '')
				print($img);
			$gender = 'Unknown';
			GLOBAL $genderID;
			$genderID = $row['gender'];
 			if ($genderID == 1)
				$gender = "Male";
			else if ($genderID == 0)
				$gender = "Female";
			print("<p><span class=\"name\">Gender:</span> <span class=\"value\">".$gender."</span></p>\n");
			break;
   		}
		unset($row);
	}

	function do_events($db, $gid)
	{
		$result = $db->query(
			"select the_type0,
				year1 ||
				case when month1 < 10 then '-0' else '-' end
				|| month1 ||
				case when day1 < 10 then '-0' else '-' end
				|| day1 as date1,
				description,
				P.title,
				D.quality
			from event_ref R
			inner join event E
				on E.gid = R.event_gid
			left join date D
				on D.gid = R.event_gid
			left join place_ref PR
				on PR.gid = R.event_gid
			left join place P
				on P.gid = PR.place_gid
				and P.private = 0
			where R.gid = '".$gid."'");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Events</h3>\n");

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
	    	if ($row['quality'] == 1)
	    		$date_qual = "estimated ";
	    	else
	    		$date_qual = "";

			$date = $row['date1'];
			if (is_null($date))
				$date = "";
			else
				$date = $date.' ';

			$description = $date_qual.$date.$row['description'];

			$place = $row['title'];
			if (!is_null($place))
			{
				if ($description != "")
					$description = $description." at ".$place;
				else
					$description = $place;
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
		$relation[6]="(unknown)";
		$relation[7]="(unknown 7)";
		$relation[8]="(unknown 8)";

		$result = $db->query(
			"select
				CR.gid,
				F.gid as family_gid,
				father_gid,
				FN.first_name||' '||FS.surname as FatherName,
				CR.frel0,
				mother_gid,
				MN.first_name||' '||MS.surname as MotherName,
				CR.mrel0
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
			$rel = $row[frel0];
			$relationship = $relation[$rel];
			$father = $row['father_gid'];
			if (!is_null($father))
				print("<p><span class=\"name\">Father:</span> <span class=\"value\"><a href=\"person.php?gid=".$father."\">".$row['FatherName']."</a> ".$relationship."</span></p>\n");
			else
				print("<p><span class=\"name\">Father:</span> <span class=\"value\">&nbsp;</span></p>\n");

			$rel = $row[mrel0];
			$relationship = $relation[$rel];
			$mother = $row['mother_gid'];
			if (!is_null($mother))
				print("<p><span class=\"name\">Mother:</span> <span class=\"value\"><a href=\"person.php?gid=".$mother."\">".$row['MotherName']."</a> ".$relationship."</span></p>\n");
			else
				print("<p><span class=\"name\">Mother:</span> <span class=\"value\">&nbsp;</span></p>\n");
		}
		unset($row);
		$result = $db->query(
			"select
				CR2.sortval,
				CR2.child_gid,
				N.first_name||' '||S.surname as Name
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
			where CR.child_gid = '".$gid."'
				and CR.private = 0
			order by CR2.sortval");
		for($i=1; $row = $result->fetch(); $i++)
		{
			$child_gid = $row['child_gid'];
			if ($i == 1)
			{
				if ($child_gid == $gid)
					print("<p><span class=\"name\">Siblings:</span> <span class=\"value\">".$i.". ".$row['Name']."</span></p>\n");
				else
					print("<p><span class=\"name\">Siblings:</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$child_gid."\">".$row['Name']."</a></span></p>\n");
			}
			else
			{
				if ($child_gid == $gid)
					print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". ".$row['Name']."</span></p>\n");
				else
					print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$child_gid."\">".$row['Name']."</a></span></p>\n");
			}
		}
		unset($row);
	}

	function do_family_events($db, $gid, $family_gid)
	{

		$result = $db->query(
			"select
				event_gid,
				the_type0,
				year1,
				month1,
				day1,
				E.description
			from event_ref ER
			inner join event E
			on ER.event_gid = E.gid
				and ER.private = E.private
			left join date D
			on ER.event_gid = D.gid
			where ER.gid = '".$family_gid."'
				and ER.private = 0");

		for($i=1; $row = $result->fetch(); $i++)
		{
			$type = $row['the_type0'];
			$name = "";
			if ($type == 1)
				$name = 'married ';
			elseif ($type == 7)
				$name = 'divorced ';
			$date = do_date($row['year1'], $row['month1'], $row['day1']);
			$name = $name.$date.' '.$row['description'];

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
				N.first_name||' '||S.surname as Name,
				R.frel0, R.mrel0
			from family F
			inner join child_ref R
				on R.gid = F.gid
			inner join name N
				on N.gid = R.child_gid
				and N.private = 0
				and N.primary_name = 1
			inner join surname S
				on S.gid = N.gid
			where (F.father_gid = '".$gid."'
				or F.mother_gid = '".$gid."')
				and F.gid = '".$family_gid."'
				and F.private = 0
			order by R.sortval");

		for($i=1; $row = $result->fetch(); $i++)
		{
			$name = "";
			if ($genderID == 0)
				$rel = $row['mrel0'];
			elseif ($genderID == 1)
				$rel = $row['frel0'];
			$name = $relation[$rel];
			$person_gid = $row['person_gid'];
			if ($i == 1)
			{
				print("<p><span class=\"name\">Children:</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$person_gid."\"> ".$row['Name']."</a> ".$name."</span></p>\n");
			}
			else
			{
				print("<p><span class=\"name\">&nbsp;</span> <span class=\"value\">".$i.". <a href=\"person.php?gid=".$person_gid."\"> ".$row['Name']."</a> ".$name."</span></p>\n");
			}
		}
		unset($row);
	}

	function do_families($db, $gid)
	{
		$result = $db->query(
			"select
				F.gid as family_gid,
				N.gid as person_gid,
				N.first_name||' '||S.surname as Name,
				F.the_type0
			from family F
			left join name N
				on (N.gid = F.mother_gid
				or N.gid = F.father_gid)
				and N.gid <> '".$gid."'
				and N.private = 0
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

			GLOBAL $genderID;
			if ($genderID == 0)
				$spouse = "Husband";
			else if ($genderID == 1)
				$spouse = "Wife";
			else
				$spouse = "Spouse";
			$name = $row['Name'];
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
				the_type0,
				the_type1,
				value
			from attribute
			where gid = '".$gid."'
			and private = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
				print("\n<h3>Attributes</h3>\n");

			switch($row['the_type0'])
			{
				case 0:
					$eventtype = $row['the_type1'];
					break;
				case 1:
					$eventtype = "Marriage";
					 break;
				case 3:
					$eventtype = "ID Number";
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
					$eventtype = "Unknown attribute ".$row['the_type0'];
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
				desc,
				type0
			from url
			where ref_gid = '".$gid."'
				and private = 0");

		for($i=0; $row = $result->fetch(); $i++)
		{
			if ($i == 0)
			{
				print("\n<h3>Web Links</h3>\n");
			}
			switch($row['type0'])
			{
				case 1:
					$eventtype = "e-mail";
					$path = "mailto:".$row['path'];
					break;
				case 2:
					$eventtype = "Web Page";
					$path = $row['path'];
					 break;
			}

			print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\"><a href=\"".$path."\">".$row['path']."</a></span></p>\n");
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


	function do_father($db, $gid, $top)
	{
   		GLOBAL $gen;
		$result = $db->query(
			"select
				father_gid,
				FN.first_name||\" \"||S.surname as name
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
				$res = $row['father_gid'];
				$left = 6 + 190*$gen;
				$height = (int)(242/pow(2,$gen-1)+1);
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; width: 16px\" class=\"line\"></div>\n");
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; height: ".$height."px\" class=\"line\"></div>\n");
				print("<div class=\"mbox AncCol".$gen."\" style=\"top: ".$top."px;\"><a href=\"person.php?gid=".$res."\">".$row['name']."</a></div>\n");
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
				MN.first_name||\" \"||S.surname as name
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
				$res = $row['mother_gid'];
				$left = 6 + 190*$gen;
				$height = (int)(242/pow(2,$gen-1)+0.5);
				print("<div style=\"top: ".($top+15)."px; left: ".$left."px; width: 16px\" class=\"line\"></div>\n");
				print("<div style=\"top: ".($top+15-$height)."px; left: ".$left."px; height: ".$height."px\" class=\"line\"></div>\n");
				print("<div class=\"fbox AncCol".$gen."\" style=\"top: ".$top."px;\"><a href=\"person.php?gid=".$res."\">".$row['name']."</a></div>\n");
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
    $db = new PDO('sqlite:../../.sqlite/gramps.db');

   	do_name($db, $gid);

	do_events($db, $gid);

	do_parents($db, $gid);

	do_families($db, $gid);

	do_attributes($db, $gid);

	do_url($db, $gid);

	do_notes($db, $gid);

	print("\n<h3>Ancestors</h3>\n<div style=\"position:relative;\">\n");
	GLOBAL $genderID;
   	GLOBAL $name;
   	GLOBAL $gen;
	$gen = 0;

	 if ($genderID == 1)
		print("<div class=\"mbox AncCol0\"><a href=\"person.php?gid=".$gid."\">".$name."</a></div>\n");
	else if ($genderID == 0)
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
