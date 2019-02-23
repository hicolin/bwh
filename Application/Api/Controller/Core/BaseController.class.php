<?php
namespace Api\Controller\Core;
use Think\Controller\RestController;
use XBCommon\XBCache;

class BaseController extends RestController{
    const T_TABLE = 'mem_info';

    //被继承的父类,检测提交POST的数据
    public function _initialize()
    {
        header('Access-Control-Allow-Origin:'."*");
        header("Access-Control-Allow-Credentials", "true");
        header("Access-Control-Allow-Methods", "*");
        header('Access-Control-Allow-Headers','*');

        //统一返回数据的字符编码
        header('Content-Type:application/json; charset=utf-8');

        if(IS_POST){
            
            //post 方式
            $para=get_json_data(1); //接收参数
            if(empty($para)){
                AjaxJson(0,0,'很抱歉，POST请求必须携带参数！');
            }
            if(empty($para['token'])){
                AjaxJson(0,-1,'很抱歉，token参数不能为空！');
            }
            if(empty($para['client'])){
                AjaxJson(0,0,'很抱歉，请填写客户端名称！');
            }
            if(empty($para['package'])){
                AjaxJson(0,0,'很抱歉，请填写客户端包名！');
            }
            if(empty($para['version'])){
                AjaxJson(0,0,'很抱歉，请填写当前软件版本号！');
            }

            $common_package = common_package($para['client'],$para['package'],$para['version']);
            if($common_package['result'] == 0){
                AjaxJson(0,0,$common_package['msg']);
            }

            $AppInfo=XBCache::GetCache($para['token']);
            if($AppInfo){
                //判断token的有效期
                $last_time = strtotime($AppInfo['TimeOut']);
                $active_time = get_basic_info('AppSession'); //单位:分钟

                if( (time()-$last_time)/86400 > $active_time){
                    //已过期重新登录
                    XBCache::Remove($para['token']);
                    AjaxJson(0,-1,'登录已失效,点击确定后重新登录！');
                }else{
                    //未过期更新过期时间
                    $AppInfo['TimeOut']=date('Y-m-d H:i:s');
                    XBCache::Insert($para['token'],$AppInfo);
                }
            }else{
                AjaxJson(0,-1,'登录已失效,请点击确定后重新登录！');
            }
        }elseif(IS_GET){
            //get 方式
            $get_data = I('get.');

            if(empty($get_data['client'])){
                AjaxJson(0,0,'很抱歉，请填写客户端名称，如Android、IOS、PC！');
            }
            if(empty($get_data['package'])){
                AjaxJson(0,0,'很抱歉，请填写客户端包名！');
            }
            if(empty($get_data['version'])){
                AjaxJson(0,0,'很抱歉，请填写当前软件版本号！');
            }

            $common_package = common_package($get_data['client'],$get_data['package'],$get_data['version']);
            if($common_package['result'] == 0){
                AjaxJson(0,0,$common_package['msg']);
            }

        }else{
            AjaxJson(0,0,'很抱歉，提交方式不对！');
        }
    }
}