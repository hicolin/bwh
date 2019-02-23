<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 优惠券模块
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 王少武
// +----------------------------------------------------------------------
// | Date: 2017/9/9
// +---------------------------------------------------------------------- 

namespace Admin\Controller\Juan;

use XBCommon;
use Admin\Controller\System\BaseController;

class CoupansController extends BaseController {

    const T_TABLE = 'juan_coupans';
    const T_MEMINFO='mem_info';
    const T_COMPANY='company';
    const T_ADMIN='sys_administrator';
    const T_MEMCOUPANS='mem_coupans';

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

        $Title=I('post.Title','','trim');
        if($Title){$where['Title'] = array('like','%'.$Title.'%');}

        $Money=I('post.Money','','trim');
        if($Money){$where['Money']=array('like','%'.$Money.'%');}

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
                //$val['StartMoney']="满足".$val['StartMoney']."元可使用";
                $val['Money']=$val['Money']."元";
                //$val['PerdayNunbs']=$val['PerdayNunbs']."张";
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
                // array('StartMoney',0,'使用条件必须填写！',0,'notequal'), //默认情况下用正则进行验证
                // array('StartTime','require','优惠券开始时间必须填写！'), //默认情况下用正则进行验证
                // array('EndTime','require','优惠券结束时间必须填写！'), //默认情况下用正则进行验证
                //array('PerdayNunbs',0,'每日可领取数必须填写！',0,'notequal'), //默认情况下用正则进行验证
                array('Sort','require','排序必须填写！'), //默认情况下用正则进行验证
                array('Sort',array(0,999),'排序的大小必须在0-999之间！',0,'between'), //默认情况下用正则进行验证
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
                $where['Title']=$FormData['Title'];
                $where['ID']=array('neq',$FormData['ID']);
                $where['IsDel']=0;
                $exit=M(self::T_TABLE)->where($where)->count();
                if($exit>0){
                    $this->ajaxReturn(0,"优惠券名称不能重复");
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


	/**
	 * 数据删除处理 单条或多条  逻辑删除
	 */
	public function Del()
	{
		$mod = D(self::T_TABLE);
		//获取删除数据id (单条或数组)
		$ids = I("post.ID", '', 'trim');
        $where['ID']=array('in',$ids);
        $res=$mod->where($where)->setField('IsDel',1);
        if ($res) {
            $this->ajaxReturn(true, "用户删除数据成功！");
        } else {
            $this->ajaxReturn(false, "用户删除数据时出错！");
        }
	}

	/**
     * 查看详情
     */
	public function Detail(){
	    $ID=I('get.ID');
        if(!empty($ID)){
            $Info=M(self::T_TABLE)->alias('a')
                ->join('left join xb_sys_administrator c on c.ID=a.OperatorID')
                ->field('a.*,c.UserName')
                ->where(array('a.ID'=>$ID))
                ->find();
            $this->assign('Info',$Info);
        }
        $this->display();
    }


    /**
     * 发放优惠卷页面
     * @access   public
     * @param
     * @return
     */
    public function fafangjuan(){
        $ID=I("request.ID",0,'intval');
        $juanInfo=M(self::T_TABLE)->where(array('ID'=>$ID))->find();
        $this->assign(array(
            'juanInfo'  =>$juanInfo,
            'ID'        =>$ID,
        ));
        $this->display();
    }


    /**
     * 发放优惠卷页面 搜索会员
     * @access   public
     * @param
     * @return
     */
    public function userlist(){
        $UserName = I('get.UserName','','trim');
        $Mobile = I('get.Mobile','','trim');
        $this->assign(array(
            'UserName'=>$UserName,
            'Mobile'=>$Mobile,
        ));
        $this->display();
    }

    /**
     * 发放优惠卷页面 搜索获取搜索会员数据
     * @access   public
     * @param
     * @return
     */
    public function userdatas(){
        $UserName   = I("request.UserName",'','trim');
        $Mobile     = I("request.Mobile",'','trim');
        if($UserName){
            $where['UserName'] = array('like',"%".$UserName."%");
        }
        if($Mobile){
            $where['Mobile'] = array('like',"%".$Mobile."%");
        }
        $table=self::T_MEMINFO;
        //查询的列名
        $col='UserName,ID,Mobile,TrueName';

        $where['Status'] =array('neq','3');
        $where['IsDel'] =array('eq','0');
        $page=I('request.page',1,'intval');
        $rows=I('request.rows',10,'intval');
        $sort='ID DESC';

        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList($table,$where,$page,$rows,$sort,$col);
        $this->ajaxReturn($array);
    }



    /**
     * 发放优惠卷页面 获取选中的会员页面
     * @access   public
     * @param
     * @return
     */
    public function ajax(){
        $ids=I('get.IDs','','trim');
        $rid=I('get.rid');
        if($rid){
            $ids_array=explode(',',$ids);
            $key=array_keys($ids_array,$rid);
            unset($ids_array[$key[0]]);
            $ids=implode(',',$ids_array);
        }

        if($ids==''){
            F('UsersID','0');
        }else{
            F('UsersID',$ids);
        }

        $this->assign('ids',$ids);
        $this->display();
    }


    /**
     * 发放优惠卷页面 搜索获取搜索会员数据
     * @access   public
     * @param
     * @return
     */
    public function getuserData(){
        $ids=I('get.IDs');

        $table=self::T_MEMINFO;
        //查询的列名
        $col='UserName,ID,Mobile,TrueName';

        $page=I('request.page',1,'intval');
        $rows=I('request.rows',10,'intval');
        $sort='ID DESC';
        $where['ID']=array('in',$ids);

        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList($table,$where,$page,$rows,$sort,$col);
        $this->ajaxReturn($array);
    }

    /**
     * 发放优惠卷页面 发送优惠卷
     * @access   public
     * @param
     * @return
     */

    public function sendjuan(){
        $juan_id  =I('get.id',0,'intval');
        $juan_num =I('get.num',0,'intval');
        $user_ids =F('UsersID');
        $StartMoney=I("request.StartMoney",'0','intval');
        $StartTime=I("request.StartTime",'','trim');
        $EndTime=I("request.EndTime",'','trim');

        if(!$juan_id){
            $this->ajaxReturn(0,'请选择优惠券');
        }
        if($juan_num<=0){
            $this->ajaxReturn(0,'请填写赠送数量');
        }
        if($user_ids<=0){
            $this->ajaxReturn(0,'请选择发放的会员');
        }
        $juanInfo=M(self::T_TABLE)->where(array('ID'=>$juan_id,'IsDel'=>0))->find();
        if(!$juanInfo){
            $this->ajaxReturn(0,'没有该优惠卷的信息');
        }
        if($juanInfo['Status']==0){
            $this->ajaxReturn(0,"该优惠券已被禁用，无法发放");
        }
        if(!is_num($StartMoney)){
            $this->ajaxReturn(0,'惠劵使用起始金额必须为数字');
        }
        if(!$StartTime){
            $this->ajaxReturn(0,'请输入优惠劵有效期的起始时间');
        }
        if(!$EndTime){
            $this->ajaxReturn(0,'请输入优惠劵有效期的结束时间');
        }
        if($StartTime>$EndTime){
            $this->ajaxReturn(0,'优惠劵有效期时间设置不合理');
        }

        $id_array=explode(',',$user_ids);
        //开启事务
        $model=M();
        $model->startTrans();

        for($j=0;$j<count($id_array);$j++){
            $addData=array();
            for($k=0;$k<$juan_num;$k++){
                $addData[]=array(
                    'GetType'   => 1,
                    'CoupansID' => $juan_id,
                    'UserID'    => $id_array[$j],
                    'Title'    => $juanInfo['Title'],
                    'Money'    => $juanInfo['Money'],
                    'Status'    => $juanInfo['Status'],
                    'StartMoney'    => $StartMoney,
                    'StartTime'    => $StartTime,
                    'EndTime'    => $EndTime,
                    'Isuser'    => 1,
                    'IsDel'    => 0,
                    'AddTime'   => date('Y-m-d H:i:s'),
                    'AddUserID'=> $_SESSION['AdminInfo']['AdminID'],
                );
            }
            $res=M(self::T_MEMCOUPANS)->addAll($addData);
            if(!$res){
               //事务失败回滚
                $model->rollback();
                $this->ajaxReturn(0,"发放失败");
            }
        }

        //成功与否 删除记录
        F('UsersID',NULL);

        //事务成功则提交
        $model->commit();
        $this->ajaxReturn(1,'成功发放');
    }

}