<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 送券设置模块
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 王少武
// +----------------------------------------------------------------------
// | Date: 2017/9/9
// +---------------------------------------------------------------------- 
namespace Admin\Controller\Juan;

use XBCommon;
use Admin\Controller\System\BaseController;

class SendsetsController extends BaseController {

    const T_TABLE='juan_sendsets';
    const T_ADMIN='sys_administrator';

	/**
     * 信息列表
     */
	public function index(){
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
        }else{
            $sort = 'ID desc';
        }

        // $Title=I('post.Title','','trim');
        // if($Title){$where['Title'] = array('like','%'.$Title.'%');}

        // $Money=I('post.Money','','trim');
        // if($Money){$where['Money']=array('like','%'.$Money.'%');}

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
                $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>$val['OperatorID']),'UserName');
                if($val['StartMoney']){
                    $val['StartMoney']="满".$val['StartMoney']."元可使用";
                }
                if($val['Money']){
                    $val['Money']=$val['Money']."元";
                }
                if($val['Nunbs']){
                    $val['Nunbs']=$val['Nunbs']."张";
                }
                if($val['Status']=='1'){
                    $val['Status']='<span style="color:green;">启用</span>';
                }elseif($val['Status']=='0'){
                    $val['Status']='<span style="color:red;">关闭</span>';
                }
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
        $this->display();
    }

	/**
	 * 查询详细信息
	 */
	public function Shows(){
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
	 *保存数据
	 */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Title','require','优惠券名称必须填写！'), //默认情况下用正则进行验证
                array('Money',0,'优惠券金额必须填写！',0,'notequal'), //默认情况下用正则进行验证
                array('StartMoney',0,'使用条件必须填写！',0,'notequal'), //默认情况下用正则进行验证
                array('StartTime','require','优惠券开始时间必须填写！'), //默认情况下用正则进行验证
                array('EndTime','require','优惠券结束时间必须填写！'), //默认情况下用正则进行验证
                array('Nunbs',0,'送券个数必须填写！',0,'notequal'), //默认情况下用正则进行验证
                array('Sort','require','排序必须填写！'), //默认情况下用正则进行验证
                //array('Sort',array(0,999),'排序的大小必须在0-999之间！',0,'between'), //默认情况下用正则进行验证
            );

            //根据表单提交的POST数据和验证规则条件，创建数据对象
            $model=D(self::T_TABLE);
            $FormData=$model->validate($rules)->create();
            if(!$FormData){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                $data=array();  //创建新数组，用于存储保存的数据
                if($FormData['StartTime']>$FormData['EndTime']){
                    $this->ajaxReturn(0,"开始时间不能大于结束时间");
                }
                $where['Name']=$FormData['Name'];
                $where['ID']=array('neq',$FormData['ID']);
                $where['IsDel']=0;
                $exit=M(self::T_TABLE)->where($where)->count();
                if($exit>0){
                    $this->ajaxReturn(0,"送券名称不能重复");
                }
                //只更新修改的字段
                $data=$FormData;
                //记录操作者信息和更新操作时间
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");
				
                //更新数据判断
                if($FormData['ID']>0) {
                    $res=$model->where(array('ID'=>$FormData['ID']))->save($data);
                    if($res>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    $res=$model->add($data);
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

}