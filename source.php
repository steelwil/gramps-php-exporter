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

		// close the database connection
		$db = NULL;
	}
	catch(PDOException $e)
	{
		print 'Exception : '.$e->getMessage();
	}
	echo foot();
?>
