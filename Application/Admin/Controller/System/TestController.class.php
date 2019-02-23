<?php
/**
 * 
 *
 *
 * 修改时间: 2017-04-27 08:50
 * 功能说明:广告管理
 */
namespace Admin\Controller\System;
use Think\Cache;
use Think\Controller;
use XBCommon\XBCache;
class TestController extends  Controller
{
    public function ltoken()
    {
        //公共token的生成
        $cache=new \XBCommon\CacheData();
        $info=$cache->BasicInfo();
        $key=md5($info['Key']);
        $iv=substr(md5($info['IV']),8,16);
        $url=$info['SystemDomain'];
        $ltoken=encrypt_pkcs7($url,$key,$iv);
        return  $ltoken;

    }

    public function password(){
        $pwd='123456';
        $val='8573bd288fe4f0d8530ea5cb0ae4d4';
        $password=base64_encode(md5($pwd.$val));
        echo $password;
    }

    public function jiami(){
        $key=md5(get_basic_info('Key'));
        $iv=substr(md5(get_basic_info('IV')),8,16);
        $this->KEY = $key;
        $this->IV = $iv;
        $data='{"username":"18130046265","pwd":"NGEzNGQ5OTRmMmQ2Y2RiNTQ5ZmM0NjUyY2IwZjYxODU=","ticksid":346,"ticks":"8573bd288fe4f0d8530ea5cb0ae4d4"}';
        $result=encrypt_pkcs7($data,$this->KEY,$this->IV);
        echo $result;
    }

    public function login(){
        $user='18130046265';
        $pwd='123456';

        $ltoken=$this->ltoken();

        $url='http://192.168.1.103/api.php/core/tool/timestamp';
        $jsonstr='{"token":"'.$ltoken.'","client":"pc","ver":"1.1"}';

//        var_dump($jsonstr);exit;
        $timeinfo=http_post_json($url,$jsonstr);

        var_dump($timeinfo[1]);
    }

    public function cache($key=null){
        $key='token0001';
        if(XBCache::GetCache($key)==null){
            $data=array(
                'token'=>$key,
                'user'=>'18130046265',
                'logintime'=>'2017-10-12 09:52:00'
            );
            XBCache::Insert($key,$data);
        }
        return XBCache::GetCache($key);
    }

    public function test()
    {
        
    }
}