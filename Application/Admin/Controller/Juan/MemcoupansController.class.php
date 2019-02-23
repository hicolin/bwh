<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：叶程鹏
 * 修改时间：2017-09-08 13:40
 * 功能说明：会员优惠劵
 */
namespace Admin\Controller\Juan;
use XBCommon;
use Admin\Controller\System\BaseController;
class MemcoupansController extends BaseController {

    const T_TABLE = 'mem_coupans';
    const T_MEMINFO='mem_info';
    const T_ADMIN = 'sys_administrator';
    const T_GOODS = 'goods';
    // const T_PAYORDER   = 'order';
	/**
     * 会员优惠劵信息列表
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
        
        $Mobile=I('post.Mobile','','trim');
        if($Mobile){
            $idArr=M(self::T_MEMINFO)->where(array('Mobile'=>array('eq',$Mobile)))->getField('ID',true);
            if($idArr){
                $where['UserID'] = array('in',$idArr);
            }else{
                $where['UserID'] = '';
            }
        }
        $GetType=I('post.GetType','-5','intval');
        if($GetType!='-5'){
            $where['GetType']=array('eq',$GetType);
        }
        $Isuser=I('post.Isuser','-5','intval');
        if($Isuser!='-5'){
            $where['Isuser']=array('eq',$Isuser);
        }
        $where['IsDel']=array('NEQ','2');
        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                $val['MemAccount']=$meminfo['MemAccount'];
                $val['TrueName']=$meminfo['TrueName'];
                $val['Mobile']=$meminfo['Mobile'];
                switch ($val['GetType']){
                    case 1:
                        $val['GetType']="平台发放";
                        $val['AddUserID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['AddUserID']),'TrueName');
                        break;
                    case 2:
                        $val['GetType']="会员领取";
                        if($val['EndTime']<date('Y-m-d')){
                            $val['EndTime']=$val['EndTime']."<span style='color:red;'>【已过期】</span>";
                        }
                        $val['AddUserID']="会员领取";
                        break;
                }
                $val['StartMoney']="满".$val['StartMoney']."元立减".$val['Money']."元";
                if($val['Oid']==0){
                    $val['Oid']='';
                }else{
                    //$val['Oid']=$query->GetValue(self::T_PAYORDER,array('ID'=>(int)$val['Oid']),'OrderSn');
                    $val['Oid']=M('loans_applylist')->where(array('CouponID'=>$val['Oid']))->getField('OrderSn');

                }
                if($val['Gid']==0){
                    $val['Gid']='';
                }else{
                    $val['Gid']=$query->GetValue(self::T_GOODS,array('ID'=>(int)$val['Gid']),'SalePrice').'元借款';
                }
                $val['Money']=number_format($val['Money'],2)."元";
                if($val['IsDel']=='1'){
                    $val['Money']=$val['Money'].'<span style="color:red;">[删]</span>';
                }
                if($val['Isuser']=='1'){
                    $val['Isuser']="<span style='color:orange;'>未使用</span>";
                }elseif($val['Isuser']=='2'){
                    $val['Isuser']="<span style='color:green;'>已使用</span>";
                }

                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }
    /**
     * 数据删除处理 单条或多条  逻辑删除
     */
    public function Del(){
        $mod = D(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $where['ID']=array('in',$ids);
        $res=$mod->where($where)->setField('IsDel',2);
        if ($res) {
            $this->ajaxReturn(true, "用户删除数据成功！");
        } else {
            $this->ajaxReturn(false, "用户删除数据时出错！");
        }
    }

}