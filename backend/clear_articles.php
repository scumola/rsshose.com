#!/usr/bin/php
<?php
include('config.php');

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

$result = mysql_query("delete from articles");
$result = mysql_query("update feeds set last_fetch = NULL");
?>
