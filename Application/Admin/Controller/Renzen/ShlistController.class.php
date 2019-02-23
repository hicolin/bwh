<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-06-28 09:21
 * 功能说明: 认证审核记录控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class ShlistController extends BaseController{

     const T_TABLE='renzen_shlist';
     const T_ADMIN='sys_administrator';
     const T_MEMINFO='mem_info';

     public function index(){
         //认证类型
         $RenzenCodes=C("help.RenzenCodes");
         $this->assign('RenzenCodes',$RenzenCodes);
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
         $MemAccount=I('post.MemAccount','');
         if($MemAccount){
            $MemIDS=M(self::T_MEMINFO)->where(array('MemAccount'=>array('like','%'.$MemAccount.'%')))->field('ID')->select();
            $ids=array_column($MemIDS, 'ID');
            //根据ID查询会员
            if($ids){
                $where['UserID']=array('in',$ids);
            }else{
                $where['UserID']=null;
            }
        }
         $OperatorID=I('post.OperatorID','');
         if($OperatorID){
            $MemIDS=M(self::T_ADMIN)->where(array('TrueName'=>array('like','%'.$OperatorID.'%')))->field('ID')->select();
            $ids=array_column($MemIDS,'ID');
            //根据ID查询会员
            if($ids){
                $where['OperatorID']=array('in',$ids);
            }else{
                $where['OperatorID']=null;
            }
        }

         $Codes=I('post.Codes','');
         if($Codes){
            $where['Codes']=$Codes;
         }
         $where['IsDel']=0;
         //查询的数据表字段名
         $col='';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $memArr='';
                 $memArr=M(self::T_MEMINFO)->field('MemAccount,TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['MemAccount']=$memArr['MemAccount'];
                 $val['UserID']=$memArr['TrueName'];
                 $val['Mobile']=$memArr['Mobile'];
                 $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }

     /**
      * 逻辑删除
      */
     public function Del(){
         //获取要删除的记录的id(单条或者多条)
         $ids=I('request.ID','','trim');
         $idArr=explode(',',$ids);
         $result=M(self::T_TABLE)->where(array('ID'=>array('in',$idArr)))->setField('IsDel',1);
         if($result){
             $this->ajaxReturn(1,'删除成功');
         }else{
             $this->ajaxReturn(0,'删除失败');
         }
     }
 }