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
class UEditorController extends  BaseController
{
    public function Upload(){
        $upload=new XBCommon\XBUpload();
        $upload->ue_upload_controller();
    }
}