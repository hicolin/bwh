<?php
/**
 * 
 *
 *
 * 修改时间: 2017-05-26 15:50
 * 功能说明:短信接口
 */
namespace Admin\Controller\Integrate;

use Think\Controller;
use Admin\Controller\System\BaseController;
use XBCommon;
class PayMentController extends  BaseController
{
    const T_TABLE = 'sys_integrate';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';
    const T_INTEPARAMETER = 'sys_inteparameter';

    public function index(){
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
        }

        $Name=I('post.Name');
        if($Name){$where['Name'] = array('like','%'.$Name.'%');}

        $EName=I('post.EName');
        if($EName){$where['EName'] = array('like','%'.$EName.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        $where['Type']=1;  //平台类型  0 短信 1 支付 2 第三方登录

        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['Status']=$query->GetValue(self::T_DICTIONARY,array('DictType'=>'2','DictValue'=>$val['Status']),'DictName');
                $val['IsDefault']==1?$val['IsDefault']='是':$val['IsDefault']='否';
                $val['OperatorID']=$query->GetValue(self::T_ADMINISTRATOR,array('ID'=>(int)$val['OperatorID']),'UserName');
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }

    /**
     * 编辑页面
     */
    public function Edit($ID=null){
        $ID=(int)$ID;
        $this->assign('ID',$ID);

        //获取状态列表
        $query=new XBCommon\XBQuery();
        $StatusList=$query->GetList(self::T_DICTIONARY,array('DictType'=>'2','Status'=>'1'),'ID ASC','DictName,DictValue');
        $this->assign('StatusList',$StatusList);
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
                $this->ajaxReturn($result);
            }else{
                //没有查询到内容
                $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
            }
        }
    }

    /**
     * 数据保存
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Name','require','平台名称必须填写！'), //默认情况下用正则进行验证
                array('EName','require','平台英文名称必须填写！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //记录操作者信息
                $data['Type']=1; // 平台类型 0 短信 1 支付 2 第三方登录
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");

                //保存或更新数据判断
                if($data['ID']>0){
                    //修改的时候，判断平台名是否唯一！
                    $where['ID']=array('neq',$data['ID']);  //不等于当前要修改的ID
                    $where['Type']=1;  // 平台类型 0 短信 1 支付 2 第三方登录
                    $con1['Name']=$data['Name'];
                    $con2['EName']=$data['EName'];

                    $NameExists=M(self::T_TABLE)->where($where)->where($con1)->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '平台名称已存在！');
                    }
                    $ENameExists=M(self::T_TABLE)->where($where)->where($con2)->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '平台英文名称已存在！');
                    }

                    if($data['IsDefault']==1){
                        $val['IsDefault']=0;
                        $model->where(array('Type'=>1,'IsDefault'=>1))->save($val);
                    }
                    $result=$model->where(array('ID'=>$data['ID']))->save($data);
                    if($result>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    //新增的时候，判断平台名是否唯一，不允许重复
                    $NameExists=M(self::T_TABLE)->where(array('Name'=>$data['Name'],'Type'=>1))->count();
                    if($NameExists>0){
                        $this->ajaxReturn(false, '平台名称已存在！');
                    }

                    $ENameExists=M(self::T_TABLE)->where(array('EName'=>$data['EName'],'Type'=>1))->count();
                    if($ENameExists>0){
                        $this->ajaxReturn(false, '平台英文名称已存在！');
                    }
                    if($data['IsDefault']==1){
                        $val['IsDefault']=0;
                        $model->where(array('Type'=>1,'IsDefault'=>1))->save($val);
                    }
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
     * 数据删除处理 单条或多条，禁止删除超级管理员
     */
    public function Del()
    {
        $mod = M(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组
        //判断是否被属性值调用，被属性值调用禁止删除
        foreach ($arr as $val){
            $IntegrateIDExists=M(self::T_INTEPARAMETER)->where(array('IntegrateID'=>$val))->count();
            if($IntegrateIDExists>0){
                $this->ajaxReturn(false, "该平台下存在参数信息，禁止删除！");
            }
        }
        //判断完成，开始执行物理删除
        $where['ID']=array('in',$arr);
        $res=$mod->where($where)->delete();  //物理删除
        if ($res) {
            $this->ajaxReturn(true, "删除数据成功！");
        } else {
            $this->ajaxReturn(false, "删除数据时出错！");
        }
    }

}