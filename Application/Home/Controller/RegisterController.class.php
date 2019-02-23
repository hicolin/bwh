<?php
/**
 * 功能说明: 注册控制器
 */

namespace Home\Controller;

class RegisterController extends HomeController
{

    const T_TABLE = 'mem_info';

    //注册页面
    public function index()
    {
        $referee = $_GET['ui'];
        if ($referee) {
            $this->assign('referee', $referee);
        }
        $this->assign('title', '注册');
        $this->display();
    }


    public function login()
    {
        if (I('post.')) {
            $Mobile = I("post.tel", '', "trim");
            $Password = I("post.pwd", '', "trim");
            //校验手机号
            if (!$Mobile) {
                $this->ajaxReturn(0, "请填写手机号码");
            }
            if (!is_mobile($Mobile)) {
                $this->ajaxReturn(0, "手机号码格式不正确！");
            }
            //校验密码
            if (!$Password) {
                $this->ajaxReturn(0, "请输入您的密码");
            }
            if (strlen($Password) < 6) {
                $this->ajaxReturn(0, "密码必须是6位以上数字的组合");
            }
            $psd = M('mem_info')->where(array('Mobile' => $Mobile, 'IsDel' => '0'))->find();
            if (!$psd) {
                $this->ajaxReturn(0, "您还未注册！");
            }

            if ($psd['Password'] == getGroupMd5($Password)) {
                //session和cookie失效，用token和expire操作
                $token = date('ymd') . rand(1, 9) . date('His') . rand(111, 999);
                $expire = time() + 3 * 24 * 3600;
                $data['Token'] = $psd['ID'];
                $data['Expire'] = $expire;
                M('mem_info')->where(array('Mobile' => $Mobile, 'IsDel' => '0'))->save($data);
                $psd['token'] = $psd['ID'];
                $this->ajaxReturn(1, "登录成功！", $psd);
            } else {
                $this->ajaxReturn(0, "密码错误，请重新输入！");
            }
        } else {
            $this->ajaxReturn(0, "非ajax请求！");
        }

    }

    public function loginfast()
    {
        if ($_POST) {
            $Mobile = I("post.mobile", '', "trim");
            $MsgCode = I("post.code", '', "trim");
            //校验手机号
            if (!$Mobile) {
                $this->ajaxReturn(0, "请填写手机号码");
            }
            if (!is_mobile($Mobile)) {
                $this->ajaxReturn(0, "手机号码格式不正确！");
            }
            //校验短信验证码
            if (!$MsgCode) {
                $this->ajaxReturn(0, "请输入短信验证码");
            }
            $code_tel = $_SESSION['mobile'];
            $code_num = $_SESSION['code'];
            if ($code_tel != $Mobile || $code_num != $MsgCode) {
                $this->ajaxReturn(0, "手机验证码错误，请重新输入!");
            } else {
                $psd = M('mem_info')->where(array('Mobile' => $Mobile, 'IsDel' => '0'))->find();
                session('uid', $psd['ID']);
                $flag = md5($Mobile . $psd['Password']);
                $lifetime = time() + 7 * 24 * 3600;
                setcookie('mobile', $psd['Mobile'], $lifetime, '/');
                setcookie('flag', $flag, $lifetime, '/');
                $this->ajaxReturn(1, "登录成功！");
            }

        }
        $this->assign('title', '快捷登录');
        $this->display();
    }

    /**
     * 找回密码
     */
    public function forgetpsd()
    {
        if (IS_POST) {
            $Mobile = I("post.mobile", '', "trim");
            $MsgCode = I("post.code", '', "trim");
            $Password = I("post.pwd", '', "trim");
            //校验手机号
            if (!$Mobile) {
                $this->ajaxReturn(0, "请填写手机号码");
            }
            if (!is_mobile($Mobile)) {
                $this->ajaxReturn(0, "手机号码格式不正确！");
            }
            //校验短信验证码
            if (!$MsgCode) {
                $this->ajaxReturn(0, "请输入短信验证码");
            }
            $data = file_get_contents('./log/' . $MsgCode . '.txt');
            $data = json_decode($data, true);
            $code_tel = $data['mobile'];
            $code_num = $data['code'];
            if ($code_tel != $Mobile || $code_num != $MsgCode) {
                $this->ajaxReturn(0, "手机验证码错误，请重新输入!");
            }
            //校验密码
            if (!$Password) {
                $this->ajaxReturn(0, "请输入您的密码");
            }
            if (strlen($Password) < 6) {
                $this->ajaxReturn(0, "密码必须是6位以上数字的组合");
            }
            $newPassword = getGroupMd5($Password);
            $res = M('mem_info')->where(array('Mobile' => $Mobile))->save(array('Password' => $newPassword));
            if ($res !== false) {
                unlink('./log/' . $MsgCode . '.txt');
                $this->ajaxReturn(1, "密码已重置，前往登录页面！");
            } else {
                $this->ajaxReturn(0, "重置失败！");
            }
        }
    }


    public function validatetel()
    {
        $tel = I('get.mobile', '');
        $res = M('mem_info')->where(array('Mobile' => $tel))->count('ID');
        if ($res) {
            $this->ajaxReturn(0, "该手机号码已注册过会员，请直接登录！");
        } else {
            $this->ajaxReturn(1, "该手机号码还未注册过会员，请先注册！");
        }
    }

    //渠道推广注册页面
    public function tuiguang()
    {
        $puser = I('get.puser', '');
        global $BasicInfo;
        $this->assign(array(
            'puser' => $puser,
            'Downurl' => $BasicInfo['Downurl']
        ));
        $this->display();
    }

    /*
     * 后台处理ajax传递的数据
     */
    public function ajaxRegister()
    {
        if (!IS_POST) {
            $this->ajaxReturn(0, "数据传递方式错误！");
        }
        $Mobile = I("post.tel", '', "trim");
        $Password = I("post.pwd", '', "trim");
        $MsgCode = I("post.code", '', "trim");
        //校验手机号
        if (!$Mobile) {
            $this->ajaxReturn(0, "请填写手机号码");
        }
        if (!is_mobile($Mobile)) {
            $this->ajaxReturn(0, "手机号码格式不正确！");
        }
        $exit = M('mem_info')->where(array('IsDel' => 0, 'Mobile' => $Mobile))->count('ID');
        if ($exit) {
            $this->ajaxReturn(0, "该手机号码已注册过会员，不能重复使用！");
        }
        //校验短信验证码

        if (!$MsgCode) {
            $this->ajaxReturn(0, "请输入短信验证码");
        }
        $data = file_get_contents('./log/' . $MsgCode . '.txt');
        $data = json_decode($data, true);
        $code_tel = $data['mobile'];
        $code_num = $data['code'];
        if ($code_tel != $Mobile || $code_num != $MsgCode) {
            $this->ajaxReturn(0, "手机验证码错误，请重新输入!");
        }
        //校验密码
        if (!$Password) {
            $this->ajaxReturn(0, "请输入您的密码");
        }
        if (strlen($Password) < 6) {
            $this->ajaxReturn(0, "密码必须是6位以上数字的组合");
        }
        $data = array(
            "NickName" => $Mobile,
            "UserName" => $Mobile,
            "TrueName" => '',
            "Password" => getGroupMd5($Password),
            "Mobile" => $Mobile,
            "RegTime" => date("Y-m-d H:i:s"),
            'Tjcode' => $Mobile,
            'Retype' => getSystem(),
        );
        //推荐人的id
        $Referee = I('post.Referee', 0, 'intval');
        if ($Referee) {
            $exit_refer = M('mem_info')->where(array('ID' => $Referee, 'IsDel' => 0))->count('ID');
            if ($exit_refer) {
                $data['Referee'] = $Referee;
            }
        }
        //渠道推广
        $puser = $_POST['puser'];
        if ($puser) {
            $TgadminID = M('tg_admin')->where(array('UserName' => $puser, 'Status' => '1', 'IsDel' => '0'))->getField('ID');
            if ($TgadminID) {
                $data['TgadminID'] = $TgadminID;
            }
        }
        $result = M('mem_info')->add($data);
        if ($result) {
            //更新 会员账号(UID_ID)  字段
            M('mem_info')->where(array('ID' => $result))->save(array('MemAccount' => 'UID_' . $result));
            send_mem_notics($result, "欢迎成为本站会员，请妥善保管自己的账号。");
//             self_sendjuan($result,'1');//注册成功立送
            if ($Referee) {
//                self_sendjuan($result,'3');//每邀请1名好友注册立送
            }
            unlink('./log/' . $MsgCode . '.txt');
            $this->ajaxReturn(1, "注册成功!", $data);
        } else {
            $this->ajaxReturn(0, "注册失败!");
        }
    }


    //退出登录
    public function logout()
    {
        $data = I('post.');
        $member = M('mem_info')->where($data)->find();
        session('uid', null);
        if ($member) {
//            $re['Token'] = '';
//            $re['Expire'] = '';
//            M('mem_info')->where($data)->save($re);
            $this->ajaxReturn('1', '成功');
        } else {
            $this->ajaxReturn('1', '成功');
        }
    }

    //注册协议
    public function regdetail()
    {
        $infos = M('sys_contentmanagement')->where(array('ID' => '9'))->field('Contents')->find();
        $content = htmlspecialchars_decode($infos['Contents']);
        $this->assign('content', $content);
        $this->display();
    }

    /**
     * 判断验证码是否正确
     */
    public function pwd()
    {
        if(!IS_POST){
            $this->ajaxReturn('0', '非法请求');
        }
        $data = I('post.');
        $member = M('mem_info')->where(array('Token'=>$data['Token'],'IsDel'=>'0'))->find();
        $pwd = getGroupMd5($data['pwd']);
        if($pwd != $member['Password']){
            $this->ajaxReturn('0', '原始密码不对',$pwd, $member['Password']);
        }
        $this->ajaxReturn('1', '成功');
    }

    /**
     * 判断验证码是否正确
     */
    public function mpwd()
    {
        if(!IS_POST){
            $this->ajaxReturn('0', '非法请求');
        }
        $data = I('post.');
        $pwd = getGroupMd5($data['pwd']);
        $res = M('mem_info')->where(array('Token'=>$data['Token'],'IsDel'=>'0'))->save(array('Password' => $pwd));
        if($res){
            $this->ajaxReturn('1', '成功');
        }else{
            $this->ajaxReturn('0', '失败');
        }

    }

}
