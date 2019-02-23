<?php
/**


 * @作者：      胡 锐
 * @修改日期：  2018-08-03 12:46
 * @功能说明：  芝麻信用接口类
 */
namespace Extend;

use Think\Request;

class Zhima
{
    private $apiUrl = 'https://t.51muge.com';
    private $appKey;#平台发放给开发者的标识开发者身份的标识符
    private $appSecret;

    private $order_id;#订单号
    private $redirect;#用户授权结束之后, 接收授权结果的异步回调地址, 须urlEncode,
    private $extras;#合作方自定义数据, 会作为异步回调地址的参数返回给合作方
    private $channel;#默认使用唤醒APP的方式进行授权, 传入H5将只跳转到支付宝的H5页面进行授权 强烈建议非微信内的应用，使用唤醒APP的方式。H5授权方式有可能芝麻信用后续不再提供支持。
    private $cert_no;#用户的身份证号
    private $cert_name;#用户的姓名
    private $cert_phone;#用户的手机号
    private $readonly;#默认为0, 当值为1时, 传入的用户三要素不能被用户修改
    private $customUrl;#用户授权结束展示的页面, 默认为我方的结果页, 须urlEncode
    private $modify_no;#不管readonly是否为1, 该值为1, 表示用户可以修改身份证
    private $modify_name;#不管readonly是否为1, 该值为1, 表示用户可以修改姓名
    private $modify_phone;#不管readonly是否为1, 该值为1, 表示用户可以修改手机号
    private $desensitized;#该值为1, 表示在页面上显示的用户三要素是脱敏的(当用户自己修改或输入是不脱敏的)

    /**
     * 必须接收的参数
     * @var array
     */
    private $mustFieldSet = array('appKey', 'orderId', 'redirect', 'channel', 'certNo', 'certName');
    private $mustFieldSetMapping = array(
        'appKey' => 'appKey',
        'orderId' => 'order_id',
        'redirect' => 'redirect',
        'channel' => 'channel',
        'certNo' => 'cert_no',
        'certName' => 'cert_name',
        'certPhone' => 'cert_phone'
    );
    private $mustField;

    /**
     * 非必须接收的参数
     * @var array
     */
    private $needFieldSet = array('extras', 'readonly', 'customUrl', 'modifyNo', 'modifyName', 'modifyPhone', 'desensitized', 'certPhone');
    private $needFieldSetMapping = array(
        'extras' => 'extras',
        'readonly' => 'readonly',
        'customUrl' => 'customUrl',
        'modifyNo' => 'modify_no',
        'modifyName' => 'modify_name',
        'modifyPhone' => 'modify_phone',
        'desensitized' => 'desensitized',
        'certPhone' => 'cert_phone'

    );
    public $postCharset = "UTF-8";



    public function __construct($appKey = null,$appSecret = null)
    {
        $this->appKey = is_null($appKey) ? C('Zhima_appKey') : $appKey;
        $this->appSecret = is_null($appSecret) ? C('Zhima_appSecret') : $appSecret;
    }

    /**
     * @return mixed
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param mixed $order_id
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }
    /**
     * 生成订单号同时赋值
     * @param mixed $order_id
     */
    public function withOrderId()
    {
        $order_id = $this->generateOrderno();
        return $this->order_id = $order_id;
    }

    /**
     * @return mixed
     */
    public function getRedirect()
    {
        return urlencode($this->redirect);
    }

    /**
     * @param mixed $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * @return mixed
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param mixed $extras
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;
    }

    /**
     * @return mixed
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param mixed $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return mixed
     */
    public function getCertNo()
    {
        return $this->cert_no;
    }

    /**
     * @param mixed $cert_no
     */
    public function setCertNo($cert_no)
    {
        $this->cert_no = $cert_no;
    }

    /**
     * @return mixed
     */
    public function getCertName()
    {
        return $this->cert_name;
    }

    /**
     * @param mixed $cert_name
     */
    public function setCertName($cert_name)
    {
        $this->cert_name = $cert_name;
    }

    /**
     * @return mixed
     */
    public function getCertPhone()
    {
        return $this->cert_phone;
    }

    /**
     * @param mixed $cert_phone
     */
    public function setCertPhone($cert_phone)
    {
        $this->cert_phone = $cert_phone;
    }

    /**
     * @return mixed
     */
    public function getReadonly()
    {
        return $this->readonly;
    }

    /**
     * @param mixed $readonly
     */
    public function setReadonly($readonly)
    {
        $this->readonly = $readonly;
    }

    /**
     * @return mixed
     */
    public function getCustomUrl()
    {
        return urlencode($this->customUrl);
    }

    /**
     * @param mixed $customUrl
     */
    public function setCustomUrl($customUrl)
    {
        $this->customUrl = $customUrl;
    }

    /**
     * @return mixed
     */
    public function getModifyNo()
    {
        return $this->modify_no;
    }

    /**
     * @param mixed $modify_no
     */
    public function setModifyNo($modify_no)
    {
        $this->modify_no = $modify_no;
    }

    /**
     * @return mixed
     */
    public function getModifyName()
    {
        return $this->modify_name;
    }

    /**
     * @param mixed $modify_name
     */
    public function setModifyName($modify_name)
    {
        $this->modify_name = $modify_name;
    }

    /**
     * @return mixed
     */
    public function getModifyPhone()
    {
        return $this->modify_phone;
    }

    /**
     * @param mixed $modify_phone
     */
    public function setModifyPhone($modify_phone)
    {
        $this->modify_phone = $modify_phone;
    }

    /**
     * @return mixed
     */
    public function getDesensitized()
    {
        return $this->desensitized;
    }

    /**
     * @param mixed $desensitized
     */
    public function setDesensitized($desensitized)
    {
        $this->desensitized = $desensitized;
    }

    /**
     * 校验 拼装数据
     * @return array
     */
    public function checkAccess()
    {
        $param = array();

        if (! $checkRes = $this->checkMustField()) {
            $this->error($this->mustField . ' is must!');
        }

        $param = array_merge($param, $checkRes, $this->checkExistField());

        #$param = array_merge($param, $this->checkExistField());

        if (strlen($this->getOrderId()) <> 32) {
            $this->error('Order number must 32 digits');
        }

        if ( $this->getCertPhone() && (!$this->is_mobile($this->getCertPhone()) ) ) {
            $this->error('Phone is incorrect');
        }
        return $param;
    }

    /**
     * 获取授权的接口地址+GET+参数
     * @param $param
     * @return string
     */
    public function accessAuthUrl($param)
    {
        $url = $this->apiUrl.'/zmop/index?'.http_build_query($param);

        $this->recordNotifyStep('|--获取授权的接口地址', $url);
        return $url;
    }

    /**
     * 获取芝麻分数据
     * @param $param
     * @return string
     */
    public function accessCreditScore()
    {
        $url = $this->apiUrl.'/zmop/data/score';
        $params = array(
            'appKey' => $this->getAppKey(),
            'timestamp' => $this->getTimestamp(),
            'token' => $this->getToken(),
            'order_id' => $this->getOrderId()
        );
        $sign = $this->generateSign($params);

        $body = array(
            'refresh' => 1,
            'sign' => $sign,
            'params' => $params
        );

        $res = json_decode( $this->httpGet($url, 'POST', $body) );

        return $res;
    }

    /**
     * 获取行业关注名单数据
     * @param $param
     * @return string
     */
    public function watchList()
    {
        $url = $this->apiUrl.'/zmop/data/watch_list';
        $params = array(
            'appKey' => $this->getAppKey(),
            'timestamp' => $this->getTimestamp(),
            'token' => $this->getToken(),
            'order_id' => $this->getOrderId()
        );
        $sign = $this->generateSign($params);

        $body = array(
            'refresh' => 1,
            'sign' => $sign,
            'params' => $params
        );
        $res = json_decode($this->httpGet($url, 'POST', $body));

        $this->recordNotifyStep('|--获取行业关注名单数据', json_encode($res));
        return $res;
    }

    private function getToken() {
        //  应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode($this->get_php_file('token.php'));

        if ($data->expire_time < time()) {

            if ($token = $this->getAccessToken()) {
                $data->expire_time = time() + 7000;
                $data->token = $token;
                $this->set_php_file('token.php', json_encode($data));
            } else {
                die('access token failed');
            }

        } else {
            $token = $data->token;
        }
        return $token;
    }

    public function getAccessToken()
    {
        $url = $this->apiUrl.'/zmop/getToken';
        $params = array(
            'appKey' => $this->getAppKey(),
            'timestamp' => $this->getTimestamp()
        );

        $sign = $this->generateSign($params);

        $body = array(
            'sign' => $sign,
            'params' => $params
        );
        $res = json_decode($this->httpGet($url, 'POST', $body));
        if ($res->code == 0) {
            return $res->data->token;
        }
        $request = Request::instance();
        if ($request->isAjax()) {
            die(json_encode($res));
        } else {
            die(json_encode($res, \JSON_UNESCAPED_UNICODE));
            die($res->msg);
        }
    }

    private function get_php_file($filename) {
        // ECHO $filename,dirname(__FILE__).'/'.$filename,'<br>';
        return trim(substr(file_get_contents(__DIR__.'/zhima/'.$filename), 15));
    }
    private function set_php_file($filename, $content) {
        file_put_contents(__DIR__.'/zhima/'.$filename,'<?php exit();?>' . $content);
    }

    /**
     * [httpGet 使用curl向服务器传输数据]
     * @param [type] $url  [请求的地址]
     * @param array  $data [数据]
     * @param string $type [请求方式GET,POST]
     */
    private function httpGet($url, $method='get', $data=array()) {
        $data = http_build_query($data);

        $ch = curl_init();//初始化
        $headers = array('Accept-Charset: utf-8');
        //设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);//指定请求的URL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));//提交方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//不验证SSL
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置HTTP头字段的数组
        // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');//头的字符串
        #curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie_file); //存储cookies
        #curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); //使用上面获取的cookies
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//自动设置header中的Referer:信息
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//提交数值
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//是否输出到屏幕上,true不直接输出
        $temp = curl_exec($ch);//执行并获取结果
        curl_close($ch);
        return $temp;//return 返回值
    }

    /**
     * 生成订单号
     */
    public function generateOrderno() {
        #生成规则：订单号（order_id）:前 17 位为精确到毫秒的时间值（yyyyMMddHHmmssSSS）作为前缀
        $defaultPrefix = date('YmdHis').substr(microtime(), 2, 3);//17位

        #不重复字符串作为后缀，组成 32 位的字符串
        $orderno = $defaultPrefix.time().mt_rand(10000, 99999);//17+15位

        return $orderno;
    }

    /**
     * 返回毫秒级 时间戳
     * @return string
     */
    private function getTimestamp()
    {
        return time() . substr(microtime(), 2, 3);
    }

    /**
     * 生成签名
     * @param $params
     * @return bool|string
     */
    public function generateSign($params) {
        #第一步：对参数按照key=value的格式，并按照参数名ASCII字典序排序如下：
        $sign = $this->getSignContent($params);

        #第二步：拼接API密钥：
        $sign .= '&appSecret='.$this->getAppSecret();

        $stringSignTemp = strtoupper(md5($sign));

        return $stringSignTemp;
    }

    //处理参数为key=value 为空跳过
    private function getSignContent($params) {

        #第一步：对参数按照key=value的格式，并按照参数名ASCII字典序排序如下：
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
//                $v = $this->characet($v, $this->postCharset);

                $stringToBeSigned .= '&'.$k.'='.$v;

                $i++;
            }
        }

        $stringToBeSigned = substr($stringToBeSigned, 1);

        unset ($k, $v);
        return $stringToBeSigned;
    }


    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //              $data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }

        return $data;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     *@功能说明:判断是否是手机
     * @param string $val 判断的数据
     * @return bool
     */
    protected function is_mobile($val){
        return preg_match("/^1[3|4|5|6|7|8|9][0-9]\d{8}$/",$val);
    }

    private function error($msg)
    {
        $arr = [
            #'result' => 'request bad',
            'result' => 0,
            #'code' => 500,
            'message' => $msg
        ];
        die(json_encode($arr, \JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return bool/array
     * bool  : 出现必需字段为空是返回false 并记录该字段
     * array : 存储必需字段的键值数组
     */
    private function checkMustField()
    {
        $mustField = $this->mustFieldSet;
        $mustFieldSetMapping = $this->mustFieldSetMapping;

        $checkRes = [];
        foreach ($mustField as $item) {
            $getter = 'get'.ucfirst($item);
            $fieldValue = $this->$getter();
            if($this->checkEmpty($fieldValue)) {

                $this->mustField = $item;
                #break;
                return false;
            }
            $checkRes[ $mustFieldSetMapping[$item] ] = $fieldValue;
        }
        return $checkRes;
    }

    /**
     * 记录不为空的可选参数
     * @return array
     */
    private function checkExistField()
    {
        $needField = $this->needFieldSet;
        $needFieldSetMapping = $this->needFieldSetMapping;
        $fieldArr = array();
        foreach ($needField as $item) {
            $getter = 'get'.ucfirst($item);
            $fieldValue = $this->$getter();
            if($this->checkEmpty($fieldValue)) {
                continue;
            }
            $fieldArr[ $needFieldSetMapping[$item] ] = $fieldValue;
        }
        return $fieldArr;
    }

    #记录回调的每一步
    public function recordNotifyStep($stepName, $putContents, $flur = false)
    {
        static $logStack=[];

        $curYmd = date('Ymd');
        $curDate = date('Y-m-d H:i:s');

        $logStack[] = [$stepName, $putContents];

        $txt = '';

        if($flur) {
            foreach ($logStack as $index => $item) {
                list($stepName, $putContents) = $item;
                $txt .= '     '.$stepName.PHP_EOL.'            '.$putContents.PHP_EOL;

            }
            file_put_contents(
                'zhimalog/notify_result'.$curYmd.'.txt',
                '['.$curDate.']'.PHP_EOL.'>>>>>>>>>'.PHP_EOL.$txt.PHP_EOL.'>>>>>>>>>'.PHP_EOL,
                \FILE_APPEND
            );
        }
    }
}
