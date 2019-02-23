<?php
/**
 * 
 *
 * 作    者: 王少武
 * 修改时间: 2018/7/4 15:40
 * 功能说明: 首页控制器
 */
 
 namespace Admin\Controller\Bank;

 use Admin\Controller\System\BaseController;
 use XBCommon\XBQuery;

 class InfoController extends BaseController{
     const T_TABLE   ='mem_bank';
     const T_TYPE    ='mem_banktype';
     const T_ADMIN   ='sys_administrator';
     const T_MEMINFO ='mem_info';

     public function index(){
         $this->display();
     }

     /**
      * 分类列表数据获取、检测、显示 （js插件处理）
      * @access   public
      * @return   object    返回json数据
      */
     public function DataList(){
         //插件排序
         $page=I('post.page',1,'intval');
         $rows=I('post.rows',20,'intval');
         $sort=I("post.sort",'','trim');
         $order=I("post.order",'','trim');
         $BankCode=I("post.BankCode","","trim");
         $AuditStatus=I("post.AuditStatus",-5,"intval");
         //排序
         if($sort && $order){
             $sort=$sort." ".$order;
         }else{
             $sort='ID DESC';
         }
         if($BankCode){
             $where['BankCode']=array("like",'%'.$BankCode."%");
         }
         if($AuditStatus!=-5){
             $where['AuditStatus']=array("eq",$AuditStatus);
         }

         $col='';
         //获取最原始的数据列表
         $query=new XBQuery();
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);
         $AuditArr=array("审核中","审核通过","审核未通过");
         //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val){
                 $val['OperaterID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperaterID']),'UserName');
                 $val['UserID']=$query->GetValue(self::T_MEMINFO,array('ID'=>(int)$val['UserID']),'UserName');
                 $val['BankType']=$query->GetValue(self::T_TYPE,array('ID'=>(int)$val['BankType']),'Name');
                 $val['AuditStatus']=$AuditArr[$val['AuditStatus']];
                 $result['rows'][]=$val;
             }
             $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }

//审核
     public function aduit(){
         $id=I('get.ID',0,'intval');
         $info=M(self::T_TABLE)->alias('a')
             ->field('a.*,b.UserName,c.Name')
             ->join('left join xb_mem_info b on b.ID=a.UserID')
             ->join('left join xb_mem_banktype c on c.ID=a.BankType')
             ->where(array("a.ID"=>$id))->find();
         $this->assign("info",$info);
         $this->display();
     }
     //审核信息提交处理
     public function aduitsave(){
         $ID=I('post.ID',0,'intval');
         $AuditStatus=I('post.AuditStatus',0,'intval');
         $Intro=I('post.Intro','');
         if($ID){
             $info=M(self::T_TABLE)->find($ID);
             if($AuditStatus!=$info['AuditStatus']){
                 if($info['AuditStatus']==1){
                     $this->ajaxReturn(0,"审核已通过，不能更改");
                 }
                 if($info['AuditStatus']==2 && $AuditStatus==0){
                    $this->ajaxReturn(0,"已有审核结果，不能更改至审核中的状态了");
                 }
             }
         }
         //修改xb_renzen_cards
         $updata=array(
             'AuditStatus'=>$AuditStatus,
             'OperaterID'=>$_SESSION['AdminInfo']['AdminID'],
             'AuditTime'=>date("Y-m-d H:i:s"),
         );
         $rest=M(self::T_TABLE)->where(array('ID'=>$ID))->save($updata);
         if($rest){
             $this->ajaxReturn(1,'恭喜您，审核操作成功！');
         }else{
             $this->ajaxReturn(0,'很抱歉，审核操作失败！');
         }
     }
 }
 