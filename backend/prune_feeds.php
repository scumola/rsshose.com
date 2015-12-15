#!/usr/bin/php
<?php
include('config.php');

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

$result = mysql_query("delete from articles where crawl_date < date_sub(now(), interval 72 hour)");
$result = mysql_query("update feeds set mins_between_fetches = '10' where id in (select feed_id from fetched) order by id");
$result = mysql_query("update feeds set mins_between_fetches = '720' where id not in (select feed_id from fetched) order by id");
$result = mysql_query("PURGE BINARY LOGS BEFORE DATE_SUB( NOW( ), INTERVAL 7 DAY);");
$result = mysql_query("delete from fetched");
?>
