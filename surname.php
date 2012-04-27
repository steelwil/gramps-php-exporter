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
    $db = new PDO('sqlite:../../.sqlite/gramps.db');

    //get all names with a specific surname
    $result = $db->query(
	    "select
			P.gid as gid,
			P.gender as gender,
			first_name,
			max(D.year1) as year1,
			max(D.month1) as month1,
			max(D.day1) as day1,
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
				and E.the_type0 = 12
		left join date D
			on D.gid = E.gid
		where S.surname = '".$surname."'
		group by P.gid, first_name
		order by first_name");
    foreach($result as $row)
    {
    	$gid = $row['gid'];
    	$descrip = '';
    	if ($row['quality'] == 1)
    		$descrip = "estimated ";
    	else
    		$descrip = "";
    	$descrip = $descrip.$row['year1'];
    	if ($descrip == '')
    		$descrip = "&nbsp;";
    	$first_name = $row['first_name'];
    	$gender = $row['gender'];
    	if ($first_name == '')
    	{
    		if ($gender == 0)
    			$first_name = "Unknown Female";
    		elseif ($gender == 1)
    			$first_name = "Unknown Male";
    		else
				$first_name = "Unknown";
		}
		print("<p><span class=\"name\"><a href=\"person.php?gid=".$gid."\">".$first_name."</a></span> <span class=\"value\">".$descrip."</span></p>\n");
    }

    // close the database connection
    $db = NULL;
  }
  catch(PDOException $e)
  {
    print 'Exception : '.$e->getMessage();
  }
  echo foot();
?>
