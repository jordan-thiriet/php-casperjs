php-casperjs
============

php-casperjs is a library PHP for wrapper CasperJs and write easily functional testing.

Installation
------------

Before using php-casperjs, you need to install both library:

1 - **PhantomJS** http://phantomjs.org/download.html

2 - **CasperJS** http://casperjs.org/installation.html

Usage
-----

```php
<?php

$casper = new \CasperJS\Casper(1280,800,"script/test.js");
$casper->start("http://spallian.com/");
$casper->click('//*[@id="menu-item-7376"]/a');
$casper->forStart(1,21);
$casper->setValue('//*[@id="post-7148"]/div/div/div[{i}]/div/div[2]/h5','username');
$casper->getValue('username');
$casper->captureSelector('//*[@id="post-7148"]/div/div/div[{i}]/div/div[1]/img','capture/{username}.jpg');
$casper->forEnd();
$casper->run();
print_r($casper->getOutput());
        
```
