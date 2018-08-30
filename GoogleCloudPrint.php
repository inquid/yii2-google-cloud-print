<?php
/*
PHP implementation of Google Cloud Print
Author, Yasir Siddiqui

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace inquid\googlecloudprint;

use Exception;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\Session;


/**
 * Class GoogleCloudPrint
 * @package app\components\GooglePrinting
 *
 * @property mixed $accessTokenByRefreshToken
 * @property array $printers
 * @property mixed $authToken
 * @property mixed $defaultPrinter
 */
class GoogleCloudPrint extends Component
{

    const PRINTERS_SEARCH_URL = "https://www.google.com/cloudprint/search";
    const PRINT_URL = "https://www.google.com/cloudprint/submit";
    const JOBS_URL = "https://www.google.com/cloudprint/jobs";

    const AUTHORIZATION_URL = "https://accounts.google.com/o/oauth2/auth";
    const ACCESSTOKEN_URL = "https://accounts.google.com/o/oauth2/token";
    const REFRESHTOKEN_URL = "https://www.googleapis.com/oauth2/v3/token";
    const SCOPE_URL = "https://www.googleapis.com/auth/cloudprint";

    private $authtoken;
    private $session;
    public $redirect_uri;

    public $refresh_token;
    public $client_id;
    public $client_secret;
    public $grant_type;
    //Optional
    public $default_printer_id;

    /**
     * Function __construct
     * Set private members varials to blank
     */
    public function init()
    {
        parent::init();
        $this->authtoken = "";
        $this->session = new Session;

    }

    /**
     * Function setAuthToken
     *
     * Set auth tokem
     * @param string $token token to set
     */
    public function setAuthToken()
    {
        $this->authtoken = $this->getAccessTokenByRefreshToken();
    }

    public function setAuthTokenByResponce($_access_token){
        $this->authtoken = $_access_token;
    }

    /**
     * Function getAuthToken
     *
     * Get auth tokem
     * return auth tokem
     */
    public function getAuthToken()
    {
        return $this->authtoken;
    }


    public function getRefreshToken($code)
    {
        $authConfig = array(
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' 	=> $this->redirect_uri,
            "grant_type"    => "authorization_code"
        );

       return $this->getAccessToken(self::ACCESSTOKEN_URL,  $authConfig);
    }

    public function test(){
       echo  $this->refresh_token?$this->refresh_token:$this->getRefreshTokenSession();
    }
    /**
     * Function getAccessTokenByRefreshToken
     *
     * Gets access token by making http request
     *
     * @param $url string to post data to
     *
     * @param $post_fields array fileds
     *
     * return access tokem
     * @return mixed
     */

    public function getAccessTokenByRefreshToken()
    {
        $refreshTokenConfig = array(
            'refresh_token' => $this->refresh_token?$this->refresh_token:$this->getRefreshTokenSession(),
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type
        );
        return $this->getAccessToken(self::REFRESHTOKEN_URL, $refreshTokenConfig)->access_token;
    }


    /**
     * Function getAccessToken
     *
     * Makes Http request call
     *
     * @param $url string to post data to
     * @return mixed
     * @internal param array $post_fields fileds array
     *
     * return http response
     */
    public function getAccessToken($url, $config)
    {

        $client = new Client(['baseUrl' => $url,
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ],
        ]);
        $response = $client->createRequest()
            ->setMethod('POST')
            ->addHeaders(['Content-Type' => 'application/json'])
            ->setContent(json_encode($config))
            ->send();
        return json_decode($response->content);
    }


    public function getAuthUrl(){
        $redirectConfig = array(
            'client_id' => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'scope'         => self::SCOPE_URL,
        );
        return self::AUTHORIZATION_URL."?".http_build_query($redirectConfig);
    }

    /**
     * Returns the default printer if set
     * @return mixed
     */
    public function getDefaultPrinter()
    {
        if ($this->default_printer_id != null) {
            return $this->default_printer_id;
        }
        return null;
    }

    /**
     * Function getPrinters
     *
     * Get all the printers added by user on Google Cloud Print.
     * Follow this link https://support.google.com/cloudprint/answer/1686197 in order to know how to add printers
     * to Google Cloud Print service.
     */
    public function getPrinters()
    {
        // Check if we have auth token
        if (empty($this->authtoken)) {
            $this->setAuthToken();
        }

        // Prepare auth headers with auth token
        $authheaders = array(
            "Authorization: Bearer " . $this->authtoken
        );

        $client = new Client(['baseUrl' => self::PRINTERS_SEARCH_URL,
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ],
        ]);

        $request = $client->createRequest();
        $request->headers->set('Authorization', 'Bearer ' . $this->authtoken);
        $response = $request->send();
        $printers = json_decode($response->content);

       // Check if we have printers?
        if (is_null($printers)) {
            // We dont have printers so return balnk array
            return array();
        } else {
            // We have printers so returns printers as array
            return $this->parsePrinters($printers);
        }
    }

    public function renderPrinters()
    {
        $gridColumn = [
            [
                'label' => 'Id',
                'attribute' => 'id',
            ],
            [
                'label' => 'Name',
                'attribute' => 'name',
            ],
            [
                'label' => 'Display Name',
                'attribute' => 'displayName',
            ],
            [
                'label' => 'Owner Name',
                'attribute' => 'ownerName',
            ],
            [
                'label' => 'Connection Status',
                'attribute' => 'connectionStatus',
                'format' => 'html',
                'value' => function ($data) {
                    if ($data['connectionStatus'] == "ONLINE")
                        return Html::decode('<span style="color: #00aa00">' . $data['connectionStatus'] . '</span>');
                    return '<span style="color: #aa1700">' . $data['connectionStatus'] . '</span>';
                },
            ]
        ];
        $dataProvider = new ArrayDataProvider([
            'allModels' => $this->getPrinters(),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        return GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => $gridColumn
        ]);
    }


    /**
     * @param $printerid
     * @param $printjobtitle
     * @param $content string text to be sent
     * @param $contenttype string application/html for example
     * @return array|Error
     * @throws Exception
     */
    public function sendPrintToPrinterContent($printerid, $printjobtitle, $content, $contenttype)
    {

        // Check if we have auth token
        if (empty($this->authtoken)) {
            $this->setAuthToken();
        }
        // Check if prtinter id is passed
        if($printerid == null || $printerid == ""){
            if($this->default_printer_id == null || $this->default_printer_id == ""){
                throw new Exception("Please provide printer ID");
            }else
                $printerid = $this->default_printer_id;
        }

        // Prepare post fields for sending print
        $post_fields = array(
            'printerid' => $printerid,
            'title' => $printjobtitle,
            'contentTransferEncoding' => 'utf-8',
            'content' => $content, // encode file content as base64
            'contentType' => $contenttype
        );

        $client = new Client(['baseUrl' => self::PRINT_URL,
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ],
        ]);
        $return = $client->createRequest()
            ->setMethod('POST')
            ->addHeaders(['Authorization'=> 'Bearer '.$this->authtoken])// 'Content-Type' => 'application/json'
            ->setData($post_fields)
            ->send();
        $response = json_decode($return->content);


        // Has document been successfully sent?
        if ($response->success == "1") {
            return array('status' => true, 'errorcode' => '', 'errormessage' => "", 'id' => $response->job->id);
            //return new Error('200', $response->job->id);
        } else {
            return new Error($response->errorCode, $response->message);
        }
    }

    public function actionPrintView($view, $job = null, $printerId = null)
    {
        return $this->sendPrintToPrinterContent($printerId, $job, $view, "text/html");
    }

    /**
     * Function sendPrintToPrinter
     *
     * Sends document to the printer
     *
     * @param $printerid
     * @param $printjobtitle
     * @param $filepath
     * @param $contenttype
     * @return array|Error
     * @throws Exception
     * @internal param id $Printer $printerid    // Printer id returned by Google Cloud Print service
     *
     * @internal param Title $Job $printjobtitle // Title of the print Job e.g. Fincial reports 2012
     *
     * @internal param Path $File $filepath      // Path to the file to be send to Google Cloud Print
     *
     * @internal param Type $Content $contenttype // File content type e.g. application/pdf, image/png for pdf and images
     */
    public function sendFileToPrinter($printerid, $printjobtitle, $filepath, $contenttype)
    {
        // Check if we have auth token
        if (empty($this->authtoken)) {
            $this->setAuthToken();
        }

        // Check if prtinter id is passed
        if($printerid == null || $printerid == ""){
            if($this->default_printer_id == null || $this->default_printer_id == ""){
                throw new Exception("Please provide printer ID");
            }else
                $printerid = $this->default_printer_id;
        }

        // Open the file which needs to be print
        $handle = fopen($filepath, "rb");
        if (!$handle) {
            // Can't locate file so throw exception
            throw new Exception("Could not read the file. Please check file path.");
        }
        // Read file content
        $contents = file_get_contents($filepath);

        // Prepare post fields for sending print
        $post_fields = array(
            'printerid' => $printerid,
            'title' => $printjobtitle,
            'contentTransferEncoding' => 'base64',
            'content' => base64_encode($contents), // encode file content as base64
            'contentType' => $contenttype
        );

        $client = new Client(['baseUrl' => self::PRINT_URL,
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ],
        ]);
        $return = $client->createRequest()
            ->setMethod('POST')
            ->addHeaders(['Authorization'=> 'Bearer '.$this->authtoken])// 'Content-Type' => 'application/json'
            ->setData($post_fields)
            ->send();
        $response = json_decode($return->content);

        // Has document been successfully sent?
        if ($response->success == "1") {
            return array('status' => true, 'errorcode' => '', 'errormessage' => "", 'id' => $response->job->id);
        } else {
            return new Error($response->errorCode, $response->message);
        }
    }

    public function jobStatus($jobid)
    {
        // Check if we have auth token
        if (empty($this->authtoken)) {
            $this->setAuthToken();
        }

        $client = new Client(['baseUrl' => self::JOBS_URL,
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ],
        ]);

        $request = $client->createRequest();
        $request->headers->set('Authorization', 'Bearer ' . $this->authtoken);
        $response = $request->send();
        $responsedata = json_decode($response->content);

        foreach ($responsedata->jobs as $job)
            if ($job->id == $jobid)
                return $job->status;

        return 'UNKNOWN';
    }


    /**
     * Function parsePrinters
     *
     * Parse json response and return printers array
     *
     * @param $jsonobj // Json response object
     *
     * @return array
     */
    private function parsePrinters($jsonobj)
    {

        $printers = array();
        if (isset($jsonobj->printers)) {
            foreach ($jsonobj->printers as $gcpprinter) {
                $printers[] = array('id' => $gcpprinter->id, 'name' => $gcpprinter->name, 'displayName' => $gcpprinter->displayName,
                    'ownerName' => @$gcpprinter->ownerName, 'connectionStatus' => $gcpprinter->connectionStatus,
                );
            }
        }
        return $printers;
    }

    public function setRefreshTokenSession($refresh_token=""){
        $this->session->set('refresh_token', $refresh_token);
    }

    public function getRefreshTokenSession(){
        return $this->refresh_token?$this->refresh_token:$this->session->get('refresh_token');
    }

    public function getRedirectUrl(){
        return $this->session->get('gcpRedirectUrl');
    }
    public function removeTokenSession(){
        $this->session->remove('refresh_token');
        $this->session->remove('gcpRedirectUrl');
    }

    public function checkRefreshTokenSession($redirectUrl){
        if(!$this->getRefreshTokenSession()) {
            $this->session->set('gcpRedirectUrl', $redirectUrl);
            return Yii::$app->response->redirect($this->redirect_uri)->send();
       }
    }



}