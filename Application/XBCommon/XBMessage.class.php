<?php
/**
 * 
 *
 * 作    者: 傅世敏
 * 修改时间: 2017-05-09 10:42
 * 功能说明: 发送短信的基础类。
 */
namespace XBCommon;
class XBMessage
{
    const SMSIGNAME=3001; //  签名错误
	//定义属性
	public $Name;                  //手机号 或邮箱地址
	Public $Appkey;			      //appid
	Public $Secret;	   		      //秘钥
	public $Signname;		          //短信签名
	Public $SetSmstemplatecode;   //模板编号(阿里大于必须在后台验证成功才可使用)
	Public $Product;                //模板内部字段(用于阿里大于 模板内)
    Public $Mess;                    //自定义发送内容
    Public $Type;                    //发送类型 0 短信 1 邮件 默认短信
    Public $Title;                   //邮件标题

	//实例化赋值属性值
    public function __Construct($Name,$Mess='',$Title='',$Type=0,$Product=''){

		$this->Name = $Name;
        $this->Mess = $Mess;
		$this->Product = $Product;
        $this->Type = $Type;
        $this->Title = $Title;
	}

	//发送短信方法
	public function send_message(){
	    if($this->Type==0) {
            $messag_info = $this->Getinfo();
        }else{
            $messag_info = $this->Email();
        }
		return $messag_info;
	}


	//获取短信基础配置信息
	public function Getinfo(){
		$where=array("Type"=>0,"Status"=>1,"IsDefault"=>1);
		$sid=M('sys_integrate')->where($where)->find();
		$info=M('sys_inteparameter')->where(array("IntegrateID"=>$sid['ID']))->select();
		foreach($info as $key=>$val){
			if($val['ParaName']=='AppKey')$this->Appkey=$val['ParaValue'];
			if($val['ParaName']=='AppScript')$this->Secret=$val['ParaValue'];
			if($val['ParaName']=='SignAture')$this->Signname=$val['ParaValue'];
			if($val['ParaName']=='Smstemplatecode')$this->SetSmstemplatecode=$val['ParaValue'];

            if($val['ParaName']=='account')$this->account=$val['ParaValue'];
            if($val['ParaName']=='password')$this->password=$val['ParaValue'];
		}
		if($sid['EName']=='luosimao'){
			$result=$this->Lsmmesage();
		}elseif($sid['EName']=='alidayu'){
			$result=$this->Alimesage();
		}elseif($sid['EName']=='yuntongxun'){
            $result=$this->Yutmesage();
        }
		    return $result;

	}

    //螺丝帽短信发送方法
    public function Lsmmesage(){
        Vendor('SMS.Luosimao.Sms','','.php');
        //api key可在后台查看 短信->触发发送下面查看
        $sms = new \Vendor\SMS\Luosimao\Sms( array('api_key' => $this->Appkey , 'use_ssl' => FALSE ) );
        //send 单发接口，签名需在后台报备
        //$res = $sms->send( $this->Name, $this->Mess.'【'.$this->Signname.'】');
        if(is_array( $this->Name )){
            $res = $sms->send_batch( $this->Name, $this->Mess.'【'.$this->Signname.'】');
        }else{
            $res = $sms->send( $this->Name, $this->Mess.'【'.$this->Signname.'】');
        }

        $data=array();
        if( $res ){
            if( isset( $res['error'] ) &&  $res['error'] == 0 ){
                $data['result']='success';
            }else{
                $data['result']='failed';
                $data['res']['error']=$res['error'];
                $data['res']['msg']=$res['msg'];
            }
        }else{
                $data['error']=$sms->last_error();
        }
            return $data;
    }

    //阿里大于短信发送方法
    public function Alimesage(){
        Vendor('SMS.Alidayu.TopSdk','','.php');
        $c->appkey = $this->Appkey;
        $c = new \TopClient;
        $c->secretKey = $this->Secret;
        $c->format = 'json';
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend($this->Code);
        $req->setSmsType('normal');
        $req->setSmsFreeSignName($this->Signname); //发送的签名
        $req->setSmsParam('{"product":"'.$this->Product.'"}');//根据模板进行填写
        $req->setRecNum($this->Name);//接收着的手机号码
        $req->setSmsTemplateCode($this->SetSmstemplatecode);
        $resp = $c->execute($req);
        if($resp->code==40){
            $data['code']=self::SMSIGNAME;
            $data['error']=' 错误信息:短信签名有误！';
            return json_encode($data);
        }
         return json_encode($resp);
    }

    //253云通讯短信发送方法
    public function Yutmesage(){
        Vendor('SMS.Yutongxun.ChuanglanSmsApi','','.php');
        //api key可在后台查看 短信->触发发送下面查看
        $clapi = new \Vendor\SMS\Yutongxun\ChuanglanSmsApi( array('account' => $this->account,'password' => $this->password,) );
        //send 单发接口，签名需在后台报备
        if(is_array( $this->Name )){
            //批量发送
            //分割数组
            $chunkData = array_chunk($this->Name,80);
            foreach($chunkData as $k=>$v){
                $mobilestr='';
                $mobilestr=implode(',',$v);
                $res = $clapi->sendSMS( $mobilestr, '【'.$this->Signname.'】'.$this->Mess);
            }
        }else{
            $res = $clapi->sendSMS( $this->Name, '【'.$this->Signname.'】'.$this->Mess);
        }

        $data=array();
        if(!is_null(json_decode($res))){
            $output=json_decode($res,true);
            
            if(isset($output['code'])  && $output['code']=='0'){
                $data['result']='success';
            }else{
                $data['result']='failed';
                $data['res']['error']=$output['code'];
                $data['res']['msg']=$output['errorMsg'];
            }
        }else{
                $data['error']=$res;
        }
            return $data;
    }

    //邮件发送方法

    public function Email(){
        $cache= new CacheData();
        $basic=$cache->BasicInfo();
        Vendor('PHPMailer.PHPMailerAutoload');
        $mail = new \PHPMailer();//实例化
        $mail->IsSMTP(); // 启用SMTP
        $mail->Host=$basic['SmtpServer']; //smtp服务器的名称
        $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
        $mail->Username = $basic['SmtpUser']; //你的邮箱名
        $mail->Password = $basic['SmtpPsd']; //邮箱密码
        $mail->From = $basic['SmtpUser']; //发件人地址（也就是你的邮箱地址）
        $mail->FromName = $basic['SmtpNiceName']; //发件人姓名
        $mail->AddAddress($this->Name);
        $mail->WordWrap = 50; //设置每行字符长度
        $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
        $mail->CharSet=C('MAIL_CHARSET'); //设置邮件编码
        $mail->Subject =$this->Title; //邮件主题
        $mail->Body = $this->Mess; //邮件内容
        $mail->AltBody = $basic['SmtpNiceName']."邮件"; //邮件正文不支持HTML的备用显示
        $result=$mail->Send();
        if($result){
            $data['result']='success';
        }else{
            $data['error']=$mail->ErrorInfo;
        }
           return $data;
    }

}