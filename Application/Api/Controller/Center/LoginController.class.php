<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明:   登录注册等post处理
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2018/1/10
// +----------------------------------------------------------------------

namespace Api\Controller\Center;
use Api\Controller\Core\CommonController;
use XBCommon\XBCache;

class LoginController extends CommonController  {

    const T_TABLE='mem_info';
    const T_TIMESTAMP='sys_timestamp';
    const T_MOBILE_CODE='sms_code';

    /**
     * @功能说明: 通过手机注册
     * @传输格式: get提交
     * @提交网址: /center/login/reg
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"mobile":"17602186118","code":"2","pwd":"123456"},"isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，注册成功！'}
     */
    public function reg(){
        $para = get_json_data();
        $para2=get_json_data(1); //接收参数 data外面参数
        //手机号码格式不正确
        if(!is_mobile($para['mobile'])){
            AjaxJson(0,0,'很抱歉，手机号码格式不正确！');
        }
        //请使用英文与数字组合且不低于6位
        if(!$para['pwd']){
            AjaxJson(0,0,'很抱歉，密码不能为空！');
        }
//        if(empty($para['ticksid'])){
//            exit(json_encode(array('result'=>0,'message'=>'很抱歉，未能获取正确的时间戳标识!')));
//        }
//        //时间戳的判断
//        $db=M(self::T_TIMESTAMP);
//        $Val=$db->where(array('ID'=>$para['ticksid']))->getField('Val'); //从数据库获取
//        if(empty($Val)){
//            exit(json_encode(array('result'=>0,'message'=>'很抱歉，时间戳不存在!')));
//        }
//        if($para['ticks']!=$Val){
//            exit(json_encode(array('result'=>0,'message'=>'很抱歉，传递的时间戳不正确!')));
//        }
        //判断验证码是否合法
        $mdb=M(self::T_MOBILE_CODE);
        $code=$mdb->where(array('Name'=>$para['mobile'],'Code'=>$para['code']))->order('UpdateTime ASC')->find();

        if($code){
            //1分钟内同一个手机只能发送一次验证码
            $curtime=strtotime(date('Y-m-d H:i:s'));
            $lasttime=strtotime($code['UpdateTime']);
            $time=($curtime-$lasttime)/60;  //分钟

            if($time < 20){
                //注册保存，查询是否已存在
                $db=M(self::T_TABLE);
                $IsExitMobile=$db->where(array('Mobile'=>$para['mobile'],"IsDel"=>'0'))->find();
                if($IsExitMobile){
                    AjaxJson(0,0,'很抱歉，此用户已存在！');
                }
                //注册来源
                $Retype='1';//默认是 安卓
                if($para2['client']=='ios'){
                    $Retype='2';
                }
                $data=array(
                    'UserName'=>$para['mobile'],
                    'NickName'=>'guser_'.rand(),
                    'Mobile'=>$para['mobile'],
                    'Password'=>$para['pwd'],
                    'Retype'=>$Retype,
                    'RegTime'=>date('Y-m-d H:i:s')
                );
                $result=$db->add($data);
                if($result){
                    //更新 会员账号(UID_ID)  字段
                    $db->where(array('ID'=>$result))->save(array('MemAccount'=>'UID_'.$result));
                    send_mem_notics($result,"欢迎成为本站会员，请妥善保管自己的账号。");
                    self_sendjuan($result,'1');//注册成功立送
                    AjaxJson(0,1,'恭喜您,注册成功！');
                }else{
                    AjaxJson(0,0,'很抱歉，保存数据失败！');
                }
            }else{
                //验证码已失效
                AjaxJson(0,0,'很抱歉，您的验证码已失效！');
            }
        }else{
            //无效验证码
            AjaxJson(0,0,'很抱歉，您的验证码无效！');
        }
    }

    /**
     * @功能说明: 忘记密码，通过手机找回
     * @传输格式: get提交
     * @提交网址: /center/login/forget
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"mobile":"17602186118","code":"123456","pwd":"123456","confpwd":"123"},"isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，重置密码成功！'}
     */
    public function forget(){

        $para = get_json_data();
        if(!$para){
            AjaxJson(0,0,'很抱歉，提交的数据非法！');
        }
        //手机号码格式不正确
        if(!is_mobile($para['mobile'])){
            AjaxJson(0,0,'很抱歉，手机号码格式不正确！');
        }
        //请使用英文与数字组合且不低于6位
        if(!$para['pwd']){
            AjaxJson(0,0,'很抱歉，密码不能为空！');
        }

        if($para['pwd'] != $para['confpwd']){
            AjaxJson(0,0,'很抱歉，两次输入的密码不一致！');
        }

        //判断验证码是否合法
        $mdb=M(self::T_MOBILE_CODE);
        $code=$mdb->where(array('Name'=>$para['mobile'],'Code'=>$para['code']))->order('UpdateTime ASC')->find();

        if($code){
            //1分钟内同一个手机只能发送一次验证码
            $curtime=strtotime(date('Y-m-d H:i:s'));
            $lasttime=strtotime($code['UpdateTime']);
            $time=($curtime-$lasttime)/60;  //分钟

            if($time < 20){
                $db=M(self::T_TABLE);
                $mem=$db->where(array('Mobile'=>$para['mobile'],'Status'=>array('neq','3'),'IsDel'=>0))->find();
                if(!$mem){
                    AjaxJson(0,0,'很抱歉，未找到相关的信息！');
                }

                // if($mem['Status'] != 1){
                //     AjaxJson(0,0,'很抱歉，该账号禁用，无法重置密码！');
                // }

                //重置密码
                $data=array(
                    'Password'=>$para['pwd'],
                    'UpdateTime'=>date('Y-m-d H:i:s')
                );
                $result=$db->where(array('ID'=>$mem['ID']))->save($data);
                if($result){
                    AjaxJson(0,1,'密码重置成功！');
                }else{
                    AjaxJson(0,0,'很抱歉，保存数据失败！');
                }

            }else{
                //验证码已失效
                AjaxJson(0,0,'很抱歉，您的验证码已失效！');
            }

        }else{
            //验证码已失效
            AjaxJson(0,0,'很抱歉，您的验证码已失效！');
        }
    }

    /**
     * @功能说明: 用户名和密码直接登录
     * @传输格式: get提交
     * @提交网址: /center/login/login
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"username":"17602186118","token":"21111111","ticksid":"1","ticks":"BE8C02977DB77AB35D4D476CF9D3AD"},"isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，登录成功！','data'} 返回加密后的私有token!
     */
    public function login(){
        echo 2;exit;
        $para = get_json_data();
        if(!$para){
            AjaxJson(0,0,'很抱歉，提交的数据非法！');
        }

        if(empty($para['ticksid'])){
            AjaxJson(0,0,'很抱歉，携带参数不正确！');
        }

        // if(!file_exists("alipay.txt")){ $fp = fopen("alipay.txt","wb"); fclose($fp);  }
        // $str = file_get_contents('alipay.txt');
        // $str .= " - trade_no:".json_encode($para)." - ".date("Y-m-d H:i:s")."\r\n";
        // $fp = fopen("alipay.txt","wb");
        // fwrite($fp,$str);
        // fclose($fp);

        //时间戳的判断
        $db=M(self::T_TIMESTAMP);
        $Val=$db->where(array('ID'=>$para['ticksid']))->getField('Val'); //从数据库获取
        if(empty($Val)){
            AjaxJson(0,0,'很抱歉，携带参数不正确！');
        }
        if($para['ticks']!=$Val){
            AjaxJson(0,0,'很抱歉，携带参数不正确！');
        }

        //登录判断
        $db=M(self::T_TABLE);
        $where=array(
            'Mobile'=>$para['username'],
            //'Token'=>$para['token'],
            'IsDel' => 0,
        );
        $member=$db->where($where)->find();
        if(!empty($member)){

            if($member['Status']==3){
                AjaxJson(0,0,'很抱歉，该账号被禁用，无法登录！');
            }

            $key = 'KEY'.strtoupper(substr(md5($member['Mobile'].$Val),1,29));
            $iv = 'IV'.strtoupper(substr(md5($member['Password'].$para['ticksid']),1,14));

            $token = strtoupper(substr(md5($member['Mobile'].$para['ticksid']),0,30).substr(md5($member['Password'].$Val),2,30));

            if($token <> $para['token']){
                AjaxJson(0,0,'很抱歉，账号密码不正确！',array('token'=>$token,'key'=>$key,'iv'=>$iv));
            }

            XBCache::Remove($member['Token']);

            $data=array(
                'LastLoginTime'=>$member['LoginTime'],
                'LoginTime'=>date('Y-m-d H:i:s'),
                'LastLoginIP'=>$member['LoginIP'],
                'LoginIP'=>get_client_ip(),
                'LastIpCity'=>$member['IpCity'],
                'IpCity'=>ip_to_address(get_client_ip()),
                'Token'=> $token,  //会员token
                'KEY' => $key,  //会员私有密钥
                'IV' => $iv,  //会员私有向量
            );
            $result=$db->where(array('ID'=>$member['ID']))->save($data);

            //登录成功后，更新登录信息
            if($result){
                $AppInfo['ID']=$member['ID'];  //会员ID
                $AppInfo['UserName']=$member['UserName'];  //会员用户名
                $AppInfo['Mobile']=$member['Mobile'];  //会员手机号
                $AppInfo['NickName']=$member['NickName'];  //会员昵称
                $AppInfo['Status']=$member['Status'];  //状态
                $AppInfo['Token']=$token;  //会员token
                $AppInfo['KEY']=$key;  //会员私有密钥
                $AppInfo['IV']=$iv;  //会员私有向量
                $AppInfo['TimeOut']=date('Y-m-d H:i:s');  //会员登录过期时间
                XBCache::Insert($token,$AppInfo);

                S('UserInfo'.$member['ID'],null);
                
                //返回私有向量和密钥加密后的数据
                $output=array(
                    'result'=>1,
                    'message'=>'登录成功!',
                    'data'=>array('KEY'=>$key,'IV'=>$iv,'Token'=>$token)
                );
                AjaxJson(0,1,'登录成功!',$AppInfo);
            }else{
                AjaxJson(0,0,'很抱歉，更新登录信息错误！');
            }

        }else{
            AjaxJson(0,0,'很抱歉，账号不存在！');
        }
    }
    /**
     * @功能说明: 手机号验证码直接登录(快捷登录)
     * @传输格式: get提交
     * @提交网址: /center/login/login2
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"username":"17602186118","msgcode":"21111111","ticksid":"1","ticks":"BE8C02977DB77AB35D4D476CF9D3AD"},"isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，登录成功！','data'} 返回加密后的私有token!
     */
    public function login2(){
        $para = get_json_data();
        if(!$para){
            AjaxJson(0,0,'很抱歉，提交的数据非法！');
        }
        if(empty($para['ticksid'])){
            AjaxJson(0,0,'很抱歉，携带参数不正确！');
        }


        $code=M(self::T_MOBILE_CODE)->where(array('Name'=>$para['username'],'Code'=>$para['msgcode']))->order('UpdateTime ASC')->find();
        if($code){
            //1分钟内同一个手机只能发送一次验证码
            $curtime=strtotime(date('Y-m-d H:i:s'));
            $lasttime=strtotime($code['UpdateTime']);
            $time=($curtime-$lasttime)/60;  //分钟
            if($time < 20){
                //登录操作
                 //时间戳的判断
                $db=M(self::T_TIMESTAMP);
                $Val=$db->where(array('ID'=>$para['ticksid']))->getField('Val'); //从数据库获取
                if(empty($Val)){
                    AjaxJson(0,0,'很抱歉，携带参数不正确！');
                }
                if($para['ticks']!=$Val){
                    AjaxJson(0,0,'很抱歉，携带参数不正确！');
                }
                //登录判断
                $db=M(self::T_TABLE);
                $where=array(
                    'Mobile'=>$para['username'],
                    //'Token'=>$para['token'],
                    'IsDel' => 0,
                );
                $member=$db->where($where)->find();
                if(!empty($member)){

                    if($member['Status']==3){
                        AjaxJson(0,0,'很抱歉，该账号被禁用，无法登录！');
                    }
                    $key = 'KEY'.strtoupper(substr(md5($member['Mobile'].$Val),1,29));
                    $iv = 'IV'.strtoupper(substr(md5($member['Password'].$para['ticksid']),1,14));

                    $token = strtoupper(substr(md5($member['Mobile'].$para['ticksid']),0,30).substr(md5($member['Password'].$Val),2,30));

                    XBCache::Remove($member['Token']);

                    $data=array(
                        'LastLoginTime'=>$member['LoginTime'],
                        'LoginTime'=>date('Y-m-d H:i:s'),
                        'LastLoginIP'=>$member['LoginIP'],
                        'LoginIP'=>get_client_ip(),
                        'LastIpCity'=>$member['IpCity'],
                        'IpCity'=>ip_to_address(get_client_ip()),
                        'Token'=> $token,  //会员token
                        'KEY' => $key,  //会员私有密钥
                        'IV' => $iv,  //会员私有向量
                    );
                    $result=$db->where(array('ID'=>$member['ID']))->save($data);
                    //登录成功后，更新登录信息
                    if($result){
                        $AppInfo['ID']=$member['ID'];  //会员ID
                        $AppInfo['UserName']=$member['UserName'];  //会员用户名
                        $AppInfo['Mobile']=$member['Mobile'];  //会员手机号
                        $AppInfo['NickName']=$member['NickName'];  //会员昵称
                        $AppInfo['Status']=$member['Status'];  //状态
                        $AppInfo['Token']=$token;  //会员token
                        $AppInfo['KEY']=$key;  //会员私有密钥
                        $AppInfo['IV']=$iv;  //会员私有向量
                        $AppInfo['TimeOut']=date('Y-m-d H:i:s');  //会员登录过期时间
                        XBCache::Insert($token,$AppInfo);
                        S('UserInfo'.$member['ID'],null);
                        
                        //返回私有向量和密钥加密后的数据
                        $output=array(
                            'result'=>1,
                            'message'=>'登录成功!',
                            'data'=>array('KEY'=>$key,'IV'=>$iv,'Token'=>$token)
                        );
                        AjaxJson(0,1,'登录成功!',$AppInfo);
                    }else{
                        AjaxJson(0,0,'很抱歉，更新登录信息错误！');
                    }

                }else{
                    AjaxJson(0,0,'很抱歉，账号不存在！');
                }
            }else{
                //验证码已失效
                AjaxJson(0,0,'很抱歉，您的验证码已失效！');
            }
        }else{
            //无效验证码
            AjaxJson(0,0,'很抱歉，您的验证码无效！');
        }
       
    }

    /**
     * @功能说明: 验证图形验证码
     * @传输方式: get
     * @提交网址: /center/login/Captcha
     * @提交信息:  {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function Captcha(){

        $para = get_json_data(); //接收参数

        $res['Path'] = "http://".$_SERVER['HTTP_HOST']."/api.php/home/public/verify?mid=".$para['mobile'];
        AjaxJson(0,1,'获取成功!',$res);
    }

    /**
     * @功能说明: 退出操作
     * @传输方式: 私有token,明文提交，明文返回
     * @提交网址: /center/login/layout
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"ios","package":"11","version":"1.1","isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'退出成功!'}
     */
    public function layout(){
        $para=get_json_data(); //接收参数

        $mem = getUserInfo(get_login_info('ID'));

        if(!get_login_info('Token')){
            AjaxJson(0,0,'已经退出登录！');
        }

        $data = array(
            'Key' => '',
            'IV' => '',
            'Token' => ''
        );
        $result = M(self::T_TABLE)->where(array('ID'=>$mem['ID']))->save($data);

        if($result){
            XBCache::Remove($para['token']);
            S('UserInfo'.$mem['ID'],null);
            AjaxJson(0,1,'退出成功！');
        }else{
            AjaxJson(0,0,'退出处理失败，请稍后重试！');
        }
    }

}