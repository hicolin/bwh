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
// | Date: 2018/6/14
// +----------------------------------------------------------------------

namespace Api\Controller\News;
use Api\Controller\Core\CommonController;

class IndexController extends CommonController
{
    const T_Table = 'sys_contentmanagement';

    //--------------------具体项目接口--------------------
    /**
     * @功能说明: 获取 版本介绍/法律责任等单页
     * @传输格式: get提交
     * @提交网址: /News/index/single
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0","data":{"id":"1"}}
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function single(){

        $para = get_json_data();
        if(!$para['id']){
            $para['id'] = 2;
        }

        $result = M(self::T_Table)->where(array("CategoriesID"=>$para['id']))->field("ID,Title,Contents")->find();
        if($result){
            $result['Contents']=htmlspecialchars_decode($result['Contents']);
            $result['Contents'] = str_replace('src="/Upload/', 'src="http://' . $_SERVER['HTTP_HOST'] . '/Upload/', $result['Contents']);

            AjaxJson(0,1,'success',$result);
        }else{

            AjaxJson(0,1,'暂无内容介绍');
        }
    }

    /**
     * @功能说明: 通知公告，发现，常见问题
     * @传输格式: get提交
     * @提交网址: /News/index/guidelist
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0","data":{"stype":"14","page":"0","rows":"10"}}
     *  stype 4发现   12通知公告  14常见问题
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function guidelist(){
        $para = get_json_data();

        $page=$para['page']?$para['page']:0;
        $rows=$para['rows']?$para['rows']:20;
        $stype=$para['stype']?$para['stype']:14;
        if(!in_array($stype,array('4','12','14'))){
            AjaxJson(0,0,'状态类型错误！');
        }
        $cols='';
        if($stype=='4'){
            $cols='ID,Title,UrlType,Url,CoverImage,Lead,AddTime,ViewCounk';
        }elseif($stype=='12'){
            $cols='ID,Title';
        }elseif($stype=='14'){
            $cols='ID,Title,Lead';
        }

        $result = M(self::T_Table)->field($cols)->where(array("IsPublish" => 1, 'CategoriesID' => $stype))->order('Sort asc,ID desc')->limit($page*$rows,$rows)->select();

        if ($result) {
            foreach($result as $k=>&$v){
                if($v['Lead'] && is_null($v['Lead'])){
                    $v['Lead']='';
                }
                if($v['CoverImage']){
                    $v['CoverImage']="http://".$_SERVER['HTTP_HOST'].$v['CoverImage'];
                }
            }
            AjaxJson(1,1,'success',$result);
        } else {
            AjaxJson(1,1,'暂无内容介绍');
        }
    }
    /**
     * @功能说明: 详情(通知公告，发现，常见问题)
     * @传输格式: get提交
     * @提交网址: /News/index/guidedetails
     *@提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0","data":{"id":"1"}}
     *  id文章id
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function guidedetails(){
        $para = get_json_data();
        if(!$para['id']){
            AjaxJson(0,1,'请提交文章id');
        }
        $result = M(self::T_Table)->where(array("ID"=>$para['id']))->field("ID,Title,CoverImage,Contents,AddTime,ViewCounk")->find();
        if($result){
            //浏览量加一
            M(self::T_Table)->where(array("ID"=>$para['id']))->setInc('ViewCounk');
            if($result['CoverImage']){
                $result['CoverImage']="http://".$_SERVER['HTTP_HOST'].$result['CoverImage'];
            }
            $result['Contents']=htmlspecialchars_decode($result['Contents']);
            $result['Contents'] = str_replace('src="/Upload/', 'src="http://' . $_SERVER['HTTP_HOST'] . '/Upload/', $result['Contents']);
            AjaxJson(0,1,'success',$result);
        }else{
            AjaxJson(0,1,'暂无内容介绍');
        }
    }

}