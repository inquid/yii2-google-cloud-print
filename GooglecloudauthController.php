<?php

namespace  inquid\googlecloudprint;
use Yii;
use yii\web\Controller;


/**
 * Googlecloudauth controller for the `@inquid/googlecloudprint` components
 */
class GooglecloudauthController extends Controller
{
    /**
     * @return redirect Url
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;

        if(count($request->get()) > 0 ){
            $code = $request->get('code',null);
            if($code != null){
                $responseObj = Yii::$app->GoogleCloudPrint->getRefreshToken($code);
                if(isset($responseObj->access_token)) Yii::$app->GoogleCloudPrint->setAuthTokenByResponce($responseObj->access_token);
                if(isset($responseObj->refresh_token)){
                    Yii::$app->GoogleCloudPrint->setRefreshTokenSession($responseObj->refresh_token);
                    return $this->redirect(Yii::$app->GoogleCloudPrint->getRedirectUrl());
                }
            }
        }
        return $this->redirect(Yii::$app->GoogleCloudPrint->getAuthUrl());
    }

    /**
     * Remove Refresh Token Session
     * Remove Redirect Url Session
     * @return redirect to Home page
     */
    public function actionRemove(){
        Yii::$app->GoogleCloudPrint->removeTokenSession();
        return $this->goHome();
    }



}
