<?php
/**
   * 微信广告转化行为数据接入
   * --------------------------------------------------------------------
   * @notice 此方法主要用于微信广告转化行为数据的接入、创建和获取行为数据源、上报页面转化和获取用户行为数据源报表等
   * --------------------------------------------------------------------
   * @author 120291704@qq.com
   * @date   2018-06-06
   */
class WechatSocialAdsController extends AppController {
    public $name = 'WechatSocialAds';
    public $uses = [];

    //微信服务号账号
    private $AppID       = 'wx1ace2c8f40fd6605';
    private $AppSecret   = '6ba999c53b578fb9aecf4b07c17a4f81';
    private $ActionSetId = '1106877781';

    //定时获取或刷新token（需要每两小时更新一次）
    public function index(){

        echo 'hello Youjuke WechatSocialAds Token';
        $this->_loadSocialAdsClass();exit;
    }

    //创建用户行为数据源
    public function set_user_actions(){

        unset($request);
        unset($post_data);
        $request = $this->_YJK->reqCheck($_REQUEST);

        $post_data                = [];
        $post_data['type']        = $request['type'];
        $post_data['name']        = $request['name'];
        $post_data['description'] = $request['description'];

        $data = $this->_loadSocialAdsClass('setUserActions', $post_data);
        dump($data);exit;
    }

    //获取用户行为数据源
    public function get_user_actions(){

        unset($post_data);

        $post_data                       = [];
        $post_data['user_action_set_id'] = $this->ActionSetId;

        $data = $this->_loadSocialAdsClass('getUserActions', $post_data);
        dump($data);exit;
    }

    //上报网页转化行为数据
    public function report_webpage_action(){

        header("Content-Security-Policy: upgrade-insecure-requests");
        unset($request);
        $request = $this->_YJK->reqCheck($_REQUEST);
        
        $params                                     = [];
        $params['actions']['user_action_set_id']    = !empty($request['set_id']) ? $request['set_id'] : $this->ActionSetId;
        $params['actions']['url']                   = $request['url'];
        $params['actions']['action_time']           = time();
        $params['actions']['action_type']           = 'COMPLETE_ORDER';
        $params['actions']['trace']['click_id']     = $request['click_id'];
        $params['actions']['action_param']['value'] = !empty($request['value']) ? $request['value'] : 40;

        $json_data = json_encode($this->_loadSocialAdsClass('reportWebpageAction', $params));
        
        if($request['type'] == 'jsonp'){//jsonp格式返回

            echo 'jsonpHandler('. $json_data .')';exit;
        }else{

            echo $json_data;exit;//json格式返回
        }
    }

    //获取用户行为数据源报表
    public function get_user_actions_reports(){

        unset($request);
        $request = $this->_YJK->reqCheck($_REQUEST);

        $params                       = [];
        $params['user_action_set_id'] = !empty($request['set_id']) ? $request['set_id'] : $this->ActionSetId;
        $params['start_date']         = $request['start_date'];
        $params['end_date']           = $request['end_date'];
        //时间粒度，针对流量的可选值 DAILY （按 天汇总）， HOURLY （按小时汇总），默认以小时汇总
        $params['time_granularity']   = !empty($request['granularity']) ? $request['granularity'] : 'DAILY';
        //聚合维度，是否将结果按照指定类型细分可选值 ('DOMAIN', ACTION_TYPE')
        $params['aggregation']        = !empty($request['aggregation']) ? $request['aggregation'] : 'ACTION_TYPE';

        $data = $this->_loadSocialAdsClass('getUserActionsReports', $params);
        dump($data);exit;

    }

    /**
     *@加载微信社交广告model
     * --------------------------------------------------------------------
     *@param  $method String 请求的具体方法
     *@param  $params Array  请求的附带参数
     * --------------------------------------------------------------------
     *@author 120291704@qq.com
     */
    private function _loadSocialAdsClass($method = 'getAcccessToken', $params = ''){

        unset($socialAdsModel);
        if(empty($method)){ return ; }

        //获取腾讯社交广告数据
        App::import('Vendor', 'WechatSocialAds');
        $socialAdsModel = new WechatSocialAds($this->AppID, $this->AppSecret);

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