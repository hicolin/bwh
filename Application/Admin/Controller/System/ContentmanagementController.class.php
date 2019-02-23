<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：马凯
 * 修改时间：2017-04-28
 * 功能说明：内容管理
 */
namespace Admin\Controller\System;
use Think\Controller;
use XBCommon;
use XBCommon\XBCache;

class ContentmanagementController extends BaseController {
	const T_TABLE = 'sys_contentmanagement';
	const T_CONTENTCATEGORIES = 'sys_contentcategories';
	const T_CHANGE  = 'sys_content_change';
	const Administrator = 'sys_administrator';
	public function index() {
		$this->display();
	}

	/**
	 * 按钮列表数据获取、检测、显示 （js插件处理）
	 * @access   public
	 * @return   object    返回json数据
	 */
	public function DataList(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }

        //选择分类
        $CategoriesID = I('request.CategoriesID');
		if(!empty($CategoriesID)){
			$where['CategoriesID'] = $CategoriesID;
		}else{
		    $CIDS=M(self::T_CONTENTCATEGORIES)->where(array('ColumnType'=>1))->field('ID')->select();
            $arr=array();
            foreach ($CIDS as $val){
                $arr[]=$val['ID'];
            }
            $where['CategoriesID']=array('in',$arr);
        }

        //标题搜索
        $Title = I('post.Title');
		if(!empty($Title)){
			$where['Title'] = array('like','%'.$Title.'%');
		}

        //是否推荐
        $IsTui = I('post.IsTui');
		if($IsTui <> -5 && $IsTui <>''){
			$where['IsTui'] = intval($IsTui);
		}

        //是否发布
        $IsPublish = I('post.IsPublish');
		if($IsPublish <> -5 && $IsPublish <>''){
			$where['IsPublish'] = intval($IsPublish);
		}

		//时间段搜索
        $AddDateTime1 = I('post.AddDateTime1');
        $AddDateTime2 = I('post.AddDateTime2');
		if($AddDateTime1 <> '' && $AddDateTime2 <> ''){
			$where['AddTime'] = array('between',array($AddDateTime1,$AddDateTime2));
		}

        //查询的列名
        $col='ID,Title,CategoriesID,Soruce,Author,ViewCounk,IsPublish,UpdateTime,IsTui,Sort,AddUserID,UrlType';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['CategoriesID']=$query->GetValue(self::T_CONTENTCATEGORIES,array('ID'=>(int)$val['CategoriesID']),'Name');
                $val['AddUserName']=$query->GetValue(self::Administrator,array('ID'=>(int)$val['AddUserID']),'UserName');
                if($val['UrlType']=='1'){
                    $val['UrlType']='<span style="color:red;">外部链接</span>';
                }elseif($val['UrlType']=='2'){
                    $val['UrlType']='站内详情';
                }
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
	}
	//侧边栏列表
	public function DataTree(){
		//实例化模型
        $arr = catemenu1(self::T_CONTENTCATEGORIES,'ParentID=0');
		echo json_encode($arr);
	}
	//查询条件列表
	public function Add(){
		$arr=has_children_cate(self::T_CONTENTCATEGORIES,'ParentID=0','sort asc');
		if($arr==false){
			$this->error("获取数据失败");
		}
		echo json_encode($arr);
	}


	/**
     * 保存编辑数据 添加数据
     * @access   public
     * @param    string  $id   获取id组成的字符串
     * @return  返回处理结果
     */
    public function Save()
    {
        $mod = M(self::T_TABLE);
        $id = I("request.ID", 0, 'intval');//获取数据ID
        IF (IS_POST) {
            $data = $mod->create();
            if (false === $data) {
                $this->ajaxReturn(0, $mod->getError());
            }
            if ($id) {
                $data['UpdateTime'] = date('Y-m-d H:i:s', time());
                $data['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
                $res = $mod->where(array('ID'=>$id))->save($data);
                if (in_array($data['ID'],array(29,30))){
                    $add=array(
                        "ContentID"=>$data['ID'],
                        "Title"=>$data['Title'],
                        "Contents"=>$data['Contents'],
                        "OperatorID"=>session('AdminInfo')['AdminID'],
                        "UpdateTime"=>$data['UpdateTime'],
                    );
                    M(self::T_CHANGE)->add($add);
                }
                if ($res === false) {
                    $this->ajaxReturn(0, '修改失败');
                } else {
                    $this->ajaxReturn(1, '修改成功');
                }
            } else {
                //添加数据
                $data['AddTime'] = date('Y-m-d H:i:s', time());
                $data['AddUserID'] = $_SESSION['AdminInfo']['AdminID'];
                if ($res = $mod->add($data)) {
                    $this->ajaxReturn(1, '保存成功');
                } else {
                    $this->ajaxReturn(0, '保存失败');
                }
            }
        }
    }

	/**
	 * @功能说明：显示编辑内容
	 * @return [type]                           [description]
	 */
	public function shows(){
		$id=I("request.ID");
        if(!empty($id)){
            $centerModel=D(self::T_TABLE);
            $center_rec=$centerModel->where("ID=".$id)->find();
            $center_rec['Contents']=htmlspecialchars_decode($center_rec['Contents']);
            $this->ajaxReturn($center_rec);
        }else{
            //没有查询到内容
            $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
        }

	}

	/**
	 * 数据删除处理 单条或多条
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
	public function del(){
		$mod = M(self::T_TABLE);
		$id=I("request.ID",0,'intval');
		//$source_data=$mod->where("ID in($id)")->select();
		//$info = M(self::T_TABLE)->where('ID='.$id)->find();
        $res = $mod->delete($id);
		if($res){
			//admin_opreat_log($source_data,'',$parameter="ID:$id_str",'删除');
			$this->ajaxReturn(1,"删除数据成功！");
		}else{
			$this->ajaxReturn(0,"删除数据时出错！");
		}
	}

	/**
	 * 编辑页面数据获取
	 * @access   public
	 * @param    intval  $id   编辑该条数据的id 用于打开相应页面并传递id值
	 * @param    intval  $getid   用于获取该条数据的id
	 * @return  返回处理结果
	 */
	public function edit(){
	    $CategoriesID=I('request.CategoriesID',0,'intval');
        if(empty($CategoriesID)){
            $id=I("request.ID",0,'intval');
            $find = M(self::T_TABLE)->where(array('ID'=>$id))->field('UrlType')->find();
            $this->assign("id",$id);
        }else{
            $id=M(self::T_TABLE)->where(array('CategoriesID'=>$CategoriesID))->getField('ID');
            $find = array('UrlType'=>1);
            $this->assign("id",$id);
        }
        $this->assign("find",$find);
		$this->display();
	}


    public function single(){
        $CategoriesID = I('request.CategoriesID',0,'intval');

        $find = M(self::T_TABLE)->where(array('CategoriesID'=>$CategoriesID))->find();
        $this->assign("find",$find);

        $cate = M(self::T_CONTENTCATEGORIES)->where(array('ID'=>$CategoriesID))->find();
        $this->assign("cate",$cate);

        $this->assign("CategoriesID",$CategoriesID);

        $this->display();
    }
}
