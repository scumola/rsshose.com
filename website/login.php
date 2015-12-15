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

$vhash_submit = $_GET['vhash'];
if (isset($vhash_submit)) {
    print ("Found vhash<br>\n");
    $conn=mysql_connect($mysql_host,$mysql_user,$mysql_passwd);
    if(!mysql_select_db("rsshose",$conn)){
        $error_string .= "ERROR: can't connect to the DB\n";
        print ("$error_string");
        exit(1);
    }
    $result = mysql_query("select * from users where verify_hash = '$vhash_submit'");
    if (mysql_num_rows($result) > 0) {
        # user hash found
        print ("Found user account in DB<br>\n");
        while ($row = mysql_fetch_assoc($result)) {
            $vhash = $row['verify_hash'];
            $vdate = $row['verified'];
            $email = $row['email'];
        }
        setcookie("email", $email, time()+60*60*24*30*12*20); #20 years
        setcookie("vhash", $vhash, time()+60*60*24*30*12*20); #20 years
        setcookie("vdate", $vdate, time()+60*60*24*30*12*20); #20 years
    } else {
        # no hash found
        clear_cookies();
        header('Location: http://rsshose.com/');
    }
} else {
    clear_cookies();
    header('Location: http://rsshose.com/');
}

header('Location: http://rsshose.com/dashboard.php');

?>
