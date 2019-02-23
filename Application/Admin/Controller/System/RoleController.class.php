<?php
/**


 、刁洪强
 * @修改日期：  2017-05-20
 * @继承版本:   1.1
 * @功能说明：  角色管理控制类
 */
namespace Admin\Controller\System;
use XBCommon;
class RoleController extends BaseController {


    const T_TABLE = 'sys_role';
    const T_MENU = 'sys_menu';
    const T_ROLEMENU = 'sys_rolemenu';
    const T_ADVERTISING = 'sys_advertising';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';


	/**
	 * 角色组列表
	 */
	public function index(){
		$this->display();
	}


    /**
     * 显示角色列表数据
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

        $Name=I('post.Name');
        if($Name){$where['Name'] = array('like','%'.$Name.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        //内置角色
        if($_SESSION['AdminInfo']['RoleID'] != 1){
            $where['ID'] = array('neq',1);
        }

        $where['IsDel']=0;
        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['MenuID']=$query->GetValue(self::T_MENU,array('ID'=>(int)$val['MenuID']),'Name');
                $val['MenuNum']=M(self::T_ROLEMENU)->where(array('RoleID'=>$val['ID']))->count();
//                $val['AdvertisingID']=$query->GetValue(self::T_ADVERTISING,array('ID'=>(int)$val['AdvertisingID']),'Name');
                $val['Status']=$query->GetValue(self::T_DICTIONARY,array('DictType'=>'1','DictValue'=>$val['Status']),'DictName');
                $val['OperatorID']=$query->GetValue(self::T_ADMINISTRATOR,array('ID'=>(int)$val['OperatorID']),'UserName');
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }


    /**
     * 查询详细信息
     */
    public function shows(){
        $mod = D(self::T_TABLE);
        $id=I("request.ID",0,'intval');
        if($id){					//获取该条数据
            $row=$mod->find($id);
            if(!$row){
                $this->error("没有该条数据");
            }
            $this->ajaxReturn($row);
        }
    }



    /**
	 * 编辑数据
	 */
    public function Edit($ID=null){
        $ID=(int)$ID;
        $this->assign('ID',$ID);

        //获取要展开的菜单项
        $query=new XBCommon\XBQuery();
        $Menus=$query->GetList(self::T_MENU,array('Status'=>1,'IsDel'=>0,'ParentID'=>0),'ID,Name');
        $this->assign('Menus',$Menus);
        $this->display();
    }

	/**
	 * 保存编辑数据 添加数据
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Name','require','角色名称必须填写！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //判断展开菜单选择是否正确
                if($data['MenuID']==-5){
                    $this->ajaxReturn(false, '请选择默认展开菜单！');
                }
//                $MenuExists=M(self::T_MENU)->where(array('ID'=>$data['MenuID'],'Status'=>1,'IsDel'=>0))->count();
//                if($MenuExists<1){
//                    $this->ajaxReturn(false, '您选择的菜单项已不存在，请刷新后操作！');
//                }
                //验证通过，执行后续保存动作
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");

                //保存或更新数据
                if($data['ID']>0){
                    //判断角色名是否唯一，如果修改的时候，将角色名改名与其他角色名重名则不被允许！
                    $where['ID']=array('neq',$data['ID']);  //不等于当前要修改的ID
                    $where['Name']=$data['Name'];
                    $where['Status']=1;
                    $where['IsDel']=0;
                    $RoleExists=M(self::T_TABLE)->where($where)->count();
                    if($RoleExists>0){
                        $this->ajaxReturn(false, '角色名已存在！');
                    }
                    //无重名，则允许更新保存
                    $result=$model->where('ID='.$data['ID'])->save($data);
                    if($result>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    //判断角色名是否唯一，不允许重复
                    $RoleExists=M(self::T_TABLE)->where(array('Name'=>$data['Name'],'Status'=>1,'IsDel'=>0))->count();
                    if($RoleExists>0){
                        $this->ajaxReturn(false, '角色名已存在！');
                    }
                    //无重名，则允许新增记录
                    $result=$model->add($data);
                    if($result>0){
                        $this->ajaxReturn(1, '添加成功');
                    }else{
                        $this->ajaxReturn(0, '添加失败');
                    }
                }
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对');
        }
    }

	/**
	 * 数据删除处理 单条或多条
	 */
    public function Del()
    {
        $mod = D(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组
        //删除判断，特殊用户禁止删除，当前登陆者禁止删除
        foreach ($arr as $val){
            if($val==1){
                $this->ajaxReturn(false, "系统默认角色不允许被删除！");
            }
            if($val==$_SESSION['AdminInfo']['RoleID']){
                $this->ajaxReturn(false, "当前登陆者的角色不允许被删除！");exit;
            }
            $UserExists=M(self::T_ADMINISTRATOR)->where(array('RoleID'=>$val,'Status'=>1,'IsDel'=>0))->count();
            if($UserExists>0){
                $this->ajaxReturn(false, "此角色下存在未删除的用户，禁止删除！");
            }
        }
        //判断完成，开始执行逻辑删除
        $data['IsDel']=1;
        $where['ID']=array('in',$arr);
        $res=$mod->where($where)->save($data);  //逻辑删除
        if ($res) {
            $this->ajaxReturn(true, "角色删除成功！");
        } else {
            $this->ajaxReturn(false, "角色删除时出错！");
        }
    }

    /**
     * @功能说明：显示权限菜单
     */
    public function rolemenu(){

        //获取角色ID
        $RoleID=I("get.ID",0,'intval');

        $arr=$this->rolePermission(self::T_MENU,"ParentID=0","sort asc",$RoleID);

        $this->assign("menulist",$arr);
        $this->assign("roleID",$RoleID);
        $this->display();

    }


	/**
	 * @功能说明：角色权限变更
	 */
	public function rolemenusave(){
		//接收参数
		$post=I("request.MBID");
		$RoleID=I("request.ID",0,'intval');//角色ID

		if(!is_int($RoleID)){
			$this->ajaxReturn(0,"参数错误");
		}
		//校验
		if(!$post){
			$this->ajaxReturn(0,"参数错误");
		}
		//启用事务
		$tranDb = M("");
		$tranDb->startTrans();
		$oneID='';
		//修改之前先删除全部
		if(!M(self::T_ROLEMENU)->where("RoleID=%d",$RoleID)->delete()){
			$tranDb->rollback();//如果插入不成功，则回滚
		}
		//print_r($post);die;
		//循环处理post的数据

		foreach($post as $key=> $val){
			$menu_arr=explode(":",$val);
			$row=M(self::T_ROLEMENU)->where("RoleID=%d and MenuID=%d",$RoleID,intval($menu_arr[0]))->find();
			if(!$row){
				if(intval($oneID)!=intval($menu_arr[0])){ //组装一级菜单并添加
					$arr=array();
					$arr['RoleID']=$RoleID;
					$oneID=intval($menu_arr[0]);
					$arr['MenuID']=$oneID;
					$arr['ParentID']=$menu_arr[2];
					if(!M(self::T_ROLEMENU)->add($arr)){
						$tranDb->rollback();//如果插入不成功，则回滚
					}
				}
			}
			//获取相应二级菜单权限 数据  有就把buttonID拼接  没有就添加
			$row=M(self::T_ROLEMENU)->where("RoleID=%d and MenuID=%d",$RoleID,intval($menu_arr[1]))->find();
			if(!$row ){							//没有
				$arr1 = array();
				$arr1['RoleID'] = $RoleID;
				$arr1['ParentID'] = intval($menu_arr[0]);
				$arr1['MenuID'] = intval($menu_arr[1]);
				$arr1['ButtonID'] = '';
				if (!M(self::T_ROLEMENU)->add($arr1)) {
					$tranDb->rollback();//如果插入不成功，则回滚
				}
			}else{								//有
				$arr1 = array();
				$arr1['RoleID'] = $RoleID;
				$arr1['ID'] = $row['ID'];
				$arr1['ParentID'] = intval($menu_arr[0]);
				$arr1['MenuID'] = intval($menu_arr[1]);
				$arr1['ButtonID'] =$row['ButtonID'].",".intval($menu_arr[2]);
				if(!M(self::T_ROLEMENU)->where()->save($arr1)){
					$tranDb->rollback();//如果插入不成功，则回滚
				}
			}
		}
		$tranDb->commit();//提交事务
        //更改权限成功，更改菜单和权限按钮缓存

        A('System/RefreshCache')->deldir(DATA_PATH);
        common_package();

        $cache=new XBCommon\CacheData();
        $cache->ClearMenu();
		$this->ajaxReturn(1,"成功");
	}

    /**
     * 组装权限、分配展示信息
     * @param $table string 表名
     * @param $where  string 条件
     * @param $where  string 排序
     * @param $RoleID  int   角色iD
     * @return array|bool
     */
    public function rolePermission($table,$where,$sort_order='',$RoleID=0){
        //获取菜单表的第一层数据
        $arr=M($table)->where($where)->order($sort_order)->select();
        if($arr){
            //遍历第一层数据
            foreach($arr as $val){
                /*********获取相应安扭功能开始*******/
                $opreatB=M("sys_menubutton")->field("ButtonID")->where("MenuID=".$val['ID'])->select();
                $row=M("sys_rolemenu")->where("RoleID=%d and MenuID=%d",$RoleID,$val['ID'])->find();
                $oparr=array();
                if($row){
                    $select=1;
                    $ButtonStr=substr($row['ButtonID'],1,strlen($row['ButtonID']));
                    $ButtonArr=explode(",",$ButtonStr);
                    foreach($opreatB as $v){
                        $buttonName=M("sys_operationbutton")->where("id=%d",$v['ButtonID'])->getField("Name");
                        $v['Name']=$buttonName;
                        if(in_array($v['ButtonID'],$ButtonArr)){
                            $v['select']=1;
                        }else{
                            $v['select']=0;
                        }
                        $oparr[]=$v;
                    }
                }else{
                    $select=0;
                    foreach($opreatB as $v) {
                        $buttonName = M("sys_operationbutton")->where("id=%d", $v['ButtonID'])->getField("Name");
                        $v['Name'] = $buttonName;
                        $v['select']=0;
                        $oparr[]=$v;
                    }
                }
                //循环处理树结构数据
                $two_arr[]=array(
                    'ID' 	    => $val['ID'],
                    'Name'    	=> $val['Name'],
                    'Select'    	=> $select?1:0,
                    'OperationButton' 	=> $oparr,
                    'IconCls'				=>$val['Icon']	,
                    'children'   =>$this->rolePermission($table,"ParentID=".$val['ID'],$sort_order,$RoleID)?$this->rolePermission($table,"ParentID=".$val['ID'],$sort_order,$RoleID):''
                );
            }
            return $two_arr;
        }
    }




}