# AstroPay cashoutcard API integration

Read full documentation at https://developers.astropaycard.com/


Example
<code>
<?php 
include __DIR__ . '/../vendor/autoload.php';

$login = 'merchant_x_login';
$trans_key = 'merchant_x_trans_key';
$secret = 'merchant_secret';


$api = new \Astropay\CashoutCard(Astropay\Constants::ENV_SANDBOX);
$api->setCredentials($login, $trans_key, $secret);

$api->setAmount(100);
$api->setCurrency('USD');
$api->setEmail('test@astropaycard.com');
$api->setName('Test recipient');
$api->setDocument('8976fsdf1234');

if($api->sendCard()){
    echo urldecode($api->getMessage());    
    echo '<br/>'.$api->getAuthCode();
}
else{
    echo urldecode($api->getMessage());
}

</code>
