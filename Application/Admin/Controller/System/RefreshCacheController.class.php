<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2017/9/9
// +----------------------------------------------------------------------  

namespace Admin\Controller\System;
use Think\Controller;
use XBCommon;
use XBCommon\CacheData;

class RefreshCacheController extends BaseController {

    //
    public function index(){

        self::deldir(TEMP_PATH);//临时文件

        self::deldir(CACHE_PATH); //缓存

        self::deldir(LOG_PATH); //日志

        self::deldir(DATA_PATH);  //数据缓存文件

        //F('Common_Package',null);
        common_package();

        //获取基本设置缓存
        $cache=new CacheData();
        $cache->UpdateCache();

        $this->display();
        //echo json_encode(array('result'=>true,'des'=>U('System/Index')));
    }

    /*
    *@功能说明: 删除某目录的全部文件
    *@param   文件夹路径
    */

    public function deldir($dir)
    {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }
    }
}