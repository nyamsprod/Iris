<?php

//Using P\Iris to issue paralles cURL requests

error_reporting(-1);
ini_set('display_errors', 'On');

use P\Iris\Message;
use P\Iris\Envelope;
use P\Iris\Batch;

require '../autoload.php';

//The envelope object that defined the parallel calls
$envelope = new Envelope;
$envelope->setOption(Envelope::MAXCONNECTS, 2); //Maximal parallels calls

$batch = new Batch($envelope); //The Object that will process the calls

// Callback when the cURL request failed
$onFailure = function ($res, $curl) {
    echo '<pre>', $curl->getErrorCode(), ' : ', $curl->getErrorMessage(), '</pre>', PHP_EOL;
};

$curl1 = new Message;
$curl1
    ->head('http://www.phpdeveloper.org', null, false)
    ->addListener(Message::EVENT_ON_FAIL, $onFailure)
    ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)');

//  Callback when the cURL request succeed
$onSuccess = function ($res, $curl) use ($curl1, $batch, $onFailure) {
    var_dump($curl->getInfo());
    //adding a new \Iris\Message to \Iris\Batch using the callback
    if ($curl === $curl1) {
        $curl4 = new Message;
        $curl4
            ->get('http://www.thephpleague.com', null, false)
            ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)')
            ->setOption(CURLOPT_FOLLOWLOCATION, true)
            ->addListener(Message::EVENT_ON_SUCCESS, function ($res, $curl) {
                echo '<pre>The page '.$curl->getInfo(CURLINFO_EFFECTIVE_URL)
                .' make : '.strlen($curl->getResponse())
                .' bytes</pre>';
            })
            ->addListener(Message::EVENT_ON_FAIL, $onFailure);
        $batch->addOne($curl4); //we are adding the new Curl
    }
};

$curl1->addListener(Message::EVENT_ON_SUCCESS, $onSuccess);

$curl2 = new Message;
$curl2
    ->head('http://www.reddit.com', null, false)
    ->addListener(Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(Message::EVENT_ON_FAIL, $onFailure)
    ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)');

//This call will trigger a Message::EVENT_ON_FAIL because of the bad URL
$curl3 = new Message;
$curl3
    ->get('http://fqsdfqsdf', null, false)
    ->addListener(Message::EVENT_ON_SUCCESS, $onSuccess)
    ->setUserAgent('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)')
    ->addListener(Message::EVENT_ON_FAIL, $onFailure);

$batch
    ->addMany([$curl1, $curl2, $curl3]) //we had all the \Iris\Message at once using an array
    ->execute();
