<?php
/**
 * 决策控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Think\Controller;

 class DecisionController extends Controller {

     /**
      * 测试
      */
     public function index()
     {
         $res = $this->getDecision(10);
         dump($res);exit;
     }

     /**
      * 获取决策结果
      * @param $userId
      * @return array
      */
     public function getDecision($userId)
     {
         // 算法：先判断所有的拒绝状态，再判断所有手工审核状态，最后判断所有通过状态，默认手工审核
         // 状态 1:直接拒绝 2:手工复审 3:直接通过
         $decision = M('sys_decision')->select();
         $rzMobileInfo = M('renzen_mobile')->where(['UserID' => $userId, 'IsDel' => 0])->find();
         $rzSocialInfo = M('renzen_social')->where(['UserID' => $userId, 'IsDel' => 0])->find();
         $rzAlipayInfo = M('renzen_alipay')->where(['UserID' => $userId, 'IsDel' => 0])->find();
         $rzTaobaoInfo = M('renzen_taobao')->where(['UserID' => $userId, 'IsDel' => 0])->find();
         $rzCardInfo = M('renzen_cards')->where(['UserID' => $userId, 'IsDel' => 0])->find();
         $rzBlacklist = M('renzen_blacklist')->where(['UserID' => $userId, 'IsDel' => 0])->find();

         // 有盾数据变量
         $Yddatas = unserialize($rzCardInfo['Yddatas']);
         $loanDetail = $Yddatas['loan_detail'];
         $devicesList = $Yddatas['devices_list'];
         $deviceDetail = $devicesList[0]['device_detail'];
         $userFeatures = $Yddatas['user_features'];
         $graphDetail = $Yddatas['graph_detail'];
         $linkUserDetail = $graphDetail['link_user_detail'];

         /**
          * 状态：直接拒绝
          */
         $res = $this->run(1,$decision,$rzMobileInfo,$rzSocialInfo,$rzAlipayInfo,$rzTaobaoInfo,$rzBlacklist,
              $Yddatas,$loanDetail,$deviceDetail,$userFeatures,$graphDetail,$linkUserDetail);
         if($res['status']) return $res;

         /**
          * 状态：手工审核
          */
         $res = $this->run(2,$decision,$rzMobileInfo,$rzSocialInfo,$rzAlipayInfo,$rzTaobaoInfo,$rzBlacklist,
             $Yddatas,$loanDetail,$deviceDetail,$userFeatures,$graphDetail,$linkUserDetail);
         if($res['status']) return $res;

         /**
          * 状态：直接通过
          */
         $res = $this->run(3,$decision,$rzMobileInfo,$rzSocialInfo,$rzAlipayInfo,$rzTaobaoInfo,$rzBlacklist,
             $Yddatas,$loanDetail,$deviceDetail,$userFeatures,$graphDetail,$linkUserDetail);
         if($res['status']) return $res;

         // 默认：手工审核
        return ['status'=>2,'msg'=>'手工审核：无直接拒绝状态，无手工审核状态，无直接通过状态'];

     }

     /**
      * 执行决策判断
      * @param $status
      * @param $decision
      * @param $rzMobileInfo
      * @param $rzSocialInfo
      * @param $rzAlipayInfo
      * @param $rzTaobaoInfo
      * @param $rzBlacklist
      * @param $Yddatas
      * @param $loanDetail
      * @param $deviceDetail
      * @param $userFeatures
      * @param $graphDetail
      * @param $linkUserDetail
      * @return array
      */
     public function run($status,$decision,$rzMobileInfo,$rzSocialInfo,$rzAlipayInfo,$rzTaobaoInfo,$rzBlacklist,
                            $Yddatas,$loanDetail,$deviceDetail,$userFeatures,$graphDetail,$linkUserDetail)
     {
         // ------------------
         // 运营商
         // ------------------
         // 1.入网时长
         $OpenDate = $rzMobileInfo['OpenDate'];
         $nowDate = date('Y-m-d');
         $rwMonths = $this->getMonthNum($nowDate, $OpenDate);
         if ($decision[0]['Status'] == $status && $rwMonths < $decision[0]['Value']) {
             return $this->msg($decision,$status,0);
         }
         // 2.前十个手机号与通讯录对比
         $CallBill = unserialize($rzMobileInfo['CallBill']);
         $Phonelist = unserialize($rzSocialInfo['Phonelist']);
         $num = $this->getMobileCompareNum($CallBill, $Phonelist);
         $noMatchNum = 10 - $num;
         if($decision[1]['Status'] == $status && $noMatchNum > $decision[1]['Value']){
             return $this->msg($decision,$status,1);
         }
         // 3.通讯录和通话记录比对
         $count = count(array_intersect($CallBill,$Phonelist));
         if($decision[2]['Status'] == $status && $count < $decision[2]['Value'] ){
             return $this->msg($decision,$status,2);
         }
         // 4.通讯录不重复号码
         $uniCount = count(array_unique($Phonelist,SORT_REGULAR));
         if($decision[3]['Status'] == $status && $uniCount < $decision[3]['Value'] ){
             return $this->msg($decision,$status,3);
         }
         // 5.平均通话时长
         $mobileData = unserialize($rzMobileInfo['MobileData']);
         $callLog = $mobileData['json']['call_log'];
         $avg  = $this->getAvgDuration($callLog);
         if($decision[4]['Status'] == $status && $avg < $decision[4]['Value'] ){
             return $this->msg($decision,$status,4);
         }
         // 6.手机未实名认证
         if($decision[5]['Status'] == $status && $rzMobileInfo && $rzMobileInfo['Status'] != 1 ){
             return $this->msg($decision,$status,5);
         }

         // ------------------
         // 淘宝支付宝
         // ------------------
         // 7.芝麻分
         $zmScore = $rzTaobaoInfo['ZmScore'];
         if($decision[6]['Status'] == $status && $zmScore && $zmScore < $decision[6]['Value'] ){
             // todo 芝麻分
             //           return $this->msg($decision,$status,6);
         }
         // 8.花呗当前逾期状态：已逾期
         if($decision[7]['Status'] == $status){
             // todo 花呗逾期状态
             //  return $this->msg($decision,$status,7);
         }
         // 9.借呗当前逾期状态：已逾期
         if($decision[8]['Status'] == $status){
             // todo 借呗逾期状态
             //  return $this->msg($decision,$status,8);
         }
         // 10.淘宝收货地址：无
         $address = $rzTaobaoInfo['Receivers'];
         if($decision[9]['Status'] == $status  && !$address){
             return $this->msg($decision,$status,9);
         }
         // 11.花呗额度
         $hbLimit = $rzAlipayInfo['HuabeiLimit'];
         if($decision[10]['Status'] == $status && $hbLimit && $hbLimit > $decision[10]['Value']){
             return $this->msg($decision,$status,10);
         }
         // 12.借呗额度
         $jbLimit = $rzTaobaoInfo['JieBei'];
         if($decision[11]['Status'] == $status && $jbLimit && $jbLimit > $decision[11]['Value']){
             return $this->msg($decision,$status,11);
         }
         // 13.关联银行卡数
         $bankcardCount = $graphDetail['bankcard_count'];
         if($decision[12]['Status'] == $status && $bankcardCount && $bankcardCount > $decision[12]['Value']){
             return $this->msg($decision,$status,12);
         }

         // ------------------
         // 多头条件
         // ------------------
         // 14.极高风险
         $riskScore = (int)$Yddatas['score_detail']['score'];
         if($decision[13]['Status'] == $status && $riskScore && $riskScore > $decision[13]['Value']){
             return $this->msg($decision,$status,13);
         }
         // 15.中高风险
         if($decision[14]['Status'] == $status && $riskScore && $riskScore < $decision[13]['Value'] && $riskScore > $decision[15]['Value']){
             return $this->msg($decision,$status,14);
         }
         // 16.低风险
         if($decision[15]['Status'] == $status && $riskScore && $riskScore < $decision[15]['Value']){
             return $this->msg($decision,$status,15);
         }
         // 17.申请借款平台数
         $loanPlatformCount = $loanDetail['loan_platform_count'];
         if($decision[16]['Status'] == $status && $loanPlatformCount && $loanPlatformCount > $decision[16]['Value']){
             return $this->msg($decision,$status,16);
         }
         // 18.实际借款平台数
         $actualLoanPlatformCount = $loanDetail['actual_loan_platform_count'];
         if($decision[17]['Status'] == $status && $actualLoanPlatformCount && $actualLoanPlatformCount > $decision[17]['Value']){
             return $this->msg($decision,$status,17);
         }
         // 19.还款平台数
         $repaymentPlatformCount = $loanDetail['repayment_platform_count'];
         if($decision[18]['Status'] == $status && $repaymentPlatformCount && $repaymentPlatformCount > $decision[18]['Value']){
             return $this->msg($decision,$status,18);
         }
         // 20.还款次数
         $repaymentTimesCount = $loanDetail['repayment_times_count'];
         if($decision[19]['Status'] == $status && $repaymentTimesCount && $repaymentTimesCount > $decision[19]['Value']){
             return $this->msg($decision,$status,19);
         }
         // 21.命中作弊软件
         $cheatsDevice = $deviceDetail['cheats_device'];
         if($decision[20]['Status'] == $status && $cheatsDevice && $cheatsDevice == 1){
             return $this->msg($decision,$status,20);
         }
         // 22.借贷app安装数量
         $appInstalmentCount = $deviceDetail['app_instalment_count'];
         if($decision[21]['Status'] == $status && $appInstalmentCount && $appInstalmentCount > $decision[21]['Value']){
             return $this->msg($decision,$status,21);
         }
         // 23.命中借新还旧
         $jjhx = $this->userFeatureType($userFeatures,25);
         if($decision[22]['Status'] == $status && $jjhx){
             return $this->msg($decision,$status,22);
         }
         // 24.命中法院失信名单
         $courtDishonestCount = $linkUserDetail['court_dishonest_count'];
         if($decision[23]['Status'] == $status && $courtDishonestCount && $courtDishonestCount > 0){
             return $this->msg($decision,$status,23);
         }
         // 25.命中网贷失信名单
         $onlineDishonestCount = $linkUserDetail['online_dishonest_count'];
         if($decision[24]['Status'] == $status && $onlineDishonestCount && $onlineDishonestCount > 0){
             return $this->msg($decision,$status,24);
         }
         // 26.命中网贷不良黑名单
         $Response = json_decode($rzBlacklist['Response'],true);
         if($decision[25]['Status'] == $status && !empty($Response)){
             return $this->msg($decision,$status,25);
         }
         // 27.命中网贷短期逾期黑名单
         $dqyq = $this->userFeatureType($userFeatures,24);
         if($decision[26]['Status'] == $status && $dqyq){
             return $this->msg($decision,$status,26);
         }
         // 28.命中疑似欺诈名单库
         $qzmd = $this->userFeatureType($userFeatures,21);
         if($decision[27]['Status'] == $status && $qzmd){
             return $this->msg($decision,$status,27);
         }
         // 29.命中活体攻击行为
         $livingAttackCount = $linkUserDetail['living_attack_count'];
         if($decision[28]['Status'] == $status && $livingAttackCount && $livingAttackCount > 0){
             return $this->msg($decision,$status,28);
         }
         // 30.命中羊毛党
         $ymd = $this->userFeatureType($userFeatures,2);
         if($decision[29]['Status'] == $status && $ymd){
             return $this->msg($decision,$status,29);
         }
         // 31.命中身份信息疑似泄漏
         $sfxl = $this->userFeatureType($userFeatures,13);
         if($decision[30]['Status'] == $status && $sfxl){
             return $this->msg($decision,$status,30);
         }
         // 32.命中活体攻击设备
         $otherLinkDeviceDetail = $graphDetail['other_link_device_detail'];
         $other_living_attack_device_count = $otherLinkDeviceDetail['other_living_attack_device_count'];
         if($decision[31]['Status'] == $status && $other_living_attack_device_count && $other_living_attack_device_count > 0){
             return $this->msg($decision,$status,31);
         }
         // 33.命中曾使用可疑设备
         $other_frand_device_count = $otherLinkDeviceDetail['other_frand_device_count'];
         if($decision[32]['Status'] == $status && $other_frand_device_count && $other_frand_device_count > 0){
             return $this->msg($decision,$status,32);
         }
         // 34.命中关联过多
         $glgd = $this->userFeatureType($userFeatures,8);
         if($decision[33]['Status'] == $status && $glgd){
             return $this->msg($decision,$status,33);
         }
         // 35.命中多头借贷
         $dtjd = $this->userFeatureType($userFeatures,0);
         if($decision[34]['Status'] == $status && $dtjd){
             return $this->msg($decision,$status,34);
         }
     }

     /**
      * 返回数据
      * @param $decision
      * @param $status
      * @param $i
      * @return array
      */
     public function msg($decision,$status,$i)
     {
         $msg = $decision[$i]['Title'] . $decision[$i]['Value'] . $decision[$i]['Desc'];
         return ['status' => $status, 'msg' => $msg];
     }

     /**
      * 获取两个日期之间相差的月数
      * @param $date1
      * @param $date2
      * @param string $tags
      * @return float|int
      */
     public function getMonthNum( $date1, $date2, $tags='-' ){
         $date1 = explode($tags,$date1);
         $date2 = explode($tags,$date2);
         return abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
     }

     /**
      * 获取通话记录前十与通讯录匹配次数
      * @param $mobiles
      * @param $addressBook
      * @return int
      */
     public function getMobileCompareNum($mobiles,$addressBook){
         $top10 = array_slice($mobiles,0,10);
         $res = array_intersect($top10,$addressBook);
         $num = count($res);
         return $num;
     }

     /**
      * 获取平均通话时长(分钟)
      * @param $arr
      * @return float
      */
     public function getAvgDuration($arr){
         $count = count($arr);
         $sum = 0;
         foreach ($arr as $value){
             $sum += $value['talk_seconds'];
         }
         $avg = round(($sum / $count)/ 60) ; // 转为分钟
         return $avg;
     }

     /**
      * 获取用户特征
      * @param $userFeatures
      * @param $type
      * @return bool
      */
     public function userFeatureType($userFeatures,$type)
     {
         $userFeatures = json_decode($userFeatures,true);
        if(empty($userFeatures)){
            return false;
        }
        foreach ($userFeatures as $val){
            if($val['user_feature_type'] == $type){
                return true;
            }
        }
        return false;
     }

 }