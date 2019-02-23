<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明:  $Sendsms = new \Extend\Sendsms('13512345678','验证码：123【签名】');
// |            $send  = $Sendsms->send();
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2017/12/8
// +----------------------------------------------------------------------
namespace Extend;

class Sendsms {

    public $mobile; // 手机号
    public $msg; // 内容
    public $smstpl; // 内容模板
    public $check; // 是否验证手机号是否存在  0不验证 1验证账号必须存在  2验证号码不存在

    public $code; // 是否验证验证码 0为否 其余为验证码数字进行验证

    public $CheckTime   = 60; //短信发送频率

    const S_TABLE = 'sys_sms';
    const C_TABLE = 'sms_code';

    //如果查找有内容模板。会以短信模板的内容为主
    public function __construct($mobile = '', $msg = '',$check = 0,$code = 0, $smstpl = '' ) {

        $this->mobile  = $mobile;
        $this->msg   = $msg;

        $this->check   = $check;
        $this->code   = $code;
        $this->smstpl   = $smstpl;
    }

    public function send(){

        if($this->smstpl){
            $msg_s = M('sys_smstemplates')->where(array('IsDel'=>0,'ID'=>$this->smstpl))->getField('Content');

            if($msg_s){
                $this->msg   = $msg_s;
            }
        }
        if(!$this->mobile){
            return array('result'=>0,'message'=>'手机号不能为空');

        }elseif(!is_mobile($this->mobile)){
            return array('result'=>0,'message'=>'手机号格式不正确');
        }
        if(!$this->msg){
            return array('result'=>0,'message'=>'短信内容不能为空');
        }

        $code = rand(1000,9999);
        $this->msg = str_replace('{SMS_验证码}', $code, $this->msg);

        if($this->code){
            $verify = new \Think\Verify();
            $res = $verify->check($this->code);
            if(!$res){
                return array('result'=>0,'message'=>'验证码错误!');
            }
        }

        if($this->check == 1){
            $find = M('mem_info')->field('ID,Status')->where(array('Mobile'=>$this->mobile,'IsDel'=>0))->find();
            if(!$find){
                return array('result'=>0,'message'=>'该号码不存在');
            }

            if(!$find['Status']){
                return array('result'=>0,'message'=>'目标账号已被限制，不可发送短信信息');
            }
        }elseif($this->check == 2){
            $find = M('mem_info')->field('ID,Status')->where(array('Mobile'=>$this->mobile,'IsDel'=>0))->find();
            if($find){
                return array('result'=>0,'message'=>'该号码已存在');
            }
        }

        $res = M(self::C_TABLE)->where(array("Name" => $this->mobile, "Type" => 0))->find();
        if($res){
            $curtime = time();
            $lasttime = strtotime($res['UpdateTime']);

            $time = $curtime - $lasttime;
            if($time < $this->CheckTime){
                return array('result'=>0,'message'=>'短信发送请求过于频繁');
            }
        }

        $result = sendmessage($this->mobile,$this->msg);
        if(!$result){
            return array('result'=>0,'message'=>'发送短信异常，请稍后重试！');
        }

        if(strpos($result,'success')) {
            $data = array("ObjectID" => $this->mobile, "Type" => 1, "Mode" => 1, "SendMess" => $this->msg, "Status" => 1, "SendTime" => date("Y-m-d H:i:s"), "Obj" => 1);
            M(self::S_TABLE)->add($data);

            $res = M(self::C_TABLE)->where(array("Name" => $this->mobile, "Type" => 0))->find();
            if ($res) {
                M(self::C_TABLE)->where(array("Name" => $this->mobile))->save(array("Code" => $code, "UpdateTime" => date("Y-m-d H:i:s"), "AddTime" => time() + 1200));
            } else {
                $datas = array("Name" => $this->mobile, "Type" => 0, "Code" => $code, "UpdateTime" => date("Y-m-d H:i:s"), "AddTime" => time() + 1200);
                M(self::C_TABLE)->add($datas);
            }
            cookie('yzm', array('yzm' => $code), 0);

            return array('result'=>1,'message'=>'短信已发送，请注意查收');
        }else{
            return array('result'=>0,'message'=>'发送失败,请重新尝试-'.$result['msg']);
        }
    }


    public function check(){

        $time = time();
        $Ccodes = M(self::C_TABLE)->where("Name='%s' and Type = 0 and AddTime> %d", $this->mobile,$time)->getField('Code');

        if(!$Ccodes){
            return array('result'=>0,'message'=>'手机验证码已过期，或者未获取!');
        }

        if ($Ccodes <> $this->msg) {
            return array('result'=>0,'message'=>'手机验证码错误，请重新输入');
        }else{
            M(self::C_TABLE)->where("Type = 0 and AddTime < %d", $time)->delete();
            return array('result'=>1,'message'=>'success');
        }

    }

}