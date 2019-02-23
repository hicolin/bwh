<?php
/**
 * 
 *
 *
 * 修改时间: 2017-06-14 16:30
 * 功能说明:IP地址查询。
 */
namespace XBCommon;

use Think\Controller;
class IpAddress
{
    /**
     * @功能说明:根据IP获取区域信息及网络服务商信息
     * @param string  $ip  传入的IP地址
     * @return array
     */
    function GetAreas($ip){
        //优先从本地数据库匹配IP地址库
        $model=M('sys_ipaddress');
        $IpInfo=$model->where(array('IpAddress'=>$ip))->find();
        $CurTime=date('Y-m-d H:i:s'); //获得当前日期时间
        $Day=floor((strtotime($CurTime)-strtotime($IpInfo['UpdateTime']))/86400);  //求差值，天数
        $result=array();
        if(!empty($IpInfo) && $Day<=7){
            //如果查询到结果，并且IP信息的更新日期未超出7天，返回数据库的查询结果
            $arr['code']=0;
            $arr['data']=$IpInfo;
            return $arr;
        }else{
            //未查询到IP信息或者IP信息的更新日期已超出7天，到阿里云获取
            $data=$this->GetIpInfo($ip);

            if($data['code']==0 && !empty($data['data'])){
                //为了让返回的字段与数据库字段相符，重新数据集合
                $result['data']['Area']=$data['data']['area'];
                $result['data']['AreaID']=$data['data']['area_id'];
                $result['data']['City']=$data['data']['city'];
                $result['data']['CityID']=$data['data']['city_id'];
                $result['data']['Country']=$data['data']['country'];
                $result['data']['CountryID']=$data['data']['country_id'];
                $result['data']['County']=$data['data']['county'];
                $result['data']['CountyID']=$data['data']['county_id'];
                $result['data']['IpAddress']=$data['data']['ip'];
                $result['data']['Isp']=$data['data']['isp'];
                $result['data']['IspID']=$data['data']['isp_id'];
                $result['data']['Region']=$data['data']['region'];
                $result['data']['RegionID']=$data['data']['region_id'];
                $result['data']['UpdateTime']=date('Y-m-d H:i:s');
                //如果获取到了信息，更新数据库的已过期的IP记录,或新增IP记录
                if($IpInfo['IpAddress']==$ip){
                    $model->where(array('IpAddress'=>$ip))->save($result['data']);
                }else{
                    $model->add($result['data']);
                }
            }
            $result['code']=$data['code'];
            return $result;
        }
    }

    /**
     * @功能说明:从阿里云的接口中获取IP信息
     * @param string  $ip  传入的IP地址
     * @return array
     */
    function GetIpInfo($ip){
        $cache=new CacheData();
        $code=$cache->BasicInfo();  //读取缓存
        $host = "https://dm-81.data.aliyun.com";
        $path = "/rest/160601/ip/getIpInfo.json";
        $method = "GET";
        $appcode = $code['AppCode'];  //插入缓存点，需在后台基本设置中填写APPCODE
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "ip=$ip";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();//创建一个新的会话
        //以下设置会话传输选项
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT,2);  //超时处理,超过2秒返回数据
        curl_setopt($curl, CURLOPT_HEADER, false); //不输出头部信息
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        //以json的方式返回数组
        $res=json_decode(curl_exec($curl),true);
        return $res;
    }

}