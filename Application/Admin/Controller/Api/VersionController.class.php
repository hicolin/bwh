<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：刁洪强
 * 修改时间：2017-06-15 17:40
 * 功能说明：资金明细
 */
namespace Admin\Controller\Api;
use XBCommon;
use Admin\Controller\System\BaseController;
class VersionController extends BaseController {

    const T_TABLE = 'version';
    const T_ADMINISTRATOR = 'sys_administrator';

    public function _initialize()
    {
        parent::_initialize();

        $this->Client = array(0=>'','android'=>'android','ios'=>'ios');
    }

	public function index(){

        $this->assign('Client',$this->Client);

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
            $sort = 'ID desc';
        }

        $where = '';

        $Client = I('post.Client',-1,'trim');
        if($Client <> -1 ){$where['Client'] = $Client;}

        $Package = I('post.Package','','trim');
        if($Package){$where['Package'] = array('like','%'.$Package.'%');}


        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                //$val['Updates']=htmlspecialchars_decode($val['Updates']);

                $val['Status']= $val['Status'] == 1 ? '启用' : '禁用';
                $val['IsDefault']= $val['IsDefault'] == 1 ? '是' : '否';

                $val['isForced']= $val['isForced'] == 1 ? '强制更新' : '非强制更新';

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
    public function Edit(){
        $ID = I("get.ID", 0, 'intval');
        $this->assign('ID',$ID);

        $this->assign('column',$this->column);

        $this->display();
    }

    public function shows(){
        $id=I("request.ID");
        if(!empty($id)){
            $centerModel=D(self::T_TABLE);
            $center_rec=$centerModel->where("ID=".$id)->find();

            $center_rec['Updates']=htmlspecialchars_decode($center_rec['Updates']);

            $Ver = explode('.',$center_rec['Ver']);

            $center_rec['Ver1'] = $Ver[0];
            $center_rec['Ver2'] = $Ver[1];
            $center_rec['Ver3'] = $Ver[2];
        }else{
            $center_rec['Ver1'] = 1;
            $center_rec['Ver2'] = 0;
            $center_rec['Ver3'] = 0;
        }

        $this->ajaxReturn($center_rec);
    }

    /**
     * 数据保存
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Client','require','客户端必须选择！'),
                array('Package','require','包名必须填写！'),
                array('Ver','require','版本号必须填写！'),
                array('Size','require','包大小必须填写！'),
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //验证通过，执行后续保存动作
                $Ver1 = I('post.Ver1',1,'intval');
                $Ver2 = I('post.Ver2',0,'intval');
                $Ver3 = I('post.Ver3',0,'intval');

                $data['Ver'] = $Ver1.'.'.$Ver2.'.'.$Ver3;

                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['Addtime']=date("Y-m-d H:i:s");

                $find = $model->where(array('Client'=>$data['Client'],'Package'=>$data['Package'],'Ver'=>$data['Ver']))->find();
                if($find && $find['ID']<>$data['ID']){
                    $this->ajaxReturn(0, '该客户端的版本号和包名已存在，不可重复');
                }

                if($data['IsDefault'] == 1){
                    $model->where(array('Client'=>$data['Client']))->save(array('IsDefault'=>2));
                }

                //保存或更新数据
                if($data['ID']>0){
                    $result=$model->where('ID='.$data['ID'])->save($data);
                    if($result>0){
                        F('Common_Package',null);
                        common_package();

                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    $result=$model->add($data);
                    if($result>0){
                        F('Common_Package',null);
                        common_package();

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
     * 查看详情
     */
    public function Detail(){
        $ID=I('get.ID');
        if(!empty($ID)){
            $Info = M(self::T_TABLE)->where(array('ID'=>$ID))->find();

            $Info['Updates']=htmlspecialchars_decode($Info['Updates']);

            $this->assign('Info',$Info);
        }
        $this->display();
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
            F('Common_Package',null);
            common_package();
            $this->ajaxReturn(true, "删除数据成功！");
        } else {
            $this->ajaxReturn(false, "删除数据时出错！");
        }
    }
}