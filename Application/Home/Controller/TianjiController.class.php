<?php

namespace Home\Controller;

use Think\Controller;

class TianjiController extends HomeController
{
    public $Appid = '2010343';

    //数聚魔盒回调地址
    public function index()
    {
        $notify = I('post.');
        //-------把信息记录下来,测试白屏问题-----start
//            if(!file_exists("logo_zhifubao.txt")){ $fp = fopen("logo_zhifubao.txt","wb"); fclose($fp);  }
//            $str = file_get_contents('logo_zhifubao.txt');
//            foreach($notify as $k=>$v){
//              $str .= " -  - ".$k.":".$v;
//            }
//            $str .= " -  -  -  - ".date("Y-m-d H:i:s")."\r\n";
//            $fp = fopen("logo_zhifubao.txt","wb");
//            fwrite($fp,$str);
//            fclose($fp);
        //-------把信息记录下来,测试白屏问题-----end
        $notify_state = $notify['state'];
        if ($notify_state == 'report') {
            //手机认证
            $meminfos = M('mem_info')->field('ID')->where(array('ID' => $notify['userId'], 'IsDel' => '0'))->find();
            if ($meminfos) {
                $sdata = array(
                    'UserID' => $notify['userId'],
                    'IsPP' => '0',
                    'Status' => '1',
                    'RenzTime' => date('Y-m-d H:i:s'),
                    'TaskID' => $notify['outUniqueId'],   //商户唯一标识id
                    'TUserID' => $notify['search_id'],    //详情查询ID
                );
                //查询的详细内容
                $infos = M('sys_inteparameter')->where(array('IntegrateID' => '15'))->select();
                foreach ($infos as $k => $v) {
                    if ($v['ParaName'] == 'AppID') {
                        $AppID = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'url') {
                        $urls = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'method') {
                        $method = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'detailmethod') {
                        $detailmethod = $v['ParaValue'];
                    }
                }
                $orgPrivateKey = file_get_contents("./tianji/rsa_private_key.pem");
                $bizData['userId'] = $notify['userId'];
                $bizData['outUniqueId'] = $notify['outUniqueId'];
                $bizData['reportType'] = "html";

                vendor('Tianji.OpenapiDevBase');
                $openapiDevBase = new \OpenapiDevBase();

                $details = $openapiDevBase->sendRequest($bizData, $detailmethod, $AppID, $urls, $orgPrivateKey);

                if ($details['error'] == 200 && $details['tianji_api_tianjireport_detail_response']) {
                    $report = $details['tianji_api_tianjireport_detail_response'];
                    if ($report) {
                        $sdata['SearchID'] = $notify['search_id'];
                        $sdata['MobileData'] = serialize($report);
                        $sdata['UpdateTime'] = date('Y-m-d H:i:s');
                        if ($report['json']['basic_info']['reg_time']) {
                            $sdata['OpenDate'] = $report['json']['basic_info']['reg_time'];
                        }
                        if ($report['json']['basic_info']['real_name']) {
                            $sdata['ZUserName'] = $report['json']['basic_info']['real_name'];
                        }
                        if ($report['json']['basic_info']['current_balance']) {
                            $sdata['AccountBalance'] = $report['json']['basic_info']['current_balance'];
                        }
                        if ($report['json']['call_log']) {
                            //6个月的通话记录
                            $billArr = array();
                            foreach ($report['json']['call_log'] as $k => $v) {
                                $onebill['tel'] = $v['phone'];  //（被）呼叫电话
                                $onebill['times'] = $v['first_contact_date'] . " - " . $v['last_contact_date'];  //联系时间段
                                $onebill['conways'] = $v['phone_location'];    //号码归属地
                                $onebill['contype'] = $v['call_cnt'];   //主叫次数
                                $onebill['longs'] = $v['talk_seconds'];     //联系总时长
                                $onebill['consite'] = $v['called_cnt'];  //被叫次数
                                $billArr[] = $onebill;
                            }
                            if ($billArr) {
                                $sdata['CallBill'] = serialize($billArr);
                            }
                        }
                    }
                }
                $checkrest = M('renzen_mobile')->field('ID')->where(array('UserID' => $meminfos['ID'], 'IsDel' => '0'))->find();
                if ($checkrest) {
                    M('renzen_mobile')->where(array('ID' => $checkrest['ID']))->save($sdata);
                } else {
                    M('renzen_mobile')->add($sdata);
                }
            }
            echo json_encode(array('code' => '0', 'message' => '回调处理成功'));
        }
    }

    //淘宝回调处理
    public function taobaoinfo()
    {
        $notify = I('post.');
        file_put_contents('1224.txt', json_encode($notify));
        $notify_state = $notify['state'];
        if ($notify_state == 'report') {
            //手机认证
            $meminfos = M('mem_info')->field('ID')->where(array('ID' => $notify['userId'], 'IsDel' => '0'))->find();
            if ($meminfos) {
                $sdata = array(
                    'UserID' => $notify['userId'],
                    'BDMobile' => $notify['account'],
                    'YZStatus' => '1',
                    'Status' => '1',
                    'IsDel' => '0',
                    'RenzTime' => date('Y-m-d H:i:s'),
                    'TaskID' => $notify['outUniqueId'],   //商户唯一标识id
                    'TUserID' => $notify['search_id'],    //详情查询ID
                );
                //查询的详细内容
                $infos = M('sys_inteparameter')->where(array('IntegrateID' => '15'))->select();
                foreach ($infos as $k => $v) {
                    if ($v['ParaName'] == 'AppID') {
                        $AppID = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'url') {
                        $urls = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'method') {
                        $method = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'detailmethod') {
                        $detailmethod = $v['ParaValue'];
                    }
                }
                $orgPrivateKey = file_get_contents("./tianji/rsa_private_key.pem");
                $bizData['userId'] = $notify['userId'];
                $bizData['outUniqueId'] = $notify['outUniqueId'];
                $bizData['reportType'] = "html";

                vendor('Tianji.OpenapiDevBase');
                $openapiDevBase = new \OpenapiDevBase();
                $details = $openapiDevBase->sendRequest($bizData, $detailmethod, $AppID, $urls, $orgPrivateKey);
                file_put_contents('9999.txt', json_encode($details));
                if ($details['error'] == 200 && $details['tianji_api_tianjireport_detail_response']) {
                    $report = $details['tianji_api_tianjireport_detail_response'];
                    if ($report) {
                        //等级
                        $sdata['Levels'] = $report['json']['credit_info']['buyer_credit_level'];
                        //金融账号余额
                        $sdata['JBalance'] = $report['json']['fortune_info']['yuebao_balance'];
                        //额度
                        $sdata['Balance'] = $report['json']['fortune_info']['zhifubao_balance'];
                        //用户名
                        $sdata['UserName'] = $report['json']['basic_info']['name'];
                        //消费额度
                        $sdata['XFQuote'] = $report['json']['shopping_amount']['total_orders_amount'];
                        //信用额度
                        $sdata['XYQuote'] = $report['json']['fortune_info']['huabei_total_credit_amount'];
                        //芝麻分
//                        $sdata['ZmScore']=$report['json']['credit_info']['tianmao_point'];
                        $sdata['ZmScore'] = '';
                        //借呗
                        $sdata['JieBei'] = $report['json']['credit_info']['naughty_score'];
                        //收获地址
                        if ($report['json']['addrs_info']['most_used_addrs']) {
                            //6个月的通话记录
                            $billArr = array();
                            foreach ($report['json']['addrs_info']['most_used_addrs'] as $k => $v) {
                                $onebill['zip'] = $v[''];  //邮编
                                $onebill['tel'] = $v['telephone'];  //电话
                                $onebill['name'] = $v['name'];    //收货人
                                $onebill['address'] = $v['address'];   //收货地址
                                $billArr[] = $onebill;
                            }
                            if ($billArr) {
                                $sdata['Receivers'] = serialize($billArr);
                            }
                        }

                        $sdata['UpdateTime'] = date('Y-m-d H:i:s');
                    }
                }
                $checkrest = M('renzen_taobao')->field('ID')->where(array('UserID' => $meminfos['ID'], 'IsDel' => '0'))->find();
                if ($checkrest) {
                    M('renzen_taobao')->where(array('ID' => $checkrest['ID']))->save($sdata);
                } else {
                    M('renzen_taobao')->add($sdata);
                }
            }
            echo json_encode(array('code' => '0', 'message' => '回调处理成功'));
        }
    }

    //支付宝回调
    public function alipayinfo()
    {
        $notify = I('post.');
        file_put_contents('211.txt', json_encode($notify));
        $notify_state = $notify['state'];
        if ($notify_state == 'report') {
            //手机认证
            $meminfos = M('mem_info')->field('ID')->where(array('ID' => $notify['userId'], 'IsDel' => '0'))->find();
            if ($meminfos) {
                $sdata = array(
                    'UserID' => $notify['userId'],
                    'ZFBMobile' => $notify['account'],
                    'Status' => '1',
                    'IsDel' => '0',
                    'RenzTime' => date('Y-m-d H:i:s'),
                    'TaskID' => $notify['outUniqueId'],   //商户唯一标识id
                );
                //查询的详细内容
                $infos = M('sys_inteparameter')->where(array('IntegrateID' => '15'))->select();
                foreach ($infos as $k => $v) {
                    if ($v['ParaName'] == 'AppID') {
                        $AppID = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'url') {
                        $urls = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'method') {
                        $method = $v['ParaValue'];
                    }
                    if ($v['ParaName'] == 'detailmethod') {
                        $detailmethod = $v['ParaValue'];
                    }
                }
                $orgPrivateKey = file_get_contents("./tianji/rsa_private_key.pem");
                $bizData['userId'] = $notify['userId'];
                $bizData['outUniqueId'] = $notify['outUniqueId'];
                $bizData['reportType'] = "html";

                vendor('Tianji.OpenapiDevBase');
                $openapiDevBase = new \OpenapiDevBase();

                $details = $openapiDevBase->sendRequest($bizData, $detailmethod, $AppID, $urls, $orgPrivateKey);
                file_put_contents('8888.txt', json_encode($details));
                if ($details['error'] == 200 && $details['tianji_api_tianjireport_detail_response']) {
                    $report = $details['tianji_api_tianjireport_detail_response'];
                    if ($report) {
                        //银行卡数量
                        $sdata['BankSum'] = $report['json']['basic_info']['user_info']['total_card_cnt'];
                        //邮箱账号
                        $sdata['Email'] = $report['json']['basic_info']['user_info']['email'];
                        //支付宝余额
                        $sdata['Balance'] = $report['json']['basic_info']['assets_info']['yuebao_tot_amt'];
                        //花呗额度
                        $sdata['HuabeiLimit'] = $report['json']['basic_info']['assets_info']['huabei_tot_amt'];
                        //花呗还款额度
                        $sdata['HuabeiRet'] = $report['json']['basic_info']['assets_info']['huabei_avlb_amt'];
                        //花呗可用额度
                        $sdata['HuabeiBalance'] = $report['json']['basic_info']['assets_info']['huabei_avlb_amt'];
                        //淘宝会员名
                        $sdata['TaobaoName'] = $report['json']['basic_info']['user_info']['real_name'];
                    }
                }
                $checkrest = M('renzen_alipay')->field('ID')->where(array('UserID' => $meminfos['ID'], 'IsDel' => '0'))->find();
                if ($checkrest) {
                    M('renzen_alipay')->where(array('ID' => $checkrest['ID']))->save($sdata);
                } else {
                    M('renzen_alipay')->add($sdata);
                }
            }
            echo json_encode(array('code' => '0', 'message' => '回调处理成功'));
        }
    }

    //根据taskid查询手机认证的详情 $taskid
    public function mobiledetails($taskid)
    {
        $url = 'https://tianji.rong360.com/tianjireport/detail';
        $data = array(
            'task_id' => $taskid,
        );
        $retdata = $this->https_request2($url, $data);
        return $retdata;
    }

    //通过api地址处理
    public function https_request2($url, $data = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/json;charset=utf-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        //return $output;
        return json_decode($output, true);
    }

    /**
     * 天机详情报告
     */
    public function report()
    {
        $searchId = I('get.searchId', '', 'trim');
        $niqueId = I('get.niqueId', '', 'trim');
        $mobileinfos = M('renzen_mobile')->field('ID,TUserID,TaskID,MobileData')->where(array('TUserID' => $searchId, 'TaskID' => $niqueId, 'IsDel' => '0'))->find();

        if ($mobileinfos['MobileData']) {
            $MobileData = unserialize($mobileinfos['MobileData']);
            $html = $MobileData['html'];
        } else {
            $html = "参数错误，暂无数据";
        }
        $this->assign(array(
            "html" => $html
        ));
        $this->display();
    }


    public function taiheyue()
    {
        //业务参数
        $params['phoneNo'] = '15155182723';
        $params['userName'] = '凌金飞';
        $params['idNo'] = '34262219910913710X';
        $params['personArea'] = '0';
        $value = json_encode($params, JSON_UNESCAPED_UNICODE);
        //系统参数
        $appKey = 'f795f01573ed49229e94b1ebe4061363';
        $appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp = time() * 1000;
        $sign = 'sha256';
        $str = $appKey . $timestamp . $value . $appSecret . $sign;
        $signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/account/addPersonAccount?appKey=" . $appKey . "&timestamp=" . $timestamp . "&signValue=" . $signValue . "&sign=sha256";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($value)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        var_dump($response);
        exit;
        return array($httpCode, $response);

    }


    /**
     * 测试芝麻分
     */
    public function zhima()
    {
        vendor('Tianji.OpenapiDevBase');
        $sample= new \OpenapiDevBase();
        $orgPrivateKey=file_get_contents("./Tian/rsa_private_key.pem");
        $method = 'wd.api.taobao.getZhimaData';
        $bizData['user_id']="15446810015367647057";
        $bizData['merchant_id']=$this->Appid;
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret=$sample->sendRequest2($bizData, $method,$AppID,$urls,$orgPrivateKey);
        print_r($ret);exit;
        if($ret['error'] != 200){
            $this->ajaxReturn(200,$ret['msg']);
        }
        $data['UserID'] = $_SESSION['uid'];
        $data['TaskID'] = $ret['request_id'];
        $data['TUserID'] =$ret['tianji_api_tianjireport_collectuser_response']['outUniqueId'];
        $re = M('renzen_taobao')->where(['UserID'=>$data['UserID'] ])->find();
        if($re){
            M('renzen_taobao')->where(['UserID'=>$data['UserID'] ])->save($data);
        }else{
            M('renzen_taobao')->add($data);
        }
        $this->ajaxReturn('100',$ret['tianji_api_tianjireport_collectuser_response']['redirectUrl']);
    }


}