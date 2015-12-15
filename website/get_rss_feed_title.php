<?php
include_once ("config.php");

function get_rss_feed_title_and_htmlurl ($url) {
	global $useragent;
	$crl = curl_init();
	curl_setopt($crl, CURLOPT_URL, $url);
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($crl, CURLOPT_MAXCONNECTS, 30);
	curl_setopt($crl, CURLOPT_TIMEOUT, 10);
	curl_setopt($crl, CURLOPT_USERAGENT, $useragent);
	$rss_xml = curl_exec($crl);
	curl_close($crl);

	$xml = simplexml_load_string($rss_xml);
	#    $pr = print_r($xml);

	if (isset($xml->channel->title)) {
		$title = $xml->channel->title;
		$htmlurl = $xml->channel->link;
		return array ($title,$htmlurl);
	} else {
		return "";
	}
}
function get_rss_feed_title ($url) {
	global $useragent;
	$crl = curl_init();
	curl_setopt($crl, CURLOPT_URL, $url);
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($crl, CURLOPT_MAXCONNECTS, 30);
	curl_setopt($crl, CURLOPT_TIMEOUT, 10);
	curl_setopt($crl, CURLOPT_USERAGENT, $useragent);
	$rss_xml = curl_exec($crl);
	curl_close($crl);

	$xml = simplexml_load_string($rss_xml);
	#    $pr = print_r($xml);

	if (isset($xml->channel->title)) {
		$title = $xml->channel->title;
		return ($title);
	} else {
		return "";
	}
}


?>
