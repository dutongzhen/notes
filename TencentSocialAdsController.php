<?php
/*
 * 腾讯社交广告接口
 * create date 2017-11-09
 * 更新于 2017-11-22
 * author 120291704@qq.com
 */
class TencentSocialAdsController extends AppController {
    public $name = 'TencentSocialAds';
    public $uses = [];

    //腾讯社交广告账号
    private $AppID       = '';
    private $AppSecret   = '';
    private $AccountId   = '';
    private $ActionSetId = '';

    public function index(){

        echo 'hello Youjuke TencentSocialAds Token';
        $this->_get_access_token();exit;
    }

    //更新token
    public function refresh_access_token(){

        echo 'hello Youjuke Refresh TencentSocialAds Token';
        $this->_loadSocialAdsClass('refreshAccessToken');exit;
    }

    //设置用户行为数据源
    public function set_user_actions(){

        $params                = [];
        $params['account_id']  = $this->AccountId;
        $params['type']        = 'WEB';
        $params['name']        = 'webuser_action_set';
        $params['description'] = '';
        $data = $this->_loadSocialAdsClass('setUserActions', $params);
        dump($data);exit;
    }

    //获取用户行为数据源
    public function get_user_actions(){

        $set_id = (int)$this->request->query('set_id');

        $params                       = [];
        $params['account_id']         = $this->AccountId;
        $params['user_action_set_id'] = !empty($set_id) ? $set_id : $this->ActionSetId;
        $data = $this->_loadSocialAdsClass('getUserActions', $params);
        dump($data);exit;
    }

    //上报网页转化行为数据
    public function report_webpage_action(){

        $set_id   = htmlspecialchars($_REQUEST['set_id'], ENT_QUOTES);
        $url      = htmlspecialchars($_REQUEST['url'], ENT_QUOTES);
        $click_id = htmlspecialchars($_REQUEST['click_id'], ENT_QUOTES);
        $value    = htmlspecialchars($_REQUEST['value'], ENT_QUOTES);
        $request  = htmlspecialchars($_REQUEST['request_type'], ENT_QUOTES);
        
        $params                                  = [];
        $params['account_id']                    = $this->AccountId;
        $params['actions']['user_action_set_id'] = !empty($set_id) ? $set_id : $this->ActionSetId;
        $params['actions']['url']                = $url;
        $params['actions']['action_time']        = time();
        $params['actions']['action_type']        = 'COMPLETE_ORDER';
        $params['actions']['trace']['click_id']  = $click_id;
        $params['actions']['value']              = !empty($value) ? $value : 100;

        $json_data = json_encode($this->_loadSocialAdsClass('reportWebpageAction', $params));
        
        if($request == 'jsonp'){//jsonp格式返回

            echo 'jsonpHandler('. $json_data .')';exit;
        }else{

            echo $json_data;exit;//json格式返回
        }

        
    }

    //回调网址
    public function response(){

        $this->layout = false;
        $authorization_code = $this->request->query('authorization_code');
        if ($authorization_code) {
            
            $this->_setAuthorizationCode($authorization_code);
        }
        echo 'success!';exit;
    }

    
    /*
     *@获取腾讯社交广告access_token
     *@author dtz 2017-11-22
     *@更新于 2017-11-22
     */
    private function _get_access_token(){

        $this->log('get_accessToken', 'TencentSocialAds');
        return $this->_loadSocialAdsClass('getAcccessToken');
    }

    /*
     *@设置用户票据
     *@author dtz 2017-11-22
     *@更新于 2017-11-22
     */
    private function _setAuthorizationCode($authorization_code){

        return $this->_loadSocialAdsClass('setAuthorizationCode', $authorization_code);

    }

    //加载腾讯社交广告model
    private function _loadSocialAdsClass($method = 'getAuthorizationCode', $params = ''){

        if(empty($method)){ return ; }

        //获取腾讯社交广告数据
        App::import('Vendor', 'TencentSocialAds');
        $socialAdsModel = new TencentSocialAds($this->AppID, $this->AppSecret);

        if ($this->_check_method_exists($socialAdsModel, $method)) {
            
            if($params){

                return $socialAdsModel->$method($params);
            }else{

                return $socialAdsModel->$method();
            }
            

        }else{

            return ;
        }
    }

}