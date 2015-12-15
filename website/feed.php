<?php
ob_start();
include ("config.php");
include ("common.php");
$feed_id = $_GET['feed_id'];
$flag_dedupe = $_GET['flag_dedupe'];
$flag_sanitize = $_GET['flag_sanitize'];
$hours = $_GET['hours'];

if (!isset($hours)) {
	$hours = 10;
}

$feed_id = addslashes($feed_id);
$flag_dedupe = addslashes($flag_dedupe);
$flag_sanitize = addslashes($flag_sanitize);
$hours = addslashes($hours);


$conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

#tracking
$result = mysql_query("select * from fetched where feed_id = '$feed_id'");
if (mysql_num_rows($result) > 0) {
	$result2 = mysql_query("update fetched set num=num+1 where feed_id = '$feed_id'");
} else {
	$result2 = mysql_query("insert into fetched values ('$feed_id','1')");
}

$result = mysql_query("select title,htmlurl,xmlurl from feeds where id = '$feed_id'");
if (mysql_num_rows($result) > 0) {
	while ($row = mysql_fetch_assoc($result)) {
		$title = addslashes($row['title']);
		$htmlurl = htmlspecialchars($row['htmlurl']);
		$xmlurl = $row['xmlurl'];
	}
	if (($flag_sanitize == 1) or ($flag_dedupe == 1)) {
		# they're using our technology, need to create the feed
		header ("content-type: text/xml");
		print ("<?xml version='1.0' encoding='utf-8'?>\n");
		print ("<rss version='2.0'>\n");
		print ("  <channel>\n");
		print ("    <title>$title (via rsshose.com)</title>\n");
		print ("    <link>$htmlurl</link>\n");
		print ("    <description>$title - from http://rsshose.com</description>\n");


		# RSS items
#		$result = mysql_query("select a.id,a.rss_title,a.rss_url,a.rss_desc,a.readability_title,a.readability_content,unix_timestamp(a.crawl_date) as crawldate from articles as a, feeds as f where a.feed_id = f.id and f.id = '$feed_id' and f.last_fetch > date_sub(now(),interval 24 HOUR) order by a.crawl_date desc");
		$result = mysql_query("select a.id,a.rss_title,a.rss_url,a.rss_desc,a.readability_title,a.readability_content,unix_timestamp(a.crawl_date) as crawldate from articles as a, feeds as f where a.feed_id = f.id and f.id = '$feed_id' and a.crawl_date > date_sub(now(),interval $hours HOUR) order by a.crawl_date desc");
		while ($row = mysql_fetch_assoc($result)) {
			$id = $row['id'];
			$rss_title = htmlspecialchars(clean_rss($row['rss_title']));
			$rss_url = $row['rss_url'];
			$rss_url = htmlspecialchars($row['rss_url']);
			$crawldate = $row['crawldate'];
			$ts = date("D, d M Y H:i:s T", $crawldate);
			$rss_desc = clean_rss($row['rss_desc']);
			$readability_title = htmlspecialchars(clean_rss($row['readability_title']));
			$readability_content = clean_rss($row['readability_content']);
			
			print ("    <item>\n");
			if ((($readability_content != "<div></div>") or ($readability_content != "")) and ($flag_sanitize == 1)) {
				print ("      <title>$rss_title</title>\n");
#				$readability_content = "<h2>Original Article title: $readability_title</h2>" . $readability_content;
			} else {
				print ("      <title>$rss_title</title>\n");
			}
			print ("      <link>$rss_url</link>\n");
			$guid = hash('sha1',$rss_url);
			print ("      <guid isPermaLink='false'>$guid</guid>\n");
			print ("      <pubDate>$ts</pubDate>\n");
			if ((($readability_content != "<div></div>") or ($readability_content != "")) and ($flag_sanitize == 1)) {
				print ("      <description>\n <![CDATA[ $readability_content ]]> \n</description>\n");
#				print ("      <description>\n <![CDATA[ $readability_content <br> <h3>Original RSS content:</h3><br> $rss_desc ]]> \n</description>\n");
			} else {
				print ("      <description>\n <![CDATA[ $rss_desc ]]> \n</description>\n");
			}
			print ("    </item>\n");
		}


		print ("  </channel>\n");
		print ("</rss>\n");
	} else {
		header("Location: $xmlurl");
		exit;
	}
} else {
	header("HTTP/1.0 404 Not Found");
	exit;
}

?>
