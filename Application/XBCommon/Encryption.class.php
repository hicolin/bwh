<?php
/**
 * 
 *
 *
 * 修改时间: 2017-07-05 18:39
 * 功能说明:
 */
namespace XBCommon;

use Think\Controller;

class Encryption extends Controller
{
/*
 * 功能说明:使用随机密钥进行再次异或处理
 * @param $str 需要使用密钥异或处理的字符串
 * @param $encrypt_key  加密密钥
 * @return string
 */
    public function key_encrypt($str, $encrypt_key) {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($str); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $str[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }
}