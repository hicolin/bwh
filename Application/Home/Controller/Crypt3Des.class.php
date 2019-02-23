<?php

namespace Home\Controller;

/**
 *
 * PHP版3DES加解密类
 *
 * 可与java的3DES(DESede)加密方式兼容
 *
 */
class Crypt3Des {

    /**
     * 3des加密
     * @param $str 待加密的字符串
     * @param $key 加密用的密钥
     * @return string
     */
    public static function encrypt($str,$key){
        $str = self::pkcs5_pad($str, 8);
        if (strlen($str) % 8) {
            $str = str_pad($str,strlen($str) + 8 - strlen($str) % 8, "\0");
        }
        $sign = openssl_encrypt($str,'DES-EDE3' ,$key,OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        return strtoupper(bin2hex($sign));
    }

    /**
     * des加密，ecb
     * @param $str
     * @param $key
     * @return string
     */
    public static function encrypt_base64($str,$key){
        $str = self::pkcs5_pad($str, 8);
        if (strlen($str) % 8) {
            $str = str_pad($str,strlen($str) + 8 - strlen($str) % 8, "\0");
        }
        $sign = openssl_encrypt($str,'DES-ECB' ,$key,OPENSSL_RAW_DATA);
        return base64_encode($sign);
    }

    /**
     * des解密
     * @param $str
     * @param $key
     * @return string
     */
    public static function decrypt_base64($str,$key){
        $sign = openssl_decrypt(base64_decode($str),'DES-ECB' ,$key,OPENSSL_RAW_DATA);
        return $sign;
    }

    /**
     * 数据填充
     * @param $text
     * @param $blocksize
     * @return string
     */
    private static function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}

// 开始64位编码
// $base64=base64_encode($spid."$".$des3);
// echo "base64:".$base64."<br>";
?>