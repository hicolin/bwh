<?php
/**
 *
 *

 */
namespace Home\Controller;
use Home\Controller\Curl;
use Home\Controller\Crypt3Des;
class FuyoupayController extends HomeController{

    public $token;//用户登录token
    public $member;//  用户所有信息
    public $uid;//  用户ID

    public function _initialize(){
        parent::_initialize();
        //配置参数
        $setsinfo=M('sys_inteparameter')->where(array('IntegrateID'=>'13'))->select();
        $setArr=array();
        foreach($setsinfo as $k=>$v){
            $setArr[$v['ParaName']]=$v['ParaValue'];
        }
        $this->config = array(
            'mchntCd' => $setArr['mchntCd'],    //商户代码
            'key' => $setArr['key'],    //商户密钥
            'url_newpropay_order'=>'http://www-1.fuiou.com:18670/mobile_pay/newpropay/order.pay',
        );

        $this->token = I('post.token');
        if(!$this->token){
            $this->ajaxReturn(0,'请先登录');
        }
        $this->member = M('mem_info')->where(array('Token'=>$this->token,'IsDel'=>'0'))->find();
        $this->uid = $this->member['ID'];
        if(!$this->member){
            $this->ajaxReturn(0,'token失效，请重新登录');
        }
        $expire = $this->member['Expire'];
        if(time() > $expire){
            $this->ajaxReturn(0,'token已过期，请重新登录');
        }
    }


    /**
     * 协议卡绑定接口-发送短信验证码接口
     * @param $merchant  @商户号
     * @param $key  @秘钥
     * @param $user_id  @用户编号
     * @param $order_no @商户流水号
     * @param $account  @账户名称
     * @param $card_no  @银行卡号
     * @param $id_card  @证件号码
     * @param $mobile   @手机号码
     * @return array
     *   [VERSION] => 1.0
    [RESPONSECODE] => 0000
    [RESPONSEMSG] => 成功
    [MCHNTCD] => 0002900F0096235
    [MCHNTSSN] => 2018120517592297495010
     */
    public function bangka(){
        $post = I('post.');
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];
        $VERSION = '1.0';
        $MCHNTCD = $this->config['mchntCd'];
        $key = $this->config['key'];
        $USERID = $this->uid ;
        $TRADEDATE = date('Ymd');
        $MCHNTSSN = getOrderSn();
        $ACCOUNT = $post['TrueName'];
        $CARDNO = $post['BankNo'];
        $IDTYPE = '0';
        $IDCARD = $post['IDCard'];
        $MOBILENO = $post['YMobile'];
        //待签名数组
        $sign_arr = [$VERSION, $MCHNTSSN, $MCHNTCD, $USERID, $ACCOUNT, $CARDNO, $IDTYPE, $IDCARD, $MOBILENO,$key];
        $SIGN = md5(implode('|',$sign_arr));

        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <USERID>'.$USERID.'</USERID>
        <TRADEDATE>'.$TRADEDATE.'</TRADEDATE>
        <MCHNTSSN>'.$MCHNTSSN.'</MCHNTSSN>
        <ACCOUNT>'.$ACCOUNT.'</ACCOUNT>
        <CARDNO>'.$CARDNO.'</CARDNO>
        <IDTYPE>'.$IDTYPE.'</IDTYPE>
        <IDCARD>'.$IDCARD.'</IDCARD>
        <MOBILENO>'.$MOBILENO.'</MOBILENO>
        <CVN></CVN>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';

        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);

        $result = Curl::curlPostHttps('https://mpay.fuiou.com/newpropay/bindMsg.pay',$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
//        echo '<pre>';
//        print_r($arr_result);exit;
        if($arr_result['RESPONSECODE'] == '0000'){
            $data = [
                'UserID'=>$this->uid,
                'OrderSn'=>$arr_result['MCHNTSSN'],
                'BankNo'=>$post['BankNo'],
                'YMobile'=> $post['YMobile'],
                'RenzTime'=>date('Y-m-d H:i:s'),
                'UpdateTime'=>date('Y-m-d H:i:s'),
            ];
            $mem = [
                'TrueName'=> $post['TrueName'],
                'IDCard'=> $post['IDCard'],
            ];
            $card = M('renzen_bank')->where(array('UserID'=>$this->uid))->find();
            if($card){
                M('renzen_bank')->where(['UserID'=>$this->uid])->save($data);
            }else{
                M('renzen_bank')->add($data);
            }
            M('mem_info')->where(array('ID'=>$this->uid))->save($mem);
            $this->ajaxReturn(200,$arr_result['RESPONSEMSG']);
        }else{
            $this->ajaxReturn(100,$arr_result['RESPONSEMSG']);
        }
    }


    /**
     * 协议卡绑定接口-协议卡绑定
     * @param $user_id
     * @param $order_no
     * @param $account
     * @param $card_no
     * @param $id_card
     * @param $mobile
     * @param $code
     * @return array
     * VERSION] => 1.0
    [RESPONSECODE] => 0000
    [RESPONSEMSG] => 协议绑卡成功！
    [MCHNTCD] => 0002900F0096235
    [PROTOCOLNO] => BYGV0G1000000252021AVS
    [MCHNTSSN] => 2018120518112398485599
     */
    public function bangkat()
    {
            $post = I('post.');
            $bank = M('renzen_bank')->where(array('UserID' =>$this->uid,'IsDel'=>'0'))->find();
            $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];
            $VERSION = '1.0';
            $MCHNTCD = $this->config['mchntCd'];
            $key = $this->config['key'];
            $USERID = $this->uid;
            $TRADEDATE = date('Ymd');
            $MCHNTSSN = $bank['OrderSn'];//必须和发送短信流水号保持一致
            $ACCOUNT = $post['TrueName'];
            $CARDNO = $post['BankNo'];
            $IDTYPE = '0';
            $IDCARD = $post['IDCard'];
            $MOBILENO = $post['YMobile'];
            $MSGCODE =  $post['Code'];;//测试默认 短信
            //待签名数组
            $sign_arr = [$VERSION, $MCHNTSSN, $MCHNTCD, $USERID, $ACCOUNT, $CARDNO, $IDTYPE, $IDCARD, $MOBILENO,$MSGCODE,$key];
            $SIGN = md5(implode('|',$sign_arr));

            $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <USERID>'.$USERID.'</USERID>
        <TRADEDATE>'.$TRADEDATE.'</TRADEDATE>
        <MCHNTSSN>'.$MCHNTSSN.'</MCHNTSSN>
        <ACCOUNT>'.$ACCOUNT.'</ACCOUNT>
        <CARDNO>'.$CARDNO.'</CARDNO>
        <IDTYPE>'.$IDTYPE.'</IDTYPE>
        <IDCARD>'.$IDCARD.'</IDCARD>
        <MOBILENO>'.$MOBILENO.'</MOBILENO>
        <MSGCODE>'.$MSGCODE.'</MSGCODE>
        <CVN></CVN>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';

            $key = str_pad($key,64,'D');
            $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
            $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);

            $result = Curl::curlPostHttps('https://mpay.fuiou.com/newpropay/bindCommit.pay',$param,$header);
            $xml_result = Crypt3Des::decrypt_base64($result,$key);
            if($xml_result === false){
                $xml_result = $result;
            }
            $arr_result = xml_to_array($xml_result);
            if($arr_result['RESPONSECODE'] == '0000'){
                $data = [
                    'UserID'=>$this->uid,
                    'OrderSn'=>$arr_result['MCHNTSSN'],
                    'PROTOCOLNO'=>$arr_result['PROTOCOLNO'],
                    'BankNo'=>$post['BankNo'],
                    'YMobile'=> $post['YMobile'],
                    'Status'=>1,
                    'RenzTime'=>date('Y-m-d H:i:s'),
                    'UpdateTime'=>date('Y-m-d H:i:s'),
                ];
                $mem = [
                    'TrueName'=> $post['TrueName'],
                    'IDCard'=> $post['IDCard'],
                ];
                M('renzen_bank')->where(['UserID'=>$this->uid])->save($data);
                M('mem_info')->where(array('ID'=>$this->uid))->save($mem);
                $this->ajaxReturn(200,$arr_result['RESPONSEMSG']);
            }else{
                $this->ajaxReturn(100,$arr_result['RESPONSEMSG']);
            }
    }

    /**
     * 协议支付
     * @return array
     */
    public function xieyi()
    {
        if(I('post.')){
            $token = I('post.token');
            $id = I('post.id');
            $this->checkToken($token);
            $order = M('loans_hklist')->where(array('ID'=>$id))->find();//查该条数据
            $bank = M('renzen_bank')->where(array('UserID'=>$this->uid))->find();
            if($bank['Status']!=1){
                $this->ajaxReturn(0,"您还未绑定银行卡，请先绑定!");
            }
            $header = array(
                '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
            );
            $VERSION = '1.0';
            $USERIP = get_client_ip();
            $MCHNTCD = $this->config['mchntCd'];
            $key = $this->config['key'];
            $TYPE = '03';
            $MCHNTORDERID = $order['OrderSn'];
            $USERID = $this->uid;
            $AMT = $order['TotalMoney']*100;   //订单金额
            //$AMT = '1';   //订单金额
            $PROTOCOLNO = $bank['PROTOCOLNO'];
            $NEEDSENDMSG  = '0';
            $BACKURL='http://'.$_SERVER['HTTP_HOST'].'/fuyoupay/hkquery';
            $SIGNTP = 'MD5';
            //待签名数组
            $sign_arr = [$TYPE,$VERSION, $MCHNTCD, $MCHNTORDERID, $USERID, $PROTOCOLNO, $AMT, $BACKURL, $USERIP,$key];
            $SIGN = md5(implode('|',$sign_arr));
            $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <USERIP>'.$USERIP.'</USERIP>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <TYPE>'.$TYPE.'</TYPE>
        <MCHNTORDERID>'.$MCHNTORDERID.'</MCHNTORDERID>
        <USERID>'.$USERID.'</USERID>
        <AMT>'.$AMT.'</AMT>
        <PROTOCOLNO>'.$PROTOCOLNO.'</PROTOCOLNO>
        <NEEDSENDMSG>'.$NEEDSENDMSG.'</NEEDSENDMSG>
        <BACKURL>'.$BACKURL.'</BACKURL>
        <REM1></REM1>
        <REM2></REM2>
        <REM3></REM3>
        <SIGNTP>'.$SIGNTP.'</SIGNTP>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';
            $key = str_pad($key,64,'D');
            $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
            $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
            $result = Curl::curlPostHttps("https://mpay.fuiou.com/newpropay/order.pay",$param,$header);
            $xml_result = Crypt3Des::decrypt_base64($result,$key);
            if($xml_result === false){
                $xml_result = $result;
            }
            $arr_result = xml_to_array($xml_result);
            file_put_contents('1739.txt',$arr_result['RESPONSEMSG']);
            if($arr_result['RESPONSECODE'] == '0000'){
                //正常还款的要修改还款表，增加支付表，修改申请表
                //续期的订单要修改还款表，增加支付表，无需修改申请表
                $return_data['OrderSn'] = $arr_result['MCHNTORDERID']; //商户订单号
                $return_data['TradeNo'] = $arr_result['ORDERID']; //富友订单号
                $return_data['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
                $return_data['PayStatus'] = 1; //支付状态——已支付
                $return_data['Status'] = 1;  //审核状态——审核成功
                $res = M('loans_hklist')->where(array('ID'=>$id))->save($return_data);
                $recoddata=array(
                    'UserID'=>$order['UserID'],
                    'OrderSn'=>$order['OrderSn'],
                    'LoanNo'=>$order['LoanNo'],
                    'TradeType'=>'2',
                    'OrderAmount'=>$order['TotalMoney'],
//                    'OrderAmount'=>$AMT,
                    'PayType'=>$order['PayType'],
                    'PayStatus'=>'1',
                    'Description'=>'客户还款',
                    'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                    'UpdateTime'=>date('Y-m-d H:i:s'),
                    'TradeNo'=>$arr_result['ORDERID'],
                );
                if($order['Mark']==1){
                    $recoddata['Description'] = '支付续期手续费';
                }
                $model = M('loans_paylist')->add($recoddata);
                if($order['Mark']==0){
                    $applyres['LoanStatus']='3';
                    $applyrest=M('loans_applylist')->where(array('LoanNo'=>$order['LoanNo'],'LoanStatus'=>'2','Status'=>'1','IsDel'=>'0'))->save($applyres);
                    if($res!==false && $model!==false && $applyrest!==false){
                        $this->ajaxReturn(100,"支付成功，返回中~");
                    }else{
                        $this->ajaxReturn(2,"支付失败");
                    }
                }else{
                    if($res!==false && $model!==false){
                        $this->ajaxReturn(100,"支付成功，返回中~");
                    }else{
                        $this->ajaxReturn(2,"支付失败");
                    }
                }

            }else{
                M('loans_hklist')->where(array('ID'=>$id))->delete();
                $this->ajaxReturn(0,$arr_result['RESPONSEMSG']);
            }
        }
    }



    /**
     * 富有的银行卡四要素认证
     * @return array
     */
    public function rzcards()
    {
        $header = array(
            '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        if(IS_AJAX){
            $post = I('post.');
            $MchntCd = $this->config['mchntCd'];
            $key = $this->config['key'];
            $Ver = '1.30';
            $OSsn= getOrderSn();
            $Ono= $post['BankNo'];
            $Onm = $post['TrueName'];
            $OCerTp = '0';
            $OCerNo = $post['IDCard'];
            $Mno = $post['YMobile'];
            $SignTp = 'MD5';
            //待签名数组
            $sign_arr = [$MchntCd,$Ver, $OSsn, $Ono, $OCerTp, $OCerNo, $key];
            $SIGN = md5(implode('|',$sign_arr));
            $FM = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <FM>
        <MchntCd>'.$MchntCd.'</MchntCd>
        <Ono>'.$Ono.'</Ono>
        <Onm>'.$Onm.'</Onm>
        <OCerTp>'.$OCerTp.'</OCerTp>
        <OCerNo>'.$OCerNo.'</OCerNo>
        <Mno>'.$Mno.'</Mno>
        <Sign>'.$SIGN.'</Sign>
        <SignTp>'.$SignTp.'</SignTp>
        <Ver>'.$Ver.'</Ver>
        <OSsn>'.$OSsn.'</OSsn>
        </FM>';
            $key = str_pad($key,64,'D');
            //$param['MCHNTCD'] = $MchntCd;  //商户代码,分配给各合作商户的唯一识别码
            $param['FM'] = $FM;
            $result = Curl::curlPostHttps('https://mpay.fuiou.com:16128/checkCard/checkCard01.pay',$param,$header);
            $xml_result = Crypt3Des::decrypt_base64($result,$key);
            if($xml_result === false){
                $xml_result = $result;
            }
            $arr_result = xml_to_array($xml_result);
            print_r($arr_result);exit;
            if($arr_result['Rcd']=='0000'){//认证通过，其他则失败
                $data = [
                    'UserID'=>$this->uid,
                    'OrderSn'=>$arr_result['OSsn'],
                    'BankNo'=>$arr_result['CardNo'],
                    'YMobile'=>$Mno,
                    'BankName'=>$arr_result['Cnm'],
                    'OpenBankName'=>$arr_result['Cnm'],
                    'Status'=>1,
                    'RenzTime'=>date('Y-m-d H:i:s'),
                    'UpdateTime'=>date('Y-m-d H:i:s'),
                ];
                $mem = [
                    'TrueName'=>$Onm,
                    'IDCard'=>$OCerNo,
                ];
                $card = M('renzen_bank')->where(array('UserID'=>$this->uid))->find();
                if($card){
                    M('renzen_bank')->where(['UserID'=>$this->uid])->save($data);
                }else{
                    M('renzen_bank')->add($data);
                }
                M('mem_info')->where(array('ID'=>$this->uid))->save($mem);
                $this->ajaxReturn('1',$arr_result['RDesc']);
            }else{
                $this->ajaxReturn('0',$arr_result['RDesc']);
            }
        }
    }

    /**
     * 测试解绑
     */
    public function jiebang(){
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];
        $VERSION = '1.0';
        $MCHNTCD = $this->config['mchntCd'];
        $key = $this->config['key'];
        $USERID = 7;
        $PROTOCOLNO = '8LBDJB1000256999316KCS';
        //待签名数组
        $sign_arr = [$VERSION,$MCHNTCD, $USERID, $PROTOCOLNO,$key];
        $SIGN = md5(implode('|',$sign_arr));
        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <USERID>'.$USERID.'</USERID>
        <PROTOCOLNO>'.$PROTOCOLNO.'</PROTOCOLNO>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';
        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
        $result = Curl::curlPostHttps('https://mpay.fuiou.com/newpropay/unbind.pay',$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
        echo '<pre>';
        print_r($arr_result);exit;
        if($arr_result['RESPONSECODE'] == '0000'){
            $data = [
                'UserID'=>$this->uid,
                'OrderSn'=>$arr_result['MCHNTSSN'],
                'BankNo'=>$post['BankNo'],
                'YMobile'=> $post['YMobile'],
                'RenzTime'=>date('Y-m-d H:i:s'),
                'UpdateTime'=>date('Y-m-d H:i:s'),
            ];
            $mem = [
                'TrueName'=> $post['TrueName'],
                'IDCard'=> $post['IDCard'],
            ];
            $card = M('renzen_bank')->where(array('UserID'=>$this->uid))->find();
            if($card){
                M('renzen_bank')->where(['UserID'=>$this->uid])->save($data);
            }else{
                M('renzen_bank')->add($data);
            }
            M('mem_info')->where(array('ID'=>$this->uid))->save($mem);
            $this->ajaxReturn('2',$arr_result['RESPONSEMSG']);
        }else{
            $this->ajaxReturn('0',$arr_result['RESPONSEMSG']);
        }
    }

    //还款支付回调地址
    public function hkquery(){
       // $data = file_get_contents("php://input");
    	$data =$_REQUEST;
       file_put_contents('12.txt',json_encode($data));
        // $abcs=$_REQUEST;
        // if(!file_exists("hlibao66666.txt")){ $fp = fopen("hlibao66666.txt","wb"); fclose($fp);  }
        //    $str = file_get_contents('hlibao66666.txt');
        //    foreach($abcs as $k=>$v){
        //      $str .= " -  - ".$k.":".$v;
        //    }
        //    $str .= " -  -  -  - ".date("Y-m-d H:i:s")."\r\n";
        //    $fp = fopen("hlibao66666.txt","wb");
        //    fwrite($fp,$str);
        //    fclose($fp);
        //验签操作
        $result=$this->checksign($data);
        if ($result){
            if($data['RESPONSECODE']== "0000"){
                $order = M('loans_hklist')->field('ID,PayStatus')->where(array('OrderSn'=>$data['MCHNTORDERID']))->find();
                if($order['PayStatus']=='0'){
                    $result =$this->change_hkorder_data($data['MCHNTORDERID'],$data['ORDERID']);
                }
            }
        }
        echo "success";
    }
    //续借支付回调地址
    public function xjquery(){
        //$data = file_get_contents("php://input");
        $data =$_REQUEST;
        //验签操作
        $result=$this->checksign($data);

        if ($result){
            if($data['RESPONSECODE']== "0000"){
                $order = M('loans_xjapplylist')->field('ID,PayStatus')->where(array('OrderSn'=>$data['MCHNTORDERID']))->find();
                if($order['PayStatus']=='0'){
                    $result =$this->change_xjorder_data($data['MCHNTORDERID'],$data['ORDERID']);
                }
            }
        }
        echo "success";
    }


    //根据订单号修改还款记录
    public function change_hkorder_data($out_trade_no,$trade_no){
        $dailinfos=M('loans_hklist')->field('ID,ApplyID,UserID,OrderSn,LoanNo,TotalMoney,PayType,HkTime')->where(array('OrderSn'=>$out_trade_no))->find();
        //$mem_info=M('mem_info')->where(array('ID'=>$dailinfos['UserID']))->find();

        $Trans = M();
        $Trans->startTrans();
        $dl_data=array(
            'TradeNo'=>$trade_no,
            'PayStatus'=>'1',
            'PayTime'=>date('Y-m-d H:i:s'),
            'ShTime'=>date('Y-m-d H:i:s'),
            'Status'=>'1',
            );
        $flag1 = $Trans->table('xb_loans_hklist')->where(array('OrderSn'=>$out_trade_no))->save($dl_data);//如果成功，则就更新 xb_loans_applylist 为已还款了
        if($flag1){
            //判断是否逾期  根据实际还款时间 与 预约还款时间 比较
            //过了当天夜里24点才算逾期
            $YyFkTime=M('loans_applylist')->where(array('ID'=>$dailinfos['ApplyID']))->getField('YyFkTime');
            $overtimes=date('Y-m-d',strtotime($YyFkTime)).' 23:59:59';
            $IsYQ='0';
            if($overtimes<$dailinfos['HkTime']){
                //逾期了
                $IsYQ='1';
            }
            $applyrest=$Trans->table('xb_loans_applylist')->where(array('ID'=>$dailinfos['ApplyID']))->save(array('LoanStatus'=>'3','IsYQ'=>$IsYQ));
            if($applyrest){
                //添加 支付记录
                $content ='支付成功,订单号 '.$out_trade_no.'，支付价格￥'.$dailinfos['TotalMoney'].'元';
                send_mem_notics($dailinfos['UserID'],$content);//发送站内消息
                $recoddata=array(
                    'UserID'=>$dailinfos['UserID'],
                    'OrderSn'=>$dailinfos['OrderSn'],
                    'LoanNo'=>$dailinfos['LoanNo'],
                    'TradeNo'=>$trade_no,
                    'TradeType'=>'2',
                    'OrderAmount'=>$dailinfos['TotalMoney'],
                    'PayType'=>$dailinfos['PayType'],
                    'PayStatus'=>'1',
                    'Description'=>$content,
                    );
                $flag2 =$Trans->table('xb_loans_paylist')->add($recoddata);
                if($flag2){
                    $Trans->commit();
                    return true;
                }else{
                    $Trans->rollback();
                    return false;
                }
            }else{
                $Trans->rollback();
                return false;
            }
        }else{
            $Trans->rollback();
            return false;
        }
    }
    //根据订单号修改 续借支付
    public function change_xjorder_data($out_trade_no,$trade_no){
        $dailinfos=M('loans_xjapplylist')->field('ID,UserID,OrderSn,LoanNo,ApplyID,LoanDay,TotalMoney,PayType')->where(array('OrderSn'=>$out_trade_no))->find();
        //$mem_info=M('mem_info')->where(array('ID'=>$dailinfos['UserID']))->find();

        $Trans = M();
        $Trans->startTrans();
        $dl_data=array(
            'TradeNo'=>$trade_no,
            'PayStatus'=>'1',
            'PayTime'=>date('Y-m-d H:i:s'),
            'ShTime'=>date('Y-m-d H:i:s'),
            'Status'=>'1',
            );
        $flag1 = $Trans->table('xb_loans_xjapplylist')->where(array('OrderSn'=>$out_trade_no))->save($dl_data);
        //如果成功，则就更新 xb_loans_applylist 为已还款了
        //并且在重新生成一条续借记录
        if($flag1){
            $applyrest=$Trans->table('xb_loans_applylist')->where(array('ID'=>$dailinfos['ApplyID']))->save(array('LoanStatus'=>'3'));
            if($applyrest){
                $applyinfos=M('loans_applylist')->where(array('ID'=>$dailinfos['ApplyID']))->find();
                //重新添加一条借款记录
                $YyFkTime2=strtotime($applyinfos['YyFkTime'])+$dailinfos['LoanDay']*86400;
                $newdata=array(
                    'UserID'=>$applyinfos['UserID'],
                    'ApplyTime'=>date('Y-m-d H:i:s'),
                    'OrderSn'=>date(ymd).rand(1,9).date(His).rand(111,999),
                    'LoanNo'=>$applyinfos['LoanNo'],
                    'ApplyMoney'=>$applyinfos['ApplyMoney'],
                    'AdoptMoney'=>$applyinfos['AdoptMoney'],
                    'FJMoney'=>$applyinfos['FJMoney'],
                    'Interest'=>$applyinfos['Interest'],
                    'ApplyDay'=>$dailinfos['LoanDay'],
                    'ProductID'=>$applyinfos['ProductID'],
                    'CouponID'=>$applyinfos['CouponID'],
                    'CoMoney'=>$applyinfos['CoMoney'],
                    'OpenM'=>$applyinfos['OpenM'],
                    'BackM'=>$applyinfos['BackM'],
                    'LoanType'=>'1',
                    'LoanStatus'=>'2',
                    'SqAdminID'=>$applyinfos['SqAdminID'],
                    'ServiceID'=>$applyinfos['ServiceID'],
                    'ShTime'=>$applyinfos['ShTime'],
                    'Status'=>$applyinfos['Status'],
                    'RongDay'=>$applyinfos['RongDay'],
                    'RongP'=>$applyinfos['RongP'],
                    'OverdueDay'=>$applyinfos['OverdueDay'],
                    'OverdueP'=>$applyinfos['OverdueP'],
                    'FKadminID'=>$applyinfos['FKadminID'],
                    'FkServiceID'=>$applyinfos['FkServiceID'],
                    'OpenTime'=>date('Y-m-d H:i:s'),
                    'YyFkTime'=>date("Y-m-d H:i:s",$YyFkTime2),
                    );
                $newapply=$Trans->table('xb_loans_applylist')->add($newdata);
                if($newapply){
                    //添加 支付记录
                    $content ='支付成功,订单号 '.$out_trade_no.'，支付价格￥'.$dailinfos['TotalMoney'].'元';
                    send_mem_notics($dailinfos['UserID'],$content);//发送站内消息
                    $recoddata=array(
                        'UserID'=>$dailinfos['UserID'],
                        'OrderSn'=>$dailinfos['OrderSn'],
                        'LoanNo'=>$dailinfos['LoanNo'],
                        'TradeNo'=>$trade_no,
                        'TradeType'=>'1',
                        'OrderAmount'=>$dailinfos['TotalMoney'],
                        'PayType'=>$dailinfos['PayType'],
                        'PayStatus'=>'1',
                        'Description'=>$content,
                        );
                    $flag2 =$Trans->table('xb_loans_paylist')->add($recoddata);
                    if($flag2){
                        $Trans->commit();
                        return true;
                    }else{
                        $Trans->rollback();
                        return false;
                    }
                }else{
                    $Trans->rollback();
                    return false;
                }
            }else{
                $Trans->rollback();
                return false;
            }
        }else{
            $Trans->rollback();
            return false;
        }
    }
    //验证签名操作
    public function checksign($data){
    	$mchntCd=$this->config['mchntCd'];//商户代码
        $key=$this->config['key'];    //商户密钥

    	$self=$data['TYPE']."|".$data['VERSION']."|".$data['RESPONSECODE']."|".$data['MCHNTCD']."|".$data['MCHNTORDERID']."|".$data['ORDERID']."|".$data['AMT']."|".$data['BANKCARD']."|".$key;
    	if(md5($self)==$data['SIGN']){
    		return true;
    	}else{
    		return false;
    	}
    }


    /**
     * 代扣测试
     * @return array
     */
    public function dk()
    {

        $order = M('loans_hklist')->where(array('ID'=>26))->find();//查该条数据
        $bank = M('renzen_bank')->where(['UserID'=>5])->find();
        if($bank['Status']!=1){
            $this->ajaxReturn(0,"您还未绑定银行卡，请先绑定!");
        }
        $header = array(
            '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        $VERSION = '1.0';
        $USERIP = get_client_ip();
        $MCHNTCD = $this->config['mchntCd'];
        $key = $this->config['key'];
        $TYPE = '03';
        $MCHNTORDERID = '';
        $USERID = 5;
//            $AMT = $order['TotalMoney'];   //订单金额
        $AMT = '100';   //订单金额
        $PROTOCOLNO = $bank['PROTOCOLNO'];
        $NEEDSENDMSG  = '0';
        $BACKURL = 'http://cunguanchou.com/fuyoupay/hkquery';
        $SIGNTP = 'MD5';
        //待签名数组
        $sign_arr = [$TYPE,$VERSION, $MCHNTCD, $MCHNTORDERID, $USERID, $PROTOCOLNO, $AMT, $BACKURL, $USERIP,$key];
        $SIGN = md5(implode('|',$sign_arr));
        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <USERIP>'.$USERIP.'</USERIP>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <TYPE>'.$TYPE.'</TYPE>
        <MCHNTORDERID>'.$MCHNTORDERID.'</MCHNTORDERID>
        <USERID>'.$USERID.'</USERID>
        <AMT>'.$AMT.'</AMT>
        <PROTOCOLNO>'.$PROTOCOLNO.'</PROTOCOLNO>
        <NEEDSENDMSG>'.$NEEDSENDMSG.'</NEEDSENDMSG>
        <BACKURL>'.$BACKURL.'</BACKURL>
        <REM1></REM1>
        <REM2></REM2>
        <REM3></REM3>
        <SIGNTP>'.$SIGNTP.'</SIGNTP>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';
        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
//        $result = Curl::curlPostHttps("https://mpay.fuiou.com/newpropay/order.pay",$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
        echo '<pre>';
        print_r($arr_result);exit;
        if($arr_result['RESPONSECODE'] == '0000'){
            $return_data['OrderSn'] = $arr_result['MCHNTORDERID']; //商户订单号
            $return_data['TradeNo'] = $arr_result['ORDERID']; //富友订单号
            $return_data['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
            $res = M('loans_hklist')->where(array('ID'=>26))->save($return_data);
            if($res!==false){
                $this->ajaxReturn(1,"支付成功，返回中~");
            }else{
                $this->ajaxReturn(2,"支付失败");
            }

        }else{
            return ['status'=>0,'msg'=>$arr_result['RESPONSEMSG'],'data'=>[]];
        }
    }


    //还款支付回调地址
    public function backhkquery(){
        $data =$_REQUEST;
        $result=$this->checksign($data);
        file_put_contents('1527.txt',$data);
        file_put_contents('1528.txt',json_encode($data));
        if ($result){
            if($data['RESPONSECODE']== "0000"){
                $order = M('loans_hklist')->field('ID,PayStatus')->where(array('OrderSn'=>$data['MCHNTORDERID']))->find();
                if($order['PayStatus']=='0'){
                    $result =$this->change_hkorder_data($data['MCHNTORDERID'],$data['ORDERID']);
                }
            }
        }
        echo "success";
    }

    /**
     * 测试查询绑卡
     * @return array
     */
    public function cxbk()
    {
        $header = array(
            '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        $VERSION = '1.0';
        $MCHNTCD = $this->config['mchntCd'];
        $key = $this->config['key'];
        $USERID = 7;
        $SIGNTP = 'MD5';
        //待签名数组
        $sign_arr = [$VERSION, $MCHNTCD, $USERID,$key];
        $SIGN = md5(implode('|',$sign_arr));
        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <USERID>'.$USERID.'</USERID>
        <SIGNTP>'.$SIGNTP.'</SIGNTP>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';
        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
        $result = Curl::curlPostHttps("https://mpay.fuiou.com/newpropay/bindQuery.pay",$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
        echo '<pre>';
        print_r($arr_result);exit;
        if($arr_result['RESPONSECODE'] == '0000'){
            $return_data['OrderSn'] = $arr_result['MCHNTORDERID']; //商户订单号
            $return_data['TradeNo'] = $arr_result['ORDERID']; //富友订单号
            $return_data['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
            $res = M('loans_hklist')->where(array('ID'=>26))->save($return_data);
            if($res!==false){
                $this->ajaxReturn(1,"支付成功，返回中~");
            }else{
                $this->ajaxReturn(2,"支付失败");
            }

        }else{
            return ['status'=>0,'msg'=>$arr_result['RESPONSEMSG'],'data'=>[]];
        }
    }

    //判断是否登录
    public function checkToken($token){
        $this->token = $token;
        if(!$this->token){
            $this->ajaxReturn(3,'请先登录');
        }
        $this->member = M('mem_info')->where(array('Token'=>$this->token,'IsDel'=>'0'))->find();
        $this->uid = $this->member['ID'];
        if(!$this->member){
            $this->ajaxReturn(3,'token失效，请重新登录');
        }
        $expire = $this->member['Expire'];
        if(time() > $expire){
            $this->ajaxReturn(3,'token已过期，请重新登录');
        }
    }
}
