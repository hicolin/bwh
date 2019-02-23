<?php
/**
   
 
 * @作者：      傅世敏
 * @修改日期：  2017-07-24
 * @继承版本:   1.1
 * @功能说明：  头像上传
 */
namespace Api\Controller\Upload;
use XBCommon;
use Think\Controller;
class UploadController extends Controller
{
    const T_MEM='mem_info';

    /**
     * @功能说明: 图片上传
     * @传输格式: 私有token,有提交，明文返回
     * @提交网址: /Upload/Upload/index
     * @提交信息：非josn form 表单 post方式提交 array("token"=>"553bd49f30b06ba3bf37250f1c3645e49bef288c58ce41dff9d6000df619","client"=>"ios","ver"=>"1.1") FILES  Multipart/form-data
     * @返回信息: {'result'=>1,'message'=>'修改成功！'}
     */
    public function index(){
        $Upload=new XBCommon\XBUpload();
        $result=$Upload->Uploadimage();
        if($result['result']!='success'){
            $result=json_decode($result,true);
            exit(json_encode(array("result"=>0,"message"=>$result['message'])));
        }

        //返回图片存储的相对路径
        exit(json_encode(array("result"=>1,"message"=>$result['message'] ,'path'=>$result['path'] ,'filepath'=>'http://'.$_SERVER['HTTP_HOST'].$result['path'] ) ));
    }
}