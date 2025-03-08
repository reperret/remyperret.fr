<?php

/***************************************
 * download-vcard.php
 ***************************************/

header('Content-Type: text/x-vcard; charset=utf-8');
header('Content-Disposition: inline; filename="remyperret.vcf"');

// Construction de la vCard
$vcard  = "BEGIN:VCARD\r\n";
$vcard .= "VERSION:3.0\r\n";
$vcard .= "FN:Rémy PERRET\r\n";
$vcard .= "N:PERRET;Rémy;;;\r\n";
$vcard .= "TEL;TYPE=mobile,voice:+33631023304\r\n";
$vcard .= "EMAIL:reperret@gmail.com\r\n";
// PHOTO en URL absolue (pas de base64)
$vcard .= "PHOTO;TYPE=JPEG;VALUE=URL:https://www.remyperret.fr/avatar.jpg\r\n";
$vcard .= "END:VCARD\r\n";

// Envoi du contenu
echo $vcard;
exit;