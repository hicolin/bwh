<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-07-02 09:21
 * 功能说明: 续借还款控制器
 */
 namespace Admin\Controller\Loans;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class XjapplylistController extends BaseController{

     const T_TABLE='loans_xjapplylist';
     const T_APPLYLIST='loans_applylist';
     const T_MEMINFO='mem_info';
     const T_ADMIN='sys_administrator';

     public function _initialize(){
        parent::_initialize();
        $this->PayType = array(0=>"未付款",1=>"微信",2=>"支付宝",3=>"银联",4=>"代付");
        $this->PayStatus = array(0=>'待支付',1=>'已支付');
        $this->Status = array(0=>'待审核',1=>'审核成功',2=>'审核失败');
     }

     public function index(){
         $this->assign(array(
            'PayType'=>$this->PayType,
            'Status'=>$this->Status,
            'PayStatus'=>$this->PayStatus,
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
             $sort='Status asc,XjTime desc';
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
         $PayStatus=I('post.PayStatus',-5,'int');
         if($PayStatus!=-5){
            $where['PayStatus']=$PayStatus;
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
         $PayType=I('post.PayType',-5,'int');
         if($PayType!=-5){
            $where['PayType']=$PayType;
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
        $xjStartTime=I('post.xjStartTime');  //按时间查询
        $xjEndTime=I('post.xjEndTime');
        $ToStartTime=$xjStartTime;
        $ToEndTime=date('Y-m-d',strtotime($xjEndTime."+1 day"));
        if($xjStartTime!=null){
            if($xjEndTime!=null){
                //有开始时间和结束时间
                $where['XjTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['XjTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($xjEndTime!=null){
                $where['XjTime']=array('elt',$ToEndTime);
            }
        }
        //不是管理员的话，只能看到自己的单子
         if($_SESSION['AdminInfo']['RoleID']!='2'){
            $selwhere=array();
            if($_SESSION['AdminInfo']['RoleID']=='10'){
                //催收专员
                $selwhere['CsadminID']=array('eq',$_SESSION['AdminInfo']['AdminID']);
            }elseif($_SESSION['AdminInfo']['RoleID']=='7'){
                //客服专员
                $selwhere['SqAdminID']=array('eq',$_SESSION['AdminInfo']['AdminID']);
            }elseif($_SESSION['AdminInfo']['RoleID']=='8'){
                //放款专员
                $selwhere['FKadminID']=array('eq',$_SESSION['AdminInfo']['AdminID']);
            }
            $selwhere['IsDel']=array('eq','0');
            $selwhere['LoanStatus']=array('in',array('2','3'));

            $memidArr='';
            $memidArr=M('loans_applylist')->field('ID')->where($selwhere)->select();
            if($memidArr){
                $memids=array();
                foreach($memidArr as $k=>$v){
                    $memids[]=$v['ID'];
                }
                $where['ApplyID']=array('in',$memids);
            }else{
                $where['ApplyID']=array('eq','0');
            }

         }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,ServiceCost,TotalMoney,TradeRemark,XjTime,PayStatus,PayType,Accounts,Status,AdminID,ShTime,LoanNo,OrderSn';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         $PayTypeArr = $this->PayType;
         $PayStatusArr = $this->PayStatus;
         $StatusArr = $this->Status;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['AdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['AdminID']),'TrueName');

                 #$val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');

                 $val['PayType']=$PayTypeArr[$val['PayType']];
                 if($val['PayStatus']==0){
                     $val['PayStatus']='<span style="color:red;">'.$PayStatusArr[$val['PayStatus']].'</span>';
                 }else{
                     $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                 }
                 $val['Status']=$StatusArr[$val['Status']];
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }
    //审核
    public function aduit(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->field('ID,Status')->where(array("ID"=>$id))->find();
        $this->assign(array(
            "res"=>$res,
            ));
        $this->display();
    }
    //审核信息提交处理
    public function aduitsave(){
        $ID=I('post.ID','');
        $Status=I('post.Status','0');
        $Remark=I('post.Remark','');
        //审核校验
        $xjinfos=M(self::T_TABLE)->where(array("ID"=>$ID))->find();
        if(!$xjinfos){
            $this->ajaxReturn(0,'很抱歉，无此申请记录！');
        }
        if($xjinfos['Status']!='0'){
            $this->ajaxReturn(0,'很抱歉，只能审核待审核的申请！');
        }
        if($Status=='2'){
            if(!$Remark){
                $this->ajaxReturn(0,'很抱歉，审核失败必须写明失败原因！');
            }
        }
        if($Status=='0'){
            $this->ajaxReturn(0,'很抱歉，此记录已经是待审核记录了！');
        }
        $applyinfos=M(self::T_APPLYLIST)->where(array('ID'=>$xjinfos['ApplyID'],'LoanStatus'=>'2','Status'=>'1','IsDel'=>'0'))->find();
        if(!$applyinfos){
            $this->ajaxReturn(0,'很抱歉，未查询到借款申请订单！');
        }

        $sdata=array();//修改的数据
        if($Status=='1'){
            //成功
            $sdata['PayStatus']='1';
        }
        $sdata['AdminID']=$_SESSION['AdminInfo']['AdminID'];
        $sdata['ShTime']=date('Y-m-d H:i:s');
        $sdata['Status']=$Status;
        $sdata['Remark']=$Remark;
        $sdata['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
        $sdata['UpdateTime']=date('Y-m-d H:i:s');

        $model=M();
        $model->startTrans();

        $result=$model->table('xb_loans_xjapplylist')->where(array("ID"=>$ID))->save($sdata);
        if($result){
            //如果是审核成功的，则就更新 xb_loans_applylist 为已完成 T_APPLYLIST
            //并且在重新生成一条续借记录
            if($Status=='1'){
                $applydata=array();
                $applydata['LoanStatus']='3';
                $applyrest=$model->table('xb_loans_applylist')->where(array('ID'=>$xjinfos['ApplyID'],'LoanStatus'=>'2','Status'=>'1','IsDel'=>'0'))->save($applydata);
                if($applyrest){
                    //重新添加一条借款记录
                    $YyFkTime2=strtotime($applyinfos['YyFkTime'])+($xjinfos['LoanDay']-1)*86400;
                    $newdata=array(
                        'UserID'=>$applyinfos['UserID'],
                        'ApplyTime'=>date('Y-m-d H:i:s'),
                        'OrderSn'=>date('ymd').rand(1,9).date('His').rand(111,999),
                        'LoanNo'=>$applyinfos['LoanNo'],
//                        'ApplyMoney'=>$applyinfos['ApplyMoney'],
                        'ApplyMoney'=>'',
                        'AdoptMoney'=>$applyinfos['AdoptMoney'],
                        'FJMoney'=>$applyinfos['FJMoney'],
                        'Interest'=>$applyinfos['Interest'],
                        'ApplyDay'=>$xjinfos['LoanDay'],
                        'ProductID'=>$applyinfos['ProductID'],
                        'CouponID'=>$applyinfos['CouponID'],
                        'CoMoney'=>$applyinfos['CoMoney'],
//                        'OpenM'=>$applyinfos['OpenM'],
                        'OpenM'=>'',
                        'xjfee'=>$xjinfos['ServiceCost'],
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
                        'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                        'UpdateTime'=>date('Y-m-d H:i:s'),
                        );
                    $model->table('xb_loans_applylist')->add($newdata);
                }else{
                    $model->rollback();
                    $this->ajaxReturn(0,'很抱歉，借款记录修改失败！');
                }
            }
            if($Status=='1'){
                //续借成功
                $msgcont='尊敬的会员，您的申请单号：'.$applyinfos['LoanNo'].'，支付审核成功!';
                $mobile=M('mem_info')->where(array('ID'=>$applyinfos['UserID']))->getField('Mobile');
                send_message($mobile,$msgcont);//发送短信消息
                send_mem_notics($applyinfos['UserID'],$msgcont);//发送站内通知消息
            }elseif($Status=='2'){
                //续借失败
                $msgcont='尊敬的会员，您的申请单号：'.$applyinfos['LoanNo'].'，支付审核失败!';
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
        if($infos['AdminID']){
            $infos['AdminID']=M(self::T_ADMIN)->where(array('ID'=>$infos['AdminID']))->getField('TrueName');
        }
        //申请详情
        $applyinfos=M(self::T_APPLYLIST)->where(array('ID'=>$infos['ApplyID']))->find();
        $PayTypeArr = $this->PayType;
        $PayStatusArr = $this->PayStatus;
        $StatusArr = $this->Status;
        $infos['PayType']=$PayTypeArr[$infos['PayType']];
        $infos['PayStatus']=$PayStatusArr[$infos['PayStatus']];
        $infos['Status']=$StatusArr[$infos['Status']];
        $this->assign(array(
            'infos'=>$infos,
            'applyinfos'=>$applyinfos,
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
         $PayStatus=I('post.PayStatus',-5,'int');
         if($PayStatus!=-5){
            $where['PayStatus']=$PayStatus;
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
         $PayType=I('post.PayType',-5,'int');
         if($PayType!=-5){
            $where['PayType']=$PayType;
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
        $xjStartTime=I('post.xjStartTime');  //按时间查询
        $xjEndTime=I('post.xjEndTime');
        $ToStartTime=$xjStartTime;
        $ToEndTime=date('Y-m-d',strtotime($xjEndTime."+1 day"));
        if($xjStartTime!=null){
            if($xjEndTime!=null){
                //有开始时间和结束时间
                $where['XjTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['XjTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($xjEndTime!=null){
                $where['XjTime']=array('elt',$ToEndTime);
            }
        }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,ServiceCost,TotalMoney,TradeRemark,XjTime,PayStatus,PayType,Accounts,Status,AdminID,ShTime,LoanNo,OrderSn';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array['rows'] =M(self::T_TABLE)->where($where)->order($sort)->select();

         $PayTypeArr = $this->PayType;
         $PayStatusArr = $this->PayStatus;
         $StatusArr = $this->Status;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['AdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['AdminID']),'TrueName');
                 $val['PayType']=$PayTypeArr[$val['PayType']];
                 $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                 $val['Status']=$StatusArr[$val['Status']];
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }

         //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>会员账号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>手机号码</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>续借服务费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付总金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>交易备注</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>续借时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付方式</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>收款账号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核人</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>订单号</td>
            </tr>';

        foreach($result['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['MemAccount'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Mobile'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['ServiceCost'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TotalMoney'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TradeRemark'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['XjTime'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['PayStatus'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['PayType'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Accounts'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Status'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['AdminID'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['ShTime'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['LoanNo'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OrderSn'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'续借记录列表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        ob_end_clean();//清除缓存区的内容
        header("Content-type: application/vnd.ms-excel; charset=GBK");
        //header('Content-Type:text/html;charset=utf-8');
        header("Content-Disposition: attachment; filename=$str_filename.xls");
        echo $html;
        exit;
    }

 }