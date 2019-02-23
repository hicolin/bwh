<?php
/**
 * 
 *
 *
 * 修改时间: 2017-04-27 08:50
 * 功能说明:广告位管理
 */
namespace Admin\Controller\System;

use Think\Controller;
use XBCommon;
class AdvertisingController extends  BaseController
{


    const T_TABLE = 'sys_advertising';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTRATOR = 'sys_administrator';
    const T_ADCONTENT = 'sys_adcontent';


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
                $val['Status']=$query->GetValue(self::T_DICTIONARY,array('DictType'=>'1','DictValue'=>$val['Status']),'DictName');
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
        $StatusList=$query->GetList(self::T_DICTIONARY,array('DictType'=>'1','Status'=>'1'),'ID ASC','DictName,DictValue');
        $this->assign('StatusList',$StatusList);

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
                array('Name','require','名称必须填写！'), //默认情况下用正则进行验证
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
     * 数据删除
     */
    public function Del()
    {
        $mod = D(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组
        //删除判断，如果该广告位下存在广告位删除时，禁止删除广告位
        foreach ($arr as $val){
            $AdExists=M(self::T_ADCONTENT)->where(array('AdvertisingID'=>$val))->count();
            if($AdExists>0){
                $this->ajaxReturn(0,"广告位下存在广告的关联，请先删除广告物料！");
            }
        }
        //判断完成，开始执行逻辑删除
        $data['IsDel']=1;
        $where['ID']=array('in',$arr);
        $res=$mod->where($where)->save($data);  //逻辑删除
        if ($res) {
            $this->ajaxReturn(true, "广告位删除成功！");
        } else {
            $this->ajaxReturn(false, "广告位删除时出错！");
        }
    }


}