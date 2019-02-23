<?php
/**
 *
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-07-02 09:21
 * 功能说明: 借款申请控制器
 */
 namespace Admin\Controller\Loans;

 use Admin\Controller\Renzen\SocialController;
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class ApplylistController extends BaseController{

     const T_TABLE='loans_applylist';
     const T_HKLIST='loans_hklist';
     const T_MEMINFO='mem_info';
     const T_ADMIN='sys_administrator';

     const T_CARDS='renzen_cards';//身份证认证表
     const T_MOBILE='renzen_mobile';//手机认证表
     const T_ALIPAY='renzen_alipay';//支付宝认证表
     const T_TAOBAO='renzen_taobao';//淘宝认证表
     const T_MEMBERINFO='renzen_memberinfo';//基本信息认证表
     const T_SOCIAL='renzen_social';//社交认证表
     const T_BANK='renzen_bank';//银行卡认证表
     const T_ZHIMA = 'renzen_zhima';//芝麻认证表

     public function _initialize(){
        parent::_initialize();
        $this->LoanStatus = array(0=>"申请中",1=>"放款中",2=>"已放款",3=>"已完成",4=>"已取消",5=>"已拒绝");
        $this->Status = array(0=>'待审核',1=>'审核成功',2=>'审核失败');
        $this->LoanType = array(0=>'普通',1=>'续期',2=>'分期');
        $this->UseforArr = array(1=>'个人生活消费',2=>'租房装修',3=>'电子数码',4=>'旅游');
     }

     public function index(){
         $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'7','Status'=>'1','IsDel'=>'0'))->select();
         $RoleID=$_SESSION['AdminInfo']['RoleID'];
         $this->assign(array(
            'LoanStatus'=>$this->LoanStatus,
            'Status'=>$this->Status,
            'kefuArr'=>$kefuArr,
            'RoleID'=>$RoleID,
            ));
         $this->display();
     }

     /**
      * 后台用户管理的列表数据获取
      * @access   public
      * @return   object    返回json数据
      */
     public function DataList(){
         $page=I('post.page',1,'intval');
         $rows=I('post.rows',20,'intval');
         $sort=I('post.sort');
         $order=I('post.order');
         if ($sort && $order){
             $sort=$sort.' '.$order;
         }else{
             $sort='LoanStatus asc,ApplyTime desc';
         }

         //搜索条件
         $TrueName=I('post.TrueName','');
         if($TrueName){
            $memidArr=M(self::T_MEMINFO)->field('ID')->where(array('TrueName'=>array('like','%'.$TrueName.'%')))->select();
            if($memidArr){
                $memids=array();
                foreach($memidArr as $k=>$v){
                    $memids[]=$v['ID'];
                }
                //$memids=array_column($memidArr, 'ID');
                $where['UserID']=array('in',$memids);
            }else{
                $where['UserID']=array('eq','0');
            }
         }
         $Mobile=I('post.Mobile','');
         if($Mobile){
            $memidArr='';
            $memidArr=M(self::T_MEMINFO)->field('ID')->where(array('Mobile'=>array('eq',$Mobile)))->select();
            if($memidArr){
                $memids=array();
                foreach($memidArr as $k=>$v){
                    $memids[]=$v['ID'];
                }
                $where['UserID']=array('in',$memids);
            }else{
                $where['UserID']=array('eq','0');
            }
         }
         $LoanStatus=I('post.LoanStatus',-5,'int');
         if($LoanStatus!=-5){
            $where['LoanStatus']=$LoanStatus;
         }
         $LoanNo=I('post.LoanNo','');
         if($LoanNo){
            $where['LoanNo']=$LoanNo;
         }
         $OrderSn=I('post.OrderSn','');
         if($OrderSn){
            $where['OrderSn']=$OrderSn;
         }
         $Status=I('post.Status',-5,'int');
         if($Status!=-5){
            $where['Status']=$Status;
         }
         $SqAdminID=I('post.SqAdminID',-5,'int');
         if($SqAdminID!=-5){
            $where['SqAdminID']=$SqAdminID;
         }
         $LoanType=I('post.LoanType',-5,'int');
         if($LoanType!=-5){
             $where['LoanType']=$LoanType;
         }
        //审核时间
        $shStartTime=I('post.shStartTime');  //按时间查询
        $shEndTime=I('post.shEndTime');
        $ToStartTime=$shStartTime;
        $ToEndTime=date('Y-m-d',strtotime($shEndTime."+1 day"));
        if($shStartTime!=null){
            if($shEndTime!=null){
                //有开始时间和结束时间
                $where['ShTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['ShTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($shEndTime!=null){
                $where['ShTime']=array('elt',$ToEndTime);
            }
        }
        //申请时间
        $sqStartTime=I('post.sqStartTime');  //按时间查询
        $sqEndTime=I('post.sqEndTime');
        $ToStartTime=$sqStartTime;
        $ToEndTime=date('Y-m-d',strtotime($sqEndTime."+1 day"));
        if($sqStartTime!=null){
            if($sqEndTime!=null){
                //有开始时间和结束时间
                $where['ApplyTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['ApplyTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($sqEndTime!=null){
                $where['ApplyTime']=array('elt',$ToEndTime);
            }
        }
        //不是管理员的话，只能看到自己的单子
         if($_SESSION['AdminInfo']['RoleID']!='2'){
            if($_SESSION['AdminInfo']['RoleID']=='10'){
                //催收专员
                $where['CsadminID']=$_SESSION['AdminInfo']['AdminID'];
            }elseif($_SESSION['AdminInfo']['RoleID']=='7'){
                //客服专员
                $where['SqAdminID']=$_SESSION['AdminInfo']['AdminID'];
            }elseif($_SESSION['AdminInfo']['RoleID']=='8'){
                //放款专员
                $where['FKadminID']=$_SESSION['AdminInfo']['AdminID'];
            }
         }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,LoanStatus,BackM,OrderSn,LoanNo,YyFkTime,SqAdminID,LoanType,Status,ServiceID,ShTime,Remark,usefor,ShReason';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         $LoanStatusArr = $this->LoanStatus;
         $StatusArr = $this->Status;
         $LoanTypeArr = $this->LoanType;
         $UseforArr = $this->UseforArr;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,TgadminID,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');
                 $val['ServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['ServiceID']),'TrueName');
                 #新增渠道
                 $val['TgAdmin'] = M('tg_admin')->where(array('ID'=>$meminfo['TgadminID'],'IsDel'=>'0'))->getField('Name');
                 ;
                 //查询还款时间 T_HKLIST
                 if($val['LoanStatus']=='3' && $val['LoanType']=='0'){
                    //普通
                    $val['RealFkTime']=M(self::T_HKLIST)->where(array('ApplyID'=>$val['ID'],'PayStatus'=>'1','Status'=>'1','IsDel'=>'0'))->getField('HkTime');
                 }elseif($val['LoanType']=='1'){
                    //续借
                    $val['RealFkTime']='未订单';
                 }
                 //统计借款次数 必须是完整的订单
                 $val['LoanNumbs']=M(self::T_TABLE)->where(array('UserID'=>$val['UserID'],'LoanStatus'=>'3','IsDel'=>'0'))->count('ID');
                 //设置显示颜色
                 if($val['LoanNumbs']>0){
                    //表示 不止是一次借了
                    //判断有没有逾期记录
                    $yuqicheck='';
                    $yqwhere=array();
                    $yqwhere['UserID']=array('eq',$val['UserID']);
                    $yqwhere['IsDel']=array('eq','0');
                    $yqwhere['_string']="IsYQ='1' OR CONCAT(DATE_FORMAT(YyFkTime,'%Y-%m-%d'),' 23:59:59')<'".date('Y-m-d H:i:s')."' AND LoanStatus='2'";
                    $yuqicheck=M(self::T_TABLE)->where($yqwhere)->count('ID');
                    if($yuqicheck){
                        //有逾期记录
                        $val['TrueName']='<span style="color:red;">'.$val['TrueName'].'</span>';
                        $val['Mobile']='<span style="color:red;">'.$val['Mobile'].'</span>';
                    }else{
                        //无逾期记录
                        $val['TrueName']='<span style="color:blue;">'.$val['TrueName'].'</span>';
                        $val['Mobile']='<span style="color:blue;">'.$val['Mobile'].'</span>';
                    }
                 }
                 if($val['LoanStatus']==0){
                     $val['LoanStatus']='<span style="color:red;">'.$LoanStatusArr[$val['LoanStatus']].'</span>';
                 }else{
                     $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                 }
//                 $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                 $val['Status']=$StatusArr[$val['Status']] .($val['Remark']?';原因:<b style="color:#FF3366">'. $val['Remark'].'<b>':'');
                 $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                 $val['usefor']=$UseforArr[$val['usefor']];
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }
    //审核
    public function aduit(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->where(array("ID"=>$id))->find();
        //查询各个认证情况
        $UserID=$res['UserID'];
        $data=array();
        $CardStatus=M(self::T_CARDS)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//身份证认证
        if(!$CardStatus && $CardStatus!='0'){
            $data['CardStatus']='-1';//没有此记录,,没认证
        }else{
            $data['CardStatus']=$CardStatus;
        }
        $MobileStatus=M(self::T_MOBILE)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//手机认证
        if(!$MobileStatus && $MobileStatus!='0'){
            $data['MobileStatus']='-1';//没有此记录,,没认证
        }else{
            $data['MobileStatus']=$MobileStatus;
        }
        $AlipayStatus=M(self::T_ALIPAY)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//支付宝认证
        if(!$AlipayStatus && $AlipayStatus!='0'){
            $data['AlipayStatus']='-1';//没有此记录,,没认证
        }else{
            $data['AlipayStatus']=$AlipayStatus;
        }
        $TaobaoStatus=M(self::T_TAOBAO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//淘宝认证
        if(!$TaobaoStatus && $TaobaoStatus!='0'){
            $data['TaobaoStatus']='-1';//没有此记录,,没认证
        }else{
            $data['TaobaoStatus']=$TaobaoStatus;
        }
        $MemberinfoStatus=M(self::T_MEMBERINFO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//基本信息认证
        if(!$MemberinfoStatus && $MemberinfoStatus!='0'){
            $data['MemberinfoStatus']='-1';//没有此记录,,没认证
        }else{
            $data['MemberinfoStatus']=$MemberinfoStatus;
        }
        $SocialStatus=M(self::T_SOCIAL)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//社交认证
        if(!$SocialStatus && $SocialStatus!='0'){
            $data['SocialStatus']='-1';//没有此记录,,没认证
        }else{
            $data['SocialStatus']=$SocialStatus;
        }
        $BankStatus=M(self::T_BANK)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//银行卡认证
        if(!$BankStatus && $BankStatus!='0'){
            $data['BankStatus']='-1';//没有此记录,,没认证
        }else{
            $data['BankStatus']=$BankStatus;
        }

        $ZhimafenStatus = M(self::T_ZHIMA)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//银行卡认证
        if(!$ZhimafenStatus && $ZhimafenStatus!='0'){
            $data['ZhimafenStatus']='-1';//没有此记录,,没认证
        }else{
            $data['ZhimafenStatus'] = $ZhimafenStatus;
        }

        $this->assign(array(
            "res"=>$res,
            "datarenzen"=>$data,
            ));
        $this->display();
    }
    //审核信息提交处理
    public function aduitsave(){
        $ID=I('post.ID','');
        $Status=I('post.Status','0');
        $ForbidDay=I('post.ForbidDay','0');
        $Remark=I('post.Remark','');
        //审核校验
        $applyinfos=M(self::T_TABLE)->where(array("ID"=>$ID))->find();
        if(!$applyinfos){
            $this->ajaxReturn(0,'很抱歉，无此申请记录！');
        }
        if($applyinfos['Status']!='0'){
            $this->ajaxReturn(0,'很抱歉，只能审核待审核的申请！');
        }
        if($applyinfos['LoanStatus']!='0'){
            $this->ajaxReturn(0,'很抱歉，只能审核申请中的订单！');
        }
        if($Status=='2'){
            if(!$Remark){
                $this->ajaxReturn(0,'很抱歉，审核失败必须写明失败原因！');
            }
        }
        if($Status=='0'){
            $this->ajaxReturn(0,'很抱歉，此记录已经是待审核记录了！');
        }
        $sdata=array();//修改的数据
        if($Status=='1'){
            //成功
            $sdata['LoanStatus']='1';
        }elseif($Status=='2'){
            //审核失败 拒绝
            $sdata['LoanStatus']='5';
            $sdata['Remark']=$Remark;
            $sdata['LimitDay']=$ForbidDay;
        }
        $sdata['ServiceID']=$_SESSION['AdminInfo']['AdminID'];
        $sdata['ShTime']=date('Y-m-d H:i:s');
        $sdata['Status']=$Status;
        $sdata['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
        $sdata['UpdateTime']=date('Y-m-d H:i:s');
        $sdata['OpenM']=$applyinfos['ApplyMoney']-$applyinfos['AdoptMoney']-$applyinfos['FJMoney']-$applyinfos['Interest'];
        $model=M();
        $model->startTrans();
        $result=$model->table('xb_loans_applylist')->where(array("ID"=>$ID))->save($sdata);
        if($result){
            //更新会员表中的
            $mdata=array(
                'ForbidTime'=>date("Y-m-d H:i:s",strtotime("+".$ForbidDay." day")),
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $result2=$model->table('xb_mem_info')->where(array('ID'=>$applyinfos['UserID']))->save($mdata);
            if(!$result2){
                $model->rollback();
                $this->ajaxReturn(0,'很抱歉，更新会员表失败！');
            }
            //发送消息通知信息
            if($Status=='1'){
                //成功
                $msgcont='尊敬的会员，您提交的申请订单：'.$applyinfos['LoanNo'].'，审核成功!';
                $mobile=M('mem_info')->where(array('ID'=>$applyinfos['UserID']))->getField('Mobile');
                send_message($mobile,$msgcont);//发送短信消息
                send_mem_notics($applyinfos['UserID'],$msgcont);//发送站内通知消息
            }elseif($Status=='2'){
                //审核失败 拒绝
                $msgcont='尊敬的会员，您提交的申请订单：'.$applyinfos['LoanNo'].'，审核失败!';
                send_mem_notics($applyinfos['UserID'],$msgcont);//发送站内通知消息
            }
            $model->commit();
            $this->ajaxReturn(1,'恭喜您，审核操作成功！');
        }else{
            $model->rollback();
            $this->ajaxReturn(0,'很抱歉，审核操作失败！');
        }
    }
    public function detail(){
        $ID=I('request.ID');
        $infos=M(self::T_TABLE)->alias('a')
               ->field('a.*,b.MemAccount,b.Mobile,b.TrueName')
               ->join('left join xb_mem_info b on a.UserID=b.ID')
               ->where(array('a.ID'=>$ID))->find();
        if($infos['ServiceID']){
            $infos['ServiceID']=M(self::T_ADMIN)->where(array('ID'=>$infos['ServiceID']))->getField('TrueName');
        }
        $infos['sjhkmoney']='0';
        if($infos['LoanStatus']=='3'){
            $sjhkmoney='';
            $sjhkmoney=M('loans_hklist')->where(array('ApplyID'=>$infos['ID'],'PayStatus'=>'1','Status'=>'1'))->getField('TotalMoney');
            if($sjhkmoney){
                $infos['sjhkmoney']=$sjhkmoney;//还款
            }else{
                //续借
                $infos['sjhkmoney']=M('loans_xjapplylist')->where(array('ApplyID'=>$infos['ID'],'PayStatus'=>'1','Status'=>'1'))->getField('TotalMoney');
            }
        }
        $LoanStatusArr=$this->LoanStatus;
        $StatusArr=$this->Status;
        $LoanTypeArr=$this->LoanType;
        $infos['LoanStatus2']=$infos['LoanStatus'];
        $infos['LoanStatus']=$LoanStatusArr[$infos['LoanStatus']];
        $infos['Status']=$StatusArr[$infos['Status']];
        $infos['LoanType']=$LoanTypeArr[$infos['LoanType']];
        //身份证认证
        $cardinfos=M('renzen_cards')->alias('a')
                   ->field('a.ID,a.Yddatas,a.Status,a.RenzTime,a.CardFace,a.CardSide,a.Cardschi,b.Mobile,b.TrueName,b.IDCard')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();
        if($cardinfos['Yddatas']){
            $cardinfos['Yddatas']=unserialize($cardinfos['Yddatas']);
        }
        $cardimgArr=array();
        $cardimgArr[]=$cardinfos['CardFace'];
        $cardimgArr[]=$cardinfos['CardSide'];
        $cardimgArr[]=$cardinfos['Cardschi'];
        //手机认证
        $mobileinfos=M('renzen_mobile')->alias('a')
                   ->field('a.ZUserName,a.OpenDate,a.AccountBalance,a.Status,a.RenzTime,b.Mobile,b.TrueName')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();
        //支付宝认证
        $alipayinfos=M('renzen_alipay')->alias('a')
                   ->field('a.TaobaoName,a.Balance,a.HuabeiBalance,a.HuabeiLimit,a.HuabeiRet,a.ZFBMobile,a.Email,a.RenzTime,a.Status,b.Mobile,b.TrueName')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();
        //淘宝认证
        $taobaoinfos=M('renzen_taobao')->alias('a')
                   ->field('a.BDMobile,a.Levels,a.Balance,a.JBalance,a.UserName,a.XFQuote,a.XYQuote,a.ZmScore,a.JieBei,a.YZStatus,a.RenzTime,a.Status,b.Mobile,b.TrueName')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();
        //基本信息认证
        $jibeninfos=M('renzen_memberinfo')->alias('a')
                   ->field('a.*,b.Mobile,b.TrueName,b.NickName,b.Sex')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();
        //社交认证
        $socialinfos=M('renzen_social')->field('ID,QQ,WeChat,Contents,Status,RenzTime')->where(array('UserID'=>$infos['UserID'],'IsDel'=>'0'))->find();
        if($socialinfos['Contents']){
            $socialinfos['Contents']=unserialize($socialinfos['Contents']);

            #2018年9月6日11:16:49 by hury
            #新增紧急人通话次数
            $socialController = new SocialController();
            $socialinfos['callTimes'] = $socialController->callPhoneHistory($infos['UserID'], $socialinfos['Contents']['tel']);

//            $UserID = $infos['UserID'];
//            foreach ($socialinfos['Contents']['qinshu'] as $index => &$people) {
//                $people['callTimes'] = $socialController->callPhoneHistory($UserID, $people['tel']);
//            }
//            foreach ($socialinfos['Contents']['shehui'] as $index => &$people2) {
//                $people2['callTimes'] = $socialController->callPhoneHistory($UserID, $people2['tel']);
//            }

        }
        //银行卡认证
        $bankinfos=M('renzen_bank')->alias('a')
                   ->field('a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();

        //芝麻分认证
        $zhimainfos=M('renzen_zhima')
            ->field('UserID,Score as Zmf,Status,WhichMonth,WatchList as Hygz')
            ->where(array('UserID'=>$infos['UserID']))->find();
        if($zhimainfos) {
            if($zhimainfos['Hygz']){
                $zhimainfos['Hygz']= json_encode( unserialize($zhimainfos['Hygz']) );
                $zhimainfos['Hygz'] = json_decode($zhimainfos['Hygz'], true);
            }
        }

        $this->assign(array(
            'infos'=>$infos,
            'cardinfos'=>$cardinfos,
            'cardimgArr'=>$cardimgArr,
            'mobileinfos'=>$mobileinfos,
            'alipayinfos'=>$alipayinfos,
            'taobaoinfos'=>$taobaoinfos,
            'jibeninfos'=>$jibeninfos,
            'socialinfos'=>$socialinfos,
            'bankinfos'=>$bankinfos,
            'zhimainfos'=>$zhimainfos,
            ));
        $this->display();
    }
    //转单页面
    public function zorder(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->where(array("ID"=>$id))->find();
        $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'7','Status'=>'1','IsDel'=>'0'))->select();
        $this->assign(array(
            'res'=>$res,
            'kefuArr'=>$kefuArr,
            ));
        $this->display();
    }
    //转单保存
    public function zordersave(){
        $ID=I('post.ID','');
        $SqAdminID=I('post.SqAdminID','0');
        if(!$SqAdminID){
            $this->ajaxReturn(0,'很抱歉，请选择专属客服！');
        }
        $result=M(self::T_TABLE)->where(array('ID'=>$ID))->save(array('SqAdminID'=>$SqAdminID));
        if($result){
            $this->ajaxReturn(1,'恭喜您，转单成功成功！');
        }else{
            $this->ajaxReturn(0,'很抱歉，转单失败！');
        }
    }
    //导出功能
    public function exportexcel(){
         $sort=I('post.sort');
         $order=I('post.order');
         if ($sort && $order){
             $sort=$sort.' '.$order;
         }else{
             $sort='ID desc';
         }

         //搜索条件
         $TrueName=I('post.TrueName','');
         if($TrueName){
            $memidArr=M(self::T_MEMINFO)->field('ID')->where(array('TrueName'=>array('like','%'.$TrueName.'%')))->select();
            if($memidArr){
                $memids=array();
                foreach($memidArr as $k=>$v){
                    $memids[]=$v['ID'];
                }
                //$memids=array_column($memidArr, 'ID');
                $where['UserID']=array('in',$memids);
            }else{
                $where['UserID']=array('eq','0');
            }
         }
         $Mobile=I('post.Mobile','');
         if($Mobile){
            $memidArr='';
            $memidArr=M(self::T_MEMINFO)->field('ID')->where(array('Mobile'=>array('eq',$Mobile)))->select();
            if($memidArr){
                $memids=array();
                foreach($memidArr as $k=>$v){
                    $memids[]=$v['ID'];
                }
                $where['UserID']=array('in',$memids);
            }else{
                $where['UserID']=array('eq','0');
            }
         }
         $LoanStatus=I('post.LoanStatus',-5,'int');
         if($LoanStatus!=-5){
            $where['LoanStatus']=$LoanStatus;
         }
         $LoanNo=I('post.LoanNo','');
         if($LoanNo){
            $where['LoanNo']=$LoanNo;
         }
         $OrderSn=I('post.OrderSn','');
         if($OrderSn){
            $where['OrderSn']=$OrderSn;
         }
         $Status=I('post.Status',-5,'int');
         if($Status!=-5){
            $where['Status']=$Status;
         }
         $SqAdminID=I('post.SqAdminID',-5,'int');
         if($SqAdminID!=-5){
            $where['SqAdminID']=$SqAdminID;
         }
        //审核时间
        $shStartTime=I('post.shStartTime');  //按时间查询
        $shEndTime=I('post.shEndTime');
        $ToStartTime=$shStartTime;
        $ToEndTime=date('Y-m-d',strtotime($shEndTime."+1 day"));
        if($shStartTime!=null){
            if($shEndTime!=null){
                //有开始时间和结束时间
                $where['ShTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['ShTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($shEndTime!=null){
                $where['ShTime']=array('elt',$ToEndTime);
            }
        }
        //申请时间
        $sqStartTime=I('post.sqStartTime');  //按时间查询
        $sqEndTime=I('post.sqEndTime');
        $ToStartTime=$sqStartTime;
        $ToEndTime=date('Y-m-d',strtotime($sqEndTime."+1 day"));
        if($sqStartTime!=null){
            if($sqEndTime!=null){
                //有开始时间和结束时间
                $where['ApplyTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['ApplyTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($sqEndTime!=null){
                $where['ApplyTime']=array('elt',$ToEndTime);
            }
        }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,LoanStatus,BackM,OrderSn,LoanNo,YyFkTime,SqAdminID,LoanType,Status,ServiceID,ShTime';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array['rows'] =M(self::T_TABLE)->where($where)->order($sort)->select();

         $LoanStatusArr = $this->LoanStatus;
         $StatusArr = $this->Status;
         $LoanTypeArr = $this->LoanType;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');
                 $val['ServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['ServiceID']),'TrueName');
                 //查询还款时间 T_HKLIST
                 if($val['LoanStatus']=='3' && $val['LoanType']=='0'){
                    //普通
                    $val['RealFkTime']=M(self::T_HKLIST)->where(array('ApplyID'=>$val['ID'],'PayStatus'=>'1','Status'=>'1','IsDel'=>'0'))->getField('HkTime');
                 }elseif($val['LoanType']=='1'){
                    //续借
                    $val['RealFkTime']='未订单';
                 }
                 $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                 $val['Status']=$StatusArr[$val['Status']];
                 $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                 $result['rows'][]=$val;
            }
         }
         //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>手机号码</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>商品金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>收购价格</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>撮合费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>息费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>优惠劵金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请天数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>到期应还</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>订单号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>预约还款时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>实际还款时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>专属客服</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请类型</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核人</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核时间</td>
            </tr>';

        foreach($result['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Mobile'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['ApplyMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['AdoptMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['FJMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Interest'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['CoMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['ApplyDay'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['ApplyTime'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['LoanStatus'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['BackM'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['OrderSn'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['LoanNo'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['YyFkTime'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['RealFkTime'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['SqAdminID'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['LoanType'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Status'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['ServiceID'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['ShTime'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'借款申请列表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        ob_end_clean();//清除缓存区的内容
        header("Content-type: application/vnd.ms-excel; charset=GBK");
        //header('Content-Type:text/html;charset=utf-8');
        header("Content-Disposition: attachment; filename=$str_filename.xls");
        echo $html;
        exit;
    }
    //匹配通话
    public function getpp(){
        $id=I('post.ID',0,'intval');
        $UserID=M('loans_applylist')->where(array('ID'=>$id))->getField('UserID');

        $mobileinfos=M('renzen_mobile')->field('ID,UserID,CallBill')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if(!$mobileinfos['CallBill']){
            $this->ajaxReturn(0,'暂未获取到通话记录!');
        }
        //查询 社交认证表 中的手机通讯录
        $socialinfo=M('renzen_social')->field('ID,Phonelist')->where(array('UserID'=>$mobileinfos['UserID'],'IsDel'=>'0'))->find();
        if(!$socialinfo['Phonelist']){
            $this->ajaxReturn(0,'暂未获取到手机通讯录');
        }
        //进行数据匹配
        $callbills=unserialize($mobileinfos['CallBill']);
        $phonelists=unserialize($socialinfo['Phonelist']);
        foreach($phonelists as $k=>$v){
            $sums='0';//次数
            foreach($callbills as $k2=>$v2){
                if($v['tel']==$v2['tel']){
                    $sums=$v2['contype']+$v2['consite'];
                }
            }
            $phonelists[$k]['sums']=$sums;
            $phonelists[$k]['updatetime']=date('Y-m-d H:i:s');
        }
        $phonelists=serialize($phonelists);
        $updata=array(
            'Phonelist'=>$phonelists,
            );
        $result=M('renzen_social')->where(array('ID'=>$socialinfo['ID']))->save($updata);
        if($result){
//            $result2=M('renzen_mobile')->where(array('ID'=>$id))->save(array('IsPP'=>'1'));
            $this->ajaxReturn(0,'匹配成功!');
        }else{
            $this->ajaxReturn(0,'匹配失败!');
        }
    }
    /**
     *通话记录
     */
    public function CallBill(){
        $listID=I('get.listID');//借款申请id
        $tel=I('post.tel');
        $contype=I('post.contype','-5');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置
            $UserID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');

            $CallBill=M('renzen_mobile')->where(array("UserID"=>$UserID))->getField('CallBill');
            if($CallBill){
                $CallBill=unserialize($CallBill);
                //搜索条件
                if($tel){
                    foreach($CallBill as $k=>$v){
                        if($v['tel']!=$tel){
                            unset($CallBill[$k]);
                        }
                    }
                }
                if($contype!='-5'){
                    if($contype=='1'){
                        //主叫
                        foreach($CallBill as $k=>$v){
                            if($v['contype']!='主叫'){
                                unset($CallBill[$k]);
                            }
                        }
                    }elseif($contype=='2'){
                        //被叫
                        foreach($CallBill as $k=>$v){
                            if($v['contype']!='被叫'){
                                unset($CallBill[$k]);
                            }
                        }
                    }
                }
                //数据分页
                $array='';
                $array=array_slice($CallBill,$start,$rows);
                $result=array();
                if($array){
                    foreach ($array as $val){
                        $result['rows'][]=$val;
                    }
                    $result['total']=count($CallBill);
                }
                $this->ajaxReturn($result);
            }
        }
    }
     /**
     *手机通讯录
     */
    public function Phonelist(){
        $listID=I('get.listID');//借款申请id
        $name=I('post.name','');
        $tel=I('post.tel','');
        //排序
        $sort=I('post.sort');
        $order=I('post.order');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置
            $UserID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');

            $Phonelist=M('renzen_social')->where(array("UserID"=>$UserID))->getField('Phonelist');
            if($Phonelist){
                $Phonelist=unserialize($Phonelist);
                //排序功能
                if($sort=='sums'){
                    if($order=='desc'){
                        $Phonelist=$this->arraySequence($Phonelist, 'sums', $sort = 'SORT_DESC');
                    }elseif($order=='asc'){
                        $Phonelist=$this->arraySequence($Phonelist, 'sums', $sort = 'SORT_ASC');
                    }
                }
                //搜索
                if($name){
                    foreach($Phonelist as $k=>$v){
                        if(strpos($v['name'],$name)===false){
                            unset($Phonelist[$k]);
                        }
                    }
                }
                if($tel){
                    foreach($Phonelist as $k=>$v){
                        if($v['tel']!=$tel){
                            unset($Phonelist[$k]);
                        }
                    }
                }
                //数据分页
                $array='';
                $array=array_slice($Phonelist,$start,$rows);
                $result=array();
                if($array){
                    foreach ($array as $val){
                        $result['rows'][]=$val;
                    }
                    $result['total']=count($Phonelist);
                }
                $this->ajaxReturn($result);
            }
        }
    }
    //收货地址
    public function saddreslist(){
        $listID=I('get.listID');//借款申请id
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置
            $UserID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');

            $Receivers=M('renzen_taobao')->where(array("UserID"=>$UserID))->getField('Receivers');
            if($Receivers){
                $Receivers=unserialize($Receivers);
                //数据分页
                $array='';
                $array=array_slice($Receivers,$start,$rows);
                $result=array();
                if($array){
                    foreach ($array as $val){
                        $result['rows'][]=$val;
                    }
                    $result['total']=count($Receivers);
                }
                $this->ajaxReturn($result);
            }
        }
    }
    /**
     * 借款申请
     */
    public function ApplyDetail(){
        $listID=I('get.listID');//借款申请id
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $sort='UpdateTime Desc';

            $UID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');
            $where['UserID']=$UID;
            //查询的列名
            $col='ID,LoanNo,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,LoanStatus,BackM,OrderSn,YyFkTime,SqAdminID,LoanType,Status,ServiceID,ShTime';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);
            $LoanStatusArr = array(0=>"申请中",1=>"放款中",2=>"已放款",3=>"已完成",4=>"已取消",5=>"已拒绝");
            $StatusArr = array(0=>'待审核',1=>'审核成功',2=>'审核失败');
            $LoanTypeArr = array(0=>'普通',1=>'续借',2=>'分期');
            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');
                    $val['ServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['ServiceID']),'TrueName');
                    $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                    $val['Status']=$StatusArr[$val['Status']];
                    $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }
    /**
     * 还款记录
     */
    public function RepayDetail(){
        $listID=I('get.listID');//借款申请id
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $sort='UpdateTime Desc';

            $UID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');
            $where['UserID']=$UID;
            //查询的列名
            $col='ID,LoanNo,TotalMoney,HkTime,CostPayable,RatePayable,SeviceCostPayable,FinePayable,Accounts,TradeNo,TradeRemark,PayType,PayStatus,AdminID,ShTime,Status';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_HKLIST,$where,$page,$rows,$sort,$col);
            $PayTypeArr = array(0=>"未付款",1=>"支付宝",2=>"微信",3=>"银联",4=>"代付");
            $PayStatusArr = array(0=>'待支付',1=>'已支付');
            $StatusArr = array(0=>'待审核',1=>'审核通过',2=>'审核未通过');
            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['AdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['AdminID']),'UserName');
                    $val['PayType']=$PayTypeArr[$val['PayType']];
                    $val['Status']=$StatusArr[$val['Status']];
                    $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }
    //魔盒报告 独立页面
     public function mohepages(){
        $id=I('post.ID',0,'intval');
        $UserID=M('loans_applylist')->where(array('ID'=>$id))->getField('UserID');
        $mobileinfos=M('renzen_mobile')->field('ID,TaskID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if(!$mobileinfos['TaskID']){
            $this->ajaxReturn(0,'暂不能查看报告!');
        }
        $binfos=M('sys_basicinfo')->field('MhCode,MhKey')->find();
        $url='https://report.shujumohe.com/report/getToken?partner_code='.$binfos['MhCode'].'&partner_key='.$binfos['MhKey'];
        $retdata=get_request($url);
        if($retdata['code']=='0'){
            $jumpurl="https://report.shujumohe.com/report/".$mobileinfos['TaskID']."/".$retdata['data'];
            //$jumpurl2="<script language=\"javascript\">window.open ('".$jumpurl."')</script>";
            $this->ajaxReturn(1,$jumpurl);
        }else{
            $this->ajaxReturn(0,'获取免密token失败!');
        }
     }
     /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
     public function arraySequence($array, $field, $sort = 'SORT_DESC'){
         $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
     }
    //天机报告 独立页面
     public function tianji(){
        $id=I('post.ID',0,'intval');
        $UserID=M('loans_applylist')->where(array('ID'=>$id))->getField('UserID');
        $id=I('post.ID',0,'intval');
        $mobileinfos=M('renzen_mobile')->field('ID,TUserID,TaskID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if(!$mobileinfos['TaskID'] && !$mobileinfos['TUserID'] && !$mobileinfos['MobileData']){
            $this->ajaxReturn(0,'暂不能查看报告!');
        }

         $jumpurl="http://".$_SERVER['HTTP_HOST']."/index.php/Tianji/report?searchId=".$mobileinfos['TUserID']."&niqueId=".$mobileinfos['TaskID'];
         $this->ajaxReturn(1,$jumpurl);
     }
     public function udcredit(){
        $id=I('post.ID',0,'intval');
        $UserID=M('loans_applylist')->where(array('ID'=>$id))->getField('UserID');
        $mobileinfos=M('renzen_cards')->field('ID,TaskID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if(!$mobileinfos['ID']){
            $this->ajaxReturn(0,'暂不能查看报告!');
        }
        $this->ajaxReturn(1,'/admin.php/Renzen/Cards/udcredit?action=showinfo&ID='.$mobileinfos['ID']);
    }

 }
