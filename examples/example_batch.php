<?php

//EXAMPLE Utilisation d'appels en parallèle

error_reporting(-1);
ini_set('display_errors', 'On');

require '../autoload.php';

$Envelope = new \Iris\Envelope; // l'objet qui encapsule les appels aux fonctions curl_Envelope*
$Envelope->setOption(\Iris\Envelope::MAXCONNECTS, 2); //le nombre maximale d'appels en parallèle que l'on veut faire

$batch = new \Iris\Batch($Envelope); //le gestionnaire des appels en parallèles

// en cas d'echec
$onFailure = function ($res, $curl) {
    echo '<pre>', $curl->getErrorCode(), ' : ', $curl->getErrorMessage(), '</pre>', PHP_EOL;
};

//en cas de succes on a defini $curl2 avant car il va être utiliser par ce callback ;)
$curl2 = new \Iris\Message;
$onSuccess = function ($res, $curl) use ($curl2, $batch, $onFailure) {
    var_dump($curl->getInfo());
    //$curl4 n'est ajoute a batch QUE SI ET SEULEMENT SI curl2 est appele avec succes!!
    if ($curl === $curl2) {
        $curl4 = new \Iris\Message;
        $curl4->setOption([
            CURLOPT_URL => 'http://www.carpediem.fr',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $curl4->addListener(\Iris\Message::EVENT_ON_SUCCESS, function ($res, $curl) {
            echo 'la page '.$curl->getOption(CURLOPT_URL)
                .' fait : '.strlen($curl->getResponse())
                .' bytes';
        });
        $curl4->addListener(\Iris\Message::EVENT_ON_ERROR, $onFailure);
        $batch->add($curl4);
    }
};

$curl1 = new \Iris\Message;
$curl1
    ->setOption([
        CURLOPT_URL => 'http://www.eurolive.com',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\Iris\Message::EVENT_ON_ERROR, $onFailure);

$curl2
    ->setOption([
        CURLOPT_URL => 'http://www.yesmessenger.com',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\Iris\Message::EVENT_ON_ERROR, $onFailure);

//cette appel va declencher l'evenement \Iris\Message::EVENT_ON_ERROR car l'url est mauvaise
$curl3 = new \Iris\Message;
$curl3
    ->setOption([
        CURLOPT_URL => 'http://fqsdfqsdf',
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY => true,
    ])
    ->addListener(\Iris\Message::EVENT_ON_SUCCESS, $onSuccess)
    ->addListener(\Iris\Message::EVENT_ON_ERROR, $onFailure);

$pool = new \Iris\MessageQueue; //conteneur d'Objet \Iris\Message
$pool->enqueue($curl1);
$pool->enqueue($curl3);

$batch
    ->add($curl2) //on peut ajouter a batch un objet  \Iris\Message
    ->addPool($pool) //on peut soumettre a batch un pool d'objet \Iris\Message
    ->execute();
