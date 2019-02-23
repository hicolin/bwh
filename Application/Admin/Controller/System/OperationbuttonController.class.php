<?php
/**
 * 功能说明：按钮设置
 */
namespace Admin\Controller\System;
use XBCommon;
class OperationbuttonController extends BaseController
{

    const T_TABLE = 'sys_operationbutton';
    const T_SMSTEMPLATES = 'sys_smstemplates';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';
    const T_ADVERTISING = 'sys_advertising';
    const T_MENUBUTTON = 'sys_menubutton';


	/**
	 * 显示按钮设置页面方法
	 */
	public function index()
	{
		$this->display();
	}

    public function DataList(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort='Sort ASC';
        }

        $Name=I('post.Name');
        if($Name){$where['Name'] = array('like','%'.$Name.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

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
                //如果有要特殊处理的字段，可在此处编写代码
                $val['IsToken']==0?$val['IsToken']='无令牌':$val['IsToken']='带令牌';
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
	 * 编辑页面数据获取
	 */
    public function Edit($ID=null){
        $ID=(int)$ID;
        $this->assign('ID',$ID);
        $this->display();
    }

    /**
     * 查询详细信息
     */
    public function shows()
    {
        $id = I("request.ID",0, 'intval');
        if ($id) {
            $model = M(self::T_TABLE);
            $result = $model->find($id);
            if(!$result==null){
                //对隐秘数据进行特殊化处理，防止泄露
                $this->ajaxReturn($result);
            }else{
                //没有查询到内容
                $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
            }
        }
    }


    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('UserName','require','按钮名称必须填写！'), //默认情况下用正则进行验证
                array('EName','require','英文名称必须填写！'), //默认情况下用正则进行验证
                array('Val','require','权限值必须填写！'), //默认情况下用正则进行验证
                array('Icon','require','按钮图标必须选择！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //验证通过，执行后续保存动作,判断选择的令牌模式是否正确
                if($data['IsToken']!=0 && $data['IsToken']!=1){
                    $this->ajaxReturn(false, '选择的令牌值不正确！');
                }
                if($data['Val']<2 || ($data['Val']%2)!=0){
                    $this->ajaxReturn(false, '权限值必须是2的倍数！');
                }
                //记录操作者信息
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");

                //保存或更新数据判断
                if($data['ID']>0){
                    //判断按钮名称和英文名称是否唯一，如果修改的时候！
                    $where['ID']=array('neq',$data['ID']);  //不等于当前要修改的ID
                    $where['Status']=1;
                    $where['IsDel']=0;
                    $con1['Name']=$data['Name'];
                    $con2['EName']=$data['EName'];
                    $con3['Val']=$data['Val'];
                    $NameExists=M(self::T_TABLE)->where($where)->where($con1)->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '按钮名称重复，请更换名称！');
                    }
                    $ENameExists=M(self::T_TABLE)->where($where)->where($con2)->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '英文名称重复，请更换名称！');
                    }
                    $ValExists=M(self::T_TABLE)->where($where)->where($con2)->count();
                    if($ValExists>0){
                        $this->ajaxReturn(false, '权限值重复，请更换权限值！');
                    }
                    $result=$model->where('ID='.$data['ID'])->save($data);
                    if($result>0){
                        //更新缓存 菜单 和权限按钮缓存
                        $cache=new XBCommon\CacheData();
                        $cache->ClearMenu();
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    //添加一条新数据，对特殊数据进行处理,判断按钮名称和英文名称是否唯一
                    $where['Status']=1;
                    $where['IsDel']=0;
                    $con1['Name']=$data['Name'];
                    $con2['EName']=$data['EName'];
                    $con3['Val']=$data['Val'];
                    $NameExists=M(self::T_TABLE)->where($where)->where($con1)->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '按钮名称重复，请更换名称！');
                    }
                    $ENameExists=M(self::T_TABLE)->where($where)->where($con2)->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '英文名称重复，请更换名称！');
                    }
                    $ValExists=M(self::T_TABLE)->where($where)->where($con2)->count();
                    if($ValExists>0){
                        $this->ajaxReturn(false, '权限值重复，请更换权限值！');
                    }
                    //判断结束，保存添加数据
                    $result=$model->add($data);;
                    if($result>0){
                        //更新缓存 菜单 和权限按钮缓存
                        $cache=new XBCommon\CacheData();
                        $cache->ClearMenu();
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
	 * 数据删除处理 单条或多条,禁止删除被引用的按钮。
	 */
    public function Del()
    {
        $mod = M(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组
        //删除判断，特殊用户禁止删除，当前登陆者禁止删除
        foreach ($arr as $val){
            $ButtonExists=M(SELF::T_MENUBUTTON )->where(array('ButtonID'=>$val,'IsDel'=>0,'Status'=>1))->count();
            if($ButtonExists>0){
                $this->ajaxReturn(false, "操作按钮被其他菜单引用时，不允许被删除！");
            }
        }
        //判断完成，开始执行逻辑删除
        $data['IsDel']=1;
        $where['ID']=array('in',$arr);
        $res=$mod->where($where)->save($data);  //逻辑删除
        if ($res) {
            $this->ajaxReturn(true, "按钮被删除成功！");
        } else {
            $this->ajaxReturn(false, "删除按钮失败！");
        }
    }
}