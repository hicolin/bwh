<?php
/**
 * 功能说明: 借款线上还款，续借线上支付(合利宝)
 */
namespace Api\Controller\Center;
use Api\Controller\Core\BaseController;
use XBCommon\XBCache;
class HelibaoController extends BaseController{

    public function _initialize(){
        parent::_initialize();
        //配置参数
        $setsinfo=M('sys_inteparameter')->field('ID,ParaName,ParaValue')->where(array('IntegrateID'=>'14'))->select();
        $setArr=array();
        foreach($setsinfo as $k=>$v){
            $setArr[$v['ParaName']]=$v['ParaValue'];
        }
        $this->config = array(
            'merchant_id' => $setArr['merchant_id'],    //商户号
            'signkey' => $setArr['signkey'],    //签名密钥
            'url'=>$setArr['url'],//网银请求的页面地址
        );
     }
    /**
     * @功能说明: 还款支付&续借支付（选择银行卡）
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/index
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
     * @功能说明: 还款支付&续借支付(输入新银行卡首次支付)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/quickpay
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","bankcode":"5452545454","mobile":"18356901596"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  bankcode银行卡号
     *   mobile银行预留手机号
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function quickpay(){
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
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Helibaoquery/hkquery';
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Helibaoquery/xjquery';
        }
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息异常');
        }
        if(!$json_data['bankcode']){
            AjaxJson(0,0,'必须填写银行卡');
        }
        if(!$json_data['mobile']){
            AjaxJson(0,0,'必须填写银行预留手机号码');
        }
        $agree=M('rongbao_agree')->where(array('BankCode'=>$json_data['bankcode'],"UserID"=>$UserID,'Status'=>'3','IsDel'=>'0'))->find();
        if ($agree){
            AjaxJson(0,0,'该银行卡已被签约通过');
        }
        $meminfos=M('mem_info')->field('ID,Mobile,TrueName,IDCard')->find($UserID);

        /*请求支付*/
        $total_fee='0.2';//默认是测试
        if(get_basic_info('Payceshi')=='1'){
            //正式
            $total_fee=trim($orderinfo['TotalMoney']);
        }
        //组织下单操作
        $P1_bizType = 'QuickPayCreateOrder';
        $P2_customerNumber =$this->config['merchant_id'];//商户号
        $P3_userId =  $UserID;
        $P4_orderId =  $orderinfo["OrderSn"];
        $P5_timestamp = date('Ymdhis',time());//时间戳
        $P6_payerName =  $meminfos['TrueName'];
        $P7_idCardType =  'IDCARD';
        $P8_idCardNo =  $meminfos['IDCard'];//身份证号，aes加密
        $P9_cardNo =  $json_data['bankcode'];//银行卡号，aes加密
        $P10_year ='';
        $P11_month =  '';
        $P12_cvv2 = '';
        $P13_phone =  $json_data['mobile'];//银行预留手机号，aes加密
        $P14_currency =  'CNY';
        $P15_orderAmount =  $total_fee;
        $P16_goodsName =  '苹果';
        $P17_goodsDesc = '';
        $P18_terminalType ='IMEI';
        $P19_terminalId = '122121212121';
        $P20_orderIp = $_SERVER['REMOTE_ADDR'];
        $P21_period =  '';
        $P22_periodUnit = '';
        $P23_serverCallbackUrl =$notify_url;

        $signkey_quickpay = $this->config['signkey'];//密钥key  签名密钥
        //构造支付签名串
        $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_payerName&$P7_idCardType&$P8_idCardNo&$P9_cardNo&$P10_year&$P11_month&$P12_cvv2&$P13_phone&$P14_currency&$P15_orderAmount&$P16_goodsName&$P17_goodsDesc&$P18_terminalType&$P19_terminalId&$P20_orderIp&$P21_period&$P22_periodUnit&$P23_serverCallbackUrl&$signkey_quickpay";

    	$sign= md5($signFormString);//MD5签名
        
    	//$Client = new \Extend\HttpClient($_SERVER['REMOTE_ADDR']); 
        $url =$this->config['url'];//网银请求的页面地址  request url
        //post的参数
        $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_currency'=>$P14_currency,'P15_orderAmount'=>$P15_orderAmount,'P16_goodsName'=>$P16_goodsName,'P17_goodsDesc'=>$P17_goodsDesc,'P18_terminalType'=>$P18_terminalType,'P19_terminalId'=>$P19_terminalId,'P20_orderIp'=>$P20_orderIp,'P21_period'=>$P21_period,'P22_periodUnit'=>$P22_periodUnit,'P23_serverCallbackUrl'=>$P23_serverCallbackUrl,'sign'=>$sign);
        //$pageContents = HttpClient::quickPost($url, $params);  //发送请求 send request
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        $retdata=array(
            'timestamps'=>$P5_timestamp,//时间戳
            'ymobile'=>$P13_phone,//银行预留手机号
            'bankcode'=>$P9_cardNo,//银行卡号
            );
        if($result['rt2_retCode']=='0000'){
            AjaxJson(0,1,$result['rt3_retMsg'],$retdata);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg'],$retdata);
        }
    }
    /**
     * @功能说明: 还款支付&续借支付(首次支付发送短信)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/sendmsg
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","timestamps":"18356901596","bankcode":"5452545454","ymobile":"18356901596"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  timestamps时间戳
     *   ymobile银行预留手机号  bankcode 银行卡号
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function sendmsg(){
        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        if(!$json_data['timestamps']){
            AjaxJson(0,0,'时间戳必须传递');
        }
        if(!$json_data['ymobile']){
            AjaxJson(0,0,'必须传递预留手机号码');
        }
        if(!$json_data['bankcode']){
            AjaxJson(0,0,'必须传递银行卡号');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }
        if (!$orderinfo){
            AjaxJson(0,0,'订单信息有误');
        }

        //组织发送短信操作
        $P1_bizType='QuickPaySendValidateCode';
        $P2_customerNumber=$this->config['merchant_id'];//商户号
        $P3_orderId=$orderinfo["OrderSn"];
        $P4_timestamp=$json_data['timestamps'];
        $P5_phone=$json_data['ymobile'];

        $signkey=$this->config['signkey'];//密钥key  签名密钥
        $url =$this->config['url'];//网银请求的页面地址  request url
        
        //发送验证码的签名串
        $orinMessage = "&$P1_bizType&$P2_customerNumber&$P3_orderId&$P4_timestamp&$P5_phone&$signkey";
        $sign = md5($orinMessage);
        //构造请求参数
        $params=array(
            'P1_bizType'=>$P1_bizType,
            'P2_customerNumber'=>$P2_customerNumber,
            'P3_orderId'=>$P3_orderId,
            'P4_timestamp'=>$P4_timestamp,
            'P5_phone'=>$P5_phone,
            'sign'=>$sign,
            );
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        $retdata=array(
            'timestamps'=>$json_data['timestamps'],//时间戳
            'ymobile'=>$json_data['ymobile'],//银行预留手机号
            'bankcode'=>$json_data['bankcode'],//银行卡号
            );
        if($result['rt2_retCode']=='0000'){
            AjaxJson(0,1,$result['rt3_retMsg'],$retdata);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg'],$retdata);
        }
    }
    /**
     * @功能说明: 还款支付&续借支付(确认支付)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/confirmpay
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","timestamps":"18356901596","bankcode":"5452545454","ymobile":"18356901596","msgcode":"25365"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  timestamps时间戳  ymobile银行预留手机号
     *   msgcode 支付短信验证码
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function confirmpay(){
        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');

        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        if(!$json_data['timestamps']){
            AjaxJson(0,0,'时间戳必须传递');
        }
        if(!$json_data['msgcode']){
            AjaxJson(0,0,'必须传递支付短信验证码');
        }
        if(!$json_data['bankcode']){
            AjaxJson(0,0,'必须传递银行卡号');
        }
        if(!$json_data['ymobile']){
            AjaxJson(0,0,'预留手机号不能为空');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }
        if (!$orderinfo){
            AjaxJson(0,0,'订单信息有误');
        }

        //组织发送短信操作
        $P1_bizType='QuickPayConfirmPay';
        $P2_customerNumber=$this->config['merchant_id'];//商户号
        $P3_orderId=$orderinfo["OrderSn"];
        $P4_timestamp=$json_data['timestamps'];
        $P5_validateCode=$json_data['msgcode'];
        $P6_orderIp=$_SERVER['REMOTE_ADDR'];

        $signkey=$this->config['signkey'];//密钥key  签名密钥
        $url =$this->config['url'];//网银请求的页面地址  request url
        
        //发送验证码的签名串
        $orinMessage = "&$P1_bizType&$P2_customerNumber&$P3_orderId&$P4_timestamp&$P5_validateCode&$P6_orderIp&$signkey";
        $sign = md5($orinMessage);
        //构造请求参数
        $params=array(
            'P1_bizType'=>$P1_bizType,
            'P2_customerNumber'=>$P2_customerNumber,
            'P3_orderId'=>$P3_orderId,
            'P4_timestamp'=>$P4_timestamp,
            'P5_validateCode'=>$P5_validateCode,
            'P6_orderIp'=>$P6_orderIp,
            'sign'=>$sign,
            );
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        if($result['rt2_retCode']=='0000' && $result['rt9_orderStatus']=='SUCCESS'){
            //支付成功
            $meminfos=M('mem_info')->field('ID,Mobile,TrueName,IDCard')->find($UserID);
            //保存绑卡的id
            $bankdata=array(
                'UserID'=>$UserID,
                'RealName'=>$meminfos['TrueName'],
                'CardID'=>$meminfos['IDCard'],
                'BankCode'=>$json_data['bankcode'],
                'Mobile'=>$json_data['ymobile'],
                'AgreeNo'=>$result['rt10_bindId'],
                'Addtime'=>date('Y-m-d H:i:s'),
                'Status'=>'3',
                );
            M('rongbao_agree')->add($bankdata);
            AjaxJson(0,1,$result['rt3_retMsg']);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg']);
        }
    }

    /**
     * @功能说明: 还款支付&续借支付(绑卡支付短信-选择已有的银行卡)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/bindkasend
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","bankid":"2"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  bankid银行卡id
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
    public function bindkasend(){
        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');
        if (!$json_data['bankid']){
            AjaxJson(0,0,'请选择支付的银行卡');
        }
        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        $agreeinfo = M('rongbao_agree')->field('ID,AgreeNo,Mobile')->where(array('ID'=>$json_data['bankid'],'UserID'=>$UserID,'Status'=>'3','IsDel'=>'0'))->find();
        if(!$agreeinfo){
            AjaxJson(0,0,'签约的银行卡信息异常');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
        }
        if (!$orderinfo){
            AjaxJson(0,0,'订单信息有误');
        }
        /*请求支付*/
        $total_fee='0.2';//默认是测试
        if(get_basic_info('Payceshi')=='1'){
            //正式
            $total_fee=trim($orderinfo['TotalMoney']);
        }
        //组织发送短信操作
        $P1_bizType='QuickPayBindPayValidateCode';
        $P2_customerNumber=$this->config['merchant_id'];//商户号
        $P3_bindId=$agreeinfo['AgreeNo'];
        $P4_userId=$UserID;
        $P5_orderId=$orderinfo['OrderSn'];
        $P6_timestamp=date('Ymdhis',time());//时间戳
        $P7_currency='CNY';
        $P8_orderAmount=$total_fee;
        $P9_phone=$agreeinfo['Mobile'];

        $signkey=$this->config['signkey'];//密钥key  签名密钥
        $url =$this->config['url'];//网银请求的页面地址  request url
        
        //发送验证码的签名串
        $orinMessage = "&$P1_bizType&$P2_customerNumber&$P3_bindId&$P4_userId&$P5_orderId&$P6_timestamp&$P7_currency&$P8_orderAmount&$P9_phone&$signkey";
        $sign = md5($orinMessage);
        //构造请求参数
        $params=array(
            'P1_bizType'=>$P1_bizType,
            'P2_customerNumber'=>$P2_customerNumber,
            'P3_bindId'=>$P3_bindId,
            'P4_userId'=>$P4_userId,
            'P5_orderId'=>$P5_orderId,
            'P6_timestamp'=>$P6_timestamp,
            'P7_currency'=>$P7_currency,
            'P8_orderAmount'=>$P8_orderAmount,
            'P9_phone'=>$P9_phone,
            'sign'=>$sign,
            );
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        $retdata=array(
            'timestamps'=>date('Ymdhis',time()),//时间戳
            );
        if($result['rt2_retCode']=='0000'){
            AjaxJson(0,1,$result['rt3_retMsg'],$retdata);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg'],$retdata);
        }
    }
     /**
     * @功能说明: 还款支付&续借支付(绑卡支付操作-选择已有的银行卡)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Helibao/bindcardpay
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"cn.ekashequ.app","version":"v1.1","isaes":"1","data":{"stype":"1","id":"1","bankid":"2","timestamps":"18356901596","msgcode":"25365"}}
     *
     * stype付款类型:1还款支付 2续借支付  id支付订单id  bankid银行卡id timestamps 时间戳
     *  msgcode支付短信验证码
     * @返回信息: {'result'=>1,'message'=>'获取成功!'}
     */
     public function bindcardpay(){
        //获取数据流
        $json_data=get_json_data();
        $UserID=get_login_info('ID');
        if (!$json_data['bankid']){
            AjaxJson(0,0,'请选择支付的银行卡');
        }
        if(!in_array($json_data['stype'],array('1','2'))){
            AjaxJson(0,0,'付款类型不正确！');
        }
        if(!$json_data['timestamps']){
            AjaxJson(0,0,'时间戳必须传递');
        }
        $agreeinfo = M('rongbao_agree')->field('ID,AgreeNo,Mobile')->where(array('ID'=>$json_data['bankid'],'UserID'=>$UserID,'Status'=>'3','IsDel'=>'0'))->find();
        if(!$agreeinfo){
            AjaxJson(0,0,'签约的银行卡信息异常');
        }
        //验证订单信息
        $orderinfo='';
        if($json_data['stype']=='1'){
            //还款支付
            $orderinfo=M('loans_hklist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Helibaoquery/hkquery';
        }elseif($json_data['stype']=='2'){
            //续借支付
            $orderinfo=M('loans_xjapplylist')->field('ID,OrderSn,LoanNo,TotalMoney')->where(array('ID'=>$json_data['id'],'UserID'=>$UserID,'PayStatus'=>'0','PayType'=>'3','IsDel'=>'0'))->find();
            $notify_url="http://".$_SERVER['HTTP_HOST'].'/index.php/Helibaoquery/xjquery';
        }
        if (!$orderinfo){
            AjaxJson(0,0,'订单信息有误');
        }
        /*请求支付*/
        $total_fee='0.2';//默认是测试
        if(get_basic_info('Payceshi')=='1'){
            //正式
            $total_fee=trim($orderinfo['TotalMoney']);
        }

        //组织数据
        $P1_bizType='QuickPayBindPay';
        $P2_customerNumber=$this->config['merchant_id'];//商户号
        $P3_bindId=$agreeinfo['AgreeNo'];
        $P4_userId=$UserID;
        $P5_orderId=$orderinfo['OrderSn'];
        $P6_timestamp=$json_data['timestamps'];
        $P7_currency='CNY';
        $P8_orderAmount=$total_fee;
        $P9_goodsName='苹果';
        $P10_goodsDesc='';
        $P11_terminalType='IMEI';
        $P12_terminalId='122121212121';
        $P13_orderIp=$_SERVER['REMOTE_ADDR'];
        $P14_period='';
        $P15_periodUnit='';
        $P16_serverCallbackUrl=$notify_url;
        $P17_validateCode=$json_data['msgcode'];

        $signkey=$this->config['signkey'];//密钥key  签名密钥
        $url =$this->config['url'];//网银请求的页面地址  request url

        //构造form表单值的签名串
        $signFormString = "&".$P1_bizType."&".$P2_customerNumber."&".$P3_bindId."&".$P4_userId."&".$P5_orderId."&".$P6_timestamp."&".$P7_currency."&".$P8_orderAmount."&".$P9_goodsName."&".$P10_goodsDesc."&".$P11_terminalType."&".$P12_terminalId."&".$P13_orderIp."&".$P14_period."&".$P15_periodUnit."&".$P16_serverCallbackUrl."&".$signkey;

        //bingPay的支付sign
        $paySign = md5($signFormString);
        
        //构造请求参数
        $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_bindId'=>$P3_bindId,'P4_userId'=>$P4_userId,'P5_orderId'=>$P5_orderId,'P6_timestamp'=>$P6_timestamp,'P7_currency'=>$P7_currency,'P8_orderAmount'=>$P8_orderAmount,'P9_goodsName'=>$P9_goodsName,'P10_goodsDesc'=>$P10_goodsDesc,'P11_terminalType'=>$P11_terminalType,'P12_terminalId'=>$P12_terminalId,'P13_orderIp'=>$P13_orderIp,'P14_period'=>$P14_period,'P15_periodUnit'=>$P15_periodUnit,'P16_serverCallbackUrl'=>$P16_serverCallbackUrl,'P17_validateCode'=>$P17_validateCode,'sign'=>$paySign);

        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        if($result['rt2_retCode']=='0000' && $result['rt9_orderStatus']=='SUCCESS'){
            //支付成功
            AjaxJson(0,1,$result['rt3_retMsg']);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg']);
        }

     }

    //可用
   private function sendHttpRequest($data = null,$url)
    {
        $data = $this->buildQueryString ( $data );
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type:application/x-www-form-urlencoded;charset=UTF-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //$output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        return $output;
       // return json_decode($output,true);
    }
    function buildQueryString($data) {
        $querystring = '';
        if (is_array($data)) {
            // Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $querystring .= urlencode($key).'='.urlencode($val2).'&';
                    }
                } else {
                    $querystring .= urlencode($key).'='.urlencode($val).'&';
                }
            }
            $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {
            $querystring = $data;
        }
        return $querystring;
    }

}