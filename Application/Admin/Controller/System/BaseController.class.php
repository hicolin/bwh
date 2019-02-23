<?php
/**

 * 功能说明：通用增删改查操作 登录验证
 */
namespace Admin\Controller\System;
use Org\Util\Ucpaas;
use Think\Controller;
class BaseController extends Controller {

    /*
     * 初始化函数，执行任何操作前都会执行
     */
    public function _initialize() {

		//判断数据库链接
		if(!check_mysql()){
			header("Location:http://".$_SERVER['HTTP_HOST']."/remind/1006.html");
			exit();
		}

		//检测登录状态是否过期
		if(empty($_SESSION['AdminInfo']['Admin'])) {
			$this->redirect('System/Login/login');
		}else{
            //如果session不为空，检测登录状态是否过期
            $last_time=strtotime($_SESSION['AdminInfo']['LastLoginTime']);
            $current_time=strtotime(date("Y-m-d H:i:s"));
            $active_time=get_basic_info('Session'); //单位:分钟
            if(($current_time-$last_time)/60>$active_time){
                //已过期重新登录
                session('AdminInfo',null);
                $this->redirect('System/Login/login');
            }else{
                //未过期更新过期时间
                $_SESSION['AdminInfo']['LastLoginTime']=date('Y-m-d H:i:s',time());
            }
        }
		//判断当前是否拥有将要执行的操作的权限
        if(!is_permission()){
            //没有权限操作
            echo "<br/>403错误:<br/>很抱歉，您没有此操作的操作权限！";
            exit();

        }

        //记录具体的操作日志，排查列表展示等不影响数据变化的操作
        \Think\Hook::add('ActionLog','Admin\\Behavior\\LogBehavior');
        \Think\Hook::listen('ActionLog');
    }

    /**
     * AJAX返回数据标准
     * @param int $status  状态
     * @param string $msg  内容
     * @param mixed $data  数据
     * @param string $dialog  弹出方式
     */
    protected function ajaxReturn($status = 0, $msg = '成功', $data = '', $dialog = '')
    {
        $return_arr = array();
        if (is_array($status)) {
            $return_arr = $status;
        } else {
            $return_arr = array(
                'result' => $status,
                'message' => $msg,
                'des' => $data,
                'dialog' => $dialog
            );
        }
        ob_clean();
        echo json_encode($return_arr);
        exit;
    }
    /**
     * 腾讯云短信
     */
    public function getYun($mobile, $orderid , $money){
        $ParaList=M('sys_inteparameter')->where(array('IntegrateID'=>18))->getField('Name,ParaValue');
        $appId=$ParaList['APPID'];
        $key = $ParaList['AppKey'];
        $template = $ParaList['templateId2'];
        $sj = $money;
        $curTime = time();
        $random = $orderid;
        $wholeUrl = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid={$appId}&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . "86";
        $tel->mobile = "" . $mobile;
        $data->tel = $tel;
        $data->sig = hash("sha256",
            "appkey={$key}&random=" . $random . "&time="
            . $curTime . "&mobile=" . $mobile, FALSE);
        $data->tpl_id = $template;
        $data->params = array($random, $sj);
        $data->time = $curTime;
        //$data->sign = '云肆网络';//如果只有一个则不需要签名
        $data->extend = '';
        $data->ext = '';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $wholeUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($curl);
        $res = json_decode($ret, true);

        $para['code'] = $random;
        $para['code_time'] = time()+180;
        $para['mobile'] = $mobile;
        if ($res['errmsg'] == 'OK') {//发送成功
            file_put_contents('./log/'.$random.'.txt',json_encode($para));
            $this->ajaxReturn(1,'发送成功,请注意查收！',$random);
        } else {
            $this->ajaxReturn(0,'发送失败,请重新尝试！');
        }
    }
  
     public function getIdcard($uid){
        $status=M('renzen_cards')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getBank($uid){
        $status=M('renzen_bank')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getSocial($uid){
        $status=M('renzen_social')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getTel($uid){
        $status=M('renzen_mobile')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getTaobao($uid){
        $status=M('renzen_taobao')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getAlipay($uid){
        $status=M('renzen_alipay')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

    public function getZhima($uid){
        $status=M('renzen_zhima')->field('Status')->where(array('UserID'=>$uid))->find();
        return $status['Status'];
    }

}