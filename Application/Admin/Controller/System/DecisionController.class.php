<?php
/**
 * @功能说明：智能决策控制类
 */
namespace Admin\Controller\System;

class DecisionController extends BaseController {

    const T_TABLE = 'sys_decision';

    /**
     * 加载基本信息页
     */
    public function index(){
        $model = M(self::T_TABLE);
        $decisions = $model->where(['IsDel'=>0])->select();
        $yysDecisions = $model->where(['Type'=>1,'IsDel'=>0])->select();
        $tbDecisions = $model->where(['Type'=>2,'IsDel'=>0])->select();
        $dtDecisions = $model->where(['Type'=>3,'IsDel'=>0])->select();
        $this->assign(compact('yysDecisions','tbDecisions','dtDecisions','decisions'));
    	$this->display();
    }

    /**
     * 更新字段
     */
    public function changeField()
    {
        if(IS_AJAX){
            $id = I('post.id');
            $val = I('post.val');
            $type = I('post.type');
            $decision = M('sys_decision');
            $data[$type] = $val;
            $res = $decision->where(['ID'=>$id])->save($data);
            if($res || $res == 0){
                $this->ajaxReturn(200,'更新成功');
            }
            $this->ajaxReturn(100,'更新失败');
        }
    }


}