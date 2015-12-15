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

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
}

$in_ch->basic_qos(0,200,false);
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
