#!/usr/bin/php
<?php
include_once ('config.php');
include_once ('../website/get_rss_feed_title.php');

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

$result = mysql_query("select xmlurl,alturl from feeds where title is NULL or title = ''");
while ($row = mysql_fetch_assoc($result)) {
	$xmlurl = $row['xmlurl'];
	$alturl = $row['alturl'];
	if ($alturl != "") {
		$xmlurl = $alturl;
	}
	$feed_title = addslashes(get_rss_feed_title($xmlurl));
	if ($alturl != "") {
		mysql_query("update feeds set title = '$feed_title' where alturl = '$xmlurl'");
	} else {
		mysql_query("update feeds set title = '$feed_title' where xmlurl = '$xmlurl'");
	}
	print ("Found title of $feed_title\n");
}

$result = mysql_query("update feeds set type = 'rss' where type = 'unknown'");
?>
