<?php  

use EdwinJuarez\Mh\Signing\Algorithm;
use EdwinJuarez\Mh\Signing\XadesEpesSigner;

require __DIR__ . '/../vendor/autoload.php';

$certPath = __DIR__ . "/cert/certificate.p12";
$certPassword = "12345";
$unsignedXmlPath = __DIR__ . "/xml_unsigned/sample_FacturaElectronica.xml";

$signedFilename = basename($unsignedXmlPath); 
$signedXmlPath = __DIR__ . "/xml_signed/{$signedFilename}";

$signer = new XadesEpesSigner(Algorithm::sha256());
$signedXml = $signer->sign($certPath, $certPassword, $unsignedXmlPath);

file_put_contents($signedXmlPath, $signedXml);

echo "OK: signed XML saved to $signedXmlPath", PHP_EOL;
exit();
