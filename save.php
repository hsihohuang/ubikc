<?php
	require("mysql_connect.php");

	$id = $_POST['id']; 
	$value = $_POST['value']; 

	list($table, $field, $id) = explode('-', $id); 

	mysql_query("UPDATE  $table SET  $field = '$value' WHERE  $table.`id` ='$id'"); 
	echo $value; 
?>
