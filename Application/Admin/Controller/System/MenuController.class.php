<?php
/**
 * 版权所有：合肥讯邦网络科技有限公司 [http://www.ahxb.cc]
 * 公司电话：0551-63668080
 * 作    者：高伟
 * 修改时间：2017-04-18
 * 继承版本：1.1
 * 功能说明：菜单管理
 */
namespace Admin\Controller\System;
use XBCommon;

class MenuController extends BaseController {

	const T_TABLE = 'sys_menu';
	const T_MENUBUTTON = 'sys_menubutton';



	//菜单管理列表
    public function index(){
		$this->display();
    }
	/**
	 * 菜单列表数据获取、检测、显示 （js插件处理）
	 * @access   public
	 * @param    string  $sort  排序字段
	 * @param    string  $order  排序方式
	 * @return   object    返回json数据
	 */
	public function DataList(){
		//接受参数
		G('begin');
		$sort=I("post.sort",'','trim');
		$order=I("post.order",'','trim');
		//排序
		if($sort && $order){
			$sort_order=$sort." ".$order;
		}else{
			$sort_order='sort asc';
		}
		//实例化模型
		$model=D(self::T_TABLE);
		$where=array();
		$where['ParentID']=0;
		//根据条件获取相应数据  调用model的数据处理

		$result_array=$this->has_children(self::T_TABLE,$where,$sort_order);
		G("end");


		$this->ajaxReturn($result_array);
    }

	/**
	 * 生成菜单管理的tree结构数据并在下拉框显示数据
	 * @param array $arr  返回的数据
	 */
	public function MenuTree(){
		$arr=has_children_menu(self::T_TABLE,'ParentID=0','ID asc');
		array_push($arr,array('id'=>0, 'text'=>'父级菜单'));
		$arr=array_values($arr);
		sort($arr);
		//print_r($arr);die;
		if($arr==false){
			$this->error("获取数据失败");
		}
		$this->ajaxReturn($arr);
	}
	/**
	 * 编辑页面数据获取
	 * @access   public
	 * @param    intval  $id   编辑该条数据的id 用于打开相应页面并传递id值
	 * @param    intval  $getid   用于获取该条数据的id
	 * @return  返回处理结果
	 */


	public function edit(){
		$id=I("request.ID",0,'intval');
        $this->assign("id",$id);
		//获取菜单的相应按钮信息
		$list=$this->get_opbuttom_info($id);
		//print_r($list);die;
		$openmode_name=array("有交互窗口","无交互的窗口","无窗口打开","带确认的无窗口","打开新标签","地址栏打开",'表单提交打开','打开后台返回链接');
		$this->assign("openmode_name",$openmode_name);
		$this->assign("list",$list);
		$this->display();
	}

	/**
	 * 保存编辑数据 添加数据
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
	public function save(){

		//接受参数
		$mod = D(self::T_TABLE);
		$id=I("request.ID",0,'intval');//获取数据ID
		$ButtonID = I('post.ButtonID');
		$ButtonSaveURL = I('post.ButtonSaveURL');
		$ButtonURL = I('post.ButtonURL');
		$Height = I('post.Height');
		$JsFunction = I('post.JsFunction');
		$OpenMode = I('post.OpenMode');
		$Width = I('post.Width');
		//$MenuButtonID=I("request.MenuButtonID");
		$count = count($ButtonID);
		$Name=I("request.Name",'','trim');
		$Url=I("request.Url",'','trim');
		$Icon=I("request.Icon",'','trim');
		$ParentID=I("request.ParentID",0,'intval');

		//校验数据
		if(!$Icon){
			$this->ajaxReturn(0, '菜单图标没有选择！');
		}
		//检查参数是否重名
		if($err_res=$this->check_param($Name,$Url,$ParentID,$id)){
			$this->ajaxReturn(0,$err_res);
		}
		if(IS_POST){
			//启用事务
			$tranDb = M("");
			$tranDb->startTrans();

			if (false === $data = $mod->create()) {
				$this->ajaxReturn(0, $mod->getError());
			}
			if ($id) {
				M(self::T_MENUBUTTON)->where("MenuID=%d",$id)->delete();

				$res = $mod->where(' ID='.$id)->save($data);
				if ($res===false) {
					$tranDb->rollback();//回滚
					$this->ajaxReturn(0, '菜单修改失败');
				}
			} else {                                        //添加数据
				if (!$id=$mod->add($data)) {
					$tranDb->rollback();//回滚
					$this->ajaxReturn(0, '菜单添加失败或修改失败');
				}
			}

			if(!empty($ButtonID)) {
				for ($i = 0; $i < $count; $i++) {
					$datas[] = array(
						'MenuID' => $id,
						'ButtonID' => $ButtonID[$i],
						'ButtonURL' => $ButtonURL[$i],
						'ButtonSaveURL' => $ButtonSaveURL[$i],
						'Width' => $Width[$i],
						'Height' => $Height[$i],
						'OpenMode' => $OpenMode[$i],
						'IsFunction' => $JsFunction[$i],
						'UpdateTime' => date('Y-m-d H:i:s', time()),
					);
				}
				if(!$tianjia = M(self::T_MENUBUTTON)->addAll($datas)){
					$tranDb->rollback();//回滚
					$this->ajaxReturn(0, '菜单功能按钮保存失败');
				}
			}



			$tranDb->commit();//提交事务
            //菜单保存成功，更新菜单和权限按钮
            $cache=new XBCommon\CacheData();
            $cache->ClearMenu();
            $cache->LeftMenu();
			$this->ajaxReturn(1, '菜单保存成功');
		}

	}
	
	
	/**
	 * 编辑数据
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
	public function shows(){
		$mod = D(self::T_TABLE);
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
	 * 删除菜单 需要注意是否有子类
	 * @param  $id int   菜单ID
	 */
	public function del()
	{
		//启用事务
		$tranDb = M("");
		$tranDb->startTrans();
		$ID=I("request.ID");
		if(M(self::T_TABLE)->where("ParentID=$ID")->select()){
			$this->ajaxReturn(0,"请先删除此菜单的子类");
		}
		if(M(self::T_TABLE)->delete($ID)){
			M(self::T_MENUBUTTON)->where("MenuID=%d",$ID)->delete();
				
			$tranDb->commit();//提交事务
            //菜单删除成功，更新菜单和权限按钮
            $cache=new XBCommon\CacheData();
            $cache->ClearMenu();
			$this->ajaxReturn(1, '菜单删除成功');
		}else{
			$this->ajaxReturn(0, '菜单删除失败');
		}


	}

	/**
	 * 菜单管理树结构数组组装
	 * @param $table string 表名
	 * @param $where  string 条件
	 * @param $where  string 排序
	 * @return array|bool
	 */
	public function has_children($table,$where,$sort_order=''){
		//获取菜单表的第一层数据
		$arr=M($table)->where($where)->order($sort_order)->select();
		if($arr){
			//遍历第一层数据
			foreach($arr as $val){
				/*********获取相应安扭功能开始*******/
				$opreatB=M("sys_menubutton")->field("ButtonID")->where("MenuID=".$val['ID'])->select();
				$str='';
				if($opreatB){
					foreach($opreatB as $bution){
						//获取相应图标及名称
						$icon=M("sys_operationbutton")->where("ID='%d'",array($bution['ButtonID']))->find();
						if($icon['ID']==1){
							$str.=" <div class='Separator'></div>";//如果是分隔符另外处理
						}else{
							$str.=" <a href='javascript:void(0);' class='ToolBtn'><span class='".$icon['Icon']."'></span><b>".$icon['Name']."</b></a>&nbsp;";//组装功能
						}
					}
				}
				/*********获取相应安扭功能结束*******/
				//菜单状态
				if($val['Status']==1){
					$status_name="<span class='Green'>正常</span>";
				}else{
					$status_name="<span class='Red'>禁用</span>";
				}
				//循环处理树结构数据
				$two_arr[]=array(
						'ID' 	    => $val['ID'],
						'Name'    	=> $val['Name'],
						'ParentID' 	=> $val['ParentID'],
						'Url'		=> $val['Url'],
						'Sort' 	    => $val['Sort'],
						'Status' 	=> $status_name,
						'OperationButton' 	=> $str,
						'iconCls'				=>$val['Icon']	,
						'children'   =>$this->has_children($table,"ParentID=".$val['ID'],$sort_order)?$this->has_children($table,"ParentID=".$val['ID'],$sort_order):''
				);
			}
			return $two_arr;
		}else{
			return false;
		}
	}


	/**
	 * 根据菜单ID获取相应的按钮信息
	 * @param $id
	 * @return mixed
	 */
	public function get_opbuttom_info($id){
		$list =  M("sys_menubutton as m")->join('xb_sys_operationbutton as o ON  o.id = ButtonID')->field("m.ID,m.ButtonID,o.Name,o.Icon,m.Width,m.Height,m.OpenMode,m.ButtonURL,m.ButtonSaveURL,m.IsFunction")->where("MenuID=%d",$id)->select();
		return $list;
	}

	/**
	 * @param $Name   菜单名称
	 * @param $Url      菜单url
	 * @param $ParentID  父级参数
	 * @param $id  校验类型   空：添加  有值：修改
	 * @return   bool
	 */
	public function  check_param($Name,$Url,$ParentID=0,$id=0){
		if(!$Name){
			return   $err_res="菜单名称不能为空！";
		}
		if(!$Url){
			return   $err_res="菜单链接不能为空！";
		}
		if($id){//修改验证
			if(M("sys_menu")->where("Name='%s' and ID!=$id and ParentID=$ParentID",$Name)->find()){
				return   $err_res="菜单名称重复！";
			}

		}else{
			if(M("sys_menu")->where("Name='%s' and ParentID=$ParentID",$Name)->find()){
				return   $err_res="菜单名称重复！";
			}

		}

		return null;
	}



}