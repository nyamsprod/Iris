<?php

//EXAMPLE Utilisation d'appels en parallèle

error_reporting(-1);
ini_set('display_errors', 'On');

require '../autoload.php';

$Envelope = new \P\Iris\Envelope; // l'objet qui encapsule les appels aux fonctions curl_Envelope*
$Envelope->setOption(\P\Iris\Envelope::MAXCONNECTS, 2); //le nombre maximale d'appels en parallèle que l'on veut faire

$batch = new \P\Iris\Batch($Envelope); //le gestionnaire des appels en parallèles

// en cas d'echec
$onFailure = function ($res, $curl) {
    echo '<pre>', $curl->getErrorCode(), ' : ', $curl->getErrorMessage(), '</pre>', PHP_EOL;
};

//en cas de succes on a defini $curl2 avant car il va être utiliser par ce callback ;)
$curl2 = new \P\Iris\Message;
$onSuccess = function ($res, $curl) use ($curl2, $batch, $onFailure) {
    var_dump($curl->getInfo());
    //$curl4 n'est ajoute a batch QUE SI ET SEULEMENT SI curl2 est appele avec succes!!
    if ($curl === $curl2) {
        $curl4 = new \P\Iris\Message;
        $curl4->setOption([
            CURLOPT_URL => 'http://www.thephpleague.com',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $curl4->addListener(\P\Iris\Message::EVENT_ON_SUCCESS, function ($res, $curl) {
            echo 'la page '.$curl->getOption(CURLOPT_URL)
                .' fait : '.strlen($curl->getResponse())
                .' bytes';
        });
        $curl4->addListener(\P\Iris\Message::EVENT_ON_FAIL, $onFailure);
        $batch->addOne($curl4);
    }
};

$curl1 = new \P\Iris\Message;
$curl1
    ->setOption([
        CURLOPT_URL => 'http://www.reddit.com',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\P\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\P\Iris\Message::EVENT_ON_FAIL, $onFailure);

$curl2
    ->setOption([
        CURLOPT_URL => 'http://www.phpdeveloper.org',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\P\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\P\Iris\Message::EVENT_ON_FAIL, $onFailure);

//cette appel va declencher l'evenement \P\Iris\Message::EVENT_ON_FAIL car l'url est mauvaise
$curl3 = new \P\Iris\Message;
$curl3
    ->setOption([
        CURLOPT_URL => 'http://fqsdfqsdf',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\P\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\P\Iris\Message::EVENT_ON_FAIL, $onFailure);

$batch
    ->addOne($curl2) //on peut ajouter a batch un objet  \P\Iris\Message
    ->addMany([$curl1, $curl3]) //on peut soumettre a batch un pool d'objet \P\Iris\Message
    ->execute();
