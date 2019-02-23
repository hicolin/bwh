<?php
namespace Home\Controller;
use Think\Controller;

class MoheController extends HomeController {
    //数聚魔盒回调地址
    public function index(){
        $notify=I('post.');
        //-------把信息记录下来,测试白屏问题-----start
           // if(!file_exists("logo_zhifubao.txt")){ $fp = fopen("logo_zhifubao.txt","wb"); fclose($fp);  }
           // $str = file_get_contents('logo_zhifubao.txt');
           // foreach($notify as $k=>$v){
           //   $str .= " -  - ".$k.":".$v;
           // }
           // $str .= " -  -  -  - ".date("Y-m-d H:i:s")."\r\n";
           // $fp = fopen("logo_zhifubao.txt","wb");
           // fwrite($fp,$str);
           // fclose($fp);
        //-------把信息记录下来,测试白屏问题-----end
    	$notify_event=$notify['notify_event'];
    	$notify_data=$notify['notify_data'];
        $notify_data = str_replace('&quot;','"',$notify_data); 
    	if($notify_event=='SUCCESS'){
    		$result=json_decode($notify_data,true);
    		if($result['data']['channel_type']=='YYS'){
    			//手机认证
    			$meminfos=M('mem_info')->field('ID')->where(array('Mobile'=>$result['data']['user_mobile'],'IsDel'=>'0'))->find();
    			if($meminfos){
    				$sdata=array(
    					'UserID'=>$meminfos['ID'],
    					'ZUserName'=>$result['data']['real_name'],
                        'IsPP'=>'0',
    					'Status'=>'1',
    					'RenzTime'=>date('Y-m-d H:i:s'),
    					'TaskID'=>$result['task_id'],
    					);
                    //查询的详细内容
                    $details=$this->mobiledetails($result['task_id']);
                    if($details['data']['task_data']['account_info']['net_time']){
                        $sdata['OpenDate']=$details['data']['task_data']['account_info']['net_time'];
                    }
                    if($details['data']['task_data']['account_info']['net_time']){
                        $sdata['AccountBalance']=$details['data']['task_data']['account_info']['account_balance']/100;
                    }
                    if($details['data']['task_data']['call_info']){
                        //6个月的通话记录
                        $billArr=array();
                        foreach($details['data']['task_data']['call_info'] as $k=>$v){
                            if($v['call_record']){
                                foreach($v['call_record'] as $k2=>$v2){
                                    $onebill=array();
                                    $onebill['tel']=$v2['call_other_number'];
                                    $onebill['times']=$v2['call_start_time'];
                                    $onebill['conways']=$v2['call_land_type'];
                                    $onebill['contype']=$v2['call_type_name'];
                                    $onebill['longs']=$v2['call_time'];
                                    $onebill['consite']=$v2['call_address'];
                                    $billArr[]=$onebill;
                                }
                            }
                        }
                        if($billArr){
                            $sdata['CallBill']=serialize($billArr);
                        }
                    }
    				//查看有没有,有就更新,没有就添加
	                $checkrest=M('renzen_mobile')->field('ID')->where(array('UserID'=>$meminfos['ID'],'IsDel'=>'0'))->find();
	                if($checkrest){
	                    $result=M('renzen_mobile')->where(array('ID'=>$checkrest['ID']))->save($sdata);
	                }else{
	                    $result=M('renzen_mobile')->add($sdata);
	                }
    			}
    		}elseif($result['data']['channel_type']=='DS' && $result['data']['channel_code']=='000003'){
                //淘宝认证
                $taobaoinfo=M('renzen_taobao')->field('ID')->where(array('TaskID'=>$result['task_id']))->find();
                if($taobaoinfo){
                    $sdata=array(
                        'BDMobile'=>$result['data']['user_mobile'],
                        'UserName'=>$result['data']['real_name'],
                        'YZStatus'=>'1',
                        'Status'=>'1',
                        'RenzTime'=>date('Y-m-d H:i:s'),
                        );
                    //查询的详细内容
                    $details=$this->taobaodetails($result['task_id']);
                    if($details['data']['task_data']['base_info']['user_level']){
                        $sdata['Levels']=$details['data']['task_data']['base_info']['user_level'];
                    }
                    if($details['data']['task_data']['account_info']['account_balance']){
                        $sdata['Balance']=$details['data']['task_data']['account_info']['account_balance']/100;
                    }
                    if($details['data']['task_data']['account_info']['financial_account_balance']){
                        $sdata['JBalance']=$details['data']['task_data']['account_info']['financial_account_balance']/100;
                    }
                    if($details['data']['task_data']['account_info']['consume_quota']){
                        $sdata['XFQuote']=$details['data']['task_data']['account_info']['consume_quota']/100;
                    }
                    if($details['data']['task_data']['account_info']['credit_quota']){
                        $sdata['XYQuote']=$details['data']['task_data']['account_info']['credit_quota']/100;
                    }
                    if($details['data']['task_data']['account_info']['zhima_point']){
                        $sdata['ZmScore']=$details['data']['task_data']['account_info']['zhima_point'];
                    }
                    if($details['data']['task_data']['account_info']['jiebei_quota']){
                        $sdata['JieBei']=$details['data']['task_data']['account_info']['jiebei_quota']/100;
                    }
                    if($details['data']['task_data']['receiver_list']){
                        //收货地址
                        $receiverArr=array();
                        foreach($details['data']['task_data']['receiver_list'] as $k=>$v){
                            $oneaddress=array();
                            $oneaddress['zip']=$v['zip_count'];
                            if($v['mobile']){
                                $oneaddress['tel']=$v['mobile'];
                            }elseif($v['telephone']){
                                $oneaddress['tel']=$v['telephone'];
                            }
                            $oneaddress['name']=$v['name'];
                            $oneaddress['address']=$v['area'].$v['address'];
                            $receiverArr[]=$oneaddress;
                        }
                        if($receiverArr){
                            $sdata['Receivers']=serialize($receiverArr);
                        }
                    }
                    $result=M('renzen_taobao')->where(array('ID'=>$taobaoinfo['ID']))->save($sdata);
                }
            }elseif($result['data']['channel_type']=='DS' && $result['data']['channel_code']=='000004'){
                //支付宝认证
                $alipayinfo=M('renzen_alipay')->field('ID')->where(array('TaskID'=>$result['task_id']))->find();
                if($alipayinfo){
                    $sdata=array(
                        'ZFBMobile'=>$result['data']['user_mobile'],
                        'Status'=>'1',
                        'RenzTime'=>date('Y-m-d H:i:s'),
                        );
                    //查询的详细内容
                    $details=$this->alipaydetails($result['task_id']);
                    if($details['data']['task_data']['account_info']['binded_bankcard_amount']){
                        $sdata['BankSum']=$details['data']['task_data']['account_info']['binded_bankcard_amount'];
                    }
                    if($details['data']['task_data']['account_info']['email']){
                        $sdata['Email']=$details['data']['task_data']['account_info']['email'];
                    }
                    if($details['data']['task_data']['assets_info']['assets_balance']){
                        $sdata['Balance']=$details['data']['task_data']['assets_info']['assets_balance']/100;
                    }
                    if($details['data']['task_data']['assets_info']['huabei_quota']){
                        $sdata['HuabeiLimit']=$details['data']['task_data']['assets_info']['huabei_quota']/100;
                    }
                    if($details['data']['task_data']['assets_info']['consume_quota']){
                        $sdata['HuabeiRet']=$details['data']['task_data']['assets_info']['consume_quota']/100;
                    }
                    if($details['data']['task_data']['assets_info']['huabei_balance']){
                        $sdata['HuabeiBalance']=$details['data']['task_data']['assets_info']['huabei_balance']/100;
                    }
                    if($details['data']['task_data']['account_info']['taobao_name']){
                        $sdata['TaobaoName']=$details['data']['task_data']['account_info']['taobao_name'];
                    }
                    $result=M('renzen_alipay')->where(array('ID'=>$alipayinfo['ID']))->save($sdata);
                }
            }
    	    echo json_encode(array('code'=>'0','message'=>'回调处理成功'));
    	}
    }
    //根据taskid查询淘宝认证的详情 $taskid
    public function taobaodetails($taskid){
        $binfos=M('sys_basicinfo')->field('MhCode,MhKey')->find();
        $url='https://api.shujumohe.com/octopus/task.unify.query/v3?partner_code='.$binfos['MhCode'].'&partner_key='.$binfos['MhKey'];
        $data=array(
            'task_id'=>$taskid,
            );
        $retdata=$this->https_request2($url,$data);
        return $retdata;
    }
    //根据taskid查询支付宝认证的详情 $taskid
    public function alipaydetails($taskid){
        $binfos=M('sys_basicinfo')->field('MhCode,MhKey')->find();
        $url='https://api.shujumohe.com/octopus/task.unify.query/v3?partner_code='.$binfos['MhCode'].'&partner_key='.$binfos['MhKey'];
        $data=array(
            'task_id'=>$taskid,
            );
        $retdata=$this->https_request2($url,$data);
        return $retdata;
    }
    //根据taskid查询手机认证的详情 $taskid
    public function mobiledetails($taskid){
        $binfos=M('sys_basicinfo')->field('MhCode,MhKey')->find();
        $url='https://api.shujumohe.com/octopus/task.unify.query/v4?partner_code='.$binfos['MhCode'].'&partner_key='.$binfos['MhKey'];
        $data=array(
            'task_id'=>$taskid,
            );
        $retdata=$this->https_request2($url,$data);
        return $retdata;
    }
    //通过api地址处理
    public function https_request2($url,$data = null){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/json;charset=utf-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        //return $output;
        return json_decode($output,true);
    }

}