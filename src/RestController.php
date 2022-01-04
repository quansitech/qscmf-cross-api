<?php
namespace QscmfCrossApi;

use QscmfCrossApi\Model\CrossApiRegisterModel;
use Think\Controller;

class RestController extends Controller {

    use ValidateHelper;

    protected $_method = ''; // 当前请求类型
    protected $_type   = ''; // 当前资源类型
    protected $_version = ''; //请求接口版本号
    // 输出类型
    protected $restMethodList    = 'get|post|put|delete';
    protected $restDefaultMethod = 'get';
    protected $restTypeList      = 'html|xml|json|rss';
    protected $restDefaultType   = 'json';
    protected $restOutputType    = [ // REST允许输出的资源类型列表
        'xml'  => 'application/xml',
        'json' => 'application/json',
        'html' => 'text/html',
    ];
    protected $restInvokeList = [
        'get' => 'gets',
        'post' => 'create',
        'put' => 'update',
        'delete' => 'delete'
    ];

    protected $_filter;


    public function __construct()
    {
        parent::__construct();

        if(env("QSCMF_CROSS_API_MP_MAINTENANCE")){
            $this->response('系统维护中', 0, [], 503);
        }

        // 资源类型检测
        if ('' == __EXT__) {
            // 自动检测资源类型
            $this->_type = $this->getAcceptType();
        } elseif (!preg_match('/\(' . $this->restTypeList . '\)$/i', __EXT__)) {
            // 资源类型非法 则用默认资源类型访问
            $this->_type = $this->restDefaultType;
        } else {
            $this->_type = __EXT__;
        }

//        $time_stamp = I('time_stamp');
//        if(strtotime('-1 minutes')>= $time_stamp){
//            $this->response('请求超时',0,$data);
//        }

        // 请求方式检测
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        //跨域嗅探,直接返回200
        if($method == 'options'){
            $this->response('', 1);
        }

        //参数过滤
        $this->filterHandle();

        if (false === stripos($this->restMethodList, $method)) {
            // 请求方式非法 则用默认请求方法
            $method = $this->restDefaultMethod;
        }
        $this->_method = $method;

        $this->getVersion();

        $this->route();

        //匹配
    }

    protected function filterHandle(){
        if(!$this->_filter){
            return;
        }

        foreach($this->_filter as $v){
            $input_value = I($v[0]);
            if(!$input_value)
                continue;

            switch($v[1]){
                case 'isExists':
                    if(D($v[2])->isExists($input_value) === false){
                        $this->response('数据不存在', 0, '', $v[3]);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    protected function route()
    {
        $func = $this->restInvokeList[$this->_method];
        $method = $func;

        if($this->_version != ''){
            $func .= '_v' . $this->_version;
        }

        if(method_exists($this, $method)){
            $this->auth($method);
            $this->$func();
            exit();
        }
        else{
            throw new \Exception('error action :' . ACTION_NAME);
        }
    }

    protected function getAcceptType(){
        $type = array(
            'xml'   =>  'application/xml,text/xml,application/x-xml',
            'json'  =>  'application/json,text/x-json,application/jsonrequest,text/json',
            'js'    =>  'text/javascript,application/javascript,application/x-javascript',
            'css'   =>  'text/css',
            'rss'   =>  'application/rss+xml',
            'yaml'  =>  'application/x-yaml,text/yaml',
            'atom'  =>  'application/atom+xml',
            'pdf'   =>  'application/pdf',
            'text'  =>  'text/plain',
            'png'   =>  'image/png',
            'jpg'   =>  'image/jpg,image/jpeg,image/pjpeg',
            'gif'   =>  'image/gif',
            'csv'   =>  'text/csv',
            'html'  =>  'text/html,application/xhtml+xml'
        );

        foreach($type as $key=>$val){
            $array   =  explode(',',$val);
            foreach($array as $k=>$v){
                if(stristr($_SERVER['HTTP_ACCEPT'], $v)) {
                    return $key;
                }
            }
        }
        return strtolower(C('DEFAULT_AJAX_RETURN'));
    }

    protected function getVersion(){
        $word_reg = "/.*version=(\d+(?:\.\d+)*)/";
        if(preg_match($word_reg, $_SERVER['HTTP_ACCEPT'], $matches)){
            $this->_version = str_replace('.', '_', $matches[1]);
        }
    }

    // 发送Http状态信息
    protected function sendHttpStatus($code) {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if(isset($_status[$code])) {
            header('HTTP/1.1 '.$code.' '.$_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:'.$code.' '.$_status[$code]);
        }
    }

    protected function encodeData($data,$type='') {
        if(empty($data))  return '';
        if('json' == $type) {
            // 返回JSON数据格式到客户端 包含状态信息
            $data = $this->_htmlDecode($data);
            $data = json_encode($data);
        }elseif('xml' == $type){
            // 返回xml格式数据
            $data = xml_encode($data);
        }elseif('php'==$type){
            $data = serialize($data);
        }// 默认直接输出
        $this->setContentType($type);
        return $data;
    }

    protected function _htmlDecode($data){
        if (is_array($data)){
            foreach ($data as $k => &$v){
                $v = $this->_htmlDecode($v);
            }
            return $data;
        }
        else{
            return is_string($data) && !isJson($data) ? htmlspecialchars_decode($data) : $data;
        }
    }

    public function setContentType($type, $charset=''){
        if(headers_sent()) return;
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        $type = strtolower($type);
        if(isset($this->restOutputType[$type])) //过滤content_type
            header('Content-Type: '.$this->restOutputType[$type].'; charset='.$charset);
        if(C('QSCMF_INTRANET_API_CORS', null, '*')){
            header("Access-Control-Allow-Origin:". C('QSCMF_INTRANET_API_CORS', null, '*'));
            header("Access-Control-Allow-Headers:*");
            header("Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS");
        }
    }


    protected function response($message, $status, $data = '', $code = 200, array $extra_res_data = []) {
        $this->sendHttpStatus($code);
        $return_data['status'] = $status;
        $return_data['info'] = $message;
        $return_data['data'] = $data;
        if (!empty($extra_res_data)){
            $return_data = array_merge($return_data, $extra_res_data);
        }
        qs_exit($this->encodeData($return_data,strtolower($this->_type)));
    }

    protected function auth($action_name){
        if(isset($this->_no_check_access) && $this->_no_check_access === true){
            return true;
        }

        $id = getallheaders()['Authorization'];
        $ip = $_SERVER['SERVER_ADDR'];
        $intranet_api_register_model = new CrossApiRegisterModel();
        if(!$intranet_api_register_model->isExistsApiById($id, $ip,MODULE_NAME, CONTROLLER_NAME, $action_name)){
            $this->response('没有访问权限', 0, '', 403);
        }

    }
}
