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
function head($title, $surname)
{
   $html = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
   \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html>
<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />
<link rel=\"stylesheet\" type=\"text/css\" href=\"basic.css\" />


<title>" . htmlspecialchars($title) . "</title></head>
<body>";

if ($surname != '')
	$html = $html."<p><a href=\"surname-list.php\">Surnames</a> <a href=\"place-list.php\">Places</a> <a href=\"surname.php?surname=".$surname."\">".$surname."</a></p>\n";
else
	$html = $html."<p><a href=\"surname-list.php\">Surnames</a> <a href=\"place-list.php\">Places</a></p>\n";
   return $html;
}

function foot()
{
	//$html = "<p>Copyright 2010 - " . date('Y') . "</p>
	$html = "</body>\n</html>";
   return $html;
}
?>
