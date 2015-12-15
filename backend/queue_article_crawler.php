#!/usr/bin/php
<?php
require 'php-readability/lib/Readability.inc.php';

require_once('amqp.inc');
include('config.php');
include('../website/common.php');

$exchange = 'articles_queue';
$queue = 'articles';
$consumer_tag = 'consumer';
$response="";

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

$conn = new AMQPConnection($rmq_HOST, $rmq_PORT, $rmq_USER, $rmq_PASS, $rmq_VHOST);
$ch = $conn->channel();
$ch->queue_declare($queue, false, true, false, false);
$ch->exchange_declare($exchange, 'direct', false, true, false);
$ch->queue_bind($queue, $exchange);

function process_message($msg) {
    global $response;
    global $useragent;

    $body = $msg->body;
    print "$body\n";
    $blob = json_decode($body);
    $id = $blob->article_id;
    $url = $blob->url;

    $crl = curl_init();
    curl_setopt($crl, CURLOPT_URL, $url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($crl, CURLOPT_MAXCONNECTS, 30);
    curl_setopt($crl, CURLOPT_TIMEOUT, 10);
    curl_setopt($crl, CURLOPT_USERAGENT, $useragent);
    $html = curl_exec($crl);
    curl_close($crl);

	$html= str_replace('&rsquo;', '&#8217;', $html);

	$r = new Readability($html);
	$rData = $r->getContent();

	$rtitle = addslashes($rData['title']);
	$rcontent = addslashes($rData['content']);

#	print ("$rtitle\n");
#	print ("$rcontent\n");

	if ($rcontent != "") {
		$result = mysql_query("update articles set readability_title = '$rtitle', readability_content = '$rcontent' where id = '$id'");
	}

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

}

$ch->basic_qos(0,20,false);
$ch->basic_consume($queue, $consumer_tag, false, false, false, false, 'process_message');

function shutdown($ch, $conn){
    $ch->close();
    $conn->close();
}

register_shutdown_function('shutdown', $ch, $conn);

// Loop as long as the channel has callbacks registered
while(count($ch->callbacks)) {
    $ch->wait();
}
?>
