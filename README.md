# Iris

`Iris` is a simple Wrapper around `cURL` functions. the main objective of [Iris] is to ease `cURL` usability by using a OOP approach to `cURL`.

This package is compliant with [PSR-1], [PSR-2], and [PSR-4].

[Iris]: http://fr.wikipedia.org/wiki/Iris_%28mythologie%29
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

`Iris` is loosely based around [phpmulticurl](https://github.com/dypa/phpmulticurl) . **But I would strongly advice anyone to use [Guzzle](http://docs.guzzlephp.org/en/latest/) when possible**.


## Requirements

You need **PHP >= 5.3.0** and the `cURL` extension to use `Iris` but the latest stable version of PHP is recommended.

## Usage

Remember ... [Guzzle](http://docs.guzzlephp.org/en/latest/) is your friend, but if you insist!!

* [Sending a simple request](examples/example_one.php)
* [Sending a batch of request](examples/example_batch.php)

## Documentation

### Iris\Message

#### Instantiation

to perform simple requests you need to instantiate the `Iris\Message` object.

```php
$request = new \Iris\Message;
$request->setUserAgent('My beautiful user agent/1.0');
```

Before anything else you may want to specify your own user agent. By defaut the library set its own user agent. You can override this behaviour by setting you own user agent using the `Message::setUserAgent` method. Once set, the user agent won't change even if you reset the `Iris\Message` object.


At any given time you can reset the current cURL handler allowing you to perform multiple call using the same `Iris\Message` object. Once resetted, the object will loose any reference to the old request, except for the user agent.

```php
$request->reset();
```

#### Adding Options

`cURL` calls are all about options, `Iris\Message` help you set them using the `setOption` method.

the `setOption` method can accept an `array` as a single value or a cURL options and its value.

```php
$request->setOption(CURLOPT_NOBODY, true);
//or
$request->setOption([CURLOPT_NOBODY => true, CURLOPT_HEADER_OUT => true]);
```

The options can be changed and altered at any given times before performing the request as their are applied only when the request is performed.

#### Performing a request

To perform a request you need to call the `Iris\Message::execute` method.

```php
$request = new \Iris\Message;
$request->setUserAgent('My beautiful user agent/1.0');
$request->setOption(CURLOPT_URL, 'http://www.example.com');
$request->setOption(CURLOPT_RETURNTRANSFER, true);
$request->execute();
$request->getResponse();
```

You'll get access to:
* the response by using the `Message::getResponse` method.
* the `cURL` request information using the `Message::getInfo` method. This method by default return an array of all available information. If you are only interested in one value then use its CURLOPT_* index;
* the `cURL` error message using `Message::getErrorCode` and Message::getErrorMessage` methods;

### Using Events

Another way to deal with the result of an cURL request is by using the event listener. The `\Iris\Message` comes with a simple event listener mechanism. You can register a callback function that will be executed on request success or on request error like explain below:

```php
$request = new \Iris\Message;
$request->setUserAgent('My beautiful user agent/1.0');
$request->setOption(CURLOPT_URL, 'http://www.example.com');
$request->setOption(CURLOPT_RETURNTRANSFER, true);
$request->addListener(\Iris\Message::EVENT_ON_SUCCESS, function ($res, $curl) {
 echo $curl->getInfo(CURLOPTINFO_HTTP_CODE); //should be 200;
});
$request->addListener(\Iris\Message::EVENT_ON_ERROR, function ($res, $curl) {
 echo $curl->getErrorCode().': '. $curl->getErrorMessage();
});
$request->execute();
```
Depending on the result of the `cURL` request one of the event will be called.


#### Simple Requests (GET, POST, PUT, DELETE, HEAD)

The class comes with specialized method to perform usual calls used in REST API:

* `Message::get` to issue a `GET` request;
* `Message::post` to issue a `POST` request;
* `Message::put` to issue a `PUT` request;
* `Message::delete` to issue a `DELETE` request;
* `Message::head` to issue a `HEAD` request;

These methods takes up to 3 parameters:
* the first parameter is the URL you want to request;
* the second parameter is a array of data associated to the url;
* the third parameter is to indicate if the request must be delayed or not. By default, the request is not delayed and the value is `false`;

Here's a simple example:

```php

$request = new \Iris\Message;
$request->get('http://www.example.com/path/to/my/script', ['var1' => 'value2', ...]);
$request->getResponse();
```
You do not need to use the `Message::execute` method as it is called directly by the `Message::get` method. If you do not want the request to be directly issue then you have to specify it using the **third argument**.

```php

$request = new \Iris\Message;
$request->get('http://www.example.com/path/to/my/script', ['var1' => 'value2', ...], true);
```
The request is registered but not execute, **you will need the `Message::execute`** to perform the request.

### Iris\Envelope

This class is responsible for wrapping `curl_multi_*` function. **As it is you could use it to perform parallel calls using `cURL` but really don't** there's a simpler way using `Iris\Batch`.

The sole purpose of this function is to set:

* the number of parallel request you want to perform. By default This number is `10` but you can change the number using the setOption like with the `Iris\Message` class  
**Of note:**  
    * Settings options was not possible **before PHP 5.5** 
    * The class comes with a constant called `Iris\Envelope::MAX_CONNECTS` the value of this value matches the value of the PHP5.5+ constant `CURLOPT_MAX_CONNECTS`. 

* to set the `selectTimeout` and the `execTimeout` used by `cURL` to perform the requests. These values are can be set and get using simple getter and setter.   
    * The `selectTimeout` must be a float;
    * The `execTimeout`  must be a integer;

### Iris\Batch

#### Instantiation

This class was build to simplify parallel calls. To instantiate this class you must give it a object of type `Iris\Enveloppe`

```php
<?php
$envelope = new Iris\Envelope;
$envelope->setTimeout(10.00);
$evenlope->setExecTimeout(100);
$envelope->setOption(Iris\Envelope::MAX_CONNECTS, 3);
$batch = new \Iris\Batch($enveloppe);
```

#### Adding Request

Once the `Iris\Batch` is instantiate you just need to provide the `cURL` requests to perform in parallel.

To do so you can use the `Iris\Batch::addOne` function that will had a single `Iris\Message` method  the object or the  `Iris\Batch::addMany` that will accept an `array` or any `Traversable` object that contained only `Iris\Message` objects.

```php
$batch->addOne($request);
//or
$batch->addMany([$request1, $request2]);
```

#### Performing the requests

```php
$batch->execute();
```

Once you have added all the `Iris\Message` objects you can safely perform you parallel requests using the `Batch::execute` method.
**When using the `Iris\Batch` it is strongly advice to use Iris\Message event listeners to process each cURL request individually.**



## Contribution

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [bertrand andres](https://youtube.com/user/BertrandAd)

