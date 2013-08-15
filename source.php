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

			$pubinfo = $row['pubinfo'];
			if ($pubinfo != "" && !is_null($pubinfo))
				print("<p><span class=\"name\">Publication Information:</span> <span class=\"value\">".$pubinfo."</span></p>\n");

			$author = $row['author'];
			if ($author != "" && !is_null($author))
				print("<p><span class=\"name\">Author:</span> <span class=\"value\">".$author."</span></p>\n");

			$abbrev = $row['abbrev'];
			if ($abbrev != "" && !is_null($abbrev))
				print("<p><span class=\"name\">Abbreviation:</span> <span class=\"value\">".$abbrev."</span></p>\n");
		}
	}

	function do_notes($db, $gid)
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
				description
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
			$img = "<p><img src=\"pics".$pic."\" alt=\"".$row['description']."\" /></p>";
			print($img);
		}
		unset($row);
	}

	function do_citation_reference($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				SR.source_gid,
				SR.gid,
				N.first_name||' '||S.surname as Name,
				C.page,
				C.confidence,
				CR.gid as person_gid
			from source_ref SR
			left join citation C
			on C.gid = SR.gid
				and C.private = 0
			left join citation_ref CR
			on CR.citation_gid = C.gid
			left join name N
			on N.gid = CR.gid
				and N.private = 0
			left join surname S
			on N.gid = S.gid
			where SR.private = 0
				and SR.source_gid = '".$gid."'
			order by length(page), page");

		for($i=1; $row = $result->fetch(); $i++)
		{
			if ($i == 1)
			{
				print("\n<h3>Citations</h3>\n");
			}
			$ref_gid = $row['gid'];
			$name = $row['Name'];
			if ($name != "" && !is_null($name))
				$descr = "<a href=\"person.php?gid=".$row['person_gid']."\">".$name."</a>";
			else
				$descr = "<a href=\"citation.php?gid=".$ref_gid."\">".$ref_gid."</a>";

			print("<p>".$i.". ".$row['page'].": ".$descr."</p>\n");

		}
		unset($row);
	}

	require_once 'template.php';
	echo head('Source', '');
	try
	{
		$gid = $_GET["gid"];
		//open the database
		$db = new PDO('sqlite:../../.sqlite/gramps1.db');

		do_source($db, $gid);

		do_notes($db, $gid);

		do_gallery($db, $gid);

		do_citation_reference($db, $gid);

		$db = NULL;
	}
	catch(PDOException $e)
	{
		print 'Exception : '.$e->getMessage();
	}
	echo foot();
?>
