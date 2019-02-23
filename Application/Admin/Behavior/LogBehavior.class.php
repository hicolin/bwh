<?php
/**
 * 
 *
 * 作    者:
 * 修改时间: 2017-06-12 13:36
 * 功能说明:
 */
namespace Admin\Behavior;

class LogBehavior extends \Think\Behavior
{

    public function run(&$param){

        $log = I('request.log',0,'intval');
        $ID = I('get.ID',0,'intval');
        if($log){
            return true;
        }
        if(strtolower(ACTION_NAME) == 'shows' && $ID==0){
            return true;
        }

        $action = array('index','datalist','list','home');

        if(!in_array(strtolower(ACTION_NAME),$action)){

            $ip = get_client_ip();
            $BackData = $_REQUEST;

            if(!ip_to_address($ip)){
                $IPCity = '';
            }else{
                $IPCity = ip_to_address($ip);
            }

            $data = array(
                'ip'=>$ip,
                'UserName'=>$_SESSION['AdminInfo']['Admin'],
                'IPCity'=>$IPCity,
                'TrueName'=>$_SESSION['AdminInfo']['TrueName'],
                'AdminID'=>$_SESSION['AdminInfo']['AdminID'],
                'BackData'=>json_encode($BackData),
            );
            Admin_Log($data);
        }
    }



}