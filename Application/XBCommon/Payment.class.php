<?php
/**
 * 
 *
 * 作    者: 陆恒
 * 修改时间: 2017-06-19 08:30
 * 功能说明:支付类。
 */
namespace XBCommon;
use Think\Controller;
class Payment extends Controller
{

    public function _initialize(){
        //载入微信接口信息
        vendor('WxPay.Api');
        vendor('WxPay.Config');
        vendor('WxPay.Data');
        vendor('WxPay.Exception');
        vendor('WxPay.Notify');
        vendor('WxPay.JsApiPay');
    }
    /**
     * @功能说明: 支付宝jsapi支付接口
     * @param  array  $Order  下单参数
     * @param  int  $tyle  支付类型  1：jsapi
     * @return string      下单结果
     */
    public function alipayOrder($Order,$tyle=1){
		
		//获取支付宝相应参数
		$info=M('sys_inteparameter')->where(array("IntegrateID"=>7))->select();
		foreach($info as $val){
			$alipay_config[$val['ParaName']]=$val['ParaValue'];
		}

        $wapPay = new \Org\alipay\wapPay($alipay_config);
        $out_trade_no = $Order['out_trade_no'];
        $subject = $Order['subject'];
        $total_amount = $Order['total_amount'];
        $body = $Order['body'];
        $res= $wapPay->index($out_trade_no,$subject,$total_amount,$body);
        return $res;
    }

	/**
     * @功能说明: 微信公众号支付接口
     * @param  array  $Order  下单参数
     * @param  int  $openId  支付方式JsApi 必须的参数
     * @return string      下单结果
     */
    public function weixinOrder($Order,$openId){

		//获取微信相应参数
		$info=M('sys_inteparameter')->where(array("IntegrateID"=>8))->select();
		foreach($info as $val){
			$alipay_config[$val['ParaName']]=$val['ParaValue'];
		}
        //统一下单
        $tools=new \JsApiPay($alipay_config);
        $input = new \WxPayUnifiedOrder;
        $input->SetBody($Order['body']);
        $input->SetAttach($Order['attach']);
        $input->SetOut_trade_no($Order['OrderSn']);
        $input->SetTotal_fee($Order['money']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($Order['goods_tag']);
        $input->SetNotify_url($Order['notify_url']);
        $input->SetTrade_type($Order['trade_type']);
        $input->SetOpenid($openId);
        $mod2 = new \WxPayApi($alipay_config);
        $orderInfo=$mod2->unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($orderInfo);
        return $jsApiParameters;
    }
    /**
     * @功能说明: 获取微信支付中的openid
     * @return string     $openId 支付方式JsApi 必须的参数
     */
    public function getOpenid(){
		//获取微信相应参数
		$info=M('sys_inteparameter')->where(array("IntegrateID"=>8))->select();
		foreach($info as $val){
			$alipay_config[$val['ParaName']]=$val['ParaValue'];
		}
        $tools=new \JsApiPay($alipay_config);
        //获取用户openid
        $openId = $tools->GetOpenid();
        return $openId;

    }

    /**
     *  作用：将xml转为array（微信）
     */
    public function xmlToArray($xml) {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
    /**
     *  作用：生成签名  用于支付完成后微信返回的签名的验证(微信)
     */
    public function makeSign($data){
        $key = M('sys_inteparameter')->where(array("IntegrateID"=>8, 'ParaName'=>"KEY"))->getField('ParaValue');
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        //$config=$this->config;
        $string_sign_temp=$string_a."&key=".$key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }
}