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
  echo head('Sources', '');
  print("\n<h3>Sources</h3>\n");
  try
  {
    //open the database
    $db = new PDO('sqlite:../../.sqlite/gramps.db');

    //now output the data to a simple html table...

    $result = $db->query('
		select
			gid,
			title
		from source
		where private = 0
		order by title');
	$prevLetter = 'ZZZ';
    foreach($result as $row)
    {
		$title = $row['title'];
		if ($title == '')
		{
			$title = "Unknown";
		}
		if ($prevLetter != $title[0])
		{
			if ($prevLetter != 'ZZZ')
				print("</div>\n");
			$prevLetter = $title[0];
			print("<div class=\"section\">\n\t<div class=\"letter\">".$prevLetter."</div>\n");
		}
		print("\t<div class=\"source\"><a href=\"source.php?gid=".$row[gid]."\">".$title."</a></div>\n");
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
