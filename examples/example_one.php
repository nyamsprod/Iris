<?php

//Using P\Iris to issue a simple cURL call

error_reporting(-1);
ini_set('display_errors', 'On');

use P\Iris\Message;

require '../autoload.php';

// Callback when the cURL request failed
$onFailure = function ($res, $curl) {
    echo '<pre>', $curl->getErrorCode(), ' : ', $curl->getErrorMessage(), '</pre>', PHP_EOL;
};

// Callback when the cURL request succeed
$onSuccess = function ($res, $curl) {
    echo '<pre>The page '.$curl->getInfo(CURLINFO_EFFECTIVE_URL)
    .' make : '.strlen($curl->getResponse())
    .' bytes</pre>';
};

// Another callback when the cURL request succeed
$onSuccess2 = function ($res, $curl) {
    var_dump($curl->getInfo());
};

$curl = new Message;
$curl
    ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)')
    ->addListener(Message::EVENT_ON_FAIL, $onFailure)
    ->addListener(Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(Message::EVENT_ON_SUCCESS, $onSuccess2) //you can registerd as many callback as you wish
    ->get('http://www.reddit.com/r/PHP');

$curl
    ->reset() //we reset the cURL handler
    ->removeListener(Message::EVENT_ON_SUCCESS, $onSuccess2) //I just removed One listener
    ->get('http://www.php.net/');
