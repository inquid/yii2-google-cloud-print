<p align="center">
    <a href="http://www.yiiframework.com/" target="_blank">
        <img src="http://static.yiiframework.com/files/logo/yii.png" width="400" alt="Yii Framework" />
    </a>
</p>

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=contact@inquid.co&item_name=Yii2+extensions+support&item_number=22+Campaign&amount=5%2e00&currency_code=USD)


Yii2 Google Cloud Print
=======================
Print documents and views using Google Cloud Print service.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist inquid/yii2-inquid-google-print "*"
```

or add

```
"inquid/yii2-inquid-google-print": "*"
```

to the require section of your `composer.json` file.


Configuration
-----
        //Inquid Components
        'GoogleCloudPrint' => [
            'class' => 'inquid\googlecloudprint\GoogleCloudPrint',
            'refresh_token' => '...',
            'client_id' => '...',
            'client_secret' => '...',
            'grant_type' => 'refresh_token',
            'default_printer_id' => '__google__docs' //Use your printer id or this to print it to Google drive file
        ],

Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
/* Get printers as an array */
$printers = Yii::$app->GoogleCloudPrint->getPrinters();
/* Render a GridView with the printers  */
echo Yii::$app->GoogleCloudPrint->renderPrinters();
/* print html code */
Yii::$app->GoogleCloudPrint->sendPrintToPrinterContent("__google__docs", "job3", "<b>boba</b>", "text/html");
/* If default printer is not sent, system will take the default printer in the configuration file */
$result = Yii::$app->GoogleCloudPrint->sendPrintToPrinterContent("", "job3", "<b>boba</b>", "text/html");
/* Check if print works */
if ($result['status']) {
    echo "it works!";
}
if(isset($result->errorMessage))
    echo $result->errorMessage;
```

SUPPORT
-----
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=contact@inquid.co&item_name=Yii2+extensions+support&item_number=22+Campaign&amount=5%2e00&currency_code=USD)
