<?php
/**
 Grampsphpexporter.- export gramps genealogy program to the web

 Copyright (C) 2012-2016 William Bell <william.bell@frog.za.net>

        Grampsphpexporter is free software: you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation, either version 3 of the License, or
        (at your option) any later version.

        Grampsphpexporter is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with Foobar.    If not, see <http://www.gnu.org/licenses/>.
*/

function doSurnameCloud($db)
{
    $totalSurnames = 0;
    $TotalPeopleInBiggestSurname = 0;
    $x = 0;
    $y = 0;

    unset($result);
    $result = $db->query(
        "select 
            count(1) as TotalSurnames, 
            max(Number) as TotalPeopleInBiggestSurname
        from
        (
            select surname, count(1) as Number
            from surname
            where surname is not null
            group by surname
        )"
    );

    if ($row = $result->fetch()) {
        $totalSurnames = $row['TotalSurnames'];
        $TotalPeopleInBiggestSurname = $row['TotalPeopleInBiggestSurname'];
        $y = (3 - 0.7*$TotalPeopleInBiggestSurname) / (1 - $TotalPeopleInBiggestSurname);
        $x = (3 - $y) / $TotalPeopleInBiggestSurname;
    }
    unset($row);

    if ($TotalPeopleInBiggestSurname > 0) {
        print("\n<h3>Surname Cloud</h3>\n<p>");
        unset($result);
        $result = $db->query(
            "select 
                surname, 
                count(1) as Number
            from surname
            where surname is not null
            group by surname
            order by surname"
        );

        for ($i=0; $row = $result->fetch(); $i++) {
            $size = round($row['Number'] * $x + $y, 3);
            //print("<p>size:".$size." x:".$x." y:".$y."</p>");
            print("<a style=\"font-size:".$size."em; vertical-align: middle;\" href=\"surname.php?surname=".$row['surname']."\">".$row['surname']."</a> \n");
        }
        unset($row);

        print("</p>\n"); 
    }
}


require_once 'template.php';
echo head('Overview', '');
try
{
    //open the database
    $db = new PDO('sqlite:../../.sqlite/gramps1.db');

    doSurnameCloud($db);
 
    // close the database connection
    $db = null;
}
catch(PDOException $e)
{
    print 'Exception : '.$e->getMessage();
}
echo foot();

?>
