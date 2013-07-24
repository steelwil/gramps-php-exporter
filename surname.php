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
  require_once 'template.php';
  echo head('Surname', '');
  try
  {
  	$surname = $_GET["surname"];
	print("\n<h3>".$surname."</h3>\n");
	if ($surname == 'Unknown')
	{
		$surname = "";
	}
	//open the database
	$db = new PDO('sqlite:../../.sqlite/gramps1.db');

	//get all names with a specific surname
	$result = $db->query(
		"select
			P.gid as gid,
			P.gender as gender,
			first_name,
			max(D.date1) as date1,
			max(D.quality) as quality
		from name N
		inner join person P
			on P.gid = N.gid
		inner join surname S
			on P.gid = S.gid
		left join event_ref ER
			on ER.gid = P.gid
		left join event E
			on ER.event_gid = E.gid
				and E.the_type = 12
		left join date D
			on D.gid = E.gid
		where S.surname = '".$surname."'
		group by P.gid, first_name
		order by first_name");

 	$prevLetter = 'ZZZ';
	foreach($result as $row)
	{
		$gid = $row['gid'];
		$descrip = '*';
		$descrip = substr($descrip.$row['date1'], 0, 5);
		if ($descrip == '*')
			$descrip = "";
		$first_name = $row['first_name'];
		$gender = $row['gender'];
		if ($first_name == '')
		{
			if ($gender == 'F')
				$first_name = "Unknown Female";
			elseif ($gender == 'M')
				$first_name = "Unknown Male";
			else
				$first_name = "Unknown";
		}
		if ($prevLetter != $first_name[0])
		{
			if ($prevLetter != 'ZZZ')
				print("</div>\n");
			$prevLetter = $first_name[0];
			print("<div class=\"section\">\n\t<div class=\"letter\">".$prevLetter."</div>\n");
		}
		print("\t<div class=\"name\"><a href=\"person.php?gid=".$gid."\">".$first_name."</a> ".$descrip."</div>\n");
	}
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
