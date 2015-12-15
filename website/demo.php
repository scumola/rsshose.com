<?php
require 'php-readability/lib/Readability.inc.php';

$html = file_get_contents($_GET['src']);

$r = new Readability($html);
$rData = $r->getContent();

echo "<h1>".$rData['title']."</h1>";
echo $rData['content'];
?>
