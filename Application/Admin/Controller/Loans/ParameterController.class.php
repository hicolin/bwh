<?php
/**


 * @作者：      叶程鹏
 * @修改日期：  2018-07-02 09:21
 * @功能说明：  贷款参数控制类
 */
namespace Admin\Controller\Loans;
 use Admin\Controller\System\BaseController;
use XBCommon;

class ParameterController extends BaseController {

    const T_TABLE = 'loans_parameter';


    /**
     * 加载基本信息页
     */
    public function index(){
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

}