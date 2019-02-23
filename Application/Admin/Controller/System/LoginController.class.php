<?php
/**
 * 登录功能
 */
namespace Admin\Controller\System;
use Think\Controller;
use XBCommon\CacheData;
use Think;
class LoginController extends Controller {

    const T_ADMINISTATOR = 'sys_administrator';
    const T_ROLE = 'sys_role';
    const T_BASEICINFO = 'sys_basicinfo';

    public function _initialize(){
        //判断数据库链接
        if(!check_mysql()){

            header("Location:http://".$_SERVER['HTTP_HOST']."/remind/1006.html");

            exit();
        }
    }

	public function login(){

        //判断是否MAC地址验证
        $mac_status = M(self::T_BASEICINFO)->where('ID = 1')->getField('MAC');
        $this->assign("mac_status",$mac_status);

        //判断是否已登录
        if(session('AdminInfo')){
            redirect(__APP__);
        }

        if(!isset($_SESSION)){session_start();}
		$this->display();
	}
	//验证码
	public function selfverify(){
		$config =    array(    
		    'fontSize'    =>    30,    // 验证码字体大小 
		    'length'      =>    4,     // 验证码位数
		    'useNoise'    =>    false, // 关闭验证码杂点
		);
		    $Verify =     new Think\Verify($config);
		    $Verify->codeSet = '0123456789';
		    ob_end_clean();
		    $Verify->entry();
	}
	
	//登录操作 检测用户的用户名和密码是否正确
	public function doLogin(){

        //登录信息接收
        $username=I('post.UserName');
        $password=I('post.UserPsd');
        $loginmac=I('post.MacAddress');
        $loginip=get_client_ip();

        //为空判断
        if(!$username){
            $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，账户名称必须填写！'));
        }
        if(!$password){
            $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，用户密码必须填写！'));
        }

		//查询登陆者是否存在系统中，并将存在的信息保存在array中。
        $where=array(
            'UserName'=>$username,
            'Status'=>1,
            'IsDel'=>0
        );
        $user=M(self::T_ADMINISTATOR)->where($where)->find();

        if(!$user==null){
            //如果账号存在，判断密码是否正确
            if($user['Password']==md5($password)){
                //账号密码正确时，判断登陆者状态
                if($user['IsDel']==1){
                    $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，该账号已被删除，请联系管理员！'));
                }
                if($user['Status']==0){
                    $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，该账号被禁用，请联系管理员！'));
                }
                //账号密码正确时，判断登陆者角色状态
                $role=M(self::T_ROLE)->where(array('IsDel'=>0,'Status'=>1,'ID'=>(int)$user['RoleID']))->find();

                if($role==null){
                    $this->ajaxReturn(array('result'=>false,'message'=>'登录失败，管理员角色受限，请联系管理员！'));
                }

                //获取基本设置缓存
                $cache=new CacheData();
                $info=$cache->BasicInfo();

                //登录密码正确时，禁止登录限定时间,代码逻辑在检查下【缓存获取点】
                if((strtotime($user['ErrorTime']) + $info['PsdErrorTime']*60) > time()){
                    $this->ajaxReturn(array('result'=>false,'message'=>'上次密码输入错误,系统为防止暴力破解, 请'.$info['PsdErrorTime'].'分钟后再试! '));
                }

                //账号密码验证成功，记录IP,MAC最后登录地址
                if(!empty($loginip)){
                    M(self::T_ADMINISTATOR)->where(array('ID'=>$user['ID']))->save(array('LoginIP'=>$loginip));
                }
                if(!empty($loginmac)){
                    M(self::T_ADMINISTATOR)->where(array('ID'=>$user['ID']))->save(array('LoginMAC'=>$loginmac));
                }

                //账号密码正确时，判断是否启用了MAC或者IP绑定【缓存获取点】
                if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])){   //代理IP是否为空
                    if($user['ID'] == 1){  //内置超级管理员
                        if($loginip !== $this->serverIp() && $loginip !== '0.0.0.0'){   //内置管理员账号判断
                            $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，你没有权限登录 '));
                        }
                    }else{
                        if($info['IP'] == 1){     //IP验证开启时验证IP地址
                            if($loginip !== $user['BindIP'] || empty($user['BindIP'])){
                                $this->ajaxReturn(array('result'=>false,'message'=>'后台开启了IP地址绑定,请联系管理员绑定IP地址!'));
                            }
                        }
                        if($info['MAC'] == 1){ //MAC验证开启时验证Mac地址，IE下有效(插件)
                            if($loginmac !== $user['BindMAC'] || empty($user['BindMAC'])){

                                $this->ajaxReturn(array('result'=>false,'message'=>'后台开启了MAC地址绑定,请联系管理员绑定MAC地址!'));
                            }
                        }
                    }
                }else{
                    $this->ajaxReturn(array('result'=>false,'message'=>'很抱歉，非法登录! '));
                }

                //账号密码正确时，判断登录错误次数 【缓存获取点】
                if($user['ErrorCount']>=$info['PsdErrorCount'] && $info['PsdErrorCount']<>0){
                    $this->ajaxReturn(array('result'=>false,'message'=>'登录失败，密码错误次数超过限制，请联系管理员！'));
                }
                //以上判断全部通过时,登录成功，更新登录信息
                $data['IpCity'] = ip_to_address($loginip);  //根据登录者IP获取登录城市
                $data['LoginCount'] = $user['LoginCount'] + 1; //记录登陆次数

                $data['ErrorCount'] = 0;  //登录错误次数清零
                $data['LoginTime'] = date('Y-m-d H:i:s',time()); //更新登录时间
                $state=M(self::T_ADMINISTATOR)->where(array('ID'=>$user['ID']))->save($data);

                if($state){
                    //更新成功，刷新session
                    $admininfo['Admin'] = $user['UserName'];
                    $admininfo['AdminID'] = $user['ID'];
                    $admininfo['TrueName'] = $user['TrueName'];
                    $admininfo['RoleID'] = $user['RoleID'];
                    $admininfo['RoleName']=M(self::T_ROLE)->where(array('ID'=>(int)$user['RoleID']))->getField('Name');//角色名称还未用到
                    $admininfo['LastLoginTime']=$data['LoginTime'];
                    session('AdminInfo',$admininfo);
                    $this->ajaxReturn(array('result'=>true,'des'=>__APP__));
                }else{
                    //更新数据库失败
                    $this->ajaxReturn(array('result'=>false,'message'=>'更新登录信息错误，请联系管理员! '));
                }
            }else{
                //如果账号存在但密码错误，登录失败
                $data['ErrorCount']=$user['ErrorCount'] + 1; //更新密码错误次数
                $data['ErrorTime']=date('Y-m-d H:i:s',time()); //更新密码错误时间
                $state=M(self::T_ADMINISTATOR)->where(array('ID'=>$user['ID']))->save($data);
                if($state){
                    //密码错误给予提示
                    $this->ajaxReturn(array('result'=>false,'message'=>'登录失败，密码错误! '));
                }else{
                    //更新数据库失败
                    $this->ajaxReturn(array('result'=>false,'message'=>'更新登录信息错误，请联系管理员! '));
                }
            }
        }else{
            //登录账号不存在
            $this->ajaxReturn(array('result'=>false,'message'=>'登录失败，用户名不存在！'));
        }
	}


	//退出登陆
	public function logout(){
		session('AdminInfo',null);
		echo json_encode(array('result'=>true,'des'=>U('System/Login/login')));
		
	}



    //获取服务器端IP
    private function serverIp(){
        // if(isset($_SERVER)){
        //     if($_SERVER['SERVER_ADDR']){
        //         $server_ip=$_SERVER['SERVER_ADDR'];
        //     }else{
        //         $server_ip=$_SERVER['LOCAL_ADDR'];
        //     }
        // }else{
        //     $server_ip = getenv('SERVER_ADDR');
        // }
        // return $server_ip;
        return gethostbyname($_SERVER['SERVER_NAME']);

    }


}