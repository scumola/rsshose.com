#!/usr/bin/php
<?php
require_once('amqp.inc');
include('config.php');

$exchange = 'feeds_queue';
$queue = 'feeds';

# Insert into rabbitmq
$conn = new AMQPConnection($rmq_HOST, $rmq_PORT, $rmq_USER, $rmq_PASS, $rmq_VHOST);
$ch = $conn->channel();
$ch->queue_declare($queue, false, true, false, false);
$ch->exchange_declare($exchange, 'direct', false, true, false);
$ch->queue_bind($queue, $exchange);

$my_conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$my_conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

#FIX ME
# fetches properly - only feeds that are being queried
$result = mysql_query("select * from feeds where (date_add(last_fetch, interval mins_between_fetches minute) < now() or last_fetch is NULL) and type != 'unknown' order by last_fetch asc");
# fetches everything every 30 mins
#$result = mysql_query("select * from feeds where (date_add(last_fetch, interval 30 minute) < now() or last_fetch is NULL) and type != 'unknown' order by last_fetch asc");
while ($row = mysql_fetch_assoc($result)) {
    $feed_id = $row['id'];
    $type = $row['type'];
    $xmlurl = $row['xmlurl'];
    $alturl = $row['alturl'];
    $crawl_articles = $row['crawl_articles'];

    if ($alturl != "") {
    	$task = array('crawl_articles' => $crawl_articles, 'feed_id' => $feed_id, 'xmlurl' => $alturl, 'origurl' => $xmlurl, 'feed_type' => $type);
    } else {
        $task = array('crawl_articles' => $crawl_articles, 'feed_id' => $feed_id, 'xmlurl' => $xmlurl, 'feed_type' => $type);
    }
    $msg_body = json_encode($task);
    $msg = new AMQPMessage($msg_body, array('content_type' => 'text/plain', 'delivery_mode' => 2));
    $ch->basic_publish($msg, $exchange);
    print ("$msg_body\n");
}

$ch->close();
$conn->close();
?>
