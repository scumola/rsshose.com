<style type="text/css">
div.fancy {
      -moz-border-radius: 4px;
      background-color: #EEEEEE;
      border: 1px solid;
      color: #AAAAAA;
      font-size: 10px;
      font-family: verdana, "DajaVu Sans", sans-serif;
      margin: 1px;
      padding: 5px;
}
body,td {
      font-size: 10px;
      font-family: verdana, "DajaVu Sans", sans-serif;
}
</style>

<div class=fancy><a href="/">rsshose.com</a></div>

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
include_once "config.php";
include_once "functions.php";
#error_reporting(0);

verify_user();

print ("<Table><tr><td valign=top>");
print ("<center>");
show_gravitar_img();

print ("<br>");
print ("<a href='dashboard.php?c=logout' alt='log out'><img src='logout.png'></a><br>");
print ("<br>");
# <!-- Twitter Button -->
print ("<a href='https://twitter.com/rsshose' class='twitter-follow-button' data-show-count='false'>Follow @rsshose</a>");
print ("<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src='//platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','twitter-wjs');</script><br>");

print ("</center>");
print ("</td><td valign=top>");
#$vhash_cookie = $_COOKIE['vhash'];
#print ("DASHBOARD HASH KEY: $vhash_cookie<br>");

print ("<form action='upload_opml_file.php' method='post' enctype='multipart/form-data'>\n");
print ("<label for='file'>Upload GReader OPML File:</label>\n");
print ("<input type='file' name='file' id='file' />\n");
print ("<input type='submit' name='submit' value='Upload' />\n");
print ("</form>\n");

print ("<a href='dashboard.php?c=clear' alt='clear feeds'><img src='clear.png'></a><br>");

print ("<br><b>");
print ("Dedupe: Remove all duplicate articles across all selected feeds over the last 24 hours.  Only display the first occurrance of that article in the original feed.  (not working yet)<br>");
print ("Sanitize: Provide a sanitized/cleaned version of the article content that removes ads and other cruft.<br>");
print ("<br>");
print ("<font color=red>NOTE: This service is still in beta.  You can invite your friends if you want, but please don't tweet or write any articles about it yet.</font><br>");
print ("</b><br>");

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

    if (isset($_GET['c'])) {
        $cmd = $_GET['c'];
        if ($cmd == 'logout') {
		$past = time() - 3600;
		foreach ( $_COOKIE as $key => $value )
		{
		    setcookie( $key, $value, $past, '/' );
		}
		header("Location: http://rsshose.com/");
        }
        if ($cmd == 'clear') {
                $result = mysql_query("delete from user_feed where user_id = '$user_id'");
        }
        if ($cmd == 'toggle') {
            $flag = $_GET['f'];
            $state = $_GET['s'];
            $id = $_GET['id'];
            if ($flag == 'sanitize') {
                $flagname = "flag_sanitize";
            } elseif ($flag == 'dedupe') {
                $flagname = "flag_dedupe";
            }
            $result = mysql_query("update user_feed set $flagname = '$state' where id = '$id'");
        }
    }

    print ("<table>\n");
    $result = mysql_query("select f.htmlurl,uf.id,uf.feed_id,uf.title,uf.tag,uf.flag_dedupe,uf.flag_sanitize from user_feed as uf, feeds as f where user_id = '$user_id' and uf.feed_id = f.id order by tag,title asc");
    while ($row = mysql_fetch_assoc($result)) {
        $id = $row['id'];
        $feed_id = $row['feed_id'];
        $title = $row['title'];
        $tag = $row['tag'];
        $htmlurl = $row['htmlurl'];
        $flag_dedupe = $row['flag_dedupe'];
        $flag_sanitize = $row['flag_sanitize'];
        if ($flag_dedupe == '1') {
            $dedupe_icon = "dedupe_on.png";
            $flag_dedupe_next = 0;
        } else {
            $dedupe_icon = "dedupe_off.png";
            $flag_dedupe_next = 1;
            $flag_dedupe = 0;
        }
        if ($flag_sanitize == '1') {
            $sanitize_icon = "sanitize_on.png";
            $flag_sanitize_next = 0;
        } else {
            $sanitize_icon = "sanitize_off.png";
            $flag_sanitize_next = 1;
            $flag_sanitize = 0;
        }
        print ("<tr><td>$tag</td><td><a href='dashboard.php?id=$id&c=toggle&f=dedupe&s=$flag_dedupe_next'><img src='$dedupe_icon' border=0></a> <a href='dashboard.php?id=$id&c=toggle&f=sanitize&s=$flag_sanitize_next'><img src='$sanitize_icon' border=0></a> <b><a href='http://rsshose.com/feed.php?feed_id=$feed_id&flag_sanitize=$flag_sanitize&flag_dedupe=$flag_dedupe&hours=24'>$title</a></b></td></tr>\n");
    }
    print ("</table>\n");
}

print("</td></tr></Table>");

?>
