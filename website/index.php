<style type="text/css">
body {
      font-size: 14px;
      font-family: verdana, "DajaVu Sans", sans-serif;
}
div.notice {
      -moz-border-radius: 4px;
      border-radius: 4px;
      background-color: #EEBBBB;
      border: 1px solid;
      color: #000000;
      font-size: 14px;
      font-family: verdana, "DajaVu Sans", sans-serif;
      margin: 1px;
      padding: 5px;
}
div.fancy {
      -moz-border-radius: 4px;
      border-radius: 5px;
      background-color: #AAAAFF;
      border: 1px solid;
      color: #000000;
      font-size: 16px;
      font-family: verdana, "DajaVu Sans", sans-serif;
      margin: 1px;
      padding: 5px;
}
</style>
<div class=fancy>rsshose.com</div>

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
include_once "get_rss_feed_title.php";
#error_reporting(0);

$conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
if(!mysql_select_db("rsshose",$conn)){
    $error_string .= "ERROR: can't connect to the DB\n";
    print ("$error_string");
    exit(1);
}

$email_submit = $_POST['email'];
if (isset($email_submit)) { # user is signing in
    $result = mysql_query("select * from users where email = '$email_submit'");
    if (mysql_num_rows($result) > 0) {
        # user already exists, pull his hash out of the DB
        while ($row = mysql_fetch_assoc($result)) {
            $vhash = $row['verify_hash'];
        }
    } else {
        # new user
        mysql_query("insert into users values ('','$email_submit','',now())");
        $result = mysql_query("select * from users where email = '$email_submit'"); # we just inserted this one line ago
        while ($row = mysql_fetch_assoc($result)) {
            $vdate = $row['verified'];
        }
        $user_string = $email_submit.$vdate.$salt;
        $vhash = hash('sha256',$user_string);
        mysql_query("update users set verify_hash = '$vhash' where email = '$email_submit'");
    }

    $headers = 'From: noreply@rsshose.com' . "\r\n" .
        'Reply-To: noreply@rsshose.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    $msg_body = "Welcome to rsshose.com\n".
            "Your dashboard url is: http://rsshose.com/login.php?vhash=$vhash";
    mail($email_submit, "[rsshose.com] Your dashboard url", $msg_body, $headers);
    print ("<div class=fancy>Check your email for your login url.  If you don't see it in a few minutes, check your spam folder.</div><br>\n");
}

$feed_title = "";
$c = $_GET['c'];
if ($c == 'oneshot') {
    $xmlurl = addslashes($_GET['xmlurl']);

    $result = mysql_query("select * from feeds where xmlurl = '$xmlurl'");
    if (mysql_num_rows($result) == 0) {
        # not in the DB yet
        mysql_query("insert into feeds values ('','rss',NULL,'$xmlurl','',NULL,NULL,'60','')");
	list ($feed_title, $htmlurl) = get_rss_feed_title_and_htmlurl($xmlurl);
	$feed_title = addslashes($feed_title);
	$htmlurl = htmlspecialchars($htmlurl);
        mysql_query("update feeds set title = '$feed_title', htmlurl = '$htmlurl' where xmlurl = '$xmlurl'");
	
    }
    $result = mysql_query("select * from feeds where xmlurl = '$xmlurl'");
    while ($row = mysql_fetch_assoc($result)) {
        $feed_id = $row['id'];
        $feed_title = $row['title'];
    }
}

$email_cookie = $_COOKIE['email'];
$vhash_cookie = $_COOKIE['vhash'];
$vdate_cookie = $_COOKIE['vdate'];
if (isset($email_cookie) and (isset($vhash_cookie) and isset($vdate_cookie))) {
    header("Location: http://rsshose.com/dashboard.php");
} else {
    print ("<br>");
    print ("<h2><b>Want to clean up your Google Reader RSS feeds?</b></h2>");
#    print ("<br>");
#    print ("<div class=notice>NOTICE: rsshose.com is still in beta, so you may experience a little noise in your feed content depending on the feed.  This is a known issue and is being worked on.</div>\n");
    print ("<br>");
    print ("<li> See the full article instead of just a snippet<br>");
    print ("<li> Remove duplicate articles across different feeds<br>");
    print ("<li> Remove ads and other cruft<br>");
    print ("<li> Make your RSS feeds cleaner<br>");
    print ("<br>");
    print ("<form method=get action=/>\n");
    print ("    Filter a single feed without signing up: <input type=entry name=xmlurl value='url to rss feed' size=50><input type=submit value='Sanitize!'><br>\n");
    print ("<input type=hidden name=c value=oneshot>");
    print ("</form>\n");


if ($c == 'oneshot') {
	$feed_uri = "http://rsshose.com/feed.php?feed_id=$feed_id&flag_sanitize=1&flag_dedupe=1&hours=24";
	$feed_uri = urlencode($feed_uri);
	print ("<div class=fancy>");
	print ("Your new feed url is here: <a href='http://rsshose.com/feed.php?feed_id=$feed_id&flag_sanitize=1&flag_dedupe=1&hours=24'>$feed_title</a><br>");
	print ("<i>NOTE: it may take a few minutes before you see any content</i><br>\n");
	print ("<a target=_blank href='http://www.google.com/reader/view/feed/$feed_uri'>Add to Google Reader</a><br>");
	print ("<a target=_blank href='http://www.netvibes.com/subscribe.php?url=$feed_uri'>Add to NetVibes</a><br>");
	print ("</div><br>\n");
}


    print ("Or try out a few starter feeds: ");
	print ("<a href='http://rsshose.com/feed.php?feed_id=166&flag_sanitize=1&flag_dedupe=0&hours=24'>Gizmodo</a> ");
	print ("<a href='http://rsshose.com/feed.php?feed_id=167&flag_sanitize=1&flag_dedupe=0&hours=24'>Lifehacker</a> ");
	print ("<a href='http://rsshose.com/feed.php?feed_id=173&flag_sanitize=1&flag_dedupe=0&hours=24'>Hacker News</a> ");
	print ("<a href='http://rsshose.com/feed.php?feed_id=168&flag_sanitize=1&flag_dedupe=0&hours=24'>The Verge</a> ");
    print ("<br>");
    print ("<br>");
    print ("<form method=post action=/>\n");
    print ("    Or sign up and filter ALL of your feeds: <input type=entry name=email value='your email address' size=50><input type=submit value='Sign in or Sign up.  FREE!'><br>\n");
    print ("</form>\n");
    print ("<br>");
    
}
# <!-- Twitter Button -->
print ("<a href='https://twitter.com/rsshose' class='twitter-follow-button' data-show-count='false'>Follow @rsshose</a>");
print ("<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src='//platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document,'script','twitter-wjs');</script><br>");

?>
