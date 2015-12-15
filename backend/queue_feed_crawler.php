#!/usr/bin/php
<?php
$debug = 0;

require_once('amqp.inc');
include('config.php');
include('../website/common.php');

$in_exchange = 'feeds_queue';
$in_queue = 'feeds';
$out_exchange = 'articles_queue';
$out_queue = 'articles';
$consumer_tag = 'consumer';
$response="";

$conn = new AMQPConnection($rmq_HOST, $rmq_PORT, $rmq_USER, $rmq_PASS, $rmq_VHOST);

$in_ch = $conn->channel();
$in_ch->queue_declare($in_queue, false, true, false, false);
$in_ch->exchange_declare($in_exchange, 'direct', false, true, false);
$in_ch->queue_bind($in_queue, $in_exchange);

$out_ch = $conn->channel();
$out_ch->queue_declare($out_queue, false, true, false, false);
$out_ch->exchange_declare($out_exchange, 'direct', false, true, false);
$out_ch->queue_bind($out_queue, $out_exchange);

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

function process_message($msg) {
    global $debug;
    global $out_ch;
    global $out_exchange;
    global $useragent;
    $json = $msg->body;
    $ob = json_decode($json);

    $url = $ob->xmlurl;
    $crawl = $ob->crawl_articles;
    $feed_id = $ob->feed_id;
    $type = $ob->feed_type;

    print ("FEED: $url\n");

    $crl = curl_init();
    curl_setopt($crl, CURLOPT_URL, $url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($crl, CURLOPT_MAXCONNECTS, 30);
    curl_setopt($crl, CURLOPT_TIMEOUT, 5);
    curl_setopt($crl, CURLOPT_USERAGENT, $useragent);
    $rss_xml = curl_exec($crl);
    curl_close($crl);

    $xml = simplexml_load_string($rss_xml);
#    $pr = print_r($xml);

    if ($debug) {
        $handle = fopen("rss.txt", "w");
        fwrite($handle, $rss_xml);
        fclose($handle);
    }
        
    if (isset($xml->channel->item)) {
        $item_arr = $xml->channel->item;
    } elseif (isset($xml->item)) {
        $item_arr = $xml->item;
    }

    if (!isset($item_arr) and ($type == 'rss')) {
	# couldn't parse the RSS file - try atom2rss converter
	$alturl = "http://rsshose.com/atom2rss.php?url=";
	$alturl .= urlencode($url);
	$result = mysql_query("update feeds set type = 'atom', alturl = '$alturl' where id = '$feed_id'");
    } elseif (!isset($item_arr) and ($type == 'atom')) {
	# doesn't parse through the atom2rss converter either - set to unknown
	$result = mysql_query("update feeds set type = 'unknown', alturl = NULL where id = '$feed_id'");
    } else {

	$result = mysql_query("select * from user_feed where feed_id = '$feed_id' and flag_sanitize = '1'");
	$num_rows = mysql_num_rows($result);
	if ($num_rows > 0) {
		$sanitize = 1;
	} else {
		$sanitize = 0;
	}

	# rss is happy
	foreach($item_arr as $items) {
	    $orig_title = (string) $items->title;
	    $orig_title= clean_rss($orig_title);
	    $orig_link = (string) $items->link;
	    $orig_desc = (string) $items->description;
	    $orig_desc= clean_rss($orig_desc);
	    $title = addslashes($orig_title);
	    $link = addslashes($orig_link);
	    $desc = addslashes($orig_desc);
#	    echo "$orig_title ----> $orig_link\n";

	    # insert article into article table if doesn't already exist
	    $result = mysql_query("insert into articles values ('','$feed_id','$title','$link','$desc',now(),'','')");
	    if (mysql_errno()) {
#            	echo "MySQL error ".mysql_errno().": ".mysql_error()."\n";
	    } else {
		print ("\tNew Article: $orig_title\n");
#		Just sanitize everything for now
#		if ($sanitize == 1) {
			$last_id = mysql_insert_id();
			$task = array('article_id' => $last_id, 'url' => $orig_link);
			$msg_body = json_encode($task);
			$out_msg = new AMQPMessage($msg_body, array('content_type' => 'text/plain', 'delivery_mode' => 2));
			$out_ch->basic_publish($out_msg, $out_exchange);
			print ("\tTO SANITIZE QUEUE: $msg_body\n");
#		}
	    }
	}
    }

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    $result = mysql_query("update feeds set last_fetch = now() where id = '$feed_id'");
#    sleep(1);
#    exit(0);
#    echo "\n";
}

$in_ch->basic_qos(0,20,false);
$in_ch->basic_consume($in_queue, $consumer_tag, false, false, false, false, 'process_message');

function shutdown($ch, $conn) {
    global $in_ch;
    global $out_ch;
    global $conn;
    $in_ch->close();
    $out_ch->close();
    $conn->close();
}

register_shutdown_function('shutdown', $in_ch, $conn);

// Loop as long as the channel has callbacks registered
while(count($in_ch->callbacks)) {
    $in_ch->wait();
}
?>
