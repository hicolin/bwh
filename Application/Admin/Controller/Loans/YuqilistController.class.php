<?php
/**
 * 功能说明: 逾期记录控制器
 */
 namespace Admin\Controller\Loans;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 use Home\Controller\Curl;
 use Home\Controller\Crypt3Des;
 class YuqilistController extends BaseController{

     const T_TABLE='loans_applylist';
     const T_HKLIST='loans_hklist';
     const T_MEMINFO='mem_info';
     const T_CSRECORD='loans_csrecord';
     const T_ADMIN='sys_administrator';

     public function _initialize(){
        parent::_initialize();
        $this->LoanStatus = array(2=>"已放款",3=>"已完成");
        $this->LoanType = array(0=>'普通',1=>'续期',2=>'分期');
     }
 
     public function index(){
         $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'10','Status'=>'1','IsDel'=>'0'))->select();

         $DockingCustomerServices = M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'7','Status'=>'1','IsDel'=>'0'))->select();

         $RoleID=$_SESSION['AdminInfo']['RoleID'];

         $this->assign(array(
            'LoanStatus'=>$this->LoanStatus,
            'Status'=>$this->Status,
            'kefuArr'=>$kefuArr,
            'DockingCustomerServices' => $DockingCustomerServices,
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
         $CsadminID=I('post.CsadminID',-5);
         if($CsadminID!=-5){
            $where['CsadminID']=$CsadminID;
         }
         $SqAdminID=I('post.SqAdminID',-5,'int');
         if($SqAdminID!=-5){
             $where['SqAdminID']=$SqAdminID;
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
         $LoanNo=I('post.LoanNo','');
         if($LoanNo){
            $where['LoanNo']=$LoanNo;
         }
         $OrderSn=I('post.OrderSn','');
         if($OrderSn){
            $where['OrderSn']=$OrderSn;
         }

         $where['IsDel']=0;
         //$where['_string']="IsYQ='1' OR YyFkTime<'".date('Y-m-d H:i:s')."' AND LoanStatus='2'";
         $where['_string']="IsYQ='1' OR CONCAT(DATE_FORMAT(YyFkTime,'%Y-%m-%d'),' 23:59:59')<'".date('Y-m-d H:i:s')."' AND LoanStatus='2'";
         //查询的数据表字段名
         $col='ID,UserID,LoanNo,SqAdminID,OrderSn,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,OpenM,BackM,YyFkTime,LoanStatus,FkServiceID,OpenTime,CsadminID';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         $LoanStatusArr = $this->LoanStatus;
         $LoanTypeArr = $this->LoanType;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['FkServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['FkServiceID']),'TrueName');
                 $val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');

                 //查询还款时间 T_HKLIST
                 if($val['LoanStatus']=='3'){
                    //普通
                    $hkdatainfo='';
                    $hkdatainfo=M(self::T_HKLIST)->field('ID,HkTime,FinePayable,TotalMoney')->where(array('ApplyID'=>$val['ID'],'PayStatus'=>'1','Status'=>'1','IsDel'=>'0'))->find();
                    //过了当天夜里24点才算逾期
                    $overtimes='';
                    $overtimes=date('Y-m-d',strtotime($val['YyFkTime'])).' 23:59:59';

                    $val['Overdays']=(strtotime($hkdatainfo['HkTime'])-strtotime($overtimes))/86400;
                    if($val['Overdays']<=0){
                        $val['Overdays']='0';
                    }else{
                        $val['Overdays']=ceil($val['Overdays']);
                    }
                    $val['Famoney']=$hkdatainfo['FinePayable'];
                    $val['RealFkTime']=$hkdatainfo['HkTime'];
                    $val['RealtotalMoney']=$hkdatainfo['TotalMoney'];
                 }elseif($val['LoanType']=='1'){
                    //续借
                    $val['RealFkTime']='';
                 }
                 if($val['LoanStatus']=='2'){
                    //查询逾期信息
                    $yuqidata='';
                    $yuqidata=$this->getoverinfos($val['ID']);
                    $val['Overdays']=$yuqidata['overdays'];
                    $val['Famoney']=$yuqidata['famoney'];
                    $val['RealtotalMoney']=$yuqidata['realtotal'];
                 }

                 $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                 $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                 if($val['CsadminID']){
                    $val['Csadmin']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['CsadminID']),'TrueName');
                 }else{
                    $val['Csadmin']='';
                 }
                 //催收信息 T_CSRECORD
                 $csinfos='';
                 $csinfos=M(self::T_CSRECORD)->where(array('ApplyID'=>$val['ID'],'IsDel'=>'0'))->order('ID desc')->find();
                 if($csinfos){
                    $val['CsRemark']=$csinfos['Remark'];
                    $val['Cstime']=$csinfos['UpdateTime'];
                 }
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }
     //算出逾期天数，罚金，应还总金额
     public function getoverinfos($id){
        $infos=M('loans_applylist')->field('ID,YyFkTime,ApplyMoney,CoMoney,RongDay,RongP,OverdueP,BackM')->find($id);
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($infos['YyFkTime'])).' 23:59:59';
        $overdays=(time()-strtotime($overtimes))/86400;
        $overdays=ceil($overdays);//逾期天数
        $loandmoney=$infos['ApplyMoney']-$infos['CoMoney'];//应还的本金
        //计算罚金
        $famoney='';
        if($overdays<=$infos['RongDay']){
            $famoney=$loandmoney*($infos['RongP']/100)*$overdays;
        }elseif($infos['RongDay']<$overdays){
            $famoney=$loandmoney*($infos['RongP']/100)*$infos['RongDay']+$loandmoney*($infos['OverdueP']/100)*($overdays-$infos['RongDay']);
        }
        $famoney=round($famoney,2);
        $retdata=array(
            'overdays'=>$overdays,
            'famoney'=>$famoney,
            'realtotal'=>$famoney+$infos['BackM'],//应还总金额
            );
        return $retdata;
    }
    //审核信息提交处理
    public function addhmd(){
        $ID=I('post.ID','');
        //审核校验
        $applyinfos=M(self::T_TABLE)->field('ID,UserID')->where(array("ID"=>$ID))->find();
        if(!$applyinfos){
            $this->ajaxReturn(0,'很抱歉，无此记录！');
        }
        $result=M(self::T_MEMINFO)->where(array('ID'=>$applyinfos['UserID']))->save(array('Status'=>'4','OperatorID'=>$_SESSION['AdminInfo']['AdminID'],'UpdateTime'=>date('Y-m-d H:i:s')));
        if($result){
            $this->ajaxReturn(1,'恭喜您，加入黑名单成功！');
        }else{
            $this->ajaxReturn(0,'很抱歉，加入黑名单失败！');
        }
    }
    public function detail(){
        $ID=I('request.ID');
        $infos=M(self::T_TABLE)->alias('a')
               ->field('a.*,b.Mobile,b.TrueName')
               ->join('left join xb_mem_info b on a.UserID=b.ID')
               ->where(array('a.ID'=>$ID))->find();
        if($infos['FkServiceID']){
            $infos['FkServiceID']=M(self::T_ADMIN)->where(array('ID'=>$infos['FkServiceID']))->getField('TrueName');
        }
         //查询还款时间 T_HKLIST
         if($infos['LoanStatus']=='3' && $infos['LoanType']=='0'){
            //普通
            $hkdatainfo='';
            $hkdatainfo=M(self::T_HKLIST)->where(array('LoanNo'=>$infos['LoanNo'],'PayStatus'=>'1','Status'=>'1','IsDel'=>'0'))->find();
            //过了当天夜里24点才算逾期
            $overtimes=date('Y-m-d',strtotime($infos['YyFkTime'])).' 23:59:59';
            $infos['Overdays']=(strtotime($hkdatainfo['HkTime'])-strtotime($overtimes))/86400;
            if($infos['Overdays']<=0){
                $infos['Overdays']='0';
            }else{
                $infos['Overdays']=ceil($infos['Overdays']);
            }
            $infos['Famoney']=$hkdatainfo['FinePayable'];
            $infos['RealFkTime']=$hkdatainfo['HkTime'];
            $infos['RealtotalMoney']=$hkdatainfo['TotalMoney'];
         }elseif($infos['LoanType']=='1'){
            //续借
            $infos['RealFkTime']='';
         }
         if($infos['LoanStatus']=='2'){
            //查询逾期信息
            $yuqidata='';
            $yuqidata=$this->getoverinfos($infos['ID']);
            $infos['Overdays']=$yuqidata['overdays'];
            $infos['Famoney']=$yuqidata['famoney'];
            $infos['RealtotalMoney']=$yuqidata['realtotal'];
         }

        $LoanStatusArr=$this->LoanStatus;
        $LoanTypeArr=$this->LoanType;
        $infos['LoanStatus2']=$infos['LoanStatus'];
        $infos['LoanStatus']=$LoanStatusArr[$infos['LoanStatus']];
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
        }
        //银行卡认证
        $bankinfos=M('renzen_bank')->alias('a')
                   ->field('a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.UserID'=>$infos['UserID']))->find();

        //催收记录
        $cslists=M('loans_csrecord')->alias('a')
                 ->field('a.*,b.TrueName')
                 ->join('left join xb_sys_administrator b on a.OperatorID=b.ID')
                 ->where(array('a.ApplyID'=>$ID))
                 ->select();
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
            'cslists'=>$cslists,
            ));
        $this->display();
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

         $where['IsDel']=0;
         //$where['_string']="IsYQ='1' OR YyFkTime<'".date('Y-m-d H:i:s')."' AND LoanStatus='2'";
         $where['_string']="IsYQ='1' OR CONCAT(DATE_FORMAT(YyFkTime,'%Y-%m-%d'),' 23:59:59')<'".date('Y-m-d H:i:s')."' AND LoanStatus='2'";
         //查询的数据表字段名
         $col='ID,UserID,LoanNo,OrderSn,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,OpenM,BackM,YyFkTime,LoanStatus,FkServiceID,OpenTime,CsadminID';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array['rows'] =M(self::T_TABLE)->where($where)->order($sort)->select();

         $LoanStatusArr = $this->LoanStatus;
         $LoanTypeArr = $this->LoanType;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['FkServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['FkServiceID']),'TrueName');
                 //查询还款时间 T_HKLIST
                 if($val['LoanStatus']=='3' && $val['LoanType']=='0'){
                    //普通
                    $hkdatainfo='';
                    $hkdatainfo=M(self::T_HKLIST)->field('ID,HkTime,FinePayable,TotalMoney')->where(array('LoanNo'=>$val['LoanNo'],'PayStatus'=>'1','Status'=>'1','IsDel'=>'0'))->find();
                    //过了当天夜里24点才算逾期
                    $overtimes='';
                    $overtimes=date('Y-m-d',strtotime($val['YyFkTime'])).' 23:59:59';
                    $val['Overdays']=(strtotime($hkdatainfo['HkTime'])-strtotime($overtimes))/86400;
                    if($val['Overdays']<=0){
                        $val['Overdays']='0';
                    }else{
                        $val['Overdays']=ceil($val['Overdays']);
                    }
                    $val['Famoney']=$hkdatainfo['FinePayable'];
                    $val['RealFkTime']=$hkdatainfo['HkTime'];
                    $val['RealtotalMoney']=$hkdatainfo['TotalMoney'];
                 }elseif($val['LoanType']=='1'){
                    //续借
                    $val['RealFkTime']='';
                 }
                 if($val['LoanStatus']=='2'){
                    //查询逾期信息
                    $yuqidata='';
                    $yuqidata=$this->getoverinfos($val['ID']);
                    $val['Overdays']=$yuqidata['overdays'];
                    $val['Famoney']=$yuqidata['famoney'];
                    $val['RealtotalMoney']=$yuqidata['realtotal'];
                 }

                 $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                 $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                 $result['rows'][]=$val;
            }
         }
         
         //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>订单号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>手机号码</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>快速申请费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>用户管理费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>息费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>优惠劵金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>到期应还</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>逾期天数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>罚金</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>应还总金</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>应还时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>实际还款时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>借款状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款人</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核时间</td>
            </tr>';

        foreach($result['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['LoanNo'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OrderSn'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Mobile'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['ApplyMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['AdoptMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['FJMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Interest'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['CoMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OpenM'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['BackM'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Overdays'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Famoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['RealtotalMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['YyFkTime'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['RealFkTime'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['LoanStatus'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['FkServiceID'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OpenTime'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'逾期记录列表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        ob_end_clean();//清除缓存区的内容
        header("Content-type: application/vnd.ms-excel; charset=GBK");
        //header('Content-Type:text/html;charset=utf-8');
        header("Content-Disposition: attachment; filename=$str_filename.xls");
        echo $html;
        exit;
    }
    //催收页面
    public function csrecord(){
        $ID=I('request.ID');
        $res=M(self::T_TABLE)->field('ID,OrderSn')->where(array('ID'=>$ID))->find();
        $this->assign(array(
            'res'=>$res,
            ));
        $this->display();
    }
    //催收数据保存
    public function csdatasave(){
        $id=I('post.ID','');
        $Status=I('post.Status','1');
        $ImgUrl=I('post.ImgUrl','');
        $Remark=I('post.Remark','');
        //校验
        if(!$Remark){
            $this->ajaxReturn(0,'备注信息不能为空！');
        }
        $data=array(
            'ApplyID'=>$id,
            'Status'=>$Status,
            'Remark'=>$Remark,
            'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
            'UpdateTime'=>date('Y-m-d H:i:s'),
            );
        if($ImgUrl){
            $data['ImgUrl']=$ImgUrl;
        }
        $result=M('loans_csrecord')->add($data);
        if($result){
            $this->ajaxReturn(1,'催收记录添加成功！');
        }else{
            $this->ajaxReturn(0,'催收记录添加失败！');
        }
    }
    /**
     *通话记录
     */
    public function CallBill(){

        $listID=I('get.listID');//借款申请id
        $tel=I('post.tel');
        $contype=I('post.contype','-5');

        //匹配通话记录
//        $this->getpp($listID);
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

     //匹配通话
     public function getpp($ApplylistID){

         $UserID=M('loans_applylist')->where(array('ID'=>$ApplylistID))->getField('UserID');

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
//             $result2=M('renzen_mobile')->where(array('ID'=>$id))->save(array('IsPP'=>'1'));
             return true;
         }else{
             return false;
         }
     }

     /**
     *手机通讯录
     */
    public function Phonelist(){
        $listID=I('get.listID');//借款申请id
        $name=I('post.name','');
        $tel=I('post.tel','');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置
            $UserID=M('loans_applylist')->where(array("ID"=>$listID))->getField('UserID');

            $Phonelist=M('renzen_social')->where(array("UserID"=>$UserID))->getField('Phonelist');
            if($Phonelist){
                $Phonelist=unserialize($Phonelist);
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
    //转单页面
    public function zorder(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->where(array("ID"=>$id))->find();
        $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'10','Status'=>'1','IsDel'=>'0'))->select();
        $this->assign(array(
            'res'=>$res,
            'kefuArr'=>$kefuArr,
            ));
        $this->display();
    }
    //转单保存
    public function zordersave(){
        $ID=I('post.ID','');
        $CsadminID=I('post.CsadminID','0');
        if(!$CsadminID){
            $this->ajaxReturn(0,'很抱歉，请选择专属客服！');
        }
        $result=M(self::T_TABLE)->where(array('ID'=>$ID))->save(array('CsadminID'=>$CsadminID));
        if($result){
            $this->ajaxReturn(1,'恭喜您，转单成功成功！');
        }else{
            $this->ajaxReturn(0,'很抱歉，转单失败！');
        }
    }

     //代扣页面
     public function daikou()
     {
         $id = I('get.ID', 0, 'intval');
         $res = M(self::T_TABLE)->alias('a')
             ->field('a.ID,a.UserID,a.OrderSn,a.ApplyMoney,a.LoanStatus,a.ReplaymentType,b.TrueName,c.BankNo')
             ->join('left join xb_mem_info b on a.UserID=b.ID')
             ->join('left join xb_renzen_bank c on c.UserID=b.ID')
             ->where(array("a.ID" => $id))->find();
         //查询会员认证的银行卡信息
         $bankinfos = M('renzen_bank')->field('ID,BankNo,BankName')->where(array('UserID' => $res['UserID'], 'Status' => '1', 'IsDel' => '0'))->find();
         $this->assign(array(
             "res" => $res,
             "bankinfos" => $bankinfos,
         ));
         $this->display();
     }

     public function daikousave()
     {
         $post = I('post.');
         $applyinfos = M(self::T_TABLE)->alias('a')
             ->field('a.ID,a.UserID,a.OrderSn,a.OpenM,a.LoanStatus,a.ReplaymentType,a.LoanNo,b.TrueName,c.BankNo,c.YMobile,c.PROTOCOLNO')
             ->join('left join xb_mem_info b on a.UserID=b.ID')
             ->join('left join xb_renzen_bank c on c.UserID=b.ID')
             ->where(array("a.ID" => $post['ID']))->find();

         if (!$applyinfos) {
             $this->ajaxReturn(0, '很抱歉，无此申请记录！');
         }
         if ($applyinfos['LoanStatus'] != '2') {
             $this->ajaxReturn(0, '很抱歉，不能对此订单进行操作！');
         }
         if(!$applyinfos['BankNo']){
             $this->ajaxReturn(0,"此用户还未绑定银行卡，请先绑定!");
         }
         $oderSn = getOrderSn();
         $configArr = $this->getfuyouset();
         $header = array(
             '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
         );
         $VERSION = '1.0';
         $USERIP = get_client_ip();
         $MCHNTCD = $configArr['merchant_id'];
         $key = $configArr['key'];
         $TYPE = '03';
         $MCHNTORDERID = $oderSn;
         $USERID = $applyinfos['UserID'];
         $AMT = '1';  //$post['ApplyMoney'] //订单金额 分  测试阶段 1分
         $PROTOCOLNO = $applyinfos['PROTOCOLNO'] = 'X4BJ051000204841472GSD';
         $NEEDSENDMSG  = '0';
         $BACKURL = 'http://cunguanchou.com/fuyoupay/backhkquery';
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
         $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
         $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
         $result = Curl::curlPostHttps("https://mpay.fuiou.com/newpropay/order.pay",$param,$header);
         $xml_result = Crypt3Des::decrypt_base64($result,$key);
         if($xml_result === false){
             $xml_result = $result;
         }
         $arr_result = xml_to_array($xml_result);
         if($arr_result['RESPONSECODE'] == '0000'){
             $hkdata=array(
                 'UserID'=>$applyinfos[''],
                 'ApplyID'=>$applyinfos['UserID'],
                 'OrderSn'=>$oderSn,
                 'LoanNo'=>$applyinfos['LoanNo'],
                 'TotalMoney'=>$post['ApplyMoney'],
                 'CostPayable'=>$applyinfos['ApplyMoney'],
                 'HkTime'=>date('Y-m-d H:i:s'),
                 'SeviceCostPayable'=>'',
                 'PayType'=>'4',
                 'TradeRemark'=>'系统代扣',
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
             $hkdata['OrderSn'] = $arr_result['MCHNTORDERID']; //商户订单号
             $hkdata['TradeNo'] = $arr_result['ORDERID']; //富友订单号
             $hkdata['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
             $res = M('loans_hklist')->add($hkdata);
             $data['LoanStatus'] = '3';
             M(self::T_TABLE)->where(['ID'=>$post['ID']])->save($data);
             if($res!==false){
                 $this->ajaxReturn(1,$arr_result['RESPONSEMSG']);
             }else{
                 $this->ajaxReturn(0,"处理失败");
             }
         }else{
             $this->ajaxReturn(0,$arr_result['RESPONSEMSG']);
         }
     }

 }