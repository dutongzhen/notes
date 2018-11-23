<?php
/*
 * 百度社交广告接口
 * @date 2018-06-05
 * @author zhanghuihui
 */
class BaiduSocialAdsController extends AppController {
    public $name = 'BaiduSocialAds';
    public $uses = [];

    public function index(){
        $this->layout = false;
        $this->autoRender = false;
        echo 'hello Youjuke BaiduSocialAds Token';
    }


    /*
     * sem2 => sh欢居2
     * 百度ocpc 家装
     * @params $logidUrl    落地页链接 如果转化发生在二跳及之后的页面，需要您将落地页url中的bd_vid参数透传后拼接在转化页的url中回传
     * @params $clickTime   打开落地页时间
     */
    public function baidu_jz_conversion(){
        $this->layout = false;
        $this->autoRender = false;

        $request  = htmlspecialchars($_REQUEST['request_type'], ENT_QUOTES);
        $clickTime  = htmlspecialchars($_REQUEST['clickTime'], ENT_QUOTES);
        $logidUrl  = htmlspecialchars($_REQUEST['logidUrl'], ENT_QUOTES);


        $convertTime = time();

        $username = '';
        $password = '';
        $token = '';
        $uid = 10226134;
        $apiurl = "https://api.baidu.com/json/sms/service/ECPAConvertDataService/uploadECPAConvertData";

        $header = array('username' =>  $username, 'password' => $password, 'token' => $token);

        $conv1 = array(
            'logidUrl' => $logidUrl,
            'uid' => $uid,
            'clickTime' => $clickTime,
            'convertTime' => $convertTime,
            'isConvert' => 1,
            'convertType' => 3
        );

        $results = array($conv1);
        $body = array('conversionTypes' => $results);
        $jsonstr = json_encode(array('header' => $header, 'body' => $body));

        $res = $this->http_post_json($apiurl, $jsonstr);

        if($request == 'jsonp'){//jsonp格式返回

            echo 'jsonpHandler('. json_encode(array('code'=>$res['0'])) .')';exit;
        }else{

            echo json_encode(array('code'=>$res['0']));//json格式返回
        }

    }

    /*
     * BAIDUZGM => sh-欢居1
     * 百度ocpc 公装
     * @params $logidUrl    落地页链接 如果转化发生在二跳及之后的页面，需要您将落地页url中的bd_vid参数透传后拼接在转化页的url中回传
     * @params $clickTime   打开落地页时间
     */
    public function baidu_gz_conversion(){
        $this->layout = false;
        $this->autoRender = false;

        $request  = htmlspecialchars($_REQUEST['request_type'], ENT_QUOTES);
        $clickTime  = htmlspecialchars($_REQUEST['clickTime'], ENT_QUOTES);
        $logidUrl  = htmlspecialchars($_REQUEST['logidUrl'], ENT_QUOTES);

        $convertTime = time();

        $username = '';
        $password = '';
        $token = '';
        $uid = 11216139;
        $apiurl = "https://api.baidu.com/json/sms/service/ECPAConvertDataService/uploadECPAConvertData";

        $header = array('username' =>  $username, 'password' => $password, 'token' => $token);

        $conv1 = array(
            'logidUrl' => $logidUrl,
            'uid' => $uid,
            'clickTime' => $clickTime,
            'convertTime' => $convertTime,
            'isConvert' => 1,
            'convertType' => 3
        );

        $results = array($conv1);
        $body = array('conversionTypes' => $results);
        $jsonstr = json_encode(array('header' => $header, 'body' => $body));

        $res = $this->http_post_json($apiurl, $jsonstr);

        if($request == 'jsonp'){//jsonp格式返回

            echo 'jsonpHandler('. json_encode(array('code'=>$res['0'])) .')';exit;
        }else{

            echo json_encode(array('code'=>$res['0']));//json格式返回
        }
    }





    private function http_post_json($url,$jsonstr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonstr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonstr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($httpCode, $response);
    }


}