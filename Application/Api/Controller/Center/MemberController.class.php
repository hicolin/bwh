<?php
namespace Api\Controller\Center;

use Api\Controller\Core\BaseController;
use XBCommon\XBCache;
use XBCommon;

class MemberController extends BaseController  {

    const T_TABLE='mem_info';
    const T_TIMESTAMP='sys_timestamp';
    const T_MOBILE_CODE='sms_code';
    const T_SYSAREAS='sys_areas';
    const T_MEMCOUPANS='mem_coupans';
    const T_MEMCOUPANS2='mem_coupans2';

    /**
     * @功能说明: 获取用户基本信息
     * @传输格式: 私有token,无提交，密文返回
     * @提交网址: /center/member/info
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回信息：{'result'=>1,'message'=>'恭喜您，数据查询成功！','data'}
     */
    public function info(){

        $mem = getUserInfo(get_login_info('ID'));

        $data = array(
            'ID'=>$mem['ID'],
            'HeadImg'=>NoPhoto($mem['HeadImg'],2),
            'HeadImgVal'=>$mem['HeadImg'],
            'NickName'=>$mem['NickName'],
            'TrueName'=>$mem['TrueName'],

            'Mobile'=>substr_replace($mem['Mobile'],'****',3,4),
            'Sex'=>$mem['Sex'],
            'SexVal'=>$mem['Sex']==1 ? '男' : ($mem['Sex']==2 ? '女' : '保密'),
            'BorthDate'=>$mem['BorthDate']
        );

        AjaxJson(0,1,'恭喜您，数据查询成功！',$data,1,$mem['KEY'],$mem['IV']);
    }

    /**
     *@功能说明: 修改用户基本信息
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/member/modify
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"NickName":"醉爱玉兰香", "HeadImg":"/file/Pic/2017-03/06VLU9KYF1ZLAI2.gif","TrueName":"张三", "Sex":1, "BorthDate":"1990-01-01"}}
     * @返回信息: {'result'=>1,'message'=>'恭喜您,修改保存成功!'}
     */
    public function modify(){
        $para = get_json_data();

        if(!file_exists("alipay.txt")){ $fp = fopen("alipay.txt","wb"); fclose($fp);  }
        $str = file_get_contents('alipay.txt');
        $str .= " - trade_no: - ".json_encode($para)." - ".date("Y-m-d H:i:s")."\r\n";
        $fp = fopen("alipay.txt","wb");
        fwrite($fp,$str);
        fclose($fp);

        if(!is_num($para['Sex']) || $para['Sex']>2 || $para['Sex']<0){
            AjaxJson(0,0,'很抱歉，性别选择错误！');
        }
        if($para['BorthDate'] && !checkDateTime($para['BorthDate']) ){
            AjaxJson(0,0,'很抱歉，生日格式不正确！');
        }

        $data = array(
            'NickName'=>$para['NickName'],
            'HeadImg'=>$para['HeadImg'],
            //'TrueName'=>$para['TrueName'],
            'Sex'=>$para['Sex'],
            //'BorthDate'=>$para['BorthDate'],
            'UpdateTime'=>date("Y-m-d H:i:s")
        );

        if($para['BorthDate']){
            $data['BorthDate'] = $para['BorthDate'];
        }
        if($para['TrueName']){
            $data['TrueName'] = $para['TrueName'];
        }

        $db = M(self::T_TABLE);
        $result = $db->where(array('ID'=>get_login_info('ID')))->save($data);

        if($result){

            S('UserInfo'.get_login_info('ID'),null);

            AjaxJson(0,1,'恭喜您,修改保存成功！');
        }else{
            AjaxJson(0,0,'很抱歉，修改数据时保存失败！');
        }
    }

    /**
     * @功能说明: 修改密码
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/member/password
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oldpsd":"2F72FD958F0EDB6","newpsd":"44CB90206CB","confpsd":"44CB90206CB"}}
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function password(){
        $para=get_json_data();//接收参数

        $db=M(self::T_TABLE);

        $mem = getUserInfo(get_login_info('ID'));

        if(!$para['oldpsd']){
            AjaxJson(0,0,'原密码不能为空！');
        }
        if(!$para['newpsd']){
            AjaxJson(0,0,'新密码不能为空！');
        }
        //原密码和新密码不能相同
        if($para['oldpsd']==$para['newpsd']){
            AjaxJson(0,0,'原密码和新密码不能相同！');
        }
        //新密码和确认密码不一致
        if($para['newpsd'] != $para['confpsd']){
            AjaxJson(0,0,'新密码和确认密码不一致！');
        }
        //原密码不正确
        if($para['oldpsd'] != $mem['Password']){
            AjaxJson(0,0,'原密码不正确！');
        }
        //保存密码
        $arr = array(
            'Password'=>$para['newpsd'],
            'UpdateTime'=>date('Y-m-d H:i:s')
        );
        $result = $db->where(array('ID'=>$mem['ID']))->save($arr);
        if($result){
            AjaxJson(0,1,'密码修改成功！');
        }else{
            AjaxJson(0,0,'密码修改失败，请稍后重试！');
        }
    }
    //------------------以下为 具体项目开发接口---------------------------------------
    /**
     * @功能说明: 获取优惠劵列表
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/coupanlist
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"page":"0","rows":"20","stype":"1"}}
     *  stype 状态类型 1未使用 2已使用 2已过期
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function coupanlist(){
        $para = get_json_data();
        $mem = getUserInfo(get_login_info('ID'));

        $page=$para['page']?$para['page']:0;
        $rows=$para['rows']?$para['rows']:20;
        $stype=$para['stype']?$para['stype']:1;
        if(!in_array($stype,array('1','2','3'))){
            AjaxJson(0,0,'状态类型错误！');
        }
        $where=array();
        $where['UserID']=array('eq',$mem['ID']);
        $where['IsDel']=array('eq','0');
        if($stype=='1'){
            //未使用
            $where['Isuser']=array('eq','1');
            $where['EndTime']=array('EGT',date('Y-m-d H:i:s'));
        }elseif($stype=='2'){
            //已使用
            $where['Isuser']=array('eq','2');
        }elseif($stype=='3'){
            //已过期
            $where['Isuser']=array('eq','1');
            $where['EndTime']=array('LT',date('Y-m-d H:i:s'));
        }
        $infolist=M(self::T_MEMCOUPANS)->field('ID,Money,StartMoney,StartTime,EndTime')->where($where)->limit($page*$rows,$rows)->order('ID desc')->select();
        if($infolist){
            foreach($infolist as $k=>$v){
                $infolist[$k]['StartTime2']=strtotime($v['StartTime']);
                $infolist[$k]['EndTime2']=strtotime($v['EndTime']);
            }
        }

        AjaxJson(1,1,'恭喜您，数据查询成功！',$infolist,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 统计优惠劵信息
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/coupancounts
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function coupancounts(){
        $mem = getUserInfo(get_login_info('ID'));

        $where=array();
        $where['UserID']=array('eq',$mem['ID']);
        $where['IsDel']=array('eq','0');
        //未使用
        $where1['Isuser']=array('eq','1');
        $where1['EndTime']=array('EGT',date('Y-m-d H:i:s'));
        //已使用
        $where2['Isuser']=array('eq','2');
        //已过期
        $where3['Isuser']=array('eq','1');
        $where3['EndTime']=array('LT',date('Y-m-d H:i:s'));

        $nouse=M(self::T_MEMCOUPANS)->where($where)->where($where1)->count("ID");
        $uses=M(self::T_MEMCOUPANS)->where($where)->where($where2)->count("ID");
        $isover=M(self::T_MEMCOUPANS)->where($where)->where($where3)->count("ID");
        $data=array(
            'nouse'=>$nouse,
            'uses'=>$uses,
            'isover'=>$isover,
            );
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 删除优惠劵
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/deltcoupan
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"id":"1"}}
     *  id 优惠劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function deltcoupan(){
        $para = get_json_data();
        $mem = getUserInfo(get_login_info('ID'));
        if(!$para['id']){
            AjaxJson(0,0,'优惠劵id不能为空！');
        }
        $sdata=array(
            'IsDel'=>'1',
            'UpdateTime'=>date('Y-m-d H:i:s'),
            );
        $result=M(self::T_MEMCOUPANS)->where(array('ID'=>$para['id'],'UserID'=>$mem['ID']))->save($sdata);
        if($result){
            AjaxJson(0,1,'删除成功！');
        }else{
            AjaxJson(0,0,'删除失败！');
        }
    }
    /**
     * @功能说明: 获取推广链接(邀请好友推广的链接)
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/share
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function share(){
        $mem = getUserInfo(get_login_info('ID'));
        $data['shareurl']="http://".$_SERVER['HTTP_HOST']."/Register/index?ui=".$mem['ID'];
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data);
    }
    /**
     * @功能说明: 获取微信号和微信二维码(联系客服)
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/getlianxi
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function getlianxi(){
        $data['QQ']=get_basic_info('QQ');
        $data['Tel']=get_basic_info('Tel');
        $data['WeChat']=get_basic_info('WeChat');
        $data['WeChatQR']="http://".$_SERVER['HTTP_HOST'].get_basic_info('WeChatQR');
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data);
    }
    /**
     * @功能说明: 获取官方账号(还款支付时用)
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/gfaccount
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function gfaccount(){
        $data['Gfaccount']=get_basic_info('Gfaccount');
        $data['Gfaccountw']=get_basic_info('Gfaccountw');
        $data['Remarkgs']=get_basic_info('Remarkgs');
        #新增 2018年9月30日09:53:32 by hury
        /*支付宝收款码*/
        $data['AlipayimgUrl']="http://".$_SERVER['HTTP_HOST'].get_basic_info('Alipayimg');
        /*微信收款码*/
        $data['WeixinimgUrl']="http://".$_SERVER['HTTP_HOST'].get_basic_info('Weixinimg');
        AjaxJson(0,1,'恭喜您，数据查询成功！',$data);
    }
    /**
     * @功能说明: 获取最大可借金额(会员登录后)
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/getedu
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function getedu(){
        $mem = getUserInfo(get_login_info('ID'));
        $meminfos=M(self::T_TABLE)->field('ID,Status,LimitBalcance')->find($mem['ID']);
        $retdata=array();//返回的信息
        if($meminfos['Status']=='1'){
            $retdata['showmoney']=get_basic_info('ShowEduNorz');//最大借款金额 
        }elseif($meminfos['Status']=='2'){
            //审核通过的了  判断有没有未完成的订单
            $retdata['showmoney']=$meminfos['LimitBalcance'];//最大服务费 
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata);
    }
    /**
     * @功能说明: 保存头像路径(调用前，请先调用图片上传接口)
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/member/updhead
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"path":"/Upload/123.png"}}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function updhead(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        if(!$para['path']){
            AjaxJson(0,0,'头像链接不能为空！');
        }
        $result=M(self::T_TABLE)->where(array('ID'=>$mem['ID']))->save(array('HeadImg'=>$para['path'],'UpdateTime'=>date('Y-m-d H:i:s')));
        if($result){
            S('UserInfo'.get_login_info('ID'),null);
            AjaxJson(0,1,'头像保存成功！');
        }else{
            AjaxJson(0,0,'头像保存失败！');
        }
    }
    /**
     * @功能说明: 获取首页弹窗信息
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /center/member/gettancinfo
     * @提交方式: {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function gettancinfo(){
        $mem = getUserInfo(get_login_info('ID'));

        //最近的借款申请被拒绝的，才会有弹窗
        $jjcheck=M('loans_applylist')->field('LoanStatus')->where(array('UserID'=>$mem['ID'],'IsDel'=>'0'))->order('ID desc')->find();
        $retdata=array();
        $retdata['imgurl']='';
        $retdata['linkurl']='';
        if(get_basic_info('Tcstatus')=='1'){
            //不弹
            $retdata['imgurl']='';
            $retdata['linkurl']='';
        }elseif(get_basic_info('Tcstatus')=='3' && $jjcheck['LoanStatus']=='5'){
            //app弹
            $retdata['imgurl']="http://".$_SERVER['HTTP_HOST'].get_basic_info('TanImg');
            $retdata['linkurl']=get_basic_info('TanUrl');
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata);
    }
    /**
     * @功能说明: 首页显示状态判断(会员登录后)
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /center/member/homepages
     * @提交方式: {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function homepages(){
        $mem = getUserInfo(get_login_info('ID'));

        $retdata=array(
            'status'=>'1',//默认是显示真正的首页
            'statusname'=>'',
            'id'=>'',
            'ordersn'=>'',
            );
        //根据最后一个订单来判断
        $lastapply=M('loans_applylist')->field('ID,OrderSn,LoanStatus')->where(array('UserID'=>$mem['ID'],'IsDel'=>'0'))->order('ID desc')->find();
        if($lastapply['LoanStatus']=='0'){
            //审核中
            $retdata['status']='2';
            $retdata['statusname']='审核中';
            $retdata['id']=$lastapply['ID'];
            $retdata['ordersn']=$lastapply['OrderSn'];
        }elseif($lastapply['LoanStatus']=='1'){
            //审核成功，待放款
            $retdata['status']='3';
            $retdata['statusname']='恭喜您审核成功';
            $retdata['id']=$lastapply['ID'];
            $retdata['ordersn']=$lastapply['OrderSn'];
        }elseif($lastapply['LoanStatus']=='2'){
            //待还款，已放款
            $retdata['status']='4';
            $retdata['statusname']='待还款';
            $retdata['id']=$lastapply['ID'];
            $retdata['ordersn']=$lastapply['OrderSn'];
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata);
    }
    /**
     * @功能说明: 获取会员银行卡认证的详情
     * @传输方式: 公有token,明文传输，明文返回
     * @提交网址: /center/member/getbankinfos
     * @提交方式: {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function getbankinfos(){
        $mem = getUserInfo(get_login_info('ID'));
        $bankinfos=M('renzen_bank')->field('BankNo,YMobile,OpenBankName,Address')->where(array('UserID'=>$mem['ID'],'Status'=>'1','IsDel'=>'0'))->find();
        $retdata=array();
        if($bankinfos){
            $retdata['BankNo']=substr_replace($bankinfos['BankNo'],'*******',3,strlen($bankinfos['BankNo'])-6);
            $retdata['YMobile']=substr_replace($bankinfos['YMobile'],'****',3,4);
            $retdata['OpenBankName']=$bankinfos['OpenBankName'];
            $retdata['Address']=$bankinfos['Address'];
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata);
    }
}