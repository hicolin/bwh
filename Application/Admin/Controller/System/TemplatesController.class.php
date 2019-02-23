<?php
/**


 * @作者：      刁洪强
 * @修改日期：  2017-05-24 14:19
 * @继承版本:   1.1
 * @功能说明：  短信模板
 */
namespace Admin\Controller\System;
use Think\Controller;
use XBCommon;
class TemplatesController extends BaseController {


    const T_SMSTEMPLATES = 'sys_smstemplates';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';

	/**
	 * 物流公司列表
	 */
	public function index(){
		$this->display();
	}


    /**
     * 短信模板列表数据
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

        $Content=I('post.Content');
        if($Content){$where['Content'] = array('like','%'.$Content.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        $where['IsDel']=0;
        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_SMSTEMPLATES,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['Status']=$query->GetValue(self::T_DICTIONARY,array('DictType'=>'2','DictValue'=>$val['Status']),'DictName');
                $val['OperatorID']=$query->GetValue(self::T_ADMINISTRATOR,array('ID'=>(int)$val['OperatorID']),'UserName');
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }




	/**
	 * 编辑数据
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
            $model = M(self::T_SMSTEMPLATES);
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
                array('Name','require','模板名称必须填写！'), //默认情况下用正则进行验证
                array('EName','require','英文名称必须填写！'), //默认情况下用正则进行验证
                array('Content','require','模板内容必须填写！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $db=D(self::T_SMSTEMPLATES);
            $data=$db->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$db->getError());
            }else{
                //验证通过，执行后续保存动作
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");

                //保存或更新数据
                if($data['ID']>0){
                    //修改时,模板名称必须唯一！
                    $where['ID']=array('neq',$data['ID']);  //不等于当前要修改的ID
                    $where['Status']=1;
                    $where['IsDel']=0;
                    $con1['Name']=$data['Name'];
                    $con2['EName']=$data['EName'];
                    $NameExists=$db->where($where)->where($con1)->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '模板名称必须唯一！');
                    }
                    $ENameExists=$db->where($where)->where($con2)->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '英文名称必须唯一！');
                    }

                    //无重名，则允许更新保存
                    $result=$db->where('ID='.$data['ID'])->save($data);
                    if($result>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    //新增时，判断物流名是否唯一，不允许重复
                    $NameExists=$db->where(array('Name'=>$data['Name'],'Status'=>1,'IsDel'=>0))->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '模板名称必须唯一！');
                    }
                    $ENameExists=$db->where(array('EName'=>$data['EName'],'Status'=>1,'IsDel'=>0))->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '英文名称必须唯一！');
                    }
                    //无重名，则允许新增记录
                    $result=$db->add($data);
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
     * 逻辑删除
	*/
    public function Del()
    {
        $mod = D(self::T_SMSTEMPLATES);
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


}