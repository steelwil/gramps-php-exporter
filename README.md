This exports your Gramps xml database to a SQLite database, using python 2 or 3.

Additionally there are some PHP scripts to display your database on the web.

All privacy settings are honoured.

==How To
1. Make an XML backup of your gramps database.
	Family Trees -> Make Backup -> Exclude Media -> OK
2. Convert the backup to an SQLite database
	python3 gs.py -i MyGrampsXMLBackup.gramps
3. if you want to do the web pages
	copy the database to the correct relative directory (../../.sqlite/gramps1.db)
	copy over the php scripts. 

I am no longer using a plugin to do this, as it no longer works with the new gramps versions.
This method is also much much faster.

[Bell Family Tree](http://www.frog.za.net/family/surname-list.php)
