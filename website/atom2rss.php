<?
// Set the MIME type to application/xml
header("Content-type: application/xml");

$atom_file = tempnam("/tmp", "ATOM");
$xsl_file = "atom2rss.xsl";

$url = urldecode($_GET['url']);

$crl = curl_init();
curl_setopt($crl, CURLOPT_URL, $url);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($crl, CURLOPT_MAXCONNECTS, 30);
curl_setopt($crl, CURLOPT_USERAGENT, "");
$atom_xml = curl_exec($crl);
curl_close($crl);

$handle = fopen($atom_file, "w");
fwrite($handle, $atom_xml);
fclose($handle);

$xml = new DOMDocument;
$xml->load($atom_file);

$xsl = new DOMDocument;
$xsl->load($xsl_file);

// Configure the transformer
$proc = new XSLTProcessor;
$proc->registerPHPFunctions();
$proc->importStyleSheet($xsl); // attach the xsl rules

echo $proc->transformToXML($xml);

unlink($atom_file);

?>
