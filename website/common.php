<?php
$useragent = "WebbCrawler 1.0 ( http://badcheese.com/crawler.html )";
function clean_rss($text) {
	$text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $text);
	$text = strip_tags($text, '<a><b><i><img><li><p>');
	$text = html_entity_decode ($text, ENT_COMPAT, 'UTF-8');
	$text = str_replace("]]>","",$text);
	$text = str_replace("<![CDATA[","",$text);
#	$text = utf8_encode ($text);
	return $text;
}
?>
