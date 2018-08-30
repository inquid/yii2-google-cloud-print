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
                    'refresh_token' => '', // '' - if don't use  refresh token offline, then this field must be empty 
                    'client_id' => '...',
                    'client_secret' => '...',
                    'grant_type' => 'refresh_token',
                    'redirect_uri' =>'http://yourdomain.com/googlecloudauth', // http://yourdomain.com/?r=googlecloudauth
                    'default_printer_id' => '__google__docs'
         ],
         
         //Inquid controllerMap
         'controllerMap' => [
                 'googlecloudauth' => 'inquid\googlecloudprint\GooglecloudauthController',
          ],
        

Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
    /* Check Refresh Token */
    Yii::$app->GoogleCloudPrint->checkRefreshTokenSession(Yii::$app->request->getAbsoluteUrl());

    /* Get printers as an array */
   $printers = Yii::$app->GoogleCloudPrint->getPrinters();

    /* Render a GridView with the printers  */
   echo Yii::$app->GoogleCloudPrint->renderPrinters();


    /* print html code */
    //Yii::$app->GoogleCloudPrint->sendPrintToPrinterContent("__google__docs", "job3", "<b>boba</b>", "text/html");

    /* If default printer is not sent, system will take the default printer in the configuration file */
    //$result = Yii::$app->GoogleCloudPrint->sendPrintToPrinterContent("", "job3", "<b>boba</b>", "text/html");

    /* Send pdf file to print */
   $result = Yii::$app->GoogleCloudPrint->sendFileToPrinter("", "Simple pdf", Yii::getAlias('@vendor').'/inquid/yii2-inquid-google-print/simple.pdf', 'application/pdf');

    /* Check if print works */
    if ($result['status']) {
            echo "it works!";
    }

    if(isset($result->errorMessage))
        echo $result->errorMessage;
```
LOGOUT LINK 
-----
if use online token you can logout by URL: 
 http://yourdomain.com/r=googlecloudauth/remove or pretty URL http://yourdomain.com/googlecloudauth/remove
 

SUPPORT
-----
[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=contact@inquid.co&item_name=Yii2+extensions+support&item_number=22+Campaign&amount=5%2e00&currency_code=USD)
