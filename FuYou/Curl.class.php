<?php
/**
 * Created by PhpStorm.
 * User: TRANSN
 * Date: 2017/5/6
 * Time: 13:03
 */
namespace FuYou;

class Curl{
    /**
     * POST 数据
     * @param $url
     * @param $params
     * @return string
     */
    public static  function curlPost( $url, $params, $timeout=120 ){

        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            $http_user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:61.0) Gecko/20100101 Firefox/61.0";
        }else{
            $http_user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 1.0);
        curl_setopt($ch, CURLOPT_POST, true);   // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);    // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_USERAGENT, $http_user_agent); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if( self::isArrayParam( $params ) ){
            curl_setopt($ch, CURLOPT_POSTFIELDS, self::data_encode($params));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $data = curl_exec($ch);
        curl_close ($ch);
        return $data;
    }


    public static  function  curlPostHttps($url, $data,$header=[], $timeout = 60){

        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            $http_user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:61.0) Gecko/20100101 Firefox/61.0";
        }else{
            $http_user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 1.0);
        curl_setopt($ch, CURLOPT_POST, true);   // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);    // 设置超时限制防止死循环
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_USERAGENT, $http_user_agent); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if( self::isArrayParam( $data ) ){
            curl_setopt($ch, CURLOPT_POSTFIELDS, self::data_encode($data));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if($ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }
        $data = curl_exec($ch);
        curl_close ($ch);
        return $data;
    }

    public static  function  curlGetHttps($url, $timeout = 30){

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);    // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
        $data = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $data;    //返回json对象

    }

    //抓取页面内容
    public static function curl_get_content($url,$header=[]){

        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            $http_user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:61.0) Gecko/20100101 Firefox/61.0";
        }else{
            $http_user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_USERAGENT, $http_user_agent); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if($ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 文件上传
     * @param $url
     * @param string $type
     * @param array $param
     * @param array $up_file
     * @param bool $post_file_name
     * @return bool|string
     */
    public static function curl_upload_file($url,$type="POST",$param=array(),$up_file=array(),$post_file_name = true) {
        $res = false;
        $type = strtoupper($type);
        if(is_string($url) && preg_match("/^http(s)?:\/\//",$url) && in_array($type,array('POST','GET'))){
            $c_url = $url;
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $c_url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER ,0);
            curl_setopt($ch,CURLOPT_HEADER,0);
            if($type == 'POST'){
                curl_setopt($ch, CURLOPT_POST, 1);
                if(is_array($param) && !empty($param)) {$param = array_map('trim',$param);}
                if(is_array($up_file) && !empty($up_file)) {
                    foreach ($up_file as $f_key => $f_val){
                        $file_info = $f_val->getInfo();
                        if($post_file_name){
                            if(class_exists('\CURLFile')){
                                $param[$f_key] = new \CURLFile($file_info['tmp_name'],$file_info['type'],$file_info['name']);
                            }else{
                                $param[$f_key] = '@'.$file_info['tmp_name'].";type=".$file_info['type'].";filename=".$file_info['name'];
                            }

                        } else {
                            if(class_exists('\CURLFile')){
                                $param[$f_key] = new \CURLFile($file_info['tmp_name']);
                            }else{
                                $param[$f_key] = '@'.$file_info['tmp_name'];
                            }
                        }
                    }
                }
                if(is_array($param) && !empty($param)){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
                }
            }
            ob_start();
            curl_exec($ch);
            $ch_error = curl_error($ch);
            $res = ob_get_contents();
            curl_close($ch);
            ob_end_clean();
            if($ch_error){var_dump($ch_error);ob_flush();flush();}
        }
        return $res;
    }

    private static function data_encode($data, $keyprefix = "", $keypostfix = "") {
        assert( is_array($data) );
        $vars=null;
        foreach($data as $key=>$value) {
            if(is_array($value)){
                $vars .= self::data_encode($value, $keyprefix.$key.$keypostfix.("["), ("]"));
            }else{
                $vars .= $keyprefix.$key.$keypostfix."=".urlencode($value)."&";
            }
        }
        return $vars;
    }
    private static function isArrayParam($param){
        if( is_array($param) ){
            foreach ($param as $key => $val) {
                if( !is_array( $val ) ){
                    if( substr( $val, 0, 1 ) == '@' ){
                        //如果是file域，则不认为是数组
                        return false;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }
}