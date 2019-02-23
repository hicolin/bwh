<?php
/**
 * @功能说明：  后台首页控制类
 */
namespace Admin\Controller\System;
use XBCommon;
use XBCommon\CacheData;

class BasicinfoController extends BaseController {

    const T_TABLE = 'sys_basicinfo';

    /**
     * 加载基本信息页
     */
    public function index(){
    	$this->display();
    } 
    //后台默认首页
    public function home(){
     
        $nowdata=date('Y-m-d');
        //会员总数 今日
        $mem_count=M('mem_info')->where(array('IsDel'=>'0','RegTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59')))->count('ID');
        //借款单数 今日
        $apply_count=M('loans_applylist')->where(array('IsDel'=>'0','ApplyTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59')))->count('ID');
        //放款单数 今日
        $fk_count=M('loans_applylist')->where(array('IsDel'=>'0','OpenTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'LoanStatus'=>array('in',array('2','3'))))->count('ID');
        //还款单数
        $hk_count=M('loans_hklist')->where(array('IsDel'=>'0','HkTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'PayStatus'=>'1','Status'=>'1'))->count('ID');

        //-------渠道信息
        //注册量  今日
        $q_memcount=M('mem_info')->where(array('IsDel'=>'0','RegTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'TgadminID'=>array('GT','0')))->count('ID');
        //进件量  今日
        $q_applycount=M('loans_applylist')->alias('a')
                      ->join('left join xb_mem_info b on a.UserID=b.ID')
                      ->where(array('a.IsDel'=>'0','a.ApplyTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'b.TgadminID'=>array('GT','0')))->count('a.ID');
        //放款量  今日
        $q_fkcount=M('loans_applylist')->alias('a')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.IsDel'=>'0','a.OpenTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'a.LoanStatus'=>array('in',array('2','3')),'b.TgadminID'=>array('GT','0')))->count('a.ID');
        //拒绝量  今日
        $q_jjcount=M('loans_applylist')->alias('a')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.IsDel'=>'0','a.ShTime'=>array('between',$nowdata.' 00:00:00'.','.$nowdata.' 23:59:59'),'a.LoanStatus'=>array('in',array('5')),'b.TgadminID'=>array('GT','0')))->count('a.ID');

        //----------------统计12天之内的数据-----------
        //放款金额
      
        $applyArr=array();
        for($s=14;$s>=0;$s--){
            $current='';
            $fangk_money='';
            $fangkArr=array();
            $current=date('Y-m-d',strtotime("-".$s."day",strtotime($nowdata)));
            $fangk_money=M('loans_applylist')->where(array('IsDel'=>'0','OpenTime'=>array('between',$current.' 00:00:00'.','.$current.' 23:59:59'),'LoanStatus'=>array('in',array('2','3'))))->sum('OpenM');

            $fangkArr['date']=date('m-d',strtotime($current));
            if($fangk_money){
                $fangkArr['money']=$fangk_money;
            }else{
                $fangkArr['money']='0';
            }
            $fkmoneyArr[]=$fangkArr;
        }
        //还款金额
        $spArr=array();
        for($sp=14;$sp>=0;$sp--){
            $current='';
            $sp_count2='';
            $dataArr=array();
            $current=date('Y-m-d',strtotime("-".$sp."day",strtotime($nowdata)));
            $sp_count2=M('loans_hklist')->where(array('IsDel'=>'0','HkTime'=>array('between',$current.' 00:00:00'.','.$current.' 23:59:59'),'PayStatus'=>'1','Status'=>'1'))->sum('TotalMoney');

            $dataArr['date']=date('m-d',strtotime($current));
            if($sp_count2){
                $dataArr['money']=$sp_count2;
            }else{
                $dataArr['money']='0';
            }
            $huankArr[]=$dataArr;
        }
        $this->assign(array(
            'mem_count'=>$mem_count,
            'apply_count'=>$apply_count,
            'fk_count'=>$fk_count,
            'hk_count'=>$hk_count,
            'q_memcount'=>$q_memcount,
            'q_applycount'=>$q_applycount,
            'q_fkcount'=>$q_fkcount,
            'q_jjcount'=>$q_jjcount,
            'fkmoneyArr'=>$fkmoneyArr,
            'huankArr'=>$huankArr,
            ));
   
        $this->display('home');
    }
    //登录后台默认页面2
    public function home2(){

        $LoginInfo['Admin']=$_SESSION['AdminInfo']['Admin'];
        $LoginInfo['RoleName']=$_SESSION['AdminInfo']['RoleName'];

        $this->assign("LoginInfo",$LoginInfo);

        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $VERSION = $Model->query("select VERSION()");
        //print_r($VERSION[0]);

        $this->assign("VERSION",$VERSION);
        //var_dump($VERSION);

        $this->display();
    }
   /**
     * 获取系统基础配置信息
     */
    public function shows(){
        $where="ID=1";
        $model=M(self::T_TABLE);
        $result=$model->where($where)->find();
        $this->ajaxReturn($result);
    }
	
    /**
     * 保存系统配置信息
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('SystemName','require','系统名称必须填写！'), //默认情况下用正则进行验证
                array('SystemDomain','require','系统域名必须填写！'), //默认情况下用正则进行验证
            );
            //处理表单接收的数据
            $model=D(self::T_TABLE);

            $data=$model->validate($rules)->create();

            if(!isset($data['IP'])){
                $data['IP'] = 0;
            }

            if(!isset($data['MAC'])){
                $data['MAC'] = 0;
            }

            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //判断是否有修改的权限，此处暂时只判断是否为管理员操作
                if($_SESSION['AdminInfo']['AdminID']==null){
                    $this->ajaxReturn(0, '对不起,您没有操作的权限！');
                }else{
                    //操作权限校验通过,执行后续保存动作
                    $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                    $data['UpdateTime']=date("Y-m-d H:i:s");
                    //保存或更新数据
                    $result=$model->where(array('ID'=>1))->save($data);

                    if($result>0){

                        //清除缓存
                        $cache=new XBCommon\XBCache();
                        $cache->Remove('BasicInfo');
                        $CacheData = new CacheData();
                        $CacheData->BasicInfo();
                        $this->ajaxReturn(1, '恭喜您,修改成功！');

                    }else{
                        $this->ajaxReturn(0, '修改失败，请检查数据库的更新记录！');
                    }
                }
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对，必须为POST方式！');
        }
    }

    /**
     * 功能说明:更新所有缓存
     */
    public function RefreshCache(){
        $cache=new XBCommon\CacheData();
        $cache->UpdateCache();
        echo '<font style="font-size:20px;">缓存更新成功！</font>';
    }
}