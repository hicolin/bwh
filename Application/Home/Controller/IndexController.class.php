<?php

namespace Home\Controller;
use Admin\Controller\Renzen\DecisionController;

class IndexController extends HomeController{
    const G_TABLE='goods';
    const T_TABLE='loans_term';
    const P_TABLE = 'loans_parameter';
    const M_TABLE = 'mem_info';
    const A_TABLE = 'loans_applylist';
    const B_TABLE = 'renzen_bank';
    const S_TABLE = 'sys_basicinfo';
    public $token;//用户登录token
    public $member;//  用户所有信息
    public $uid;//  用户ID

    //网站首页
    public function index(){
        global $BasicInfo;
        $dayfor = array('dayfor'=>array(
            '7天','14天'
        ));
        $usefor = array('usefor'=>array(
            '个人生活消费','租房装修','电子数码','旅游'
        ));
        $info = array_merge($BasicInfo,$dayfor,$usefor);
        $this->ajaxReturn(1,"初始加载 !",$info);

    }


    //点击立即借款时的判断
    public function go_borrow(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $parameter=M('renzen_parameter')->where(array('IsMust'=>1))->select();
            foreach ($parameter as $k=>$v){
                $renzen[] = M('renzen_'.$v['Flag'])->where(array('UserID'=>$this->uid,'IsDel'=>0,'Status'=>1))->count();
            }
            //有未认证的项则返回0，全部认证返回1
            if(in_array('0',$renzen)){
                $this->ajaxReturn(2,'您有未认证项，请先认证！',$renzen);
            }
            //在有申请的，放款中的，和已放款的订单情况下都不能再次借款
            $map['LoanStatus'] = array('in','0,1,2');
            $map['UserID']= array('eq',$this->uid);
            $applied=M(self::A_TABLE)->where($map)->count();
            if($applied){
                $this->ajaxReturn(4,'您有未完成的借款，请先完成再借款！');
            }
            // 审核失败的订单,七天内不能再次借款
            $mapFail['LoanStatus'] = 5;
            $mapFail['UserID'] = $this->uid;
            $apply = M(self::A_TABLE)->where($mapFail)->order('ShTime desc')->find();
            $limitDay = $apply['LimitDay'];
            $applyTime = strtotime($apply['ApplyTime']);
            $canBorrowTime = $applyTime + $limitDay * 24 *3600;
            if(time() < $canBorrowTime){
                $this->ajaxReturn(0,'您有审核失败的借款记录，'.$limitDay.'天内不能再次借款');
            }
            $money = I('post.money');
            $days = I('post.days');
            $days==1?$day=7:$day=14;
            $usefor = I('post.usefor');
            $map['SalePrice'] = array('eq',$money);
            $map['Day'] = array('eq',$day);
            $map['IsDel'] = array('eq','0');
            //是否有该产品
            $isGoods=M(self::G_TABLE)->where($map)->find();
            $map2['LoanStatus'] = array('eq','3');
            $map2['UserID']= array('eq',$this->uid);
            $total=M(self::A_TABLE)->where($map2)->sum('ApplyMoney');
            if(!$total){
                $total = 0;
            }
            if($isGoods){
                $isGoods['usefor'] = $usefor;
                //判断这个人是否达到了解锁额度，小于该额度不可借款
                //有历史借款且总额小于解锁额度
                if( $total < $isGoods['JSMoney']){
                    $condition['IsDel'] = array('eq','0');
                    $condition['JSMoney'] = array('elt',$total);
                    $isGoods=M(self::G_TABLE)->where($condition)->order('JSMoney desc')->find();
                    $this->ajaxReturn(1,"没有该额度的借款产品!",$isGoods);
//                    $this->ajaxReturn(0,"您的历史借款金额未达到解锁额度 !");
                }else{
                    //第一次借款
                    $this->ajaxReturn(1,"达到借款条件 !",$isGoods);
                }
            }else{
                $condition['IsDel'] = array('eq','0');
                $condition['JSMoney'] = array('elt',$total);
                $isGoods=M(self::G_TABLE)->where($condition)->order('JSMoney desc')->find();
                $this->ajaxReturn(1,"没有该额度的借款产品!",$isGoods);
            }
        }else{
            $this->ajaxReturn(0,"非post请求!");
        }

    }

    //提交借款
    public function borrow(){
        if($_GET){
            $money=I('get.money','',trim);
            $days = I('get.days','',trim);
            $usefor = I('get.for','',trim);
            $loanterm=M(self::G_TABLE)->where(array('Day'=>$days,'SalePrice'=>$money,'IsDel'=>0))->find();
            $this->assign('loanterm',$loanterm);
            $this->assign('money',$money);
            $this->assign('usefor',$usefor);
        }
        $this->display();
    }


    //生成订单
    public function applylist(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $parameter=M('renzen_parameter')->where(array('IsMust'=>1))->select();
            foreach ($parameter as $k=>$v){
                $renzen[] = M('renzen_'.$v['Flag'])->where(array('UserID'=>$this->uid,'IsDel'=>0,'Status'=>1))->count();
            }
            //必备认证有未认证的项则返回0，全部认证返回1
            if(in_array('0',$renzen)){
                $this->ajaxReturn(0,"你尚未认证完全!");
            }
            $meminfo=M(self::M_TABLE)->where(array('ID'=>$this->uid,'IsDel'=>0))->find();
            $MemAccount = $meminfo['MemAccount'];
            $money=I('post.money','',trim);
            $day = I('post.day','',trim);
            $usefor = I('post.usefor','',trim);
            $total = I('post.total','',trim);
            $loanterm=M(self::G_TABLE)->where(array('Day'=>$day,'SalePrice'=>$money,'IsDel'=>0))->find();
            $AdoptMoney = $loanterm['Fastmoney'];
            $FJMoney = $loanterm['GuanliCost'];
            $Interest = $loanterm['Interest'];
            $ProductID = $loanterm['ID'];
            $loanpara=M(self::P_TABLE)->where(array('ID'=>1))->find();
            $RongDay = $loanpara['RongDay'];
            $RongP = $loanpara['RongP'];
            $OverdueDay = $loanpara['OverdueDay'];
            $OverdueP = $loanpara['OverdueP'];
            $decision = new DecisionController();
            $res = $decision->getDecision($this->uid);
            $order = array(
                "UserID" => $this->uid,
                "ApplyTime" => date("Y-m-d H:i:s"),
                "OrderSn" => date('ymd').rand(1,9).date('His').rand(111,999),
                "LoanNo" => date('ymd').rand(1,9).date('His').rand(111,999),
                "ApplyMoney" => $money,
                "AdoptMoney" =>$AdoptMoney,
                'FJMoney'=>$FJMoney,
                'Interest'=>$Interest,
                'ApplyDay'=>$day,
                'ProductID'=>$ProductID,
                'CouponID'=>'',
                'CoMoney'=>'',
                'OpenM'=>'',
                'BackM'=>$total,
                'LoanType'=>'0',
                'LoanStatus'=>'0',
                'SqAdminID'=>'',
                'ServiceID'=>'',
                'ShTime'=>'',
//                'Status'=>'0',
                'LoanSum'=>'',
                'RongDay'=>$RongDay,
                'RongP'=>$RongP,
                'OverdueDay'=>$OverdueDay,
                'OverdueP'=>$OverdueP,
                'FKadminID'=>'',
                'FkServiceID'=>'',
                'OpenTime'=>'',
                'YyFkTime'=>date("Y-m-d H:i:s",strtotime("+6 days")),
                'ReplaymentType'=>'0',
                'RepaymentAccount'=>'',
                'TradeNum'=>'',
                'UserAccount'=>$MemAccount,
                'Remark'=>'',
                'IsYQ'=>'0',
                'CsadminID'=>'',
                'IsDel'=>'0',
                'OperatorID'=>'',
                'UpdateTime'=>'',
                'usefor'=>$usefor,
            );
            if($res['status']==1){
                //直接拒绝
                $order['Status']=2;
                $order['LoanStatus']=5;
                $order['ShReason']=$res['msg'];
            }elseif ($res['status']==2){
                //手工复审
                $order['Status']=0;
                $order['ShReason']=$res['msg'];
            }else{
                //直接通过
                $order['Status']=1;
                $order['LoanStatus']=1;
                $order['ShReason']=$res['msg'];
            }
            $result = M(self::A_TABLE)->add($order);
            if ($result) {
                $this->ajaxReturn(1,"申请成功!");
            } else {
                $this->ajaxReturn(0,"申请失败!");
            }

        }else{
            $this->ajaxReturn(0,"缺少参数!");
        }

    }

    //我要借款提交判断
    public function beforeSub(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $map['LoanStatus'] = array('in','0,1,2');
            $map['UserID']= array('eq',$this->uid);
            $nolist=M(self::A_TABLE)->where(array('UserID'=>$this->uid))->count();
            $deal=M(self::A_TABLE)->where($map)->count();
            if(!$nolist || !$deal){
                $this->ajaxReturn(1,"可以申请!");
            }
            if($nolist && $deal){
                $this->ajaxReturn(0,"您的订单正在处理中,请勿重复提交!");
            }
//            $this->ajaxReturn(0,"您的订单正在处理中,请勿重复提交!");
            $map['LoanStatus'] = array('eq',3);
            $map['UserID']= array('eq',$this->uid);
            $appling=M(self::A_TABLE)->where($map)->count();
            $map1['LoanStatus'] = array('eq','2');
            $map1['UserID']= array('eq',$this->uid);
            $loan=M(self::A_TABLE)->where($map1)->find();
            if($loan){
                $overtimes=date('Y-m-d',strtotime($loan['YyFkTime'])).' 23:59:59';
                if($overtimes<date('Y-m-d H:i:s')){
                    //已经逾期了
                    $yuqidata=getoverinfos($loan['ID']);
                    $loan['BackM']=$yuqidata['realtotal'];//应付总金额
                }
                $sys=M(self::S_TABLE)->where(array('ID'=>1))->find();
                $wxpay = array('wxpay'=>array(
                    'account'=>$sys['Gfaccountw'],
                    'note'=>$sys['Remarkgs'],
                    'img'=>$sys['Weixinimg'],
                ));
                $alipay = array('alipay'=>array(
                    'account'=>$sys['Gfaccount'],
                    'note'=>$sys['Remarkgs'],
                    'img'=>$sys['Alipayimg'],
                ));
            }
            $map2['LoanStatus'] = array('in','4,5');
            $map2['UserID']= array('eq',$this->uid);
            $fail=M(self::A_TABLE)->where($map2)->count();
            //没有借款记录
            if($this->uid && !$nolist){
                $this->ajaxReturn(2,"您没有申请借款，请先申请!");
            }
            //申请中，不用还款
            if($this->uid && $appling){
                $this->ajaxReturn(2,"您已申请借款，请等待审核通过!");
            }
            //必须要已放款，才可进入还款或续期
            if($this->uid && $loan){
                $info = array_merge($loan,$wxpay,$alipay);
                $this->ajaxReturn(1,"OK!",$info);
            }
            //拒绝，不用还款
            if($this->uid && !$appling && !$loan && $fail){
                $this->ajaxReturn(2,"您的借款申请已取消或已拒绝!");
            }
            //已完成，不用还款（前面的都已经通过）
            if($this->uid && $finish){
                $this->ajaxReturn(2,"您没有未还款订单，可先借款!");
            }

        }else{
            $this->ajaxReturn(0,"参数错误!");
        }
    }
    //我要还款
    public function repaymoney(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $nolist=M(self::A_TABLE)->where(array('UserID'=>$this->uid))->count();
            $finish=M(self::A_TABLE)->where(array('UserID'=>$this->uid,'LoanStatus'=>3))->count();
            $map['LoanStatus'] = array('in','0,1');
            $map['UserID']= array('eq',$this->uid);
            $appling=M(self::A_TABLE)->where($map)->count();
            $map1['LoanStatus'] = array('eq','2');
            $map1['UserID']= array('eq',$this->uid);
            $loan=M(self::A_TABLE)->where($map1)->find();
            if($loan){
                $overtimes=date('Y-m-d',strtotime($loan['YyFkTime'])).' 23:59:59';
                if($overtimes<date('Y-m-d H:i:s')){
                    //已经逾期了
                    $yuqidata=getoverinfos($loan['ID']);
                    $loan['BackM']=$yuqidata['realtotal'];//应付总金额
                }
                $sys=M(self::S_TABLE)->where(array('ID'=>1))->find();
                $wxpay = array('wxpay'=>array(
                    'account'=>$sys['Gfaccountw'],
                    'note'=>$sys['Remarkgs'],
                    'img'=>$sys['Weixinimg'],
                ));
                $alipay = array('alipay'=>array(
                    'account'=>$sys['Gfaccount'],
                    'note'=>$sys['Remarkgs'],
                    'img'=>$sys['Alipayimg'],
                ));
            }
            $map2['LoanStatus'] = array('in','4,5');
            $map2['UserID']= array('eq',$this->uid);
            $fail=M(self::A_TABLE)->where($map2)->count();
            //没有借款记录
            if($this->uid && !$nolist){
                $this->ajaxReturn(2,"您没有申请借款，请先申请!");
            }
            //申请中，不用还款
            if($this->uid && $appling){
                $this->ajaxReturn(2,"您已申请借款，请等待审核通过!");
            }
            //必须要已放款，才可进入还款或续期
            if($this->uid && $loan){
                $info = array_merge($loan,$wxpay,$alipay);
                $this->ajaxReturn(1,"OK!",$info);
            }
            //拒绝，不用还款
            if($this->uid && !$appling && !$loan && $fail){
                $this->ajaxReturn(2,"您的借款申请已取消或已拒绝!");
            }
            //已完成，不用还款（前面的都已经通过）
            if($this->uid && $finish){
                $this->ajaxReturn(2,"您没有未还款订单，可先借款!");
            }

        }else{
            $this->ajaxReturn(0,"参数错误!");
        }
    }


    //生成还款订单
    public function hklist(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $LoanNo = I('post.LoanNo','',trim);
            $mark = I('post.mark','',trim);
            $payMoney = I('post.payMoney','',trim);
            $paytype = I('post.paytype','',trim);
            $applyinfo=M('loans_applylist')
                ->where(array('LoanNo'=>$LoanNo,'UserID'=>$this->uid,'LoanStatus'=>'2','IsDel'=>'0'))
                ->find();
            if($applyinfo){
                //如果续期还在审核 不予提交
                $checkxj=M('loans_xjapplylist')->where(array('LoanNo'=>$LoanNo,'Status'=>'0'))->find();
                if($checkxj){
                    $this->ajaxReturn(0,"您提交的续期申请还在审核中!");
                }
                //如果还款还在审核 不予提交
                $checkhk=M('loans_hklist')->where(array('LoanNo'=>$LoanNo,'Status'=>'0'))->find();
                if($checkhk){
                    $this->ajaxReturn(0,"您提交的还款申请还在审核中，请勿重复提交!");
                }
                $hkdata=array(
                    'UserID'=>$this->uid,
                    'ApplyID'=>$applyinfo['ID'],
                    'OrderSn'=>date(ymd).rand(1,9).date(His).rand(111,999),
                    'LoanNo'=>$applyinfo['LoanNo'],
                    'HkTime'=>date('Y-m-d H:i:s'),
                    'TradeRemark'=>'',
                    'Mark'=>$mark,
                    'TradeNo'=>'',
                    'PayStatus'=>'',
                    'PayTime'=>'',
                    'AdminID'=>'',
                    'ShTime'=>'',
                    'Status'=>'0',
                    'Remark'=>'',
                    'IsDel'=>'0',
                    'OperatorID'=>'',
                    'UpdateTime'=>'',
                );
                //过了当天夜里24点才算逾期
                $overtimes=date('Y-m-d',strtotime($applyinfo['YyFkTime'])).' 23:59:59';
                if($overtimes<date('Y-m-d H:i:s')){
                    //已经逾期了
                    $yuqidata=getoverinfos($applyinfo['ID']);
                    $hkdata['TotalMoney']=$yuqidata['realtotal'];//应付总金额
                    $hkdata['FinePayable']=$yuqidata['famoney'];//应还罚金
                }else{
                    //未逾期
                    $hkdata['TotalMoney']=$payMoney;//到期应还
                }
                if($mark==0){
                    $hkdata['CostPayable']=$applyinfo['ApplyMoney'];//应还本金
                    $hkdata['RatePayable']=$applyinfo['Interest'];//应还本息
                    $hkdata['SeviceCostPayable']=$applyinfo['AdoptMoney']+$applyinfo['FJMoney'];//应还服务费
                }else{
                    //获取续期手续费（每个产品续期手续费不一样）
                    $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
                    //获取期数（7天）的续期手续费
                    $termlist=M('loans_term')->field('ID,Applyfee,NumDays,ServiceCost')->where(array('Status'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->find();
                    //获取 快速申请费
                    $xqNum=M('loans_hklist')->where(array('LoanNo'=>$LoanNo,'Mark'=>'1'))->count();
                    $renewsetlist=M('loans_renewset')->field('Nums,Applyfee')->where(array('Status'=>'1','IsDel'=>'0','Nums'=>$xqNum))->order('Nums asc')->find();
                    $fast_Applyfee = $renewsetlist['Applyfee'];
                    $hkdata['CostPayable']='';//应还本金
                    $hkdata['RatePayable']=$termlist['Applyfee'];//应还本息
                    $hkdata['SeviceCostPayable']=$fast_Applyfee+$termlist['ServiceCost']+$goodinfo['ServiceCost'];//应还服务费
                }
                $hkdata['PayType']=intval($paytype);
                if($paytype=='1'){
                    $hkdata['Accounts']=get_basic_info('Gfaccountw');//1微信
                }else if($paytype=='2'){
                    $hkdata['Accounts']=get_basic_info('Gfaccount');//2支付宝
                }else{
                    $hkdata['PayTime'] = date('Y-m-d H:i:s');
                }
                $result=M('loans_hklist')->add($hkdata);
                if($paytype=='3'){
                    $id = M('loans_hklist')->getLastInsID();
                    $this->ajaxReturn(2,'前往支付中，请等待~',$id);
                }
                if($result){
                    $this->ajaxReturn(1,"您的还款申请已提交审核!");
                }else{
                    $this->ajaxReturn(0,"提交失败!");
                }

            }else{
                $this->ajaxReturn(0,"您的借款申请不存在!");
            }
        }else{
            $this->ajaxReturn(0,"非ajax请求!");
        }
    }

    public function selectPayway()
    {
        if (I('post.')) {
            $token = I('post.token');
            $this->checkToken($token);
            $LoanNo = I('post.LoanNo');
            //如果还款还在审核 不予提交
            $checkhk = M('loans_hklist')->where(array('LoanNo' => $LoanNo, 'Status' => '0'))->find();
            if ($checkhk) {
                $this->ajaxReturn(0, "该还款订单已经提交审核，请勿重复操作!");
            }
            //如果续期已提交
            $xqinfo = M('loans_xjapplylist')->where(array('UserID' => $this->uid, 'LoanNo' => $LoanNo, 'Status' => 0))->count();
            if ($xqinfo > 0) {
                $this->ajaxReturn(0, "该续期订单已经提交审核，请勿重复操作!");
            }
            $this->ajaxReturn(1, "可以提交！");
        }
    }
    //判断续期次数
    public function beforeXq(){
        if(I('post.')) {
            $token = I('post.token');
            $this->checkToken($token);
            $LoanNo = I('post.LoanNo');
            //如果还款还在审核 不予提交
            $checkhk=M('loans_hklist')->where(array('LoanNo'=>$LoanNo,'Status'=>'0'))->find();
            if($checkhk){
                $this->ajaxReturn(0,"您提交的还款申请还在审核中，无法续期!");
            }
            //如果续期已提交
            $xqinfo=M('loans_xjapplylist')->where(array('UserID'=>$this->uid,'LoanNo'=>$LoanNo,'Status'=>0))->count();
            if($xqinfo>0){
                $this->ajaxReturn(0,"该续期订单已经提交审核，请勿重复操作!");
            }
            $hkNum=M('loans_hklist')->where(array('LoanNo'=>$LoanNo,'Mark'=>'1'))->count();
            $xqNum=M('loans_renewset')->where(array('Status'=>'1','IsDel'=>'0'))->count();
            if($hkNum >= $xqNum){
                $this->ajaxReturn(0,"该订单的续期次数已达上限!");
            }else{
                $this->ajaxReturn(1,"可以续期!");
            }
        }else{
            $this->ajaxReturn(0,"非ajax请求!");
        }
    }
    //我要续期
    public function renewal(){
        if(I('post.')){
            $token = I('post.token');
            $mark = I('post.mark');

            $this->checkToken($token);
            $map['LoanStatus'] = array('eq','2');
            $map['UserID']= array('eq',$this->uid);
            $applyinfo=M(self::A_TABLE)->where($map)->find();
            //获取续期手续费（每个产品续期手续费不一样）
            $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
            //获取期数（7天）的续期手续费
            $termlist=M('loans_term')->field('ID,Applyfee,NumDays,ServiceCost')->where(array('Status'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->find();
            //获取 快速申请费
            if($mark==1){
                $xqNum =M('loans_hklist')->where(array('LoanNo'=>$applyinfo['LoanNo'],'Mark'=>'1'))->count();
                $xqNum++;
            }else{
                $xqNum=1;
            }
            $renewsetlist=M('loans_renewset')->field('Nums,Applyfee')->where(array('Status'=>'1','IsDel'=>'0','Nums'=>$xqNum))->order('Nums asc')->find();
            if(!$renewsetlist){
                $this->ajaxReturn(2,"该订单的续期次数已达上限!");
            }
            $fast_Applyfee = $renewsetlist['Applyfee'];
            $yytime = strtotime($applyinfo['YyFkTime']);
            $xqdate = date('Y-m-d',$yytime+6*24*60*60);
            $retdata=array(
                //到期天数
                'arrivetime'=>$xqdate,
                //期数（7天）服务费
                'termlist'=>$termlist,
                //第一次续期手续费+产品续期手续费
                'xjfee'=>$fast_Applyfee+$goodinfo['ServiceCost'],
                'total'=>$termlist['Applyfee']+$termlist['ServiceCost']+$fast_Applyfee+$goodinfo['ServiceCost'],
            );
            $this->ajaxReturn(1,"获取成功!",$retdata);

        }else{
            $this->ajaxReturn(0,"参数错误!");
        }
    }


    //生成续期订单
    public function xqlist(){
        if(I('post.')){
            $token = I('post.token');
            $this->checkToken($token);
            $LoanNo = I('post.LoanNo','',trim);
            $day = I('post.day','',trim);
            $paytype = I('post.paytype','',trim);
            $total = I('post.total','',trim);
            $xjfee = I('post.xjfee','',trim);
            //校验是否有借款订单
            $map['LoanNo']=array('eq',$LoanNo);
            $map['UserID']=array('eq',$this->uid);
            $map['IsDel']=array('eq',0);
            $applyinfo=M('loans_applylist')->where($map)->find();
            if(!$applyinfo){
                $this->ajaxReturn(0,"该借款订单不存在!");
            }
//            //有过一次续期则不予提交
//            $xqinfos=M('loans_xjapplylist')->where(array('UserID'=>$this->uid,'LoanNo'=>$LoanNo))->count();
//            if($xqinfos>=1){
//                $this->ajaxReturn(0,"该订单的续期次数已达上限!");
//            }
            //如果还款还在审核 不予提交
            $checkhk=M('loans_hklist')->where(array('LoanNo'=>$LoanNo,'Status'=>'0'))->find();
            if($checkhk){
                $this->ajaxReturn(0,"您提交的还款申请还在审核中，无法续期!");
            }
            //如果续期已提交
            $xqinfo=M('loans_xjapplylist')->where(array('UserID'=>$this->uid,'LoanNo'=>$LoanNo,'Status'=>0))->count();
            if($xqinfo>0){
                $this->ajaxReturn(0,"该续期订单已经提交审核，请勿重复操作!");
            }
            //过了当天夜里24点才算逾期
            $overtimes=date('Y-m-d',strtotime($applyinfo['YyFkTime'])).' 23:59:59';
            if($overtimes<date('Y-m-d H:i:s')){
                $this->ajaxReturn(0,"此单已经逾期，不能续期！");
            }
            //判断第几次续期了
            $xjnumbs=M('loans_applylist')->where(array('LoanNo'=>$applyinfo['LoanNo'],'LoanType'=>'1','IsDel'=>'0'))->count('ID');
            if(!$xjnumbs){
                $xjnumbs='0';
            }
            $loansets=M('loans_parameter')->find();
            if($loansets['MaxRenewSum']<=$xjnumbs){
                $this->ajaxReturn(0,"此单已经续期多次，不能再续期了！");
            }
            //商品续期费
            $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
            if(!$goodinfo){
                $this->ajaxReturn(0,"商品信息异常，暂不能续期！");
            }

            //续期表新增数据
            //续期操作
            $xjdata=array(
                'UserID'=>$this->uid,
                'ApplyID'=>$applyinfo['ID'],
                'OrderSn'=>date(ymd).rand(1,9).date(His).rand(111,999),
                'LoanNo'=>$applyinfo['LoanNo'],
                'TradeNo'=>'',
                'LoanDay'=>$day,
//                'TotalMoney'=>$total,
                'TotalMoney'=>'',
                'ServiceCost'=>$xjfee,
                'XjTime'=>date('Y-m-d H:i:s'),
                'TradeRemark'=>'',
                'Accounts'=>'',
                'PayType'=>'',
                'PayStatus'=>'',
                'PayTime'=>'',
                'AdminID'=>'',
                'ShTime'=>'',
                'Status'=>'',
                'Remark'=>'',
                'IsDel'=>'0',
                'OperatorID'=>'',
                'UpdateTime'=>'',
            );
            if($paytype==3){
                $xjdata['Status'] = 0;
                $xjdata['PayStatus'] = 1;
                $xjdata['PayType'] =3;
            }
            $result = M('loans_xjapplylist')->add($xjdata);
            if ($result) {
                $this->ajaxReturn(1,"续期成功!");
            } else {
                $this->ajaxReturn(0,"续期失败!");
            }
        }else{
            $this->ajaxReturn(0,"请用post提交!");
        }
    }

    //借款协议
    public function borrowdetail(){
        $infos = M('sys_contentmanagement')->where(array('ID'=>'39'))->field('Contents')->find();
        $content = htmlspecialchars_decode($infos['Contents']);
        $this->assign('content',$content);
        $this->display();
    }

    public function down()
    {
        echo '<script>window.location.href="http://cunguanchou.com/apk/1.apk"</script>';
        echo '<script>history.back()</script>';exit;
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

    /**
     * 常见问题
     */
    public function question(){

        $model = M('sys_contentmanagement');
        $ids = [38,33,32,21];  // 相关ID
        $articles = $model->where(['IsPublish'=>1,'ID'=>['in',$ids]])
            ->order('Sort desc,ID desc')->select();
        $this->ajaxReturn(1,'获取常见问题成功',$articles);
    }

    /**
     * 关于我们
     */
    public function about(){
        header("Content-type: text/html; charset=utf-8");
        $model = M('sys_contentmanagement');
        $articles = $model->where(['IsPublish'=>1,'ID'=>7])
            ->order('Sort desc,ID desc')->find();
        $articles['Contents'] = htmlspecialchars_decode($articles['Contents']);
        $this->ajaxReturn(1,'关于我们',$articles);
    }

    /**
     * 隐私协议
     */
    public function xieyi(){
        header("Content-type: text/html; charset=utf-8");
        $model = M('sys_contentmanagement');
        $articles = $model->where(['IsPublish'=>1,'ID'=>39])
            ->order('Sort desc,ID desc')->find();
        $articles['Contents'] = htmlspecialchars_decode($articles['Contents']);
        $this->ajaxReturn(1,'关于我们',$articles);
    }

    public function tianjiReturn()
    {
//       file_put_contents('2.txt',date('Y-m-d H:i:s'));
        $req = $_REQUEST;
        if($req['outUniqueId']){
//           echo '<script>location.href = "http://we.jinpinfin.cn/#/pages/certificate"</script>';
            echo '<script>history.back()</script>';
            exit;
        }
    }

    public function setting()
    {
        global $BasicInfo;
        $this->ajaxReturn(1,'获取成功',$BasicInfo);
    }
}
