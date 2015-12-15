<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-78945-24']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<?php
ob_start();
include_once ("config.php");
include_once ("get_rss_feed_title.php");

$conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$conn)){
    $error_string = "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
} else {
    $email = $_COOKIE['email'];
    $result = mysql_query("select * from users where email = '$email'");
    while ($row = mysql_fetch_assoc($result)) {
        $user_id = $row['id'];
    }
}

function insert_feed ($tag, $title, $htmlurl, $xmlurl, $type) {
    global $user_id;
    global $mysql_host;
    global $mysql_user;
    global $mysql_passwd;
    $tag = addslashes($tag);
    $title = addslashes($title);
    $result = mysql_query("select * from feeds where xmlurl = '$xmlurl'");
    if (mysql_num_rows($result) == 0) {
        # not in the DB yet
        mysql_query("insert into feeds values ('','$type',NULL,'$xmlurl','$htmlurl',NULL,NULL,'60','')");
	$feed_title = get_rss_feed_title($xmlurl);
        mysql_query("update feeds set title = '$feed_title' where xmlurl = '$xmlurl'");
	
    }
    $result = mysql_query("select * from feeds where xmlurl = '$xmlurl'");
    while ($row = mysql_fetch_assoc($result)) {
        $feed_id = $row['id'];
    }
    mysql_query("insert into user_feed values ('','$user_id','$feed_id',NULL,'$tag','$title',NULL,NULL)");
    mysql_query("insert into user_feed values ('','0','$feed_id',NULL,'',NULL,NULL,NULL)");
}

if ($_FILES["file"]["error"] > 0) {
    echo "Error: " . $_FILES["file"]["error"] . "<br />";
} else {
    echo "Upload: " . $_FILES["file"]["name"] . "<br />";
    echo "Type: " . $_FILES["file"]["type"] . "<br />";
    echo "Size: " . $_FILES["file"]["size"] . " bytes<br />";
    echo "Stored in: " . $_FILES["file"]["tmp_name"] . "<br><br>";
    $xml = simplexml_load_file($_FILES["file"]["tmp_name"]);
    foreach($xml->body->outline as $item) {
        unset($tag);
        unset($html_url);
        unset($xml_url);
        unset($title);
        unset($type);
        foreach ($item->attributes() as $name => $value) {
            if ($name == 'htmlUrl') {
                $html_url = $value;
            }
            if ($name == 'xmlUrl') {
                $xml_url = $value;
            }
            if ($name == 'title') {
                $title = $value;
            }
            if ($name == 'type') {
                $type = $value;
            }
        }
        if (!isset($html_url)) {
            $tag = $title;
            foreach($item->outline as $tag_item) {
                unset($html_url);
                unset($xml_url);
                unset($title);
                unset($type);
                foreach ($tag_item->attributes() as $name => $value) {
                    if ($name == 'htmlUrl') {
                        $html_url = $value;
                    }
                    if ($name == 'xmlUrl') {
                        $xml_url = $value;
                    }
                    if ($name == 'title') {
                        $title = $value;
                    }
                    if ($name == 'type') {
                        $type = $value;
                    }
                }
                print ("[$tag] $title ($xml_url)<br>\n");
                insert_feed($tag,$title,$html_url,$xml_url,$type);
            }
        } else {
            print ("$title ($xml_url)<br>\n");
            insert_feed('',$title,$html_url,$xml_url,$type);
        }
    }
}
header('Location: http://rsshose.com/dashboard.php');
?>
