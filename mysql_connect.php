<?php
require_once('../../config.php');
global $CFG;

$link = mysql_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass) or die("Could not connect :" .mysql_error());
mysql_select_db($CFG->dbname) or die("Could not select database.");

mysql_query("SET NAMES 'utf8'");
mysql_query("SET CHARACTER_SET_CLIENT=utf8");
mysql_query("SET CHARACTER_SET_RESULTS=utf8");

?>
