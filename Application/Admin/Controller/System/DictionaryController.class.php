<?php
/**
 * 
 *
 *
 * 修改时间: 2017-04-26 10:30
 * 功能说明: 数据字典，定义一些常用的字段转义文字描述。
 */
namespace Admin\Controller\System;

use Think\Controller;
use XBCommon;
class DictionaryController extends BaseController
{

    const T_DICTYPE = 'sys_dictype';
    const T_TABLE = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';

    function index(){
        $query=new XBCommon\XBQuery();
        //获取内置的字典类型
        $DType=$query->GetList(self::T_DICTYPE,null,null,null);
        $this->assign('DType',$DType);
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
        $where=array();
        $Name=I('post.DictName');
        if($Name){$where['DictName'] = array('like','%'.$Name.'%');}

        $Value=I('post.DictValue');
        if($Value){$where['DictValue'] = array('like','%'.$Value.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        $DictType=I('post.DictType',-5,'intval');
        if($DictType!=-5){$where['DictType']=$DictType;}

        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $cols='';
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$cols);
        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $dict_type=M(self::T_DICTYPE)->where(array('ID'=>(int)$val['DictType']))->find();
                $val['DictType']=$dict_type['Name'];
                $val['TypeName']=$dict_type['EName'];
                $val['Status']=$query->GetValue(self::T_TABLE,array('DictType'=>'1','Status'=>'1','DictValue'=>$val['Status']),'DictName');
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
        $query=new XBCommon\XBQuery();
        $StatusList=$query->GetValue(self::T_TABLE,array('DictType'=>'1','Status'=>'1'),'DictName,DictValue');
        $DType=$query->GetList(self::T_DICTYPE,null,null,null);
        $this->assign('StatusList',$StatusList);
        $this->assign('DType',$DType);
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

    /**
     * 数据保存
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('DictName','require','参数名必须填写！'), //默认情况下用正则进行验证
                array('DictValue','require','参数值必须填写！'), //默认情况下用正则进行验证
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
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