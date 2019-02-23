<?php
namespace Extend;
class Rbpay{
    //签名函数
    public function  createSign ($paramArr,$apiKey) {
        global $appSecret;
        $sign = $appSecret;
        ksort($paramArr);
        foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '' && $key != 'sign' && $key != 'sign_type') {
                $sign .= $key.'='.$val.'&';
            }
        }

        $sign = substr ( $sign,0,(strlen ( $sign )-1));
        $sign.=$appSecret;
        $sign = md5($sign.$apiKey);
        return $sign;
    }
    /**
     * 生成一个随机的字符串作为AES密钥
     *
     * @param number $length
     * @return string
     */
    public function  generateAESKey($length=16){
        $baseString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $AESKey = '';
        $_len = strlen($baseString);
        for($i=1;$i<=$length;$i++){
            $AESKey .= $baseString[rand(0, $_len-1)];
        }
        return $AESKey;
    }
    /**
     * 通过RSA，使用融宝公钥，加密本次请求的AESKey
     *
     * @return string
     */
    public function  RSAEncryptkey($encryptKey,$reapalPublicKey){
        $public_key= $this->getPublicKey($reapalPublicKey);

        $pu_key = openssl_pkey_get_public($public_key);//这个函数可用来判断公钥是否是可用的  

        openssl_public_encrypt($encryptKey,$encrypted,$pu_key);//公钥加密  

        return base64_encode($encrypted);



    }
    /**
     * 通过RSA，使用融宝公钥，加密本次请求的AESKey
     *
     * @return string
     */
    public function  RSADecryptkey($encryptKey,$merchantPrivateKey){
        $private_key= $this->getPrivateKey($merchantPrivateKey);

        $pi_key =  openssl_pkey_get_private($private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id

        openssl_private_decrypt(base64_decode($encryptKey),$decrypted,$pi_key);//私钥解密
        return $decrypted;


    }
    /**
     * 通过AES加密请求数据
     *
     * @param array $query
     * @return string
     */
    public function  AESEncryptRequest($encryptKey,array $query){

        return $this->encrypt(json_encode($query),$encryptKey);

    }
    /**
     * 通过AES解密请求数据
     *
     * @param array $query
     * @return string
     */
    public function  AESDecryptResponse($encryptKey,$data){
        return $this->decrypt($data,$encryptKey);

    }
    public function  encrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = $this->pkcs5_pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public function  pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    public function  decrypt($sStr, $sKey) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
        );

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    public function  getPrivateKey($cert_path) {
        $pkcs12 = file_get_contents ( $cert_path );

        return $pkcs12;
    }
    public function  getPublicKey($cert_path) {
        $pkcs12 = file_get_contents ( $cert_path );
        return $pkcs12;
    }

    //已废弃
    private function  sendHttpRequest2($params, $url) {
        $opts = $this->getRequestParamString ( $params );
//        echo $opts;
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false);//不验证证书
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false);//不验证HOST
        curl_setopt ( $ch, CURLOPT_SSLVERSION, 3);
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
            'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
        ) );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $opts );

        /**
         * 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
         */
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

        // 运行cURL，请求网页
        $html = curl_exec ( $ch );
        var_dump($html);exit;
        // close cURL resource, and free up system resources
        curl_close ( $ch );
        return $html;
    }

    //可用
   private function sendHttpRequest($data = null,$url)
    {
        $data = $this->getRequestParamString ( $data );
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type:application/x-www-form-urlencoded;charset=UTF-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //$output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        return $output;
       // return json_decode($output,true);
    }

    /**
     * 组装报文
     *
     * @param unknown_type $params
     * @return string
     */
    public function  getRequestParamString($params) {
        $params_str = '';
        foreach ( $params as $key => $value ) {
            $params_str .= ($key . '=' . (!isset ( $value ) ? '' : urlencode( $value )) . '&');
        }
        return substr ( $params_str, 0, strlen ( $params_str ) - 1 );
    }


    public function  send($paramArr, $url, $apiKey, $reapalPublicKey, $merchant_id){
        //生成签名
        $sign = $this->createSign($paramArr,$apiKey);

        $paramArr['sign'] = $sign;
        //生成AESkey
        $generateAESKey = $this->generateAESKey();
        $request = array();
        $request['merchant_id'] = $merchant_id;
        //加密key
        $request['encryptkey'] = $this->RSAEncryptkey($generateAESKey,$reapalPublicKey);
        //加密数据
        $request['data'] = $this->AESEncryptRequest($generateAESKey,$paramArr);
        return $this->sendHttpRequest($request,$url);
    }




//签名函数
    public function  orderstr ($paramArr) {
        global $appSecret;
        $sign = $appSecret;

        ksort($paramArr);
        foreach ($paramArr as $key => $val) {

            if ($key != '' && $val != '' && $key != 'sign' && $key != 'sign_type') {
                $sign .= $key.'='.$val.'&';
            }
        }
//        echo $sign,"\n";
        $sign = substr ( $sign,0,(strlen ( $sign )-1));
        return $sign;
    }



    /**RSA签名函数

     * $data为待签名数据，比如URL

     * 签名用游戏方的保密私钥，必须是没有经过pkcs8转换的.结果需要用base64编码以备在URL中传输

     * return Sign 签名

     */

    public function  sign_rsa($data,$merchantPrivateKey) {
        $algo = "SHA256";

        $priKey = file_get_contents($merchantPrivateKey);

        //转换为openssl密钥，必须是没有经过pkcs8转换的私钥

        $res = openssl_get_privatekey($priKey );

        //调用openssl内置签名方法，生成签名$sign

        openssl_sign($data, $sign, $res, $algo );

        openssl_free_key($res);

        $sign = base64_encode($sign);

        return $sign;

    }



    /**RSA验证签名

     * $data为要验证的数据字符串

     * $sign是需要验证的签名数据，是直接从URL中取到的$_POST["sign"]型参数，函数里面会进行base64_decode的。

     * return 验签是否通过，为BOOL值

     */

    public function verify_rsa($paramArr, $sign,$reapalPublicKey,$merchantPrivateKey)  {
        $algo = "SHA256";

        $data = $this->orderstr($paramArr);
//        echo "aaaaaaa";
//        echo $data,"\n";
        //读取公钥文件,也就是签名方公开的公钥，用来验证这个data是否真的是签名方发出的
        $pubKey = file_get_contents($reapalPublicKey);
        $res = openssl_get_publickey($pubKey);
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res,$algo );
        openssl_free_key($res);
        return $result;

    }



    public function  send_rsa($paramArr, $url, $merchantPrivateKey, $reapalPublicKey, $merchant_id){
        //生成签名
        $data = $this->orderstr($paramArr);

        $paramArr['sign'] = $this->sign_rsa($data,$merchantPrivateKey);
        //生成AESkey
        $generateAESKey = $this->generateAESKey();
       
        $request = array();
        $request['merchant_id'] = $merchant_id;
        //加密key
        $request['encryptkey'] = $this->RSAEncryptkey($generateAESKey,$reapalPublicKey);
        //加密数据
        $request['data'] = $this->AESEncryptRequest($generateAESKey,$paramArr);
  
        return $this->sendHttpRequest($request,$url);
    }

    public function verifypay($paramArr,$url,$merchantPrivateKey,$reapalPublicKey,$merchant_id){
        $result = $this->send_rsa($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);
        $response = json_decode($result,true);
        $encryptkey = $this->RSADecryptkey($response['encryptkey'],$merchantPrivateKey);
        $decryData = $this->AESDecryptResponse($encryptkey, $response['data']);
        $jsonObject = json_decode($decryData,true);
        if(isset($jsonObject['sign'])){
            return $jsonObject;
        }else{
            return false;
        }
    }
}



?>