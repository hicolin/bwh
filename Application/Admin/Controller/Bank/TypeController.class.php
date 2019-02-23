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

 class TypeController extends BaseController{
     const T_TABLE ='mem_banktype';
     const T_TYPE  ='mem_bank';
     const T_ADMIN ='sys_administrator';

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
         $name=I("post.Name","","trim");
         //排序
         if($sort && $order){
             $sort=$sort." ".$order;
         }else{
             $sort='Sort ASC,ID DESC';
         }
         $where['IsDel']=0;
         if($name){
             $where['Name']=array("like",'%'.$name."%");
         }
         $state=I("post.Status",-5,"intval");
         if($state!=-5){
             $where['Status']=$state;
         }
         $where['IsDel']=0;
         $col='';
         //获取最原始的数据列表
         $query=new XBQuery();
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);
         //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val){
                 $val['OperaterID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperaterID']),'UserName');
                 $result['rows'][]=$val;
             }
             $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }


     public function edit(){
         $id=I("request.ID",0,'intval');
         if($id){
             $this->assign('ID',$id);
         }
         $this->display();

     }

     /**
      * 获取分类的编辑数据
      * @access   public
      * @param    string  $id   获取id组成的字符串
      * @return  返回处理结果
      */
     public function shows(){
         $mod = M(self::T_TABLE);
         $id=I("request.ID",0,'intval');
         if($id){					//获取该条数据
             $row=$mod->find($id);
             $this->ajaxReturn($row);
         }
     }


     /**
      * 分类保存
      * @access   public
      * @param    string  $id   获取id组成的字符串
      * @return  返回处理结果
      */
     public function Save(){
         //接受数据
         $Name=I("request.Name");
         $Status=I("request.Status",0,'intval');
         $Intro=I("request.Intro",'','trim');
         $sort=I("request.Sort",999,"intval");
         $id=I("request.ID",0,'intval');
         //校验参数
         if(!$Name){
             $this->ajaxReturn(0,"银行名称不允许为空");
         }

         $res=M(self::T_TABLE)->where(array("Name"=>$Name,"ID"=>array("neq",$id),'IsDel'=>0))->find();
         if($res){
             $this->ajaxReturn(0,"银行名称不允许重复");
         }
         //组装数组
         $data=array();
         $data['Name']=$Name;
         $data['Intro']=$Intro;
         $data['Status']=$Status;
         $data['Sort']=$sort;
         $data['UpdateTime']=date('Y-m-d H:i:s');
         $data['OperaterID']=$_SESSION['AdminInfo']['AdminID'];
         //保存数据
         if($id){    //修改
             if(!M(self::T_TABLE)->where("ID=".$id)->save($data)){
                 $this->ajaxReturn(0,"保存失败");
             }
         }else{		//添加
             if(!M(self::T_TABLE)->add($data)){
                 $this->ajaxReturn(0,"添加失败");
             }
         }
         $this->ajaxReturn(1,"保存成功");
     }


     /**
      * 数据删除处理 单条或多条
      * @access   public
      * @param    string  $id   获取id组成的字符串
      * @return  返回处理结果
      */
     public function Del()
     {
         $mod = D(self::T_TABLE);
         //获取删除数据id (单条或数组)
         $ids = I("post.ID", '', 'trim');
         $arr=explode(',',$ids);  //转化为一维数组

         //开始执行逻辑删除
         $where['ID']=array('in',$arr);
         $data['IsDel']=1;
         $res=$mod->where($where)->save($data);  //逻辑删除
         if ($res) {
             $this->ajaxReturn(true, "用户删除数据成功！");
         } else {
             $this->ajaxReturn(false, "用户删除数据时出错！");
         }
     }
 }