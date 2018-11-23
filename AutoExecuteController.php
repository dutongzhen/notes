<?php
/**
 * @第三方数据对接自动执行程序
 * @该项目下涉及到第三方（例如：今日头条的飞鱼CRM数据转发）的数据定期拉取统一走此控制器
 * @该控制器将获取到的数据会插入到报名表当中，所以需要调用前台逻辑的报名接口cake24_commit2方法
 * --------------------------------------------------------------------------------
 * @注：涉及到调用报名接口的必须包含 'Baomings', 'Cities', 'JcSetPeriod', 'BaomingsPlus', 'BaomingService', 'RejectLogs', 'ResetLogs', 'BaomingSources', 'Sources', 'BaomingCxfp' 这几个Model
 * @注：调用客服热线 cake24_get_hotline() 方法时一定要加载 Model GlobalConfig
 * @注：免费体验顾问你服务报名需要加上 Model ExperienceAdviserService
 * @注：工程部报名需要加上 Model BaomingJiong
 * @注：服务咨询类报名需要加上 Model GenzongCsfp
 * --------------------------------------------------------------------------------
 * @author 120291704@qq.com @date 2018-11-09
 * --------------------------------------------------------------------------------
 */
class AutoExecuteController extends AppController {

    public $name = 'AutoExecute';
    public $uses = ['Baomings', 'Cities', 'JcSetPeriod', 'BaomingsPlus', 'BaomingService', 'RejectLogs', 'ResetLogs', 'BaomingSources', 'Sources', 'BaomingCxfp', 'GlobalConfig'];
    private $Crm_cluesInfo = [];

    /**
     * @今日头条的7个飞鱼CRM账号数据转发数据接口（自动程序主入口）
     * @author 120291704@qq.com @date 2018-11-09
     * @update date 2018-11-13
     **/
    public function index(){

        $this->layout  = false;

        $request = $this->_YJK->reqCheck($_GET);
        
        for($type=1; $type<=7; $type++){
            
            $this->_load_crmDataFromType($request, $type);
        }
        echo 'success!';
        exit;

    }

    /**
     * @今日头条的飞鱼CRM数据转发数据接口
     * @param  $request Array 请求参数
     * @param  $type    Int   7个今日头条的飞鱼CRM账号 值为1到7
     * @author 120291704@qq.com @date 2018-11-14
     * @update date 2018-11-14
     **/
    protected function _load_crmDataFromType(array $request, int $type){

        if( empty($type) || !is_numeric($type) ){ $type = 1; }

        //线索数据必要的信息
        $this->Crm_cluesInfo  = $this->_load_cluesConfigInfo($type);

        $return_data = $this->_load_bytedanceData($request);

        if($return_data['status'] == 'success'){

            if($return_data['count'] > 10){

                $times = ceil( $return_data['count'] / 10);
                for($i=1; $i<$times; $i++){

                    $request['page'] = $i+1;
                    $return = $this->_load_bytedanceData($request);//通过curl获取飞鱼的转发数据
                    if( $return['status'] != 'fail' ){

                        $return_data['data'] = array_merge($return_data['data'], $return['data']);
                    }
                }
            }

            //将分析出的飞鱼转发数据进行存储
            $this->_save_bytedanceData($return_data['data'], $type);

        }else{

            $this->log($return_data['msg'], 'AutoExecuteCrm'); die($return_data['msg']);
        }

        unset($request, $type, $return_data, $times);
    }


    /**
     * @通过curl获取飞鱼的转发数据
     * ---------------------------------------------------------------------
     * @param  $request                Array  需要获取的数据
     * @param  $request['page']        Int    获取转发数据的第几页
     * @param  $request['start_time']  date   获取转发数据的开始时间
     * @param  $request['end_time']    date   获取转发数据的结束时间
     * @return $return                 Array  返回信息
     * @return $return_data['status']  Str 成功为'sucess'
     * @return $return_data['data']    Array data具体字段数据信息（里面保密那报名需要的数据信息）
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-11-09
     * @update date 2018-11-13
     **/
    private function _load_bytedanceData(array $request){

        $domain         = 'https://crm.bytedance.com';//数据拉取地址
        $signature_info = $this->_load_crmSignature($request);

        $curlHeaders    = [

            'Content-Type:application/json',
            'Signature:'.$signature_info['signature'],
            'Timestamp:'.$signature_info['time'],
            'Access-Token:'.$signature_info['token']//token
        ];

        $request_url = $domain.$signature_info['url'].'&page='.$signature_info['page'];

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $request_url);
        curl_setopt( $ch, CURLOPT_HTTPGET, true);
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 300);

        $return = curl_exec($ch);
        curl_close($ch);

        return json_decode($return, true);
        
    }

    /**
     * @获取签名
     * ---------------------------------------------------------------------
     * @param  $request                  Array  需要获取的数据
     * @param  $request['page']          Int    获取转发数据的第几页
     * @param  $request['start_time']    date   获取转发数据的开始时间
     * @param  $request['end_time']      date   获取转发数据的结束时间
     * @return $return                   Array  返回信息
     * @return $return_data['url']       Str    请求地址
     * @return $return_data['page']      Int    需要请求的第几页
     * @return $return_data['time']      Int    请求时间戳
     * @return $return_data['signature'] Str    签名信息
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-11-12
     * @update date 2018-11-13
     **/
    private function _load_crmSignature($request){

        if(isset($request['page']) && !empty($request['page']) ){ $page = $request['page']; }else{ $page = 1; }

        if(isset($request['start_time']) && $this->cake24_check_date($request['start_time']) ){

            $start_time = $request['start_time'];
        }else{

            $start_time = date('Y-m-d');
        }

        if(isset($request['end_time']) && $this->cake24_check_date($request['end_time']) ){

            $end_time   = $request['end_time'];
        }else{

            $end_time   = date('Y-m-d', strtotime('+1 day'));
        }

        if( strtotime($end_time) <= strtotime($start_time) ){

            $notice = 'index function errorMsg: End time must be greater than start time!';
            $this->log($notice, 'AutoExecuteCrm'); die($notice);
        }

        //时间跨度不允许超过一周
        if( (strtotime($end_time) - strtotime($start_time)) > 604800 ){

            $notice = 'Time span is not allowed for more than one week!';
            $this->log($notice, 'AutoExecuteCrm'); die($notice);
        }

        //线索数据必要的秘钥和token
        $clues_info = $this->Crm_cluesInfo;
        if( empty($clues_info) || !is_array($clues_info) ){

            $notice = 'key and token must be effective!';
            $this->log($notice, 'AutoExecuteCrm'); die($notice);
        }

        //通过hash加密获取签名
        $time          = time();
        $signature_key = $clues_info['key'];//hash加密密钥
        $url           = '/crm/v2/openapi/pull-clues/?start_time='.$start_time.'&end_time='.$end_time;
        $signature     = base64_encode(hash_hmac('sha256', $url.' '.$time, $signature_key));

        unset($request, $start_time, $end_time, $notice, $signature_key);

        return [
            
            'url'       => $url,
            'page'      => $page,
            'time'      => $time,
            'token'     => $clues_info['token'],
            'signature' => $signature
        ];
    }

    /**
     * @将分析出的飞鱼转发数据进行存储
     * ---------------------------------------------------------------------
     * @param  $crm_data                Array  需要保存的数据
     * @return 
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-11-09
     * @update date 2018-11-13
     **/
    private function _save_bytedanceData(array $crm_data){

        $crm_dataCnt = count($crm_data);
        $clues_info  = $this->Crm_cluesInfo;

        $this->log('-------------------------'.date('Y-m-d H:i').'总计：'.$crm_dataCnt.' start-------------------------------', 'AutoExecuteCrm');

        if ( empty($crm_data) || !is_array($crm_data) ) {
            $this->log('渠道为：'.$clues_info['channel'].' 的CRM转发数据为空！', 'AutoExecuteCrm');
        }else{

            foreach ($crm_data as $key => $value) {
                
                unset($_POST);
                $format_data = $this->_format_bytedanceData($value['remark_dict']);
                $from_union  = $this->_get_promotion_configData('qd_'.$value['site_id'], 5);//获取渠道来源

                if( !isset($value['name']) || empty($value['name']) ){ $value['name'] = 'CRM_NONAME'; }

                $utm_page = $this->_get_promotion_configData($value['site_id']);
                if(empty($utm_page)){ $utm_page = '今日头条飞鱼数据转发'; }

                $data = [

                    'name'         => $value['name'],
                    'mobile'       => str_replace(' ', '', $value['telphone']),
                    'utm_page'     => $utm_page,
                    'unionKey'     => isset($clues_info['channel']) ? $clues_info['channel'].'_1' : '',
                    'find_cookies' => 1
                ];

                $data  = array_merge($data, $format_data);
                $_POST = $data;
                $reponse_data = $this->cake24_commit2(true, true, true);

                if($this->_check_isJson($reponse_data)){

                    $notice = json_decode($reponse_data, true);
                    $this->log('渠道为：'.$clues_info['channel'].' 提交的数据错误信息为：'.$notice['str'], 'AutoExecuteCrm');
                }else{

                    $this->log('渠道为：'.$clues_info['channel'].' 的CRM转发数据存储成功 baoming_id:'.$reponse_data, 'AutoExecuteCrm');
                }
                
                unset($this->Baomings->id);
            }

            unset($crm_data, $format_data, $from_union, $utm_page, $data, $_POST, $reponse_data);
        }

        $this->log('-------------------------'.date('Y-m-d H:i').'总计：'.$crm_dataCnt.'   end-------------------------------', 'AutoExecuteCrm');

        unset($clues_info);

    }

    /**
     * @获取今日头条飞鱼CRM各账号的秘钥和token
     * ---------------------------------------------------------------------
     * @param  $type         Int   账号类型
     * @return clues_infoArr Array 各账号的秘钥、渠道和token
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-11-13
     * @update date 2018-11-13
     **/
    private function _load_cluesConfigInfo(int $type){

        if( empty($type) || !is_numeric($type) ){ $type = 1; }

        $clues_infoArr = [

            1 => [

                'channel' => 'jrtt',
                'key'     => 'MzgwTjVLMUhGVlpF',
                'token'   => 'b3c3e553296548023c5f6610d65d2593106291fe'
            ],
            2 => [

                'channel' => 'JRTTDSP',
                'key'     => 'QzVSS0NVTlRNTDZE',
                'token'   => 'b1a2c55e0665d5544a7c8b30f2778b098ecb7455'
            ],
            3 => [

                'channel' => 'jrtt3',
                'key'     => 'NDhPQkFZU0dPVFk3',
                'token'   => 'e30207ea6bcc7ced916b27e9c9de5188fe5f4a21'
            ],
            4 => [

                'channel' => 'JRTT4',
                'key'     => 'QUNIMUxYQVRTT08y',
                'token'   => '244695bb1019efa181a52cb84a54b747ec1f77a2'
            ],
            5 => [

                'channel' => 'YJJRTTZQ',
                'key'     => 'VVNWUFZPMTlHS1NC',
                'token'   => '8f03970093e6d52442ca84ac1101081e4114f804'
            ],
            6 => [

                'channel' => 'JRTTZC',
                'key'     => 'WUU2NU5PTFhFME1C',
                'token'   => '428d1d4cbe3bf46d3006548b6ead5fb5b714baa3'
            ],
            7 => [

                'channel' => 'YJJRTTZC',
                'key'     => 'VDlNSE9GNjU4VVFH',
                'token'   => '9b6eea48d58c6699935eb77742382b8f4099b0a8'
            ]
        ];

        return $clues_infoArr[$type];
    }


    //格式化今日头条推送过来的数据
    private function _format_bytedanceData($data){

        $return_data = [];
        
        $lable_arr   = [

            '请输入您的房屋面积' => 'area',
            '请输入您的装修面积' => 'area',
            '装修类型'          => 'bm_bak',
            '请选择您所在的区域' => 'bm_bak',
            '请选择您的户型'     => 'bm_bak',
        ];

        foreach ($data as $key => $value) {
            
            $value = $this->cake24_replace_specialChar($value);//屏蔽掉特殊字符
            if(!empty($value)){

                if($lable_arr[$key] == 'area'){ $value = (int)$value; }
                $return_data[$lable_arr[$key]] = $value;
            }
            
        }

        return $return_data;
    }

    //获取页面来源的名称
    private function _get_promotion_configData($short, $category_id = 1){

        if(empty($short)){ return ;}
        if( !is_numeric($category_id) || empty($category_id)){ return ; }

        $conditions = array('GlobalConfig.category_id' => $category_id); //1-报名页面来源 5-渠道来源数据
        $utmPageArr = $this->GlobalConfig->find('list', array(
            'conditions' => $conditions, 
            'fields' => array('GlobalConfig.short', 'GlobalConfig.name'))
        );

        return $utmPageArr[$short];

    }
}