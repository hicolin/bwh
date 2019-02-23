<?php
/**
 * 修改时间: 2018-06-28 09:21
 * 功能说明: 认证参数控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class ParameterController extends BaseController{

     const T_TABLE='renzen_parameter';
     const T_ADMIN='sys_administrator';

     public function index(){
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
             $sort='Sort asc,ID desc';
         }

         //搜索条件
         $Name=I('post.Name');
         if($Name){
            $where['Name']=array('like','%'.$Name.'%');
         }
         $IsShow=I('post.IsShow',-5,'int');
         if($IsShow!=-5){
            $where['IsShow']=$IsShow;
         }
         $IsMust=I('post.IsMust',-5,'int');
         if($IsMust!=-5){
            $where['IsMust']=$IsMust;
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
                 if($val['IsShow']=='1'){
                    $val['IsShow']='<span style="color:green;">是</span>';
                 }else{
                    $val['IsShow']='<span style="color:red;">否</span>';
                 }
                 if($val['IsMust']=='1'){
                    $val['IsMust']='<span style="color:green;">是</span>';
                 }else{
                    $val['IsMust']='<span style="color:red;">否</span>';
                 }
                 $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'UserName');
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }


     /**
      * 编辑功能
      */
     public function edit(){

         $id=I('request.ID',0,'intval');
         if($id){
            $codename=M(self::T_TABLE)->where(array('ID'=>$id))->getField('Flag');
         }

         $this->assign(array(
            'ID'=>$id,
            'codename'=>$codename,
            ));
         $this->display();
     }

     /**
      * 查询详细信息
      */
     public function shows(){
         $id=I('request.ID',0,'intval');
         if($id){
             $tabList=M(self::T_TABLE)->where(array('ID'=>$id))->find();
             $this->ajaxReturn($tabList);
         }else{
            $this->ajaxReturn(array('tabList'=>false,'message'=>'没有该记录'));
         }
     }

     /**
      *保存数据
      */
     public function Save(){

         if(!IS_POST){
             $this->ajaxReturn(0,'数据提交方式不对');
         }
         //验证规则
         $rules=array(
             array('Name','require','名称必须填写'),
             array('ImgUrl','require','已认证图标必须上传'),
             array('ImgUrl1','require','未认证图标必须上传'),
         );

         //提交的表单数据进行规则验证,并创建数据对象
         $model=D(self::T_TABLE);
         $FormData=$model->validate($rules)->create();
         if(!$FormData){
              //验证不通过，返回保存失败的提示信息
             $this->ajaxReturn(0,$model->getError());
         }else{
             //创建新数组，用于存储保存的数据
             $data=array();
             //保存数据
             $data=$FormData;
             $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
             $data['UpdateTime']=date("Y-m-d H:i:s");
         }

         //更新或添加数据
         if($FormData['ID']>0){   //修改数据
             $saveResult=$model->where(array('ID'=>$FormData['ID']))->save($data);
             //返回保存的结果
             if($saveResult){
                 $this->ajaxReturn(1,'修改成功');
             }else{
                 $this->ajaxReturn(0,'修改失败');
             }
         }else{//添加数据(ID为空)

             $addResult=$model->add($data);
             if($addResult){
                $this->ajaxReturn(1,'添加成功');
             }else{
                 $this->ajaxReturn(1,'添加失败');
             }
         }
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