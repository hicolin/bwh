<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018/07/19 9:56
 * 功能说明: 借款线上还款，续借线上支付
 */
namespace Api\Controller\Center;
use Api\Controller\Core\BaseController;
use XBCommon\XBCache;
class Rongbao2Controller extends BaseController{

    public function _initialize(){
        parent::_initialize();
        //配置参数
        $setsinfo=M('sys_inteparameter')->where(array('IntegrateID'=>'11'))->select();
        $setArr=array();
        foreach($setsinfo as $k=>$v){
            $setArr[$v['ParaName']]=$v['ParaValue'];
        }
        $this->config = array(
            'merchant_id' => $setArr['merchant_id'],    //商户号ID
            'seller_email' => $setArr['seller_email'],    //商户邮箱
            'merchantPrivateKey' => "./rongbao/user-rsa.pem",   //商户私钥路径
            'reapalPublicKey' => "./rongbao/public-rsa.pem",    //融宝公钥路径
            'apiKey' => $setArr['apiKey'],      //apikey
            'url'=>$setArr['url'],
        );
     }
    /**
     * @功能说明: 还款支付&续借支付（选择银行卡）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/index
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","oid":"8885585"}}
     *  stype付款类型:1还款支付 2续借支付  id支付订单id    oid支付订单编号
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function index(){
        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $order='';
        if($json_data['stype']=='1'){
            //还款支付
            $order=M('loans_hklist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'OrderSn'=>$json_data['oid'],'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $order=M('loans_xjapplylist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'OrderSn'=>$json_data['oid'],'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }
        
        if (!$order){
            AjaxJson(0,0,'订单有误，请重新提交订单');
        }
        //已成功签约的银行卡
        $agreeList=M('rongbao_agree')->where(array('UserID'=>$UserID,'Status'=>'3','IsDel'=>'0'))->select();
        if(!$agreeList){
            $List=array(
                "order"=>$order,
                "agreeList"=>array(),
            );
            AjaxJson(1,1,'还没有签约的银行卡',$List);
        }

        foreach ($agreeList as $key=>$val){
            $agreeList[$key]['BankCode']=substr_replace($val['BankCode'],'*******',3,strlen($val['BankCode'])-6);
            $agreeList[$key]['Mobile']=substr_replace($val['Mobile'],'****',3,4);
        }
        $List=array(
            "order"=>$order,
            "agreeList"=>$agreeList,
        );
        AjaxJson(1,1,'有签约的银行卡',$List);
    }


    /**
     * @功能说明: 还款支付&续借支付（预签约保存银行卡）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/banksave
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","bankcode":"5452545454","realname":"姓名","card":"3403218555485","mobile":"18356901596"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  bankcode银行卡号 realname真实姓名
     * card身份证号  mobile银行预留手机号
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function banksave(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //获取数据流
        $para=get_json_data();
        $UserID=get_login_info('ID');
        //密文解密
        $json_data=json_decode(decrypt_pkcs7($para['data'],get_login_info('KEY'),get_login_info('IV')),true);
        if($json_data==false){
            AjaxJson(0,0,'很抱歉,提交的数据非法');
        }

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }

        if(!$orderinfo){
            AjaxJson(0,0,'订单信息异常');
        }
        if(!$json_data['bankcode']){
            AjaxJson(0,0,'必须填写银行卡');
        }
        if(!$json_data['realname']){
            AjaxJson(0,0,'必须填写真实姓名');
        }
        if(!$json_data['card']){
            AjaxJson(0,0,'必须填写身份证号');
        }
        if(!$json_data['mobile']){
            AjaxJson(0,0,'必须填写银行预留手机号码');
        }
        $agree=M('rongbao_agree')->where(array('BankCode'=>$json_data['bankcode'],"UserID"=>$UserID,'Status'=>'3','IsDel'=>'0'))->find();
        if ($agree){
            AjaxJson(0,0,'该银行卡已被签约通过');
        }
        $data=array(
            'UserID'=>$UserID,
            'BankCode'=>$json_data['bankcode'],
            'RealName'=>$json_data['realname'],
            'CardID'=>$json_data['card'],
            'Mobile'=>$json_data['mobile'],
            'Status'=>0,
            'UpdateTime'=>date('Y-m-d H:i:s'),
        );
        //将数据保存到协议表中
        $result=M('rongbao_agree')->add($data);
        if (!$result){
            AjaxJson(0,0,'验证异常');
        }
        //持卡人账户基本信息一致性与有效性的验证,
        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'member_id' => $UserID,    //会员ID
            'order_no' =>$orderinfo['OrderSn'],             //订单号
            'card_no' => $data['BankCode'],                 //银行卡号
            'owner' => $data['RealName'],                   //银行卡开户姓名
            'cert_no' => $data['CardID'],                   //身份证号
            'cert_type' => '01',                            //身份证号
            'phone'=> $data['Mobile'],                      //银行预留手机号
            'sign_type' => 'RSA',                           //签名类型
            'version' => '1.0.0'                            //版本号
        );
        //请求接口
        $url = $this->config['url'].'/delivery/authentication';
        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);
        if ($jsonObject){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)) {
                M('rongbao_agree')->where(array('ID' => $result))->setField('Status', 1);
                $retdata=array('aid'=>$result);
                AjaxJson(0,1,$jsonObject['result_msg'],$retdata);
            }else{
                M('rongbao_agree')->where(array('ID'=>$result))->setField('Status',2);
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            M('rongbao_agree')->where(array('ID'=>$result))->setField('Status',2);
            AjaxJson(0,0,'提交的数据有误');
        }
    }

    /**
     * @功能说明: 还款支付&续借支付（预签约短信验证）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/verifymsg
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","aid":"1","msgcode":"552585"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  aid保存的银行卡ID  msgcode短信验证码
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function verifymsg(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $order='';
        if($json_data['stype']=='1'){
            //还款支付
            $order=M('loans_hklist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $order=M('loans_xjapplylist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }

        if (!$order){
            AjaxJson(0,0,'订单信息有误');
        }
        //验证本次签约信息
        $agree=M('rongbao_agree')->where(array('ID'=>$json_data['aid'],'UserID'=>$UserID,'Status'=>1))->find();
        if (!$agree){
            AjaxJson(0,0,'签约信息的参数有误');
        }
        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'order_no' =>$order['OrderSn'],
            'check_code' => $json_data['msgcode'],
            'sign_type' => 'RSA',
            'version' => '1.0.0'

        );
        //访问储蓄卡签约服务
        $url = $this->config['url'].'/delivery/sign';

        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);

        if($jsonObject){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)){
                $data=array(
                    "AgreeNo"=>$jsonObject['sign_no'],
                    "Status"=>3
                );
                M('rongbao_agree')->where(array('ID'=>$json_data['aid']))->save($data);
                $retdata=array("AgreeNo"=>$jsonObject['sign_no']);
                AjaxJson(0,1,$jsonObject['result_msg'],$retdata);
            }else{
                //记录预签约状态
                M('rongbao_agree')->where(array('ID'=>$json_data['aid']))->setField('Status',4);
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            M('rongbao_agree')->where(array('ID'=>$json_data['aid']))->setField('Status',4);
            AjaxJson(0,0,'请求的参数有误');
        }
    }


    /**
     * @功能说明: 还款支付&续借支付（融宝支付）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/payment
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"2","aid":"1"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  aid保存的银行卡ID
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function payment(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if (!$json_data['aid']){
            AjaxJson(0,0,'请选择支付的银行卡');
        }

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Rongbaoquery/hkquery';
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Rongbaoquery/xjquery';
        }

        if(!$orderinfo){
            AjaxJson(0,0,'订单信息异常');
        }
        $agreeinfo = M('rongbao_agree')->where(array('ID'=>$json_data['aid'],'UserID'=>$UserID,'Status'=>'3','IsDel'=>'0'))->find();
        if(!$agreeinfo){
            AjaxJson(0,0,'签约的银行卡信息异常');
        }
        $ProName=M('order')->where(array('OrderSn'=>$orderinfo['LoanNo']))->getField('ProName');

        /*支付前先查询该银行卡协议号状态*/
        //参数数组
        $paramArr1 = array(
            'merchant_id' => $merchant_id,
            'member_id' => $UserID,
            'sign_no' => $agreeinfo['AgreeNo'],
            'card_no' => $agreeinfo['BankCode'],
            'sign_type' => 'RSA',
            'version' => '1.0.0'
        );
        $url1=$this->config['url']."/delivery/querycontract";
        $Rbpay=new \Extend\Rbpay();
        $jsonObject1=$Rbpay->verifypay($paramArr1, $url1, $merchantPrivateKey , $reapalPublicKey, $merchant_id);

        if($jsonObject1){
            if($jsonObject1['result_code']=="0000"){
                /*请求支付*/
                $total_fee='1';//默认是测试
                if(get_basic_info('Payceshi')=='1'){
                    //正式
                    $total_fee=trim($orderinfo['TotalMoney'])*100;
                }
                //参数数组
                $paramArr = array(
                    'merchant_id' => $merchant_id,
                    'member_id' => $UserID,
                    'currency' =>'156',
                    'total_fee' =>$total_fee,//以分为单位
                    'order_no' =>$orderinfo['OrderSn'],
                    'sign_no' => $agreeinfo['AgreeNo'],
                    'notify_url' =>$notify_url,
                    'title' => $ProName,
                    'body'=> "购买商品",
                    'sign_type' => 'RSA',
                    'version' => '1.0.0'
                );
                $url=$this->config['url']."/delivery/pay";
                $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);

                if($jsonObject){
                    $sign= $jsonObject['sign'];
                    if($jsonObject['result_code']=="3068" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)){
                        //exit(json_encode(array('result'=>1,'message'=>$jsonObject['result_msg'],'data'=>array())));
                        AjaxJson(0,1,$jsonObject['result_msg']);
                    }else{
                        exit(json_encode(array('result'=>0,'message'=>$jsonObject['result_msg'],'data'=>array())));
                        AjaxJson(0,0,'请求的参数有误');
                    }
                }else{
                    AjaxJson(0,0,'请求的参数有误');
                }
            }else{
                //记录该银行卡的状态
                M('rongbao_agree')->where(array('ID'=>$json_data['aid']))->setField('Status',5);
                AjaxJson(0,0,$jsonObject1['result_msg']);
            }
        }else{
            AjaxJson(0,0,'请求的参数有误');
        }
    }

    /**
     * @功能说明: 还款支付&续借支付（支付短信验证）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/verifypaymsg
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","aid":"2","id":"1","msgcode":"85585"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  aid保存的银行卡ID  msgcode短信验证码
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function verifypaymsg(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $order='';
        if($json_data['stype']=='1'){
            //还款支付
            $order=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $order=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }

        if (!$order){
            AjaxJson(0,0,'订单信息有误');
        }

        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'order_no' =>$order['OrderSn'],
            'check_code' => $json_data['msgcode'],
            'sign_type' => 'RSA',
            'version' => '1.0.0'
        );

        //访问储蓄卡签约服务
        $url = $this->config['url'].'/delivery/smspay';
        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);


        if($jsonObject){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)){
                //exit(json_encode(array('result'=>1,'message'=>$jsonObject['result_msg'],'data'=>array())));
                AjaxJson(0,1,$jsonObject['result_msg']);
            }else{
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            AjaxJson(0,0,'请求参数错误');
        }
    }

    /**
     * @功能说明: 还款支付&续借支付（支付短信重发接口）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Rongbao2/sendmsg
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function sendmsg(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        //验证订单信息
        $order='';
        if($json_data['stype']=='1'){
            //还款支付
            $order=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $order=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }

        if (!$order){
            AjaxJson(0,0,'订单信息有误');
        }
        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'order_no' =>$order['OrderSn'],
            'sign_type' => 'RSA',
            'version' => '1.0.0'
        );

        //访问储蓄卡签约服务
        $url = $this->config['url'].'/delivery/sms';
        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);

        if($jsonObject['sign']){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)){
                //exit(json_encode(array('result'=>1,'message'=>$jsonObject['result_msg'],'data'=>array())));
                AjaxJson(0,1,$jsonObject['result_msg']);
            }else{
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            AjaxJson(0,0,'请求参数错误');
        }
    }
}