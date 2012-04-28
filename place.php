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

	function do_place($db, $gid)
	{
		// do place
		$img = '';
		$result = $db->query(
			"select
				P.title,
				P.long,
				P.lat,
				L.street,
				L.locality,
				L.city,
				L.county,
				L.state,
				L.country,
				U.path
			from place P
			left join location L
			on P.gid = L.gid
			left join url U
			on P.gid = U.ref_gid
				and U.private = 0

			where P.private = 0
				and P.gid = '".$gid."'");
		if ($row = $result->fetch())
		{
			$title = $row['title'];

			if ($title != "" && !is_null($title))
				print("<h3>".$title."</h3>");

			$long = $row['long'];
			$lat = $row['lat'];
			if ($long != "" && !is_null($long))
				print("<p><span class=\"name\">Location:</span> <span class=\"value\">".'<a href="http://maps.google.com/maps?q='.$lat.','.$long.'&t=h&z=5">'.$lat.', '.$long."</a></span></p>\n");

			$street = $row['street'];
			if ($street != "" && !is_null($street))
				print("<p><span class=\"name\">Street:</span> <span class=\"value\">".$street."</span></p>\n");

			$locality = $row['locality'];
			if ($locality != "" && !is_null($locality))
				print("<p><span class=\"name\">Locality:</span> <span class=\"value\">".$locality."</span></p>\n");

			$city = $row['city'];
			if ($city != "" && !is_null($city))
				print("<p><span class=\"name\">City:</span> <span class=\"value\">".$city."</span></p>\n");

			$county = $row['county'];
			if ($county != "" && !is_null($county))
				print("<p><span class=\"name\">County:</span> <span class=\"value\">".$county."</span></p>\n");

			$state = $row['state'];
			if ($state != "" && !is_null($state))
				print("<p><span class=\"name\">State:</span> <span class=\"value\">".$state."</span></p>\n");

			$country = $row['country'];
			if ($country != "" && !is_null($country))
				print("<p><span class=\"name\">Country:</span> <span class=\"value\">".$country."</span></p>\n");
		}
		// http://maps.google.com/maps?ll=56.948889,24.106389&t=h&z=5
		// http://maps.google.com/maps?ll=7.710556,50.338056&t=h&z=5
		// http://maps.google.com/maps?ll=50.338056,7.710556&t=h&z=12
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
			$desc = $row['desc'];
			if ($desc == "" || is_null($desc))
				$desc = $row['path'];

			print("<p><span class=\"name\">".$eventtype.":</span> <span class=\"value\"><a href=\"".$path."\">".$desc."</a></span></p>\n");
		}
		unset($row);
	}

	require_once 'template.php';
	echo head('Place', '');
	try
	{
		$gid = $_GET["gid"];
		//open the database
		$db = new PDO('sqlite:../../.sqlite/gramps.db');

		do_place($db, $gid);

		do_url($db, $gid);

		// close the database connection
		$db = NULL;
	}
	catch(PDOException $e)
	{
		print 'Exception : '.$e->getMessage();
	}
	echo foot();
?>
