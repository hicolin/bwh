<?php
namespace Api\Controller\Core;

class ToolController extends CommonController {
    const T_TABLE='sys_timestamp';
    const T_MEM_INFO='mem_info';
    const T_SYS_ADCONTENT='sys_adcontent';
    const T_MOBILE_CODE='sms_code';
    const S_TABLE='notice_message';

    /**
     * @功能说明: 获取服务器时间戳
     * @传输方式: get提交
     * @提交网址: /core/tool/timestamp
     * @提交方式: {"client":"android","package":"ceshi.app","version":"v1.1"}
     * @返回方式: {'result'=>1,'message'=>'恭喜您，获取时间戳成功！','data'}
     */
    public function timestamp()
    {
        //根据服务器当前时间，生成时间戳
        $data=array();
        $data['Time'] = time();

        $data['Val'] = strtoupper(substr(md5($data['Time']+mt_rand(100,999)),0,30));

        $db=M(self::T_TABLE);
        $result=$db->add($data);

        if(!$result){
            AjaxJson(0,0,'生成时间戳失败,请稍后重试！');
        }

        AjaxJson(0,1,'恭喜您，获取时间戳成功！',array('ID'=>$result,'Val'=>$data['Val']));
    }

    /**
     * @功能说明: 获取手机验证码
     * @传输方式: get提交
     * @提交网址: /core/tool/getcode
     * @提交方式：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"mobile":"13524311277","type":"1","captcha":"1"},"isaes":"0"}   type 2注册  1找回密码
     * @返回方式: {'result'=>1,'message'=>'恭喜您!,验证码获取成功!'}
     */
    public function getcode(){
        $para = get_json_data();

        if(!$para['type']){
            AjaxJson(0,0,'请携带发送短信类型参数！');
        }
        //判断手机格式
        if(!is_mobile($para['mobile'])){
            AjaxJson(0,0,'很抱歉,手机号码格式不正确！');
        }

        $verify = new \Think\Imgcode();
        $res_code = $verify->check($para['captcha']);
        if(!$res_code){
            AjaxJson(0,0,'抱歉，图形验证码不正确！');
        }

        $db = M(self::T_MOBILE_CODE);
        $code = $db->where(array('Name'=>$para['mobile']))->order('UpdateTime Asc')->find();
        $res = M(self::T_MEM_INFO)->where(array("Mobile"=>$para['mobile'],'IsDel'=>'0'))->find();

        if($para['type']==2 && $res){
            AjaxJson(0,0,'您已注册过账号！');
        }
        if($para['type']==1 && !$res){
            AjaxJson(0,0,'该账号尚未注册！');
        }
        if($para['type']==3 && !$res){
            AjaxJson(0,0,'该账号尚未注册！');
        }
        if($code){
            //1分钟内同一个手机只能发送一次验证码
            $curtime = strtotime(date('Y-m-d H:i:s'));
            $lasttime = strtotime($code['UpdateTime']);
            $time = ($curtime-$lasttime)/60;  //分钟
            if($time < 1){
                AjaxJson(0,0,'请求过于频繁,请于1分钟后尝试');
            }else{
                //删除过期的验证码
                $db->where(array('ID'=>$code['ID']))->delete();
            }
        }
        //发送验证码并记录入库
        $data=array(
            'Name'=>$para['mobile'],
            'Code'=>mt_rand(1000,9999),
            'UpdateTime'=>date('Y-m-d H:i:s'),
        );

        $code = $data['Code'];
        $msg = "请输入您的验证码：".$code."工作人员不会向您索要，请勿提供给任何人，如若泄露，责任自负";

        $result = send_message($para['mobile'],$msg);
        //$result = json_decode($res,true);

        if($result['result']=='success'){

            $res = $db->add($data);
            if($res){
                $sdata = array("UserID" => $res['ID'],  "Contents" => $msg, "SendTime" => date("Y-m-d H:i:s"));
                //$data1 = array("ObjectID"=>$para['mobile'],"Type"=>1,"Mode"=>1,"SendMess"=>$msg,"Status"=>1,"SendTime"=>date("Y-m-d H:i:s"),"Obj"=>1);
                M(self::S_TABLE)->add($sdata);
                AjaxJson(0,1,'恭喜您,验证码获取成功！');
            }else{
                AjaxJson(0,0,'很抱歉,验证码获取失败！');
            }
        }else{
            AjaxJson(0,0,'验证码获取失败,请稍后重试！');
        }
    }
    /**
     * @功能说明: 获取手机验证码(直接获取)
     * @传输方式: get提交
     * @提交网址: /core/tool/getcode2
     * @提交方式：{"client":"android","package":"ceshi.app","version":"v1.1","data":{"mobile":"13524311277","type":"1"},"isaes":"0"}   type 2注册  1找回密码 3快速登录
     * @返回方式: {'result'=>1,'message'=>'恭喜您!,验证码获取成功!'}
     */
    public function getcode2(){
        $para = get_json_data();

        if(!$para['type']){
            AjaxJson(0,0,'请携带发送短信类型参数！');
        }
        //判断手机格式
        if(!is_mobile($para['mobile'])){
            AjaxJson(0,0,'很抱歉,手机号码格式不正确！');
        }

        $db = M(self::T_MOBILE_CODE);
        $code = $db->where(array('Name'=>$para['mobile']))->order('UpdateTime Asc')->find();
        $res = M(self::T_MEM_INFO)->where(array("Mobile"=>$para['mobile'],'IsDel'=>'0'))->find();

        if($para['type']==2 && $res){
            AjaxJson(0,0,'您已注册过账号！');
        }
        if($para['type']==1 && !$res){
            AjaxJson(0,0,'该账号尚未注册！');
        }
        if($para['type']==3 && !$res){
            AjaxJson(0,0,'该账号尚未注册！');
        }
        if($code){
            //1分钟内同一个手机只能发送一次验证码
            $curtime = strtotime(date('Y-m-d H:i:s'));
            $lasttime = strtotime($code['UpdateTime']);
            $time = ($curtime-$lasttime)/60;  //分钟
            if($time < 1){
                AjaxJson(0,0,'请求过于频繁,请于1分钟后尝试');
            }else{
                //删除过期的验证码
                $db->where(array('ID'=>$code['ID']))->delete();
            }
        }
        //发送验证码并记录入库
        $data=array(
            'Name'=>$para['mobile'],
            'Code'=>mt_rand(1000,9999),
            'UpdateTime'=>date('Y-m-d H:i:s'),
        );

        $code = $data['Code'];
        $msg = "请输入您的验证码：".$code."工作人员不会向您索要，请勿提供给任何人，如若泄露，责任自负";

        $result = send_message($para['mobile'],$msg);
        //$result = json_decode($res,true);

        if($result['result']=='success'){

            $res = $db->add($data);
            if($res){
                $sdata = array("UserID" => $res['ID'],  "Contents" => $msg, "SendTime" => date("Y-m-d H:i:s"));
                //$data1 = array("ObjectID"=>$para['mobile'],"Type"=>1,"Mode"=>1,"SendMess"=>$msg,"Status"=>1,"SendTime"=>date("Y-m-d H:i:s"),"Obj"=>1);
                M(self::S_TABLE)->add($sdata);
                AjaxJson(0,1,'恭喜您,验证码获取成功！');
            }else{
                AjaxJson(0,0,'很抱歉,验证码获取失败！');
            }
        }else{
            AjaxJson(0,0,'验证码获取失败,请稍后重试！');
        }
    }

}