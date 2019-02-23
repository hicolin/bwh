<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：马凯
 * 修改时间：2017-04-18
 * 功能说明：分类管理
 */
namespace Admin\Controller\Api;
use Admin\Controller\System\BaseController;
class CategoriesController extends BaseController {

	const T_TABLE = 'api_column';
	const T_Api = 'api';

	public function index() {
		$this->display();
	}

	/**
	 * 分类列表数据获取、检测、显示 （js插件处理）
	 * @access   public
	 * @return   object    返回json数据
	 */
	public function DataList(){
		//插件排序
		$sort=I("post.sort",'','trim');
		$order=I("post.order",'','trim');
		//排序
		if($sort && $order){
			$sort_order=$sort." ".$order;
		}else{
			$sort_order='sort asc';
		}
		//调用函数处理分类数据
		$arr = apimenu(self::T_TABLE,'ParentID=0',$sort_order);
		$this->ajaxReturn($arr);
	}

	public function add(){
		//接受父级数据
		$json = $_POST['Add'];
		$res = json_decode($json,true);
		//获取分类数据
		$arr=has_children_cate(self::T_TABLE,'ParentID=0','sort asc');
		if($arr==false){
			$arr = $res;
			$this->ajaxReturn($arr);
		}
		//把父类插入数据头部
		foreach ($res as $k => $v) {
			array_unshift($arr,$v);
		}
		$this->ajaxReturn($arr);
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
		$ParentID=I("request.ParentID",0,'intval');
		$Sort=I("request.Sort",0,'intval');
		$Ststus=I("request.Ststus",0,'intval');
		$id=I("request.ID",0,'intval');

		//校验参数
		if(!$Name){
			$this->ajaxReturn(0,"分类名称不允许为空");
		}

		if($id){//修改验证

			if(M(self::T_TABLE)->where("Name='%s' and ID!=$id and ParentID=$ParentID",$Name)->find()){
				$this->ajaxReturn(0,"分类名称重复");
			}
		}else{
			if(M(self::T_TABLE)->where("Name='%s' and ParentID=$ParentID",$Name)->find()){
				$this->ajaxReturn(0,"分类名称重复");
			}
		}
		//组装数组
		$data=array();
		$data['Name']=$Name;

		$data['ParentID']=$ParentID;

		if($Sort){
			$data['Sort'] = $Sort;
		}
		$data['Ststus']=$Ststus;
		$data['UpdateTime']=date('Y-m-d H:i:s',time());
		$data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
		//保存数据
		if($id){    //修改
			if(!M(self::T_TABLE)->where("ID=".$id)->save($data)){
				$this->ajaxReturn(0,"分类数据保存失败");
			}
		}else{		//添加
			if(!M(self::T_TABLE)->add($data)){
				$this->ajaxReturn(0,"分类数据添加失败");
			}
		}
		$this->ajaxReturn(1,"保存成功");
	}

	public function edit(){
		$id=I("request.ID",0,'intval');
		if($id){
			$this->assign('id',$id);
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
			if(!$row){
				$this->error("没有该条数据");
			}
			if($row['ParentID'] == 0){
				$row['ParentID'] = "父类菜单";
			}else{
				$info = $mod->where(' ID='.$row['ParentID'])->find();
				if($info){
					$row['ParentID'] == $info['Name'];
				}
			}
			$this->ajaxReturn($row);
		}
	}

	/**
	 * 数据删除处理 单条或多条
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
	public function del(){
		//实例化分类表
		$mod = M(self::T_TABLE);
		//获取删除数据id (单条或数组)
		$id_str = I("post.ID",0,'intval');
		$ParentID = $id_str;
		if(!$ParentID){
			$this->ajaxReturn(0,"请选择要删除的分类");
		}

		$info = $mod->where('ParentID='.$ParentID)->find();
		if($info){
			$this->ajaxReturn(0,"父级分类下有子分类,请先删除子分类！");
		}
		$info = M(self::T_Api)->where('Cid='.$ParentID)->find();
		if($info){
			$this->ajaxReturn(0,"分类下有Api接口,请先删除！");
		}

        $res = $mod->where(array('ID'=>$id_str))->delete();
		if($res){
			$this->ajaxReturn(1,"删除数据成功！");
		}else{
			$this->ajaxReturn(0,"删除数据时出错！");
		}
	}
}

?>
