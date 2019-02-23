<?php
namespace Api\Controller\Center;

use Api\Controller\Core\BaseController;
use XBCommon\XBCache;

class OrderController extends BaseController  {
	const T_MEMINFO='mem_info';
    const T_GOODS='goods';
    const T_MEMCOUPANS='mem_coupans';
    const T_CONTENTMANAGEMENT='sys_contentmanagement';
    const T_LOANSAPPLYLIST='loans_applylist';
    const T_LOANSXJAPPLYLIST='loans_xjapplylist';
    const T_LOANSPARAMETER='loans_parameter';//贷款参数设置
	const T_LOANSHKLIST='loans_hklist';

    public function _initialize(){
        parent::_initialize();
        $this->statusArr=array(
            '0'=>'申请中',
            '1'=>'放款中',//放款中
            '2'=>'已放款',//已放款
            '3'=>'已完成',
            '4'=>'已取消',
            '5'=>'已拒绝',
            );
     }
	/**
     * @功能说明: 立即借款校验(首页立即借款)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/fastbuycheck
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function fastbuycheck(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $retdata=array(
            'status'=>'1',
            'msg'=>'可以借款',
            );
        $meminfos=M(self::T_MEMINFO)->field('ForbidTime,Status,LimitBalcance')->find($mem['ID']);
        //判断是否认证，
        if($meminfos['Status']=='1'){
            $retdata['status']='0';
            $retdata['jumps']='2';
            $retdata['msg']='您还没有认证，请先去认证！';
            AjaxJson(0,1,'您还没有认证，请先去认证！',$retdata,1,$mem['KEY'],$mem['IV']);
        }
        //校验必须认证
        $checkflags=checkmust_renz($mem['ID']);
        if(!$checkflags){
            $retdata['status']='0';
            $retdata['msg']='必须认证未认证完，请先去认证！';
            AjaxJson(0,1,'必须认证未认证完，请先去认证！',$retdata,1,$mem['KEY'],$mem['IV']);
        }
        //校验会员年龄有没有达到借款条件
        $membirthday=M('renzen_cards')->where(array('UserID'=>$mem['ID'],'Status'=>'1','IsDel'=>'0'))->getField('Birthday');
        if($membirthday){
            $membirthday=strtotime($membirthday);
            $memage=getage($membirthday);
            $MaxAges=get_basic_info('MaxAges');
            $MinAges=get_basic_info('MinAges');
            if($memage<$MinAges || $memage>$MaxAges){
                $err='';
                AjaxJson(0,0,'抱歉，您的年龄不符合条件！',$err,1,$mem['KEY'],$mem['IV']);
            }
        }

        if(in_array($meminfos['Status'],array('3','4'))){
            $err='';
            AjaxJson(0,0,'您没有购买权限！',$err,1,$mem['KEY'],$mem['IV']);
        }
        if($meminfos['ForbidTime'] && $meminfos['ForbidTime']>date('Y-m-d H:i:s')){
            $retdata['status']='0';
            $retdata['ForbidTime']=strtotime($meminfos['ForbidTime'])-time();
            $retdata['msg']= '请于'.$meminfos['ForbidTime'].'之后再申请!';
        }
        //判断是否有未结束的 借款订单
        $orderinfos=M(self::T_LOANSAPPLYLIST)->where(array('UserID'=>$mem['ID'],'LoanStatus'=>array('in',array('0','1','2')),'IsDel'=>'0'))->count('ID');
        if($orderinfos){
            $retdata['status']='0';
            $retdata['jumps']='1';
            $retdata['msg']='还有未完成的订单，暂不能下单!';
        }
        AjaxJson(0,1,'恭喜您，数据校验成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 获取借款金额与借款期限(我要借贷)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/getjkparater
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *   gid 商品详情id
     * @返回信息: {'result'=>1,'message'=>'数据获取成功!'}
     */
    public function getjkparater(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        $LimitBalcance=M(self::T_MEMINFO)->where(array('ID'=>$mem['ID']))->getField('LimitBalcance');
        //获取借款金额
        $moneylist=M(self::T_GOODS)->field('ID,SalePrice,Interest,Fastmoney,GuanliCost,CashCoupon')->where(array('SalePrice'=>array('ELT',$LimitBalcance),'IsShelves'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->select();
        //获取借款期限
        $termlist=M('loans_term')->field('ID,NumDays,Applyfee,Fastmoney,GuanliCost')->where(array('Status'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->select();
        $retdata=array(
            'moneylist'=>$moneylist,
            'termlist'=>$termlist,
            );
        AjaxJson(1,1,'恭喜您，数据校验成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 到期应还金额(我要借贷)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/gethkmoney
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"gid":"2","termid":"2","juanid":"2"}}
     *   gid 借款金额id   termid 借款期限id juanid 优惠劵id
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function gethkmoney(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        if(!$para['gid']){
            AjaxJson(0,0,'请选择借款金额！');
        }
        if(!$para['termid']){
            AjaxJson(0,0,'请选择借款期限！');
        }
        $LimitBalcance=M(self::T_MEMINFO)->where(array('ID'=>$mem['ID']))->getField('LimitBalcance');
        $goodinfo=M(self::T_GOODS)->field('ID,SalePrice,Interest,CashCoupon')->where(array('ID'=>$para['gid'],'SalePrice'=>array('ELT',$LimitBalcance),'IsShelves'=>'1','IsDel'=>'0'))->find();
        if(!$goodinfo){
            AjaxJson(0,0,'借款金额信息异常！');
        }
        $terminfo=M('loans_term')->field('ID,Applyfee')->where(array('ID'=>$para['termid'],'Status'=>'1','IsDel'=>'0'))->find();
        if(!$terminfo){
            AjaxJson(0,0,'借款期限信息异常！');
        }
        //优惠劵信息校验
        if($para['juanid']!='0'){
            if($goodinfo['CashCoupon']=='2'){
                AjaxJson(0,0,'此借款金额不能使用优惠劵！');
            }
            $coupaninfo=M(self::T_MEMCOUPANS)->field('ID,StartMoney,StartTime,Money')->where(array('ID'=>$para['juanid'],'UserID'=>$mem['ID'],'Isuser'=>'1','IsDel'=>'0','EndTime'=>array('EGT',date('Y-m-d H:i:s'))))->find();
            if(!$coupaninfo){
                AjaxJson(0,0,'优惠劵信息异常！');
            }
            if($goodinfo['SalePrice']<$coupaninfo['StartMoney']){
                AjaxJson(0,0,'此借款金额不符合此优惠劵使用条件！');
            }
            if($coupaninfo['StartTime']>date('Y-m-d H:i:s')){
                AjaxJson(0,0,'此优惠劵暂不能使用！');
            }
        }
        //到期应还金额
        $hkmoney='0';
        if($coupaninfo){
            $hkmoney=$goodinfo['SalePrice']-$coupaninfo['Money']+$goodinfo['Interest']+$terminfo['Applyfee'];
        }else{
            $hkmoney=$goodinfo['SalePrice']+$goodinfo['Interest']+$terminfo['Applyfee'];
        }
        $retdata=array(
            'hkmoney'=>$hkmoney,
            );
        AjaxJson(0,1,'恭喜您，数据校验成功！',$retdata);
    }
    /**
     * @功能说明: 提交订单操作(我要借贷)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/downorder
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"gid":"2","termid":"2","juanid":"2"}}
     *   gid 借款金额id   termid 借款期限id juanid 优惠劵id
     * @返回信息: {'result'=>1,'message'=>'数据获取成功!'}
     */
    public function downorder(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        if(!$para['gid']){
            AjaxJson(0,0,'请选择借款金额！');
        }
        if(!$para['termid']){
            AjaxJson(0,0,'请选择借款期限！');
        }
        //校验 是否有借款的条件
        $rtdata=checkmembuy($mem['ID']);
        if($rtdata['result']=='0'){
            AjaxJson(0,0,$rtdata['message']);
        }
        //校验必须认证
        $checkflags=checkmust_renz($mem['ID']);
        if(!$checkflags){
            AjaxJson(0,0,'必须认证未认证完，请先去认证！');
        }
        //校验会员年龄有没有达到借款条件
        $membirthday=M('renzen_cards')->where(array('UserID'=>$mem['ID'],'Status'=>'1','IsDel'=>'0'))->getField('Birthday');
        if($membirthday){
            $membirthday=strtotime($membirthday);
            $memage=getage($membirthday);
            $MaxAges=get_basic_info('MaxAges');
            $MinAges=get_basic_info('MinAges');
            if($memage<$MinAges || $memage>$MaxAges){
                AjaxJson(0,0,'抱歉，您的年龄不符合条件！');
            }
        }
        //获取会员的借款额度
        $LimitBalcance=M(self::T_MEMINFO)->where(array('ID'=>$mem['ID']))->getField('LimitBalcance');

        $goodinfo=M(self::T_GOODS)->field('ID,SalePrice,Interest,Fastmoney,GuanliCost,CashCoupon')->where(array('ID'=>$para['gid'],'SalePrice'=>array('ELT',$LimitBalcance),'IsShelves'=>'1','IsDel'=>'0'))->find();
        if(!$goodinfo){
            AjaxJson(0,0,'借款金额信息异常！');
        }
        $terminfo=M('loans_term')->field('ID,NumDays,Applyfee,Fastmoney,GuanliCost')->where(array('ID'=>$para['termid'],'Status'=>'1','IsDel'=>'0'))->find();
        if(!$terminfo){
            AjaxJson(0,0,'借款期限信息异常！');
        }
        //优惠劵信息校验
        if($para['juanid']!='0'){
            if($goodinfo['CashCoupon']=='2'){
                AjaxJson(0,0,'此借款金额暂不支持使用优惠劵！');
            }
            $coupaninfo=M(self::T_MEMCOUPANS)->field('ID,StartMoney,StartTime,Money')->where(array('ID'=>$para['juanid'],'UserID'=>$mem['ID'],'Isuser'=>'1','IsDel'=>'0','EndTime'=>array('EGT',date('Y-m-d H:i:s'))))->find();
            if(!$coupaninfo){
                AjaxJson(0,0,'优惠劵信息异常！');
            }
            if($goodinfo['SalePrice']<$coupaninfo['StartMoney']){
                AjaxJson(0,0,'此借款金额不符合此优惠劵使用条件！');
            }
            if($coupaninfo['StartTime']>date('Y-m-d H:i:s')){
                AjaxJson(0,0,'此优惠劵暂不能使用！');
            }
        }

        //提交借款申请
        $hkmoney='0';//到期应还金额
        if($coupaninfo){
            $hkmoney=$goodinfo['SalePrice']-$coupaninfo['Money']+$goodinfo['Interest']+$terminfo['Applyfee'];
        }else{
            $hkmoney=$goodinfo['SalePrice']+$goodinfo['Interest']+$terminfo['Applyfee'];
        }

        $model=M();
        $model->startTrans();
        $paraterinfos=M(self::T_LOANSPARAMETER)->find();//贷款参数
        //提交借款申请
        $OrderSn=date(ymd).rand(1,9).date(His).rand(111,999);
        $AdoptMoney=$goodinfo['Fastmoney']+$terminfo['Fastmoney'];//快速申请费
        $FJMoney=$goodinfo['GuanliCost']+$terminfo['GuanliCost'];//用户管理费
        $Interest=$goodinfo['Interest']+$terminfo['Applyfee'];//利息
        $applydata=array(
            'UserID'=>$mem['ID'],
            'ApplyTime'=>date('Y-m-d H:i:s'),
            'OrderSn'=>$OrderSn,
            'LoanNo'=>$OrderSn,
            'ApplyMoney'=>$goodinfo['SalePrice'],
            'AdoptMoney'=>$AdoptMoney,
            'FJMoney'=>$FJMoney,
            'Interest'=>$Interest,
            'ApplyDay'=>$terminfo['NumDays'],
            'ProductID'=>$goodinfo['ID'],
            'OpenM'=>$goodinfo['SalePrice']-$AdoptMoney-$FJMoney,
            'BackM'=>$hkmoney,
            //'SqAdminID'=>,//申请专属客服
            //'LoanSum'=>,
            'RongDay'=>$paraterinfos['RongDay'],
            'RongP'=>$paraterinfos['RongP'],
            'OverdueDay'=>$paraterinfos['OverdueDay'],
            'OverdueP'=>$paraterinfos['OverdueP'],
            //'FKadminID'=>,//放款专属客服
            );
        if($coupaninfo){
            //使用了优惠劵
            $applydata['CouponID']=$coupaninfo['ID'];
            $applydata['CoMoney']=$coupaninfo['Money'];
        }
        //分配客服
        $kefudata=getkefudata($mem['ID']);
        if($kefudata['kfid']){
            $applydata['SqAdminID']=$kefudata['kfid'];
        }
        if($kefudata['fkid']){
            $applydata['FKadminID']=$kefudata['fkid'];
        }
        if($kefudata['csid']){
            $applydata['CsadminID']=$kefudata['csid'];
        }
        $result2=$model->table('xb_loans_applylist')->add($applydata);
        if($result2){
            //如果使用了优惠劵就更新优惠劵表
            if($coupaninfo){
                //使用了优惠劵
                $jsdata=array(
                    'Isuser'=>'2',
                    'Oid'=>$result2,
                    'Gid'=>$goodinfo['ID'],
                    'UseTime'=>date('Y-m-d H:i:s'),
                    );
                $jres = $model->table('xb_mem_coupans')->where(array('ID'=>$coupaninfo['ID']))->save($jsdata);
                if(!$jres){
                    $model->rollback();
                    AjaxJson(0,0,'抱歉，优惠劵信息更新失败！');
                }
            }
            $model->commit();
            AjaxJson(0,1,'申请提交成功！');
        }else{
            $model->rollback();
            AjaxJson(0,0,'申请提交失败！');
        }
    }
    /**
     * @功能说明: 订单列表
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /center/Order/orderlist
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"page":"0","rows":"20","status":"6"}}
     *  status 订单状态 0申请中 1放款中 2已放款 3已完成 4已取消 5已拒绝 6全部
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function orderlist(){
        $para = get_json_data();
        $mem = getUserInfo(get_login_info('ID'));

        if(!in_array($para['status'],array('0','1','2','3','4','5','6'))){
            AjaxJson(0,0,'订单状态异常！');
        }
        $page=$para['page']?$para['page']:0;
        $rows=$para['rows']?$para['rows']:10;
        $statusArr=$this->statusArr;
        $where=array();
        $where['UserID']=array('eq',$mem['ID']);
        $where['IsDel']=array('eq','0');
        if($para['status']!='6'){
            $where['LoanStatus']=array('eq',$para['status']);
        }
        $orderlist=M('loans_applylist')->field('ID,ApplyTime,OrderSn,ApplyMoney,ApplyDay,LoanType,LoanStatus,OpenTime,YyFkTime,IsYQ')
                  ->where($where)->order('ID desc')->limit($page*$rows,$rows)->select();
        if($orderlist){
            foreach($orderlist as $k=>&$v){
                if($v['LoanType']=='0'){
                    $v['LoanType']='普通借款';
                }elseif($v['LoanType']=='1'){
                    $v['LoanType']='续借';
                }elseif($v['LoanType']=='2'){
                    $v['LoanType']='分期';
                }
                if(!$v['OpenTime']){
                    $v['OpenTime']='';
                }
                if(!$v['YyFkTime']){
                    $v['YyFkTime']='';
                }
                //查看是否逾期
                if($v['LoanStatus']=='2'){
                    //过了当天夜里24点才算逾期
                    $overtimes='';
                    $overtimes=date('Y-m-d',strtotime($v['YyFkTime'])).' 23:59:59';
                    if($overtimes<date('Y-m-d H:i:s')){
                        // $yuqidata='';
                        // $yuqidata=getoverinfos($v['ID']);
                        // $retdata['TotalMoney']=$yuqidata['realtotal'];
                        // $retdata['famoney']=$yuqidata['famoney'];
                        $v['IsYQ']='1';
                    }else{
                        //未逾期
                        $v['IsYQ']='0';
                        //$retdata['TotalMoney']=$v['BackM'];
                    }
                }
                $v['statusname']=$statusArr[$v['LoanStatus']];
            }
        }
        AjaxJson(1,1,'恭喜您，数据查询成功！',$orderlist,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 取消订单
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/cancelorder
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id   ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function cancelorder(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $orderinfo=M(self::T_LOANSAPPLYLIST)->field('ID,Status')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息不存在！');
        }
        if($orderinfo['Status']!='0'){
            AjaxJson(0,0,'此订单不能取消操作！');
        }
        $result=M(self::T_LOANSAPPLYLIST)->where(array('ID'=>$para['oid']))->save(array('LoanStatus'=>'4'));
        if($result){
            AjaxJson(0,1,'取消成功！');
        }else{
            AjaxJson(0,0,'取消失败！');
        }
    }
    /**
     * @功能说明: 借款合同
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/hetong
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id   ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function hetong(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $orderinfo=M(self::T_LOANSAPPLYLIST)->field('ID,OrderSn,ApplyTime,ApplyMoney,ApplyDay,OpenTime,Interest,FJMoney,AdoptMoney,YyFkTime,BackM')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'LoanStatus'=>array('in',array('2','3')),'IsDel'=>'0'))->find();
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息不存在！');
        }
        $meminfo=M(self::T_MEMINFO)->field('Mobile,TrueName,IDCard')->find($mem['ID']);
        $hetonginfo=M(self::T_CONTENTMANAGEMENT)->field('Contents')->where(array('CategoriesID'=>'18'))->find();
        $hetonginfo['Contents']=htmlspecialchars_decode($hetonginfo['Contents']);
        //替换操作
        $hetonginfo['Contents']=str_replace('{$ContractNO}',$orderinfo['OrderSn'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$AddTime}',$orderinfo['OpenTime'],$hetonginfo['Contents']);

        $hetonginfo['Contents']=str_replace('{$Lender}',get_basic_info('CompanyName'),$hetonginfo['Contents']);

        $hetonginfo['Contents']=str_replace('{$Borrower}',$meminfo['TrueName'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$BIdenty}',$meminfo['IDCard'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$BMobile}',$meminfo['Mobile'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$loanTime}',date('Y-m-d',strtotime($orderinfo['ApplyTime'])),$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$Principal}',$orderinfo['ApplyMoney'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$term}',$orderinfo['ApplyDay'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$Interest}',$orderinfo['Interest'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$Userfee}',$orderinfo['FJMoney'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$Applyfee}',$orderinfo['AdoptMoney'],$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$repayTime}',date('Y-m-d',strtotime($orderinfo['YyFkTime'])),$hetonginfo['Contents']);
        $hetonginfo['Contents']=str_replace('{$repayMoney}',$orderinfo['BackM'],$hetonginfo['Contents']);
        AjaxJson(0,1,'恭喜您，数据查询成功！',$hetonginfo,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 获取订单简单信息(我要还款页面)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/hhdetails
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id   ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function hhdetails(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $orderinfo=M(self::T_LOANSAPPLYLIST)->field('ID,LoanStatus,ApplyTime,YyFkTime,OrderSn,ApplyMoney,AdoptMoney,FJMoney,Interest,BackM,CoMoney')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息不存在！');
        }
        if($orderinfo['LoanStatus']!='2'){
            AjaxJson(0,0,'此订单不能进行此操作了！');
        }
        $statusArr=$this->statusArr;

        $retdata=array();//返回的详情信息
        $retdata['isjump']='1';//能跳
        $retdata['errmsg']='';
        $retdata['Status']=$orderinfo['LoanStatus'];
        $retdata['Statusname']=$statusArr[$orderinfo['LoanStatus']];
        $retdata['ApplyTime']=$orderinfo['ApplyTime'];
        $retdata['YyFkTime']=$orderinfo['YyFkTime'];
        $retdata['OrderSn']=$orderinfo['OrderSn'];
        $retdata['ApplyMoney']=$orderinfo['ApplyMoney'];
        $retdata['AdoptMoney']=$orderinfo['AdoptMoney'];
        $retdata['FJMoney']=$orderinfo['FJMoney'];
        $retdata['Interest']=$orderinfo['Interest'];
        $retdata['CoMoney']='';
        if($orderinfo['CoMoney']){
            $retdata['CoMoney']=$orderinfo['CoMoney'];
        }
        $retdata['famoney']='';
               
        //查看是否逾期
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($orderinfo['YyFkTime'])).' 23:59:59';
        if($overtimes<date('Y-m-d H:i:s')){
            $yuqidata='';
            $yuqidata=getoverinfos($orderinfo['ID']);
            $retdata['TotalMoney']=$yuqidata['realtotal'];
            $retdata['famoney']=$yuqidata['famoney'];
            $retdata['IsYQ']='1';
            
            //算出距离发货日
            $lastday=(strtotime($overtimes)-time())/86400;
            $lastday=abs($lastday);
            $lastday=ceil($lastday);
            $retdata['lastday']=-$lastday;
            //算出总天数
            $totaldays='0';
            $totaldays=(strtotime($overtimes)-strtotime($orderinfo['ApplyTime']))/86400;
            $totaldays=ceil($totaldays);
            $retdata['totaldays']=$totaldays;
        }else{
            //未逾期
            $retdata['IsYQ']='0';
            $retdata['TotalMoney']=$orderinfo['BackM'];

            //算出距离发货日
            $lastday=(strtotime($orderinfo['YyFkTime'])-time())/86400;
            $lastday=ceil($lastday);
            $retdata['lastday']=$lastday;
            //算出总天数
            $totaldays='0';
            $totaldays=(strtotime($orderinfo['YyFkTime'])-strtotime($orderinfo['ApplyTime']))/86400;
            $totaldays=ceil($totaldays);
            $retdata['totaldays']=$totaldays;
        }
        //校验是否提交借款申请，或是提交了续借申请了
        //如果还款申请已经提交，并且还处于待审核状态，则不予提交
        $checkresult=M('loans_hklist')->where(array('ApplyID'=>$orderinfo['ID'],'PayStatus'=>'0','Status'=>'0','PayType'=>array('neq','3')))->count('ID');
        if($checkresult){
            $retdata['isjump']='0';//不能跳
            $retdata['errmsg']='还款申请审核中!';
        }
        //如果续借还在审核 不予提交
        $checkxj=M('loans_xjapplylist')->where(array('ApplyID'=>$orderinfo['ID'],'PayStatus'=>'0','Status'=>'0','PayType'=>array('neq','3')))->count('ID');
        if($checkxj){
            $retdata['isjump']='0';//不能跳
            $retdata['errmsg']='续借申请审核中!';
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 订单详情页
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/orderdetails
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id   ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function orderdetails(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $orderinfo=M(self::T_LOANSAPPLYLIST)->field('ID,LoanStatus,ApplyTime,YyFkTime,OrderSn,ApplyMoney,AdoptMoney,FJMoney,Interest,ApplyDay,LoanType,OpenTime,CoMoney')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息不存在！');
        }
        $statusArr=$this->statusArr;

        $retdata=array();//返回的详情信息
        $retdata['Status']=$orderinfo['LoanStatus'];
        $retdata['Statusname']=$statusArr[$orderinfo['LoanStatus']];
        $retdata['ApplyTime']=$orderinfo['ApplyTime'];
        $retdata['YyFkTime']=$orderinfo['YyFkTime'];
        $retdata['OrderSn']=$orderinfo['OrderSn'];
        $retdata['ApplyMoney']=$orderinfo['ApplyMoney'];
        $retdata['AdoptMoney']=$orderinfo['AdoptMoney'];
        $retdata['FJMoney']=$orderinfo['FJMoney'];
        $retdata['Interest']=$orderinfo['Interest'];
        $retdata['ApplyDay']=$orderinfo['ApplyDay'];
        $retdata['HkTime']='';

        if($orderinfo['LoanType']=='0'){
            $retdata['LoanType']='普通借款';
        }elseif($orderinfo['LoanType']=='1'){
            $retdata['LoanType']='续借';
        }elseif($orderinfo['LoanType']=='2'){
            $retdata['LoanType']='分期';
        }
        $retdata['OpenTime']='';
        if($orderinfo['OpenTime']){
            $retdata['OpenTime']=$orderinfo['OpenTime'];
        }
        $retdata['CoMoney']='';
        if($orderinfo['CoMoney']){
            $retdata['CoMoney']=$orderinfo['CoMoney'];
        }
        //未逾期
        $retdata['overdays']='0';
        $retdata['overmoney']='0.00';
        //算出逾期金额和逾期天数
        if(in_array($orderinfo['LoanStatus'],array('0','1','4','5'))){
            $retdata['overdays']='0';
            $retdata['overmoney']='0.00';
        }elseif($orderinfo['LoanStatus']=='2'){
            //查看是否逾期
            //过了当天夜里24点才算逾期
            $overtimes=date('Y-m-d',strtotime($orderinfo['YyFkTime'])).' 23:59:59';
            if($overtimes<date('Y-m-d H:i:s')){
                //已经逾期了
                $yuqidata='';
                $yuqidata=getoverinfos($orderinfo['ID']);
                $retdata['overdays']=$yuqidata['overdays'];
                $retdata['overmoney']=$yuqidata['famoney'];
            }else{
                //未逾期
                $retdata['overdays']='0';
                $retdata['overmoney']='0.00';
            }
        }elseif($orderinfo['LoanStatus']=='3'){
            //已完成  查询还款记录
            $hkinfos=M(self::T_LOANSHKLIST)->field('ID,FinePayable,HkTime')->where(array('ApplyID'=>$orderinfo['ID'],'IsDel'=>'0'))->find();
            if($hkinfos){
                $retdata['HkTime']=$hkinfos['HkTime'];
                if($hkinfos['FinePayable']>0){
                    //表示逾期了
                    //过了当天夜里24点才算逾期
                    $overtimes=date('Y-m-d',strtotime($orderinfo['YyFkTime'])).' 23:59:59';

                    $overdays=strtotime($hkinfos['HkTime'])-strtotime($overtimes);
                    $overdays=$overdays/86400;
                    $overdays=ceil($overdays);
                    $retdata['overdays']=$overdays;
                    $retdata['overmoney']=$hkinfos['FinePayable'];
                }
            }else{
                //如果不存在，则是续借的
                $xjtimes=M('loans_xjapplylist')->where(array('ApplyID'=>$orderinfo['ID'],'Status'=>'1','PayStatus'=>'1'))->getField('XjTime');
                $retdata['HkTime']=$xjtimes;
            }
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 还款支付方式
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/hkpaystype
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function hkpaystype(){
        $mem = getUserInfo(get_login_info('ID'));
        $retdata=array(
            array('paytype'=>'3','name'=>'银联'),
            array('paytype'=>'1','name'=>'支付宝'),
            array('paytype'=>'2','name'=>'微信'),
            );
        AjaxJson(1,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 还款支付操作
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/hkpay
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222","paytype":"1","traderemark":"我用支付宝182@qq.com账号支付了100元"}}
     *   oid 订单的主键id   ordersn订单编号 paytype 还款类型 1支付宝 2微信 3银联 4代付
     *   traderemark 转账备注信息
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function hkpay(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        if(!in_array($para['paytype'],array('1','2','3','4'))){
            AjaxJson(0,0,'还款类型不正确！');
        }
        if($para['paytype']!='3' && !$para['traderemark']){
            AjaxJson(0,0,'转账备注信息必须提交！');
        }
        $BankNo='';
        if($para['paytype']=='3'){
            //银联还款  查询自己的认证银行卡号
            $baninfos=M('renzen_bank')->field('ID,BankNo')->where(array('UserID'=>$mem['ID'],'Status'=>'1','IsDel'=>'0'))->find();
            if(!$baninfos){
                AjaxJson(0,0,'银行认证信息异常！');
            }
            $BankNo=$baninfos['BankNo'];
        }

        $applyinfo=M('loans_applylist')->field('ID,LoanNo,YyFkTime,BackM,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'LoanStatus'=>'2','IsDel'=>'0'))->find();
        if(!$applyinfo){
            AjaxJson(0,0,'借款订单不存在！');
        }
        //如果还款申请已经提交，并且还处于待审核状态，则不予提交
        $checkresult=M('loans_hklist')->field('ID,PayType')->where(array('ApplyID'=>$applyinfo['ID'],'PayStatus'=>'0','Status'=>'0'))->find();
        if($checkresult){
            if($checkresult['PayType']=='3'){
                //银联支付   把此记录改成审核失败
                $sdata=array(
                    'ShTime'=>date('Y-m-d H:i:s'),
                    'Status'=>'2',
                    );
                M('loans_hklist')->where(array('ID'=>$checkresult['ID']))->save($sdata);
            }else{
                AjaxJson(0,0,'您已经提交支付申请，并在审核中！');
            }
        }
        //如果续借还在审核 不予提交
        $checkxj=M('loans_xjapplylist')->field('ID,PayType')->where(array('ApplyID'=>$applyinfo['ID'],'PayStatus'=>'0','Status'=>'0'))->find();
        if($checkxj){
            if($checkxj['PayType']=='3'){
                //银联支付   把此记录改成审核失败
                $sdata=array(
                    'ShTime'=>date('Y-m-d H:i:s'),
                    'Status'=>'2',
                    );
                M('loans_xjapplylist')->where(array('ID'=>$checkxj['ID']))->save($sdata);
            }else{
                AjaxJson(0,0,'您提交的续借申请还在审核中！');
            }
        }

        //还款操作
        $hkdata=array(
            'UserID'=>$mem['ID'],
            'ApplyID'=>$applyinfo['ID'],
            'OrderSn'=>date(ymd).rand(1,9).date(His).rand(111,999),
            'LoanNo'=>$applyinfo['LoanNo'],
            'HkTime'=>date('Y-m-d H:i:s'),
            );
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($applyinfo['YyFkTime'])).' 23:59:59';
        if($overtimes<date('Y-m-d H:i:s')){
            //已经逾期了
            $yuqidata=getoverinfos($applyinfo['ID']);
            $hkdata['TotalMoney']=$yuqidata['realtotal'];//应付总金额
            $hkdata['FinePayable']=$yuqidata['famoney'];//应还罚金
        }else{
            //未逾期
            $hkdata['TotalMoney']=$applyinfo['BackM'];//到期应还
        }
        $hkdata['CostPayable']=$applyinfo['ApplyMoney']-$applyinfo['CoMoney'];//应还本金
        //$hkdata['SeviceCostPayable']=$applyinfo['FJMoney'];//应还服务费
        $hkdata['RatePayable']=$applyinfo['Interest'];//应还本息
        if($para['paytype']!='3'){
            $hkdata['TradeRemark']=$para['traderemark'];
            if($para['paytype']=='1'){
                $hkdata['Accounts']=get_basic_info('Gfaccount');//1支付宝 
            }elseif($para['paytype']=='2'){
                $hkdata['Accounts']=get_basic_info('Gfaccountw');//2微信
            }
        }

        $hkdata['PayType']=$para['paytype'];
        $result=M('loans_hklist')->add($hkdata);
        if($result){
            $retdata=array(
                'id'=>$result,
                'ordersn'=>$hkdata['OrderSn'],
                );
            //------富友支付需要的参数-----------start
            $fyparats=M('sys_inteparameter')->field('ParaName,ParaValue')->where(array('IntegrateID'=>'13'))->select();
            $mchntCd='';
            $key='';
            if($fyparats){
                foreach($fyparats as $k=>$v){
                    if($v['ParaName']=='mchntCd'){
                        $mchntCd=$v['ParaValue'];
                    }elseif($v['ParaName']=='key'){
                        $key=$v['ParaValue'];
                    }
                }
            }
            $meminfos=M('mem_info')->field('ID,Mobile,TrueName,IDCard')->find($mem['ID']);
            $retdata['mchntCd']=$mchntCd;
            $retdata['key']=$key;
            //支付金额
            $total_fee='1';//默认是测试
            if(get_basic_info('Payceshi')=='1'){
                //正式
                $total_fee=trim($hkdata['TotalMoney'])*100;
            }
            $retdata['amt']=$total_fee;//单位为分
            $retdata['userId']=$mem['ID'];
            $retdata['idNo']=$meminfos['IDCard'];
            $retdata['userName']=$meminfos['TrueName'];
            $retdata['Mobile']=$meminfos['Mobile'];
            $retdata['BankNo']=$BankNo;
            $retdata['backUrl']="http://".$_SERVER['HTTP_HOST'].'/index.php/Fuyoupay/hkquery';//支付结果回调地址
            //------富友支付需要的参数-----------end
            AjaxJson(0,1,'还款记录提交成功，等待审核！',$retdata);
        }else{
            AjaxJson(0,0,'还款记录提交失败！');
        }
    }
     /**
     * @功能说明: 获取续借信息(续借选择页-我要续借页面)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/xjdetails
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function xjdetails(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $applyinfo=M('loans_applylist')->field('ID,LoanNo,YyFkTime,ProductID')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'LoanStatus'=>'2','IsDel'=>'0'))->find();
        if(!$applyinfo){
            AjaxJson(0,0,'借款订单不存在！');
        }
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($applyinfo['YyFkTime'])).' 23:59:59';
        if($overtimes<date('Y-m-d H:i:s')){
            AjaxJson(0,0,'此单已经逾期，不能续借！');
        }
        //判断第几次续借了
        $xjnumbs=M('loans_applylist')->where(array('LoanNo'=>$applyinfo['LoanNo'],'LoanType'=>'1','IsDel'=>'0'))->count('ID');
        if(!$xjnumbs){
            $xjnumbs='0';
        }

        $loansets=M('loans_parameter')->find();
        if($loansets['MaxRenewSum']<=$xjnumbs){
            AjaxJson(0,0,'此单已经续借多次，不能再续借了！');
        }
        //获取借款期限
        $termlist=M('loans_term')->field('ID,NumDays,ServiceCost')->where(array('Status'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->select();
        //获取 快速申请费
        $fast_Applyfee='0';
        $maxfee='';
        $currentfee='';
        //续借参数设置
        $renewsetlist=M('loans_renewset')->field('Nums,Applyfee')->where(array('Status'=>'1','IsDel'=>'0'))->order('Nums asc')->select();
        foreach($renewsetlist as $k=>$v){
            if($v['Nums']==($xjnumbs+1)){
                $currentfee=$v['Applyfee'];
            }
            $maxfee=$v['Applyfee'];
        }
        if($currentfee){
            $fast_Applyfee=$currentfee;
        }else{
            $fast_Applyfee=$maxfee;
        }
        //商品续借费
        $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
        if(!$goodinfo){
            AjaxJson(0,0,'商品信息异常，暂不能续借！');
        }
        $retdata=array(
            'arrivetime'=>$overtimes,
            'termlist'=>$termlist,
            'xjfee'=>$fast_Applyfee+$goodinfo['ServiceCost'],
            );
        AjaxJson(1,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 获取续借支付总金额
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/getxjfee
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222","termid":"1"}}
     *   oid 订单的主键id  ordersn订单编号  termid续借期限id
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function getxjfee(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $applyinfo=M('loans_applylist')->field('ID,LoanNo,ProductID')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'LoanStatus'=>'2','IsDel'=>'0'))->find();
        if(!$applyinfo){
            AjaxJson(0,0,'借款订单不存在！');
        }
        //判断第几次续借了
        $xjnumbs=M('loans_applylist')->where(array('LoanNo'=>$applyinfo['LoanNo'],'LoanType'=>'1','IsDel'=>'0'))->count('ID');
        if(!$xjnumbs){
            $xjnumbs='0';
        }
        //获取借款期限
        $terminfo=M('loans_term')->field('ID,NumDays,ServiceCost')->where(array('ID'=>$para['termid'],'Status'=>'1','IsDel'=>'0'))->find();
        if(!$terminfo){
            AjaxJson(0,0,'借款期限异常！');
        }
        //获取 快速申请费
        $fast_Applyfee='0';
        $maxfee='';
        $currentfee='';
        //续借参数设置
        $renewsetlist=M('loans_renewset')->field('Nums,Applyfee')->where(array('Status'=>'1','IsDel'=>'0'))->order('Nums asc')->select();
        foreach($renewsetlist as $k=>$v){
            if($v['Nums']==($xjnumbs+1)){
                $currentfee=$v['Applyfee'];
            }
            $maxfee=$v['Applyfee'];
        }
        if($currentfee){
            $fast_Applyfee=$currentfee;
        }else{
            $fast_Applyfee=$maxfee;
        }
        //商品续借费
        $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
        if(!$goodinfo){
            AjaxJson(0,0,'商品信息异常，暂不能续借！');
        }
        $retdata=array(
            'xjtotalmoney'=>$fast_Applyfee+$terminfo['ServiceCost']+$goodinfo['ServiceCost'],
            );
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    /**
     * @功能说明: 续借支付确认操作(选择支付方式页面)
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/xjpay
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2",,"ordersn":"12352222""termid":"1","paytype":"1","traderemark":"我用支付宝182@qq.com账号支付了100元"}}
     *   oid 订单的主键id  ordersn订单编号  termid续借期限id paytype 支付类型 1支付宝 2微信 3银联 4代付
     *   traderemark 转账备注信息
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function xjpay(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        if(!in_array($para['paytype'],array('1','2','3','4'))){
            AjaxJson(0,0,'还款类型不正确！');
        }
        if($para['paytype']!='3' && !$para['traderemark']){
            AjaxJson(0,0,'转账备注信息必须提交！');
        }
        $BankNo='';
        if($para['paytype']=='3'){
            //银联还款  查询自己的认证银行卡号
            $baninfos=M('renzen_bank')->field('ID,BankNo')->where(array('UserID'=>$mem['ID'],'Status'=>'1','IsDel'=>'0'))->find();
            if(!$baninfos){
                AjaxJson(0,0,'银行认证信息异常！');
            }
            $BankNo=$baninfos['BankNo'];
        }

        $applyinfo=M('loans_applylist')->field('ID,LoanNo,YyFkTime,ProductID')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'LoanStatus'=>'2','IsDel'=>'0'))->find();
        if(!$applyinfo){
            AjaxJson(0,0,'借款订单不存在！');
        }
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($applyinfo['YyFkTime'])).' 23:59:59';
        if($overtimes<date('Y-m-d H:i:s')){
            AjaxJson(0,0,'此单已经逾期，不能续借！');
        }
        //如果还款还在审核 不予提交
        $checkhks=M('loans_hklist')->field('ID,PayType')->where(array('ApplyID'=>$applyinfo['ID'],'PayStatus'=>'0','Status'=>'0'))->find();
        if($checkhks){
            if($checkhks['PayType']=='3'){
                //银联支付   把此记录改成审核失败
                $sdata=array(
                    'ShTime'=>date('Y-m-d H:i:s'),
                    'Status'=>'2',
                    );
                M('loans_hklist')->where(array('ID'=>$checkhks['ID']))->save($sdata);
            }else{
                AjaxJson(0,0,'您提交的还款申请还在审核中！');
            }
        }
        
        //判断第几次续借了
        $xjnumbs=M('loans_applylist')->where(array('LoanNo'=>$applyinfo['LoanNo'],'LoanType'=>'1','IsDel'=>'0'))->count('ID');
        if(!$xjnumbs){
            $xjnumbs='0';
        }
        $loansets=M('loans_parameter')->find();
        if($loansets['MaxRenewSum']<=$xjnumbs){
            AjaxJson(0,0,'此单已经续借多次，不能再续借了！');
        }
        //获取借款期限
        $terminfo=M('loans_term')->field('ID,NumDays,ServiceCost')->where(array('ID'=>$para['termid'],'Status'=>'1','IsDel'=>'0'))->find();
        if(!$terminfo){
            AjaxJson(0,0,'借款期限异常！');
        }
        //获取 快速申请费
        $fast_Applyfee='0';
        $maxfee='';
        $currentfee='';
        //续借参数设置
        $renewsetlist=M('loans_renewset')->field('Nums,Applyfee')->where(array('Status'=>'1','IsDel'=>'0'))->order('Nums asc')->select();
        foreach($renewsetlist as $k=>$v){
            if($v['Nums']==($xjnumbs+1)){
                $currentfee=$v['Applyfee'];
            }
            $maxfee=$v['Applyfee'];
        }
        if($currentfee){
            $fast_Applyfee=$currentfee;
        }else{
            $fast_Applyfee=$maxfee;
        }
        //商品续借费
        $goodinfo=M('goods')->field('ServiceCost')->where(array('ID'=>$applyinfo['ProductID'],'IsShelves'=>'1','IsDel'=>'0'))->find();
        if(!$goodinfo){
            AjaxJson(0,0,'商品信息异常，暂不能续借！');
        }

        //如果续借申请已经提交，并且还处于待审核状态，则不予提交
        $checkresult=M('loans_xjapplylist')->field('ID,PayType')->where(array('ApplyID'=>$applyinfo['ID'],'PayStatus'=>'0','Status'=>'0'))->find();
        if($checkresult){
            if($checkresult['PayType']=='3'){
                //银联支付   把此记录改成审核失败
                $sdata=array(
                    'ShTime'=>date('Y-m-d H:i:s'),
                    'Status'=>'2',
                    );
                M('loans_xjapplylist')->where(array('ID'=>$checkresult['ID']))->save($sdata);
            }else{
                AjaxJson(0,0,'您已经提交续借申请，并在审核中！');
            }
        }
        //续借操作
        $xjdata=array(
            'UserID'=>$mem['ID'],
            'ApplyID'=>$applyinfo['ID'],
            'OrderSn'=>date(ymd).rand(1,9).date(His).rand(111,999),
            'LoanNo'=>$applyinfo['LoanNo'],
            'LoanDay'=>$terminfo['NumDays'],
            'TotalMoney'=>$fast_Applyfee+$terminfo['ServiceCost']+$goodinfo['ServiceCost'],
            'ServiceCost'=>$fast_Applyfee+$terminfo['ServiceCost']+$goodinfo['ServiceCost'],
            'XjTime'=>date('Y-m-d H:i:s'),
            'PayType'=>$para['paytype'],
            );
        if($para['paytype']!='3'){
            $xjdata['TradeRemark']=$para['traderemark'];
            if($para['paytype']=='1'){
                $xjdata['Accounts']=get_basic_info('Gfaccount');//1支付宝 
            }elseif($para['paytype']=='2'){
                $xjdata['Accounts']=get_basic_info('Gfaccountw');//2微信
            }
        }
        $result=M('loans_xjapplylist')->add($xjdata);
        if($result){
            $retdata=array(
                'id'=>$result,
                'ordersn'=>$xjdata['OrderSn'],
                );
            //------富友支付需要的参数-----------start
            $fyparats=M('sys_inteparameter')->field('ParaName,ParaValue')->where(array('IntegrateID'=>'13'))->select();
            $mchntCd='';
            $key='';
            if($fyparats){
                foreach($fyparats as $k=>$v){
                    if($v['ParaName']=='mchntCd'){
                        $mchntCd=$v['ParaValue'];
                    }elseif($v['ParaName']=='key'){
                        $key=$v['ParaValue'];
                    }
                }
            }
            $meminfos=M('mem_info')->field('ID,Mobile,TrueName,IDCard')->find($mem['ID']);
            $retdata['mchntCd']=$mchntCd;
            $retdata['key']=$key;
            //支付金额
            $total_fee='1';//默认是测试
            if(get_basic_info('Payceshi')=='1'){
                //正式
                $total_fee=trim($xjdata['TotalMoney'])*100;
            }
            $retdata['amt']=$total_fee;//单位为分
            $retdata['userId']=$mem['ID'];
            $retdata['idNo']=$meminfos['IDCard'];
            $retdata['userName']=$meminfos['TrueName'];
            $retdata['Mobile']=$meminfos['Mobile'];
            $retdata['BankNo']=$BankNo;
            $retdata['backUrl']="http://".$_SERVER['HTTP_HOST'].'/index.php/Fuyoupay/xjquery';//支付结果回调地址
            //------富友支付需要的参数-----------end
            AjaxJson(0,1,'续借记录提交成功，等待审核！',$retdata);
        }else{
            AjaxJson(0,0,'续借记录提交失败！');
        }
    }
    /**
     * @功能说明: 获取还款支付总金额
     * @传输方式: 私有token,密文提交，明文返回
     * @提交网址: /center/Order/gethkmoney
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222"}}
     *   oid 订单的主键id   ordersn订单编号
     * @返回信息: {'result'=>1,'message'=>'登录密码修改成功!'}
     */
    public function gethkpaymoney(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $orderinfo=M(self::T_LOANSAPPLYLIST)->field('ID,OrderSn,YyFkTime,BackM')->where(array('ID'=>$para['oid'],'OrderSn'=>$para['ordersn'],'UserID'=>$mem['ID'],'LoanStatus'=>'2','IsDel'=>'0'))->find();
        if(!$orderinfo){
            AjaxJson(0,0,'订单信息不存在！');
        }
        $retdata=array();//返回的详情信息
        $retdata['ID']=$orderinfo['ID'];
        $retdata['OrderSn']=$orderinfo['OrderSn'];
        
        //查看是否逾期
        //过了当天夜里24点才算逾期
        $overtimes=date('Y-m-d',strtotime($orderinfo['YyFkTime'])).' 23:59:59';
        if($overtimes<date('Y-m-d H:i:s')){
            //已经逾期了
            $yuqidata='';
            $yuqidata=getoverinfos($orderinfo['ID']);
            $retdata['TotalMoney']=$yuqidata['realtotal'];
        }else{
            //未逾期
            $retdata['TotalMoney']=$orderinfo['BackM'];
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }
    
}