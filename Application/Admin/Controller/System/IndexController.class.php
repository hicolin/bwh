<?php
namespace Admin\Controller\System;
use XBCommon;
class IndexController extends BaseController {

    const T_ROLEMENU = 'sys_rolemenu';
    const T_MENU = 'sys_menu';
    const T_ADMINISTRATOR = 'sys_administrator';

    /**
     * @功能说明：后台首页页面
     * @return [type]                           [description]
     */
    public function index(){
        //根据角色权限获取相应的菜单数据
        $cache=new XBCommon\CacheData();
        $menu=$cache->LeftMenu();

        $sys=M('sys_basicinfo')->where('id = 1')->find();

        //登录者信息
        $LoginInfo['Admin']=$_SESSION['AdminInfo']['Admin'];
        $LoginInfo['RoleName']=$_SESSION['AdminInfo']['RoleName'];
        $this->assign("menu_list",$menu);
        $this->assign("sys",$sys);
        $this->assign("LoginInfo",$LoginInfo);
		$this->display();
    }


    /**
     * @功能说明：修改密码
     * @return [type]                           [description]
     */
    public function modifypwd(){
        $this->display();
    }

    /**
     * 保存密码逻辑
     */
    public function Save(){
        $password=I('post.Password');
        $new=I('post.NewPWD');
        $confirm=I('post.ConfirmPWD');

        if($password == null){
            $this->ajaxReturn(false,'请先输入原始密码');
        }

        if($new == null || strlen($new)<5){
            $this->ajaxReturn(false,'新的密码不可为空，或长度不可小于6位');
        }

        if($new != $confirm){
            $this->ajaxReturn(false,'新密码和确认密码不一致');
        }

        $db=M(self::T_ADMINISTRATOR);
        $pwd=$db->where(array('ID'=>(int)$_SESSION['AdminInfo']['AdminID']))->getField('Password');

        if($pwd!=md5($password)){
            $this->ajaxReturn(false,'原始密码输入错误，修改失败');
        }else{
            $data['Password']=md5($new);
            $result=$db->where(array('ID'=>(int)$_SESSION['AdminInfo']['AdminID']))->save($data);

            if(false !== $result){
                $this->ajaxReturn(true,'密码修改成功！');
            }else{
                $this->ajaxReturn(true,'密码修改失败！');
            }
        }

    }

}