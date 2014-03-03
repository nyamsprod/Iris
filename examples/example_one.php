<?php

//EXAMPLE SIMPLE Utilisation d'une connexion cURL basique

error_reporting(-1);
ini_set('display_errors', 'On');

require '../autoload.php';

$curl = new \Iris\Message;

$curl->get('http://www.carpediem.fr');
var_dump($curl->getInfo(CURLINFO_HTTP_CODE));
echo $curl->getResponse();
// a la fin de execute;
// on peut très bien re-utiliser la connection cURL en changeant les setOption ou an faisant un reset()
// pour fermer la connexion cURL soit on fait $curl->close() our $curl = null;
