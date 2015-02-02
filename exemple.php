<?php
/**
 * Exemple
 */

date_default_timezone_set('Europe/Paris');
require 'Casperjs/Casper.php';

$casper = new \CasperJS\Casper(1280,800,"script/test.js");
$casper->start("http://spallian.com/");
$casper->click('//*[@id="menu-item-7458"]/a');
$casper->sendKeys('//*[@id="wpcf7-f7743-p7456-o1"]/form/p[1]/span/input','test');
$casper->captureSelector('/html/body','capture/test.png');
//$casper->click('//*[@id="menu-item-7376"]/a');
/*$casper->forStart(1,21);
$casper->setValue('//*[@id="post-7148"]/div/div/div[{i}]/div/div[2]/h5','username');
$casper->getValue('username');
$casper->captureSelector('//*[@id="post-7148"]/div/div/div[{i}]/div/div[1]/img','capture/{i}.jpg');
$casper->forEnd();*/
$casper->run();
print_r($casper->getOutput());