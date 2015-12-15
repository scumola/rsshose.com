<?php
function clear_cookies() {
    setcookie("email", "");
    setcookie("vhash", "");
    setcookie("vdate", "");
}

function verify_user () {
    global $salt;
    $email_cookie = $_COOKIE['email'];
    $vhash_cookie = $_COOKIE['vhash'];
    $vdate_cookie = $_COOKIE['vdate'];
    $user_string = $email_cookie.$vdate_cookie.$salt;
    $vhash = hash('sha256',$user_string);
    if (strcmp($vhash,$vhash_cookie)) {
        clear_cookies();
        header("Location: http://rsshose.com/");
    }
}

function show_gravitar_img() {
    $email_cookie = $_COOKIE['email'];
    $hash = md5(strtolower(trim($email_cookie)));
    print ("<img src='http://www.gravatar.com/avatar/$hash?d=mm&s=60'><br>\n");
    print ("<a href='http://gravatar.com' target=_blank>gravatar</a><br>\n");
}
?>