<?php
/**
 * @功能说明:验证包名和版本号
 * @return string
 */
function common_package($client,$package,$ver){
    $Common_Package = F('Common_Package');

    if($Common_Package[$client][$ver] <> $package){
        $result = array('result'=>0,'msg'=>'未查找到想对应的包名和版本号');
    }else{
        $result = array('result'=>1,'msg'=>'success');
    }
    return $result;
}

function get_login_info($key){

    $para=get_json_data(1); //接收参数
    $AppInfo=\XBCommon\XBCache::GetCache($para['token']);

    if(!empty($AppInfo)){
        if(!empty($key)){
            return $AppInfo[$key];
        }else{
            return $AppInfo;
        }
    }else{
        return null;
    }
}

/**
 * @功能说明:处理成给定的json数据
 * @return $mark  为 0是返回用于一维  为1必须用于二维数组
 * @return $code  0-1   返回的状态描述
 * @return $msg  为返回内容
 */
function AjaxJson($mark = 0,$code = 0, $msg = 'success', $data = array(),$isaes = 0 ,$key='',$iv=''){
    $Array = array();
    if (is_array($code)) {
        $Array = $code;
    } else {
        if(!$msg){
            $msg = 'success';
        }
        if($isaes == 1){
            //if(!$key || !$iv){
                $key = get_login_info('KEY');
                $iv = get_login_info('IV');
            //}

            if($mark){
                $json = json_encode($data);
            }else{
                $json = json_encode($data,JSON_FORCE_OBJECT);
            }
            $data = encrypt_pkcs7($json,$key,$iv);
        }
        if($isaes == 1 && empty($data)){
            $data='abc';
        }
        $Array = array(
            'result' => $code,
            'data' => $data,
            'message' => $msg,
            'isaes' => $isaes,
            'mark' => $mark,
        );
    }
    if($mark == 0){
        exit(json_encode($Array,JSON_FORCE_OBJECT ));
    }else{
        exit(json_encode($Array ));
    }
}

/**
 * @功能说明: 加解密处理 [相关参数必须传递]
 * @return $code  为 0是加密 为1解密
 * @return $array  为密文字符串或者加密密文数组
 * @return $key  / $iv
 * @return $mark   用于加密的时候 是一维【0】还是二维【1】
 */
function  Ciphertext($code = 0,$array = array(),$key='',$iv='',$mark = 0){
    //加密
    if(!$key || !$iv){
        $key = get_login_info('KEY');
        $iv = get_login_info('IV');
    }
    if($code == 0){
        if(!$array){
            $array = array();
        }

        if($mark){
            $json = json_encode($array);
        }else{
            $json = json_encode($array,JSON_FORCE_OBJECT);
        }
        $str = encrypt_pkcs7($json,$key,$iv);
    }else{
    //解密

        $str = decrypt_pkcs7($array,$key,$iv);
        if(!$str){
            /*file_put_contents(
                'baoqianfeifashuju.log',
                '错误码:003 ['.date('Y-m-d H:i:s').']'.PHP_EOL.$_SERVER['REQUEST_URI'].PHP_EOL.
                '---KEY:'.$key.'---IV:'.$iv.'---UserID:'.PHP_EOL.
                '>>>>'.$array.'>>>>'.PHP_EOL,
                \FILE_APPEND
            );*/
            AjaxJson(0, 0, '抱歉，非法数据！');
        }

    }
    return $str;
}

/**
 * @功能说明: 判断用户及时信息
 * @return $uid
 */
function  getUserInfo($uid,$table='mem_info',$cache='UserInfo'){

    if(!$uid){
        AjaxJson(0, -1, '抱歉，未查找到相关的数据！');
    }

    $mem = S($cache.$uid);
    if(!$mem){
        $mem = M($table)->where('ID=%d and IsDel=0 ',$uid)->cache($cache.$uid,600)->find();
    }

    if($mem) {
        if ($mem['Status'] == 3) {
            S(get_login_info('token'),null);
            S($cache.$uid,null);
            AjaxJson(0, -1, '抱歉，账号已被禁用，禁止操作！');
        }
        return $mem;
    }else{
        S(get_login_info('token'),null);
        AjaxJson(0,-1,'很抱歉，未查找到相关的数据！');
    }
}
