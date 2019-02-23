<?php
/**
 * 功能说明: 公共控制器
 */

namespace Home\Controller;
use Org\Util\Ucpaas;
use Think\Controller;
use XBCommon\XBCache;
use XBCommon;
class CommonController extends HomeController
{
    const S_TABLE='sys_sms';
    const C_TABLE='sms_code';
    const B_TABLE='sys_basicinfo';

    /**
     * 生成验证码
     */
    public function selfverify(){
        $config =    array(
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify =     new \Think\Verify($config);
        $Verify->codeSet = '0123456789';
        ob_end_clean();
        $Verify->entry();
    }

    /**
     * 获取手机验证码
     */
    public function getcode(){

        $mobile = I("post.mobile",'','trim');
        $check=I("post.check",'','trim');
        $yzm = I("post.code",'','trim');//图形验证码

        //尝试在cookie里取手机号码
        if(!$mobile){
            $mobile=cookie('checkArr')['m'];
        }

        if(!$mobile){
            $this->ajaxReturn(0,'手机号不能为空');
        }elseif(!is_mobile($mobile)){
            $this->ajaxReturn(0,'手机号格式不正确');
        }

        $where['Mobile']=$mobile;
        $where['IsDel']=0;
        $find = M('mem_info')->field('ID')->where($where)->find();
        if($find){
            $this->ajaxReturn(2,"该手机号码已被注册，进入app下载页面！");
        }

        if($yzm){
            $verify = new \Think\Verify();
            $res = $verify->check($yzm);
            if(!$res){
                $this->ajaxReturn(0,"图形验证码不正确，请重新输入!");
            }
        }


        if($check==1){  //注册验证身份
            $where['Mobile']=$mobile;
            $where['IsDel']=0;
            $find = M('mem_info')->field('ID')->where($where)->find();
            if($find){
                $this->ajaxReturn(0,"该手机号码已注册过会员，不能重复使用！");
            }
        }


        $code=rand(0000,9999);
        $msg="尊敬的用户，您已通过手机验证，验证码：".$code;

        // $message = new \XBCommon\XBMessage($mobile,$msg);
        // $res = $message->send_message();
        // if(!$res){
        //     $this->ajaxReturn(0,'发送短信异常，请稍后重试！');
        // }
        $result = send_message($mobile,$msg);

        //$result=json_decode($res,true);
        if($result['result']=='success'){
            $data=array("ObjectID"=>$mobile,"Type"=>1,"Mode"=>1,"SendMess"=>$msg,"Status"=>1,"SendTime"=>date("Y-m-d H:i:s"),"Obj"=>1);
            M(self::S_TABLE)->add($data);

            $res=M(self::C_TABLE)->where(array("Name"=>$mobile,"Type"=>0))->find();
            if($res){
                M(self::C_TABLE)->where(array("Name"=>$mobile))->save(array("Code"=>$code,"UpdateTime"=>date("Y-m-d H:i:s")));
            }else{
                $datas=array("Name"=>$mobile,"Type"=>0,"Code"=>$code,"UpdateTime"=>date("Y-m-d H:i:s"));
                M(self::C_TABLE)->add($datas);
            }
            cookie('yzm',array('yzm'=>$yzm),0);
            $this->ajaxReturn(1,'发送成功,请注意查收！');
        }else{
            $this->ajaxReturn(0,'发送失败,请重新尝试！');
        }
    }

    public function getImgCode(){
        $config =    array(
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify =     new \Think\ImgCode($config);
        $Verify->codeSet = '0123456789';
        ob_end_clean();
        $Verify->entry();
    }


    /**
     * 腾讯云短信
     */
    public function getYun(){
        $ParaList=M('sys_inteparameter')->where(array('IntegrateID'=>18))->getField('Name,ParaValue');
        $mobile = I('post.mobile');
        $check = I('post.check','','trim');
        if($check){
            $where['Mobile']=$mobile;
            $where['IsDel']=0;
            $find = M('mem_info')->field('ID')->where($where)->find();
            if($check==1){
                if($find){
                    $this->ajaxReturn(2,"该手机号码已被注册！");
                }
            }
            if($check==2){
                if(!$find){
                    $this->ajaxReturn(0,"该手机号尚未注册哦！");
                }
            }
        }
        $appId=$ParaList['APPID'];
        $key = $ParaList['AppKey'];
        $template = $ParaList['templateId'];
        $sj = 3;
        $curTime = time();
        $random = rand(1000,9999);
        $wholeUrl = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid={$appId}&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . "86";
        $tel->mobile = "" . $mobile;
        $data->tel = $tel;
        $data->sig = hash("sha256",
            "appkey={$key}&random=" . $random . "&time="
            . $curTime . "&mobile=" . $mobile, FALSE);
        $data->tpl_id = $template;
        $data->params = array($random, $sj);
        $data->time = $curTime;
        //$data->sign = '云肆网络';//如果只有一个则不需要签名
        $data->extend = '';
        $data->ext = '';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $wholeUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($curl);
        $res = json_decode($ret, true);
        $para['code'] = $random;
        $para['code_time'] = time()+180;
        $para['mobile'] = $mobile;
        if ($res['errmsg'] == 'OK') {//发送成功
            file_put_contents('./log/'.$random.'.txt',json_encode($para));
            $this->ajaxReturn(1,'发送成功,请注意查收！',$random);
        } else {
            $this->ajaxReturn(0,'发送失败,请重新尝试！');
        }
    }

    /**
     * 云之讯短信
     */
    public function getYun2(){
        $ParaList=M('sys_inteparameter')->where(array('IntegrateID'=>17))->getField('Name,ParaValue');
        $mobile = I('post.mobile');
        $check = I('post.check','','trim');
        if($check){
            $where['Mobile']=$mobile;
            $where['IsDel']=0;
            $find = M('mem_info')->field('ID')->where($where)->find();
            if($check==1){
                if($find){
                    $this->ajaxReturn(2,"该手机号码已被注册！");
                }
            }
            if($check==2){
                if(!$find){
                    $this->ajaxReturn(0,"该手机号尚未注册哦！");
                }
            }
        }
        $templateId=$ParaList['templateId'];
        $options['accountsid'] = $ParaList['accountsid'];
        $options['token'] = $ParaList['token'];
        $ucpass = new Ucpaas($options);
        //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
        $appId = $ParaList['Appid'];
        $mobile_code = rand(100000, 999999);
        //$templateId = "101647";
        $param = "$mobile_code";
        session('code',$param,'/');
        session('code_time',time()+60*3);
        session('mobile',$mobile);
        $data = array(
            'code'=>$param,
            'code_time'=>time()+60*3,
            'mobile'=>$mobile,
        );
        file_put_contents('./log/'.$param.'.txt',json_encode($data));
        $res = $ucpass->templateSMS($appId, $mobile, $templateId, $param);
        $result = json_decode($res,true);
        if($result['resp']['respCode']=='000000'){
            $this->ajaxReturn(1,'发送成功,请注意查收！',$param);
        }else{
            $this->ajaxReturn(0,'发送失败,请重新尝试！');
        }
    }



}