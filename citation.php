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




select
	C.gid as C_gid,
	C.confidence as C_confidence,
	C.page as C_page,
	C.change as C_change,
	C.private as C_private,
	S.gid as S_gid,
	S.title as S_title,
	S.author as S_author,
	S.pubinfo as S_pubinfo,
	S.abbrev as S_abbrev,
	S.change as S_change,
	S.private as S_private,
	RR.gid as RR_gid,
	RR.repository_gid as RR_repository_gid,
	RR.callno as RR_callno,
	RR.medium as RR_medium,
	RR.private as RR_private,
	R.gid as R_gid,
	R.name as R_name,
	R.the_type as R_the_type,
	R.change as R_change,
	R.private as R_private,
	L.gid as L_gid,
	L.street as L_street,
	L.locality as L_locality,
	L.city as L_city,
	L.county as L_county,
	L.state as L_state,
	L.country as L_country,
	L.postal as L_postal,
	L.phone as L_phone,
	L.parish as L_parish
from source_ref SR
left join citation C
on C.gid = SR.gid
	and C.private = 0
left join source S
on SR.source_gid = S.gid
	and S.private = 0
left join repository_ref RR
on RR.gid = SR.source_gid
	and RR.private = 0
left join repository R
on RR.repository_gid = R.gid
	and R.private = 0
left join location L
on L.gid = S.gid
where SR.gid = 'C0000'
and SR.private  = 0

*/

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


	function do_citation($db, $gid)
	{
		unset($result);
		$result = $db->query(
			"select
				C.gid as C_gid,
				C.confidence as C_confidence,
				C.page as C_page,
				C.change as C_change,
				C.private as C_private,
				S.gid as S_gid,
				S.title as S_title,
				S.author as S_author,
				S.pubinfo as S_pubinfo,
				S.abbrev as S_abbrev,
				S.change as S_change,
				S.private as S_private,
				RR.gid as RR_gid,
				RR.repository_gid as RR_repository_gid,
				RR.callno as RR_callno,
				RR.medium as RR_medium,
				RR.private as RR_private,
				R.gid as R_gid,
				R.name as R_name,
				R.the_type as R_the_type,
				R.change as R_change,
				R.private as R_private,
				L.gid as L_gid,
				L.street as L_street,
				L.locality as L_locality,
				L.city as L_city,
				L.county as L_county,
				L.state as L_state,
				L.country as L_country,
				L.postal as L_postal,
				L.phone as L_phone,
				L.parish as L_parish
			from source_ref SR
			left join citation C
			on C.gid = SR.gid
				and C.private = 0
			left join source S
			on SR.source_gid = S.gid
				and S.private = 0
			left join repository_ref RR
			on RR.gid = SR.source_gid
				and RR.private = 0
			left join repository R
			on RR.repository_gid = R.gid
				and R.private = 0
			left join location L
			on L.gid = S.gid
			where SR.private = 0
			and SR.gid = '".$gid."'");

		if ($row = $result->fetch())
		{
			print("\n<h3>Citation</h3>\n");
			$ref_gid = $row['gid'];
			print("<p><span class=\"name\">Page:</span> <span class=\"value\">".$row['C_page']."</span></p>\n");
			print("\n<h3>Source</h3>\n");
			print("<p><span class=\"name\">Title:</span> <span class=\"value\">".$row['S_title']."</span></p>\n");
			print("<p><span class=\"name\">Author:</span> <span class=\"value\">".$row['S_author']."</span></p>\n");
			print("<p><span class=\"name\">Publish Info:</span> <span class=\"value\">".$row['S_pubinfo']."</span></p>\n");
			print("<p><span class=\"name\">Abbreviation:</span> <span class=\"value\">".$row['S_abbrev']."</span></p>\n");
			print("<p><span class=\"name\">Call Number:</span> <span class=\"value\">".$row['RR_callno']."</span></p>\n");
			print("<p><span class=\"name\">Medium:</span> <span class=\"value\">".$row['RR_medium']."</span></p>\n");
			print("\n<h3>Repository</h3>\n");
			print("<p><span class=\"name\">Repository:</span> <span class=\"value\">".$row['R_name']."</span></p>\n");
			print("<p><span class=\"name\">Type:</span> <span class=\"value\">".$row['R_the_type']."</span></p>\n");
			print("\n<h3>Location</h3>\n");
			print("<p><span class=\"name\">Street:</span> <span class=\"value\">".$row['L_street']."</span></p>\n");
			print("<p><span class=\"name\">Locality:</span> <span class=\"value\">".$row['L_locality']."</span></p>\n");
			print("<p><span class=\"name\">City:</span> <span class=\"value\">".$row['L_city']."</span></p>\n");
			print("<p><span class=\"name\">County:</span> <span class=\"value\">".$row['L_county']."</span></p>\n");
			print("<p><span class=\"name\"></span>State:</span> <span class=\"value\">".$row['L_state']."</span></p>\n");
			print("<p><span class=\"name\">Country:</span> <span class=\"value\">".$row['L_country']."</span></p>\n");
			print("<p><span class=\"name\">Postal Code:</span> <span class=\"value\">".$row['L_postal']."</span></p>\n");
			print("<p><span class=\"name\">Phone Number:</span> <span class=\"value\">".$row['L_phone']."</span></p>\n");
			print("<p><span class=\"name\">Parish:</span> <span class=\"value\">".$row['L_parish']."</span></p>\n");
		}
		unset($row);
	}

	require_once 'template.php';
	echo head('Citation', '');
	try
	{
		$gid = $_GET["gid"];
		//open the database
		$db = new PDO('sqlite:../../.sqlite/gramps1.db');

		do_citation($db, $gid);

		$db = NULL;
	}
	catch(PDOException $e)
	{
		print 'Exception : '.$e->getMessage();
	}
	echo foot();
?>
