<?php
/**
 * 
 *
 *
 * 修改时间: 2017-04-27 08:50
 * 功能说明:在线客服
 */
namespace Admin\Controller\System;

use Think\Controller;
use XBCommon;
class QqController extends  BaseController
{
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

        $QQ=I('post.QQ');
        if($QQ){$where['QQ'] = array('like','%'.$QQ.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $cols='';
        $array=$query->GetDataList('qq',$where,$page,$rows,$sort,$cols);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['Status']=$query->GetValue('dictionary',array('DictType'=>'1','DictValue'=>$val['Status']),'DictName');
                $val['OperatorID']=$query->GetValue('administrator',array('ID'=>(int)$val['OperatorID']),'UserName');
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

        $query=new XBCommon\XBQuery();
        $StatusList=$query->GetList('dictionary',array('DictType'=>'1','Status'=>'1'),'ID ASC','DictName,DictValue');
        $this->assign('StatusList',$StatusList);

        $this->display();
    }

    /**
     * 数据保存
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Name','require','名称必须填写！'), //默认情况下用正则进行验证
                array('QQ','require','QQ必须填写！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $model=D($this->_name);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //验证通过，执行后续保存动作
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");

                //保存或更新数据
                if($data['ID']>0){
                    $result=$model->where('ID='.$data['ID'])->save($data);
                    if($result>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
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

        //根据选择的ID值，进行物理删除
        $con['ID']=array('in',$arr);
        $res=$mod->where($con)->delete();  //逻辑删除
        if ($res) {
            $this->ajaxReturn(true, "删除数据成功！");
        } else {
            $this->ajaxReturn(false, "删除数据时出错！");
        }
    }
}