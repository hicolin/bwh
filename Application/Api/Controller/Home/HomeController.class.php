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
// | Date: 2017/10/10
// +----------------------------------------------------------------------

namespace Api\Controller\Home;
use Api\Controller\Core\CommonController;
use XBCommon;

class HomeController extends CommonController
{
    const T_ADS = 'sys_adcontent';

    /**
     * @功能说明: 获取广告轮播图
     * @传输格式: get提交
     * @提交网址: /Home/home/ListAds
     * @提交信息：{"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0","data":{"aid":"1","num":"4"}}
     *            aid 是广告ID  14是首页头部四广告  15是首页板块下面一广告  num是显示数量
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function ListAds(){
        $para = get_json_data();
        $num = $para['num'] ? $para['num'] : 4;
        $aid = $para['aid'] ? $para['aid'] : 3;

        $ads = M(self::T_ADS)->field("Name,Pic,Url")->where(array("AdvertisingID"=>$aid,"Status"=>1))->order('Sort asc,ID desc')->limit($num)->select();

        if($ads){
            $list = array();
            foreach($ads as $val){
                if($val['Pic']) {
                    $val['Pic'] = NoPhoto($val['Pic']);
                }
                if($val['Url'] == '#'){
                    $val['Url'] = '';
                }

                $list[] = $val;
            }

            AjaxJson(1,1,'success',$list);
        }else{

            AjaxJson(1,1,'暂没有可用的轮播图信息');
        }
    }

    public function SingleAds(){
        $para = get_json_data();

        $ads = M(self::T_ADS)->field("Name,Pic,Url")->where(array("AdvertisingID"=>$para['aid'],"Status"=>1))->order('Sort asc,ID desc')->limit(1)->find();

        if($ads){
            if($ads['Pic']) {
                $ads['Pic'] = NoPhoto($ads['Pic']);
            }
            if($ads['Url'] == '#'){
                $ads['Url'] = '';
            }

            AjaxJson(0,1,'success',$ads);
        }else{
            AjaxJson(0,1,'暂没有可用的轮播图信息');
        }
    }

    /**
     * @功能说明: 根据客户端和版本号与后台设置的匹配版本号，提示更新下载
     * @传输格式: 公有token,明文传输，明文返回
     * @提交网址: {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @提交信息：{"token":"frkAES2C6LDrbUeqxOHkKeVqBw+Yv62/bIVbcGak1qk=","client":"ios","version":"1.1"}
     *               Ver 版本号  isForced  是否强制更新 1是 2不是  Url下载地址
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function version(){
        $para = get_json_data();

        $find = M('version')->field('ID,Ver,isForced,Url,Updates')->where(array("Client"=>$para['client'],"IsDefault"=>1))->find();

        if($find){
            $find['Updates'] = htmlspecialchars_decode($find['Updates']);
            AjaxJson(0,1,'success',$find);
        }else{
            $find = M('version')->field('ID,Ver,isForced,Url,Updates')->where(array("Client"=>$para['client'],"Status"=>'1','Ver'=>$para['version']))->find();
            AjaxJson(0,1,'success',$find);
        }
    }
    /**
     * @功能说明: 获取地区树形结构
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /Home/home/areas
     * @提交方式: {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function areas(){
        $cache=new XBCommon\CacheData();
        $arr1=$cache->Areas();
        //遍历处理源数据库数据
        if ($arr1) {
            foreach ($arr1 as $val) {
                $province['text'] = $val['Name'];
                $province['id'] = $val['ID'];
                $province['ID'] = $val['ID'];
                $province['state']=$val['state'];
                $arr_city = array();
                foreach ($val['children'] as $city) {
                    $citys['text'] = $city['Name'];
                    $citys['id'] = $city['ID'];
                    $citys['ID'] = $city['ID'];
                    $citys['state']=$city['state'];
                    $arr_country = array();
                    foreach ($city['children'] as $country) {
                        $countrys['text'] = $country['Name'];
                        $countrys['id'] = $country['ID'];
                        $countrys['ID'] = $country['ID'];
                        $countrys['state']=$country['state'];
                        $arr_country[] = $countrys;
                    }
                    $citys['children'] = $arr_country;
                    $arr_city[] = $citys;
                }
                $province['children'] = $arr_city;
                $arr_one[] = $province;
            }
        }
        if($arr_one){
            // $data=array(
            //     'result'=>1,
            //     'message'=>'恭喜您，获取成功！',
            //     'data'=>$arr_one
            // );
            // exit(json_encode($data));
            AjaxJson(1,1,'success',$arr_one);
        }else{
            AjaxJson(0,0,'暂无数据');
            // $data=array(
            //     'result'=>1,
            //     'message'=>'暂无内容！',
            // );
            // exit(json_encode($data));
        }
    }
    /**
     * @功能说明: 获取最大可借金额(会员未登录时)
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /Home/home/getedunolog
     * @提交方式: {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function getedunolog(){
        $data['showmoney']=get_basic_info('ShowEduNolog');
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data);
    }
    /**
     * @功能说明: 监督举报电话
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /Home/home/jiandphone
     * @提交方式: {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function jiandphone(){
        $data['JdPhone']=get_basic_info('JdPhone');
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data);
    }
    /**
     * @功能说明: 获取借款金额与借款期限(未登录时)
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /Home/home/getjkparater2
     * @提交方式: {"client":"android","package":"ceshi.app","version":"v1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function getjkparater2(){
        //获取借款金额
        $moneylist=M('goods')->field('ID,SalePrice,Interest,Fastmoney,GuanliCost,CashCoupon')->where(array('IsShelves'=>'1','IsDel'=>'0'))->order('SalePrice asc')->select();
        //获取借款期限
        $termlist=M('loans_term')->field('ID,NumDays,Applyfee,Fastmoney,GuanliCost')->where(array('Status'=>'1','IsDel'=>'0'))->order('NumDays asc')->select();
        $retdata=array(
            'moneylist'=>$moneylist,
            'termlist'=>$termlist,
            );
        AjaxJson(1,1,'恭喜您，数据校验成功！',$retdata);
    }

}