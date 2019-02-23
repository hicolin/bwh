<?php
/**
 * 
 *
 *
 * 修改时间: 2017-05-26 15:50
 * 功能说明:KindEditor上传控制器
 */
namespace Admin\Controller\Attachment;

use Think\Controller;
use Admin\Controller\System\BaseController;
use XBCommon;
class KindEditorController extends  BaseController
{
    public function Upload(){
        $upload=new XBCommon\XBUpload();
        $dir=$_GET['dir'];
        if(!empty($dir)){
            $upload->ke_upload_image();
        }else{
            return '参数传递错误！';
        }
    }

    public function DataList(){
        $upload=new XBCommon\XBUpload();
        $upload->ke_upload_manage();
    }
}