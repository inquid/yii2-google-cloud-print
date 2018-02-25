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
use yii\base\Component;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

require_once 'HttpRequest.Class.php';

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

    private $authtoken;
    private $httpRequest;
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
        $this->httpRequest = new HttpRequest();
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
            'refresh_token' => $this->refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type
        );
        //$responseObj = $this->getAccessToken(REFRESHTOKEN_URL, http_build_query($refreshTokenConfig));
        return $this->getAccessToken(self::REFRESHTOKEN_URL, http_build_query($refreshTokenConfig))->access_token;
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
    public function getAccessToken($url)
    {
        $refreshTokenConfig = array(
            'refresh_token' => $this->refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type
        );
        $this->httpRequest->setUrl($url);
        $this->httpRequest->setPostData(http_build_query($refreshTokenConfig));
        $this->httpRequest->send();
        $response = json_decode($this->httpRequest->getResponse());
        return $response;
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

        $this->httpRequest->setUrl(self::PRINTERS_SEARCH_URL);
        $this->httpRequest->setHeaders($authheaders);
        $this->httpRequest->send();
        $responsedata = $this->httpRequest->getResponse();
        // Make Http call to get printers added by user to Google Cloud Print
        $printers = json_decode($responsedata);
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
        // Prepare authorization headers
        $authheaders = array(
            "Authorization: Bearer " . $this->authtoken
        );

        // Make http call for sending print Job
        $this->httpRequest->setUrl(self::PRINT_URL);
        $this->httpRequest->setPostData($post_fields);
        $this->httpRequest->setHeaders($authheaders);
        $this->httpRequest->send();
        $response = json_decode($this->httpRequest->getResponse());

        // Has document been successfully sent?
        if ($response->success == "1") {
            return new Error('200', $response->job->id);
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
        if (empty($printerid)) {
            // Printer id is not there so throw exception
            if (!empty($this->default_printer_id))
                $printerid = $this->default_printer_id;
            else
                throw new Exception("Please provide printer ID");
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
        // Prepare authorization headers
        $authheaders = array(
            "Authorization: Bearer " . $this->authtoken
        );

        // Make http call for sending print Job
        $this->httpRequest->setUrl(self::PRINT_URL);
        $this->httpRequest->setPostData($post_fields);
        $this->httpRequest->setHeaders($authheaders);
        $this->httpRequest->send();
        $response = json_decode($this->httpRequest->getResponse());

        // Has document been successfully sent?
        if ($response->success == "1") {
            return array('status' => true, 'errorcode' => '', 'errormessage' => "", 'id' => $response->job->id);
        } else {
            return new Error($response->errorCode, $response->message);
        }
    }

    public function jobStatus($jobid)
    {
        // Prepare auth headers with auth token
        $authheaders = array(
            "Authorization: Bearer " . $this->authtoken
        );

        // Make http call for sending print Job
        $this->httpRequest->setUrl(self::JOBS_URL);
        $this->httpRequest->setHeaders($authheaders);
        $this->httpRequest->send();
        $responsedata = json_decode($this->httpRequest->getResponse());

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
}