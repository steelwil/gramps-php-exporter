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
  echo head('Surnames', '');
  print("\n<h3>Surnames</h3>\n");
  try
  {
    //open the database
    $db = new PDO('sqlite:../../.sqlite/gramps.db');

    //now output the data to a simple html table...

    $result = $db->query('
		select surname, count(1) as Number
		from surname
		group by surname
		order by upper(surname)'
    );
 	$prevLetter = 'ZZZ';
   foreach($result as $row)
    {
		$surname = $row['surname'];
		if ($surname == '')
		{
			$surname = "Unknown";
		}
		$currentLetter = strtoupper($surname[0]);
		if ($prevLetter != $currentLetter)
		{
			if ($prevLetter != 'ZZZ')
				print("</div>\n");
			$prevLetter = $currentLetter;
			print("<div class=\"section\">\n\t<div class=\"letter\">".$prevLetter."</div>\n");
		}
		print("\t<div class=\"surname\"><a href=\"surname.php?surname=".$surname."\">".$surname."</a> (".$row['Number'].")</div>\n");
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
