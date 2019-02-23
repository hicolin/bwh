<?php
/**
 * 

 * 功能说明: 融宝支付
 */
namespace Home\Controller;
class RongbaoqueryController extends HomeController{

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

    //还款支付回调地址
    public function hkquery(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //接收参数
        $merchantId = $_REQUEST['merchant_id'];
        $data = $_REQUEST['data'];
        $encryptkey = $_REQUEST['encryptkey'];

        $Rbpay=new \Extend\Rbpay();
        $encryptkey = $Rbpay->RSADecryptkey($encryptkey,$merchantPrivateKey);
        $decryData = $Rbpay->AESDecryptResponse($encryptkey, $data);
        $jsonObject = json_decode($decryData,true);

        $status = $jsonObject['status'];
        $sign = $jsonObject['sign'];
        $paramarr = array(
            'merchant_id' => $jsonObject['merchant_id'],    //商户号
            'trade_no' => $jsonObject['trade_no'],          //融宝交易流水号
            'order_no' => $jsonObject['order_no'],          //会员订单号
            'total_fee' => $jsonObject['total_fee'],        //支付金额
            'status' => $jsonObject['status'],              //支付状态：TRADE_FAILURE支付失败，TRADE_FINISHED支付成功
            'notify_id' => $jsonObject['notify_id'],         //通知编号ID
            'result_code' => $jsonObject['result_code'],      //错误码
            'result_msg' => $jsonObject['result_msg'],        //错误信息
        );
        //生成签名
        $data = $Rbpay->orderstr($paramarr);
        $mysign = $Rbpay->sign_rsa($data,$merchantPrivateKey);

        if ($mysign === $sign){
            if($status === "TRADE_FINISHED"){
                $order = M('loans_hklist')->field('ID,PayStatus')->where(array('OrderSn'=>$jsonObject['order_no']))->find();
                if($order['PayStatus']=='0'){
                    $result =$this->change_hkorder_data($jsonObject['order_no'],$jsonObject['trade_no']);
                }
            }
        }
        echo "success";
    }
    //续借支付回调地址
    public function xjquery(){
        $merchant_id    =$this->config['merchant_id'];              //商户号
        $merchantPrivateKey=$this->config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$this->config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$this->config['apiKey'];                   //apiKey

        //接收参数
        $merchantId = $_REQUEST['merchant_id'];
        $data = $_REQUEST['data'];
        $encryptkey = $_REQUEST['encryptkey'];

        $Rbpay=new \Extend\Rbpay();
        $encryptkey = $Rbpay->RSADecryptkey($encryptkey,$merchantPrivateKey);
        $decryData = $Rbpay->AESDecryptResponse($encryptkey, $data);
        $jsonObject = json_decode($decryData,true);

        $status = $jsonObject['status'];
        $sign = $jsonObject['sign'];
        $paramarr = array(
            'merchant_id' => $jsonObject['merchant_id'],    //商户号
            'trade_no' => $jsonObject['trade_no'],          //融宝交易流水号
            'order_no' => $jsonObject['order_no'],          //会员订单号
            'total_fee' => $jsonObject['total_fee'],        //支付金额
            'status' => $jsonObject['status'],              //支付状态：TRADE_FAILURE支付失败，TRADE_FINISHED支付成功
            'notify_id' => $jsonObject['notify_id'],         //通知编号ID
            'result_code' => $jsonObject['result_code'],      //错误码
            'result_msg' => $jsonObject['result_msg'],        //错误信息
        );
        //生成签名
        $data = $Rbpay->orderstr($paramarr);
        $mysign = $Rbpay->sign_rsa($data,$merchantPrivateKey);

        if ($mysign === $sign){
            if($status === "TRADE_FINISHED"){
                $order = M('loans_xjapplylist')->field('ID,PayStatus')->where(array('OrderSn'=>$jsonObject['order_no']))->find();
                if($order['PayStatus']=='0'){
                    $result =$this->change_xjorder_data($jsonObject['order_no'],$jsonObject['trade_no']);
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
                    //统计会员完成的总的借款金额，并给期提升额度
                    selfpromotes($dailinfos['UserID']);
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

}