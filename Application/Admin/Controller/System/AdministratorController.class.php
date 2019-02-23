<?php
/**
 * 功能说明：后台用户管理模块
 */
namespace Admin\Controller\System;
use Think\Controller;
use XBCommon;
class AdministratorController extends BaseController {

    const T_TABLE = 'sys_administrator';
    const T_ROLE = 'sys_role';

	/**
     * 用户管理
     */
	public function index(){
		//获取角色信息，用于查询条件的下拉选项
        $where=array(
            'Status'=>1,
            'IsDel'=>0
        );

        //默认内置管理员
        if($_SESSION['AdminInfo']['AdminID'] != 1){
            $where['ID'] = array('neq',1);
        }

		$roleList=M(self::T_ROLE)->field("ID,Name")->where($where)->select();

		$this->assign("roleList",$roleList);
		$this->display();
	}


	/**
	 * 后台用户管理的列表数据获取
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

        $UserName=I('post.UserName');
        if($UserName){$where['UserName'] = array('like','%'.$UserName.'%');}

        $TrueName=I('post.TrueName');
        if($TrueName){$where['TrueName']=array('like','%'.$TrueName.'%');}

        $RoleID=I('post.RoleID',-5,'intval');
        if($RoleID!=-5){$where['RoleID']=$RoleID;}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        //默认内置管理员
        if($_SESSION['AdminInfo']['AdminID'] != 1){
            $where['ID'] = array('neq',1);
        }

        $where['IsDel']=0;
        //查询的列名
        $col='ID,UserName,TrueName,RoleID,LoginCount,ErrorCount,LoginTime,LoginIP,LoginMAC,BindIP,BindMAC,Status,IpCity,OperatorID,UpdateTime,Status';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['RoleID']=$query->GetValue(self::T_ROLE,array('ID'=>(int)$val['RoleID']),'Name');
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }

	/**
     * 编辑功能
	*/


    public function Edit($ID=null){
        $ID=(int)$ID;
        $this->assign('ID',$ID);

        $where=array(
            'Status'=>1,
            'IsDel'=>0
        );

        //默认内置管理员
        if($_SESSION['AdminInfo']['AdminID'] != 1){
            $where['ID'] = array('neq',1);
        }
        //获取角色列表
        $roleList=M(self::T_ROLE)->field("ID,Name")->where($where)->select();
        $this->assign("roleList",$roleList);
        $this->display();
    }

	/**
	 * 查询详细信息
	 */
	public function shows()
	{
		$id = I("request.ID", 0, 'intval');
		if ($id) {
            $model = M(self::T_TABLE);
            $result = $model->find($id);
            if(!$result==null){
                //对隐秘数据进行特殊化处理，防止泄露
                $result['Password']='******';
                $this->ajaxReturn($result);
            }else{
                //没有查询到内容
                $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
            }
		}
	}

	/**
	 *保存数据
	 */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('UserName','require','用户名必须填写！'), //默认情况下用正则进行验证
                array('password','require','密码必须填写！'), //默认情况下用正则进行验证
                array('TrueName','require','真实姓名必须填写！'), //默认情况下用正则进行验证
            );

            //根据表单提交的POST数据和验证规则条件，创建数据对象
            $model=D(self::T_TABLE);
            $FormData=$model->validate($rules)->create();
            if(!$FormData){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                $data=array();  //创建新数组，用于存储保存的数据
                //验证通过，执行后续保存动作,判断选择的角色状态是否正常
                if($FormData['RoleID']==-5){
                    $this->ajaxReturn(false, '请选择用户角色！');
                }else{
                    $RoleExists=M(self::T_ROLE)->where(array('ID'=>$FormData['RoleID'],'Status'=>1,'IsDel'=>0))->count();
                    if($RoleExists<1){
                        $this->ajaxReturn(false, '角色不存在，或被限制使用！');
                    }
                }
                //保存或更新数据判断
                if($FormData['ID']>0){
                    //判断用户名是否唯一，如果修改的时候，将用户名改名与其他用户名重名则不被允许！
                    $where['ID']=array('neq',$FormData['ID']);  //不等于当前要修改的ID
                    $where['UserName']=$FormData['UserName'];
                    $where['Status']=1;
                    $where['IsDel']=0;
                    $UserExists=M(self::T_TABLE)->where($where)->count();

                    if($UserExists>0){
                        $this->ajaxReturn(false, '用户名已存在！');
                    }

                    //只更新修改的字段
                    $data['UserName']=$FormData['UserName'];
                    $data['RoleID']=$FormData['RoleID'];
                    //密码不为6个星，表示修改了新密码，对密码进行加密入库，否则密码不更新。
                    if($FormData['Password']!='******'){
                        $data['Password']=md5($FormData['Password']);
                    }
                    $data['TrueName']=$FormData['TrueName'];
                    $data['PsdErrorCount']=$FormData['PsdErrorCount'];
                    $data['Status']=$FormData['Status'];
                    //记录操作者信息和更新操作时间
                    $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                    $data['UpdateTime']=date("Y-m-d H:i:s");
                    $res=$model->where(array('ID'=>$FormData['ID']))->save($data);
                    if($res>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    //判断用户名是否唯一，不允许重复 (ID为空，表示新增数据)
                    $UserExists=M(self::T_TABLE)->where(array('UserName'=>$FormData['UserName'],'Status'=>1,'IsDel'=>0))->count();
                    if($UserExists>0){
                        $this->ajaxReturn(false, '用户名已存在！');
                    }
                    //判断选择的角色状态是否正常
                    if($FormData['RoleID']==-5){
                        $this->ajaxReturn(false, '请选择用户角色！');
                    }else{
                        $count=M(self::T_ROLE)->where(array('ID'=>$FormData['RoleID'],'Status'=>1,'IsDel'=>0))->count();
                        if($count<1){
                            $this->ajaxReturn(false, '角色不存在，或被限制使用！');
                        }
                    }
                    $data=$FormData; //将POST提交的数到数组中，等待处理后保存入库
                    $data['Password']=md5($FormData['Password']);  //密码加密
                    //记录操作者信息和更新操作时间
                    $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                    $data['UpdateTime']=date("Y-m-d H:i:s");
                    $res=$model->add($data);  //新增入库
                    if($res>0){
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
	 * 数据删除处理 单条或多条，禁止删除超级管理员
	 */
	public function Del()
	{
		$mod = D(self::T_TABLE);
		//获取删除数据id (单条或数组)
		$ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组
        //删除判断，特殊用户禁止删除，当前登陆者禁止删除
        foreach ($arr as $val){
            if($val==$_SESSION['AdminInfo']['AdminID']){
                $this->ajaxReturn(false, "当前登陆者不允许被删除！");exit;
            }
            if($val==1){
                $this->ajaxReturn(false, "系统超级管理员不允许被删除！");
            }
        }
        //判断完成，开始执行逻辑删除
        $data['IsDel']=1;
        $where['ID']=array('in',$arr);
        $res=$mod->where($where)->save($data);  //逻辑删除
        if ($res) {
            $this->ajaxReturn(true, "用户删除数据成功！");
        } else {
            $this->ajaxReturn(false, "用户删除数据时出错！");
        }
	}

    /**
     * 绑定IP
     */
    public function Bindip($ID =null){
        $ID=(int)$ID;

        $res = M(self::T_TABLE)->where('ID ='.$ID)->find();

        if(!$res){
            $this->ajaxReturn(false, "请选择需要绑定的用户！");
        }

        if($res['LoginIP']){
            $result =  M(self::T_TABLE)->where('ID ='.$ID)->save(array('BindIP'=>$res['LoginIP']));

            if($result !== false){
                $this->ajaxReturn(true, "IP绑定成功");
            }else{
                $this->ajaxReturn(false, "IP绑定失败！");
            }
        }else{
            $this->ajaxReturn(false, "最后登录IP地址为空！");
        }

    }

    /**
     * 绑定MAC
     */
    public function BindMAC($ID =null){
        $ID=(int)$ID;

        $res = M(self::T_TABLE)->where('ID ='.$ID)->find();

        if(!$res){
            $this->ajaxReturn(false, "请选择需要绑定的用户！");
        }

        if($res['LoginMAC']){
            $result =  M(self::T_TABLE)->where('ID ='.$ID)->save(array('BindMAC'=>$res['LoginMAC']));

            if($result !== false){
                $this->ajaxReturn(true, "MAC绑定成功！");
            }else{
                $this->ajaxReturn(false, "MAC绑定失败！");
            }
        }else{
            $this->ajaxReturn(false, "最后登录MAC地址为空!");
        }
    }




}