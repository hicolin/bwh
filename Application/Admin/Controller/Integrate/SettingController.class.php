<?php
/**
 * 修改时间: 2017-05-26 15:50
 * 功能说明:短信接口
 */
namespace Admin\Controller\Integrate;

use Think\Controller;
use Admin\Controller\System\BaseController;
use XBCommon;
class SettingController extends  BaseController
{
    const T_TABLE = 'sys_inteparameter';

    /**
     * 编辑页面
     */
    public function Edit(){
        $ID=I('get.ID');
        $ParaList=M(self::T_TABLE)->where(array('IntegrateID'=>$ID))->select();
        $this->assign('ParaList',$ParaList);
        $this->assign('ID',$ID);
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
            $list = $model->where(array('IntegrateID'=>$id))->select();
            if(!empty($list)){
                //将参数转化为带键名的一维数组
                $result=array();
                foreach ($list as $val){
                    $result[$val['ParaName']]=$val['ParaValue'];
                }
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
        //需要对参数进行特殊的处理和保存，功能未实现
        if(IS_POST){
            //接收POST的所有数据
            $data=I('post.');
            $id=$data['ID'];
            unset($data['ID']);  //销毁键值为ID的数组
            if(!empty($data)){
                //因牵涉多条数据的保存，下面采用了循环处理
                $model=M(self::T_TABLE);
                $result=0;
                foreach ($data as $key=>$val){
                    $savedata=array('ParaValue'=>$val,'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],'UpdateTime'=>date("Y-m-d H:i:s"));
                    $res=$model->where(array('IntegrateID'=>$id,'ParaName'=>$key))->setField($savedata);
                    if($res==1){$result=1;};
                }
                if ($result==1){
                    $this->ajaxReturn(1, '修改成功！');
                }else{
                    $this->ajaxReturn(0, '修改失败！');
                }
            }else{
                $this->ajaxReturn(0, '保存的数据不存在！');
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对');
        }
    }

}