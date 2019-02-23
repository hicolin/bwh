<?php
/**
 * 富友支付接口
 * Created by PhpStorm.
 * User: xuyuanhua
 * Date: 2017/9/14
 * Time: 09:49
 */

namespace FuYou;
use FuYou\Curl as CurlService;
use FuYou\Crypt3Des as Crypt3DesService;

use think\Exception;

class Fuiou
{
    private $config = [];

    private $config_list = [
        'test' => [
            'url_bind_msg' => 'http://www-1.fuiou.com:18670/mobile_pay/newpropay/bindMsg.pay',
            'url_bind_commit' => 'http://www-1.fuiou.com:18670/mobile_pay/newpropay/bindCommit.pay',
            'url_newpropay_order' => 'http://www-1.fuiou.com:18670/mobile_pay/newpropay/order.pay',
            'merchant' => '0002900F0096235',
            'key' => '5old71wihg2tqjug9kkpxnhx9hiujoqj',

        ],
        'official' => [
            'url_bind_msg'=>'https://mpay.fuiou.com/newpropay/bindMsg.pay',
            'url_bind_commit' => 'https://mpay.fuiou.com/newpropay/bindCommit.pay',
            'url_newpropay_order' => 'https://mpay.fuiou.com/newpropay/order.pay',
            'merchant' => '',
            'key' => '',
        ]
    ];

    public function __construct($api_type)
    {
        $this->config = isset($this->config_list[$api_type])?$this->config_list[$api_type]:[];
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
     */
    public function bind_msg($merchant,$key,$user_id,$order_no,$account,$card_no,$id_card,$mobile){

        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];

        $VERSION = '1.0';
        $MCHNTCD = $merchant;
        $USERID = $user_id;
        $TRADEDATE = date('Ymd');
        $MCHNTSSN = $order_no;
        $ACCOUNT = $account;
        $CARDNO = $card_no;
        $IDTYPE = '0';
        $IDCARD = $id_card;
        $MOBILENO = $mobile;
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
        $param['MCHNTCD'] = $merchant;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3DesService::encrypt_base64($APIFMS,$key);

        $result = CurlService::curlPostHttps($this->config['url_bind_msg'],$param,$header);
        $xml_result = Crypt3DesService::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xmlToArray($xml_result);
        if($arr_result['RESPONSECODE'] == '0000'){
            return ['status'=>1,'msg'=>'success','data'=>$arr_result['MCHNTSSN']];
        }else{
            return ['status'=>0,'msg'=>$arr_result['RESPONSEMSG'],'data'=>$arr_result['MCHNTSSN']];
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
     */
    public function bind_commit($merchant,$key,$user_id,$order_no,$account,$card_no,$id_card,$mobile,$code){

        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];

        $VERSION = '1.0';
        $MCHNTCD = $merchant;
        $USERID = $user_id;
        $TRADEDATE = date('Ymd');
        $MCHNTSSN = $order_no;
        $ACCOUNT = $account;
        $CARDNO = $card_no;
        $IDTYPE = '0';
        $IDCARD = $id_card;
        $MOBILENO = $mobile;
        $MSGCODE = $code;
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
        $param['MCHNTCD'] = $merchant;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3DesService::encrypt_base64($APIFMS,$key);

        $result = CurlService::curlPostHttps($this->config['url_bind_commit'],$param,$header);
        $xml_result = Crypt3DesService::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xmlToArray($xml_result);
        if($arr_result['RESPONSECODE'] == '0000'){
            $return_data['MCHNTSSN'] = $arr_result['MCHNTSSN'];
            $return_data['PROTOCOLNO'] = $arr_result['PROTOCOLNO'];
            return ['status'=>1,'msg'=>'success','data'=>$return_data];
        }else{
            return ['status'=>0,'msg'=>$arr_result['RESPONSEMSG'],'data'=>$arr_result['MCHNTSSN']];
        }
    }


    /**
     * 协议支付接口
     * @param $user_id
     * @param $order_no
     * @param $user_ip
     * @param $amt
     * @param $protolno
     * @param $back_url
     * @return array
     */
    public function newpropay_order($merchant,$key,$user_id,$order_no,$user_ip,$amt,$protolno,$back_url){

        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];

        $VERSION = '1.0';
        $USERIP = $user_ip;
        $MCHNTCD = $merchant;
        $TYPE = '03';
        $MCHNTORDERID = $order_no;
        $USERID = $user_id;
        $AMT = $amt;
        $PROTOCOLNO = $protolno;
        $NEEDSENDMSG  = '0';

        $BACKURL = $back_url;
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
        $param['MCHNTCD'] = $merchant;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3DesService::encrypt_base64($APIFMS,$key);

        $result = CurlService::curlPostHttps($this->config['url_newpropay_order'],$param,$header);
        $xml_result = Crypt3DesService::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xmlToArray($xml_result);
        if($arr_result['RESPONSECODE'] == '0000'){
            $return_data['MCHNTORDERID'] = $arr_result['MCHNTORDERID']; //商户订单号
            $return_data['ORDERID'] = $arr_result['ORDERID']; //富友订单号
            $return_data['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
            return ['status'=>1,'msg'=>'success','data'=>$return_data];
        }else{
            return ['status'=>0,'msg'=>$arr_result['RESPONSEMSG'],'data'=>[]];
        }
    }

}