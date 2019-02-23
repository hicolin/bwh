<?php
/**
 * 功能说明：会员信息模块
 */
namespace Admin\Controller\Members;
use Admin\Controller\Renzen\CardsController;
use Admin\Controller\Renzen\DecisionController;
use XBCommon;
use Admin\Controller\System\BaseController;
class MemInfoController extends BaseController {

    const T_TABLE      = 'mem_info';
    const T_MEMBERINFO ='renzen_memberinfo';  //基本信息认证表
    const T_IDCARD     ='renzen_cards';  //身份证认证表
    const T_MOBILE     ='renzen_mobile';  //手机认证表
    const T_APPLYLIST  = 'loans_applylist';
    const T_REPAY      = 'loans_hklist';
    const T_ADMIN      = 'sys_administrator';


    /**
     * 会员信息列表
     */
    public function Index(){
        $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'7','IsDel'=>'0'))->select();
        $this->assign(array(
            'kefuArr'=>$kefuArr,
        ));
        $this->display();
    }

    /**
     * 后台用户管理的列表数据获取
     * @access   public
     * @return   object    返回json数据
     */
    public function DataList(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');

        $id = I("request.id",0,'intval');

        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort='RegTime Desc';
        }

        $where['IsDel'] = 0;
        if($id){
            $where['Referee'] = $id;
            $result = M(self::T_TABLE)->where($where)->order($sort)->select();
            if($result){
                foreach ($result as $k=>$val){
                    if($result[$k]['Retype']=='0'){
                        $result[$k]['Retype']='网页';
                    }elseif($result[$k]['Retype']=='1'){
                        $result[$k]['Retype']='安卓';
                    }elseif($result[$k]['Retype']=='2'){
                        $result[$k]['Retype']='苹果';
                    }
                    if($result[$k]['Mtype']=='0'){
                        $result[$k]['Mtype']='正常';
                    }elseif($result[$k]['Mtype']=='1'){
                        $result[$k]['Mtype']='测试';
                    }
                    $result[$k]['ZsAdminID']=M(self::T_ADMIN)->where(array('ID'=>$result[$k]['ZsAdminID'],'IsDel'=>'0'))->getField('TrueName');
                    $result[$k]['state'] = has_mem($val['ID']) ? 'closed' : 'open';
                }
            }
        }else{
            $MemAccount=I('post.MemAccount','','trim');
            if($MemAccount){$where['MemAccount']=array('like','%'.$MemAccount.'%');}

            $Mobile=I('post.Mobile','','trim');
            if($Mobile){$where['Mobile']=array('like','%'.$Mobile.'%');}

            $NickName=I('post.NickName','','trim');
            if($NickName){$where['NickName']=array('like','%'.$NickName.'%');}

            $TrueName=I('post.TrueName','','trim');
            if($TrueName){$where['TrueName']=array('like','%'.$TrueName.'%');}

            $LoginIP=I('post.LoginIP','','trim');
            if($LoginIP){$where['LoginIP']=array('like','%'.$LoginIP.'%');}

            $IpCity=I('post.IpCity','','trim');
            if($IpCity){$where['IpCity']=array('like','%'.$IpCity.'%');}

            $Status=I('post.Status',-5,'intval');
            if($Status!=-5){$where['Status']=$Status;}

            $Retype=I('post.Retype',-5,'intval');
            if($Retype!=-5){$where['Retype']=$Retype;}

            $Mtype=I('post.Mtype',-5,'intval');
            if($Mtype!=-5){$where['Mtype']=$Mtype;}

            $ZsAdminID=I('post.ZsAdminID',-5,'int');
            if($ZsAdminID!=-5){
                $where['ZsAdminID']=$ZsAdminID;
            }

            if(!$MemAccount && !$Mobile && !$NickName && !$Status!=-5 && !$LoginIP && !$IpCity && !$Status!=-5 && !$Mtype!=-5 && !$ZsAdminID!=-5){
                $where['Referee'] = 0;  //顶级代理
            }

            //变更时间
            $StartTime=I('post.StartTime');  //按时间查询
            $EndTime=I('post.EndTime');
            $ToStartTime=$StartTime;
            $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
            if($StartTime!=null){
                if($EndTime!=null){
                    //有开始时间和结束时间
                    $where['RegTime']=array('between',$ToStartTime.','.$ToEndTime);
                }else{
                    //只有开始时间
                    $where['RegTime']=array('egt',$ToStartTime);
                }
            }else{
                //只有结束时间
                if($EndTime!=null){
                    $where['RegTime']=array('elt',$ToEndTime);
                }
            }


            $where['MemberType']=0; //会员
            //查询的列名
            $col='ID,MemAccount,TrueName,Mobile,LimitBalcance,NickName,TgadminID,Sex,ZsAdminID,RegTime,Status,Mtype,Retype,LoginTime,LoginIP,IpCity';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);
            $StatusArr=array(
                1=>"<span style='color:orange'>待审核</span>",
                2=>"<span style='color:green'>审核通过</span>",
                3=>"<span style='color:red'>禁用</span>",
                4=>"<span style='color:red'>黑名单</span>",
            );

            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            $decision = new DecisionController();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['LimitBalcance']=number_format($val['LimitBalcance'],2)."元";
                    $val['Status']=$StatusArr[$val['Status']];

                    $val['ZsAdminID']=M(self::T_ADMIN)->where(array('ID'=>$val['ZsAdminID'],'IsDel'=>'0'))->getField('TrueName');
                    $val['TgAdmin']=M('tg_admin')->where(array('ID'=>$val['TgadminID'],'IsDel'=>'0'))->getField('Name');
                    $val['state'] = has_mem($val['ID']) ? 'closed' : 'open';
                    $val['RepayNum'] = M('loans_hklist')->where(['UserID'=>$val['ID'],'PayStatus'=>1,'IsDel'=>0])->count();

                    $res = $decision->getDecision($val['ID']);
                    $reason = $res['msg'];
                    if($res['status'] == 1){
                        $val['Risk'] = '<span class="risk_reason" style="color: red">高危</span><span style="display: none">'.$reason.'</span>';
                    }elseif($res['status'] == 2){
                        $val['Risk'] = '<span style="color: orange">正常</span>';
                    }else{
                        $val['Risk'] = '<span class="risk_reason" style="color: green">优质</span><span class="risk_reason" style="display: none">'.$reason.'</span>';
                    }

                    $val['LoginAddress'] = $val['IpCity'];

                    $id = $this->getIdcard($val['ID']);
                    $bank = $this->getBank($val['ID']);
                    $social = $this->getSocial($val['ID']);
                    $tel = $this->getTel($val['ID']);
                    $tb = $this->getTaobao($val['ID']);
                    $ali = $this->getAlipay($val['ID']);
                    $zm = $this->getZhima($val['ID']);

                    if($id==1){
                        $val['IDCards'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['IDCards'] = '否';
                    }
                    if($bank==1){
                        $val['Bank'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Bank'] = '否';
                    }
                    if($social==1){
                        $val['Social'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Social'] = '否';
                    }
                    if($tel==1){
                        $val['Tel'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Tel'] = '否';
                    }
                    if($tb==1){
                        $val['Taobao'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Taobao'] = '否';
                    }
                    if($ali==1){
                        $val['Alipay'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Alipay'] = '否';
                    }
                    if($zm==1){
                        $val['Zhima'] = "<span style='color:orange'>是</span>";
                    }else{
                        $val['Zhima'] = '否';
                    }

                    if($val['Retype']=='0'){
                        $val['Retype']='网页';
                    }elseif($val['Retype']=='1'){
                        $val['Retype']='安卓';
                    }elseif($val['Retype']=='2'){
                        $val['Retype']='苹果';
                    }
                    if($val['Mtype']=='0'){
                        $val['Mtype']='正常';
                    }elseif($val['Mtype']=='1'){
                        $val['Mtype']='测试';
                    }
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
        }
//        var_dump($result);exit;
        $this->ajaxReturn($result);
    }

    /**
     * 编辑功能
     */
    public function Edit($ID=null){
        $ID=(int)$ID;
        $ForbidTime=M(self::T_TABLE)->where(array('ID'=>$ID))->getField('ForbidTime');
        $Limitdays='0';//限制天数 默认为0
        if($ForbidTime && $ForbidTime>date('Y-m-d H:i:s')){
            $lasttimes=(strtotime($ForbidTime)-time())/86400;
            $Limitdays=ceil($lasttimes);
        }
        $this->assign(array(
            'ID'=>$ID,
            'Limitdays'=>$Limitdays,
        ));
        $this->display();
    }

    /**
     * 查询详细信息
     */
    public function shows()
    {
        $id = I("request.ID", 0, 'intval');
        if ($id) {
            $model = M(self::T_TABLE);
            $result = $model->find($id);
            if(!$result==null){
                //对隐秘数据进行特殊化处理，防止泄露
                $result['Password']='******';
                $this->ajaxReturn($result);
            }else{
                //没有查询到内容
                $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
            }
        }
    }

    /**
     *保存数据
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('NickName','require','会员昵称必须填写！'), //默认情况下用正则进行验证
            );

            //根据表单提交的POST数据和验证规则条件，创建数据对象
            $Limitdays=I('post.Limitdays','0');
            $model=D(self::T_TABLE);
            $FormData=$model->validate($rules)->create();
            if(!$FormData){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                $data=array();  //创建新数组，用于存储保存的数据
                //更新数据判断
                if($FormData['ID']>0){
                    $pre_limibalance=M(self::T_TABLE)->where(array('ID'=>$FormData['ID']))->getField('LimitBalcance');
                    //只更新修改的字段
                    $data['LimitBalcance']=$FormData['LimitBalcance'];
                    $data['NickName']=$FormData['NickName'];
                    $data['Sex']=$FormData['Sex'];
                    $data['BorthDate']=$FormData['BorthDate'];
                    $data['Status']=$FormData['Status'];
                    $data['Mtype']=$FormData['Mtype'];
                    $data['ForbidTime']=date("Y-m-d H:i:s",strtotime("+".$Limitdays." day"));
                    //记录操作者信息和更新操作时间
                    $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                    $data['UpdateTime']=date("Y-m-d H:i:s");
                    $res=$model->where(array('ID'=>$FormData['ID']))->save($data);
                    if($res>0){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对');
        }
    }


    /**
     * 数据删除处理 单条或多条  逻辑删除
     */
    public function Del()
    {
        //获取删除数据id (单条或数组)
        $id = I("post.ID", '', 'trim');
        //校验
        $Mtype=M('mem_info')->where(array('ID'=>$id))->getField('Mtype');
        if($Mtype=='0'){
            $this->ajaxReturn(0,'正常用户不可删除!');
        }
        $checkinfos=M('mem_info')->where(array('Referee'=>$id,'IsDel'=>'0'))->find();
        if($checkinfos){
            $this->ajaxReturn(0,'此用户下面有子用户，不能删除!');
        }
        //测试账号，删除这个会员在本站里的所有记录信息
        //反馈留言
        M('message')->where(array('Uid'=>$id))->delete();
        //消息中心
        M('notice_message')->where(array('UserID'=>$id))->delete();
        M('notice_num')->where(array('UID'=>$id))->delete();
        M('notice_read')->where(array('UID'=>$id))->delete();
        //优惠劵
        M('mem_coupans')->where(array('UserID'=>$id))->delete();
        //身份证认证
        M('renzen_cards')->where(array('UserID'=>$id))->delete();
        //手机认证
        M('renzen_mobile')->where(array('UserID'=>$id))->delete();
        //支付宝认证
        M('renzen_alipay')->where(array('UserID'=>$id))->delete();
        //淘宝认证
        M('renzen_taobao')->where(array('UserID'=>$id))->delete();
        //银行卡认证
        M('renzen_bank')->where(array('UserID'=>$id))->delete();
        //基本信息认证
        M('renzen_memberinfo')->where(array('UserID'=>$id))->delete();
        //社交认证
        M('renzen_social')->where(array('UserID'=>$id))->delete();
        // 黑名单
        M('renzen_blacklist')->where(array('UserID'=>$id))->delete();
        // 小额贷款模型
        M('renzen_pdscore')->where(array('UserID'=>$id))->delete();
        //借款申请
        M('loans_applylist')->where(array('UserID'=>$id))->delete();
        //还款记录
        M('loans_hklist')->where(array('UserID'=>$id))->delete();
        //支付记录
        M('loans_paylist')->where(array('UserID'=>$id))->delete();

        //会员表
        M('mem_info')->where(array('ID'=>$id))->delete();
        $this->ajaxReturn(1,'会员信息删除成功!');
    }

    /**
     * 查看详情
     */
    public function Detail(){
        $ID=I('get.ID');
        if(!empty($ID)){
            $Info=M(self::T_TABLE)->where(array('ID'=>$ID))->find();
            $this->assign('Info',$Info);
        }
        //身份证认证
        $cardinfos=M('renzen_cards')->alias('a')
            ->field('a.ID,a.Status,a.RenzTime,a.CardFace,a.CardSide,a.Cardschi,b.Mobile,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $cardimgArr=array();
        $cardimgArr[]=$cardinfos['CardFace'];
        $cardimgArr[]=$cardinfos['CardSide'];
        $cardimgArr[]=$cardinfos['Cardschi'];
        //手机认证
        $mobileinfos=M('renzen_mobile')->alias('a')
            ->field('a.ZUserName,a.OpenDate,a.AccountBalance,a.Status,a.RenzTime,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //支付宝认证
        $alipayinfos=M('renzen_alipay')->alias('a')
            ->field('a.TaobaoName,a.Balance,a.HuabeiBalance,a.HuabeiLimit,a.HuabeiRet,a.ZFBMobile,a.Email,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //淘宝认证
        $taobaoinfos=M('renzen_taobao')->alias('a')
            ->field('a.BDMobile,a.Levels,a.Balance,a.JBalance,a.UserName,a.XFQuote,a.XYQuote,a.ZmScore,a.JieBei,a.YZStatus,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //基本信息认证
        $jibeninfos=M('renzen_memberinfo')->alias('a')
            ->field('a.*,b.Mobile,b.TrueName,b.NickName,b.Sex')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //社交认证
        $socialinfos=M('renzen_social')->field('ID,QQ,WeChat,Contents,Status,RenzTime')->where(array('UserID'=>$ID,'IsDel'=>'0'))->find();
        if($socialinfos['Contents']){
            $socialinfos['Contents']=unserialize($socialinfos['Contents']);
        }
        //print_r($socialinfos['Contents']);
        //银行卡认证
        $bankinfos=M('renzen_bank')->alias('a')
            ->field('a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        // 黑名单认证
        $blacklistInfo = M('renzen_blacklist')->alias('a')
            ->field('a.Response,a.Status,a.UpdateTime')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $blacklistInfo['Response'] = json_decode($blacklistInfo['Response'],true);
        // 小额模型认证
        $pdscoreInfo = M('renzen_pdscore')->alias('a')
            ->field('a.Response,a.Status,a.UpdateTime')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $this->assign(array(
            'cardinfos'=>$cardinfos,
            'cardimgArr'=>$cardimgArr,
            'mobileinfos'=>$mobileinfos,
            'alipayinfos'=>$alipayinfos,
            'taobaoinfos'=>$taobaoinfos,
            'jibeninfos'=>$jibeninfos,
            'socialinfos'=>$socialinfos,
            'bankinfos'=>$bankinfos,
            'blacklistInfo'=>$blacklistInfo,
            'pdscoreInfo'=>$pdscoreInfo,
        ));
        $this->display();
    }

    /**
     * 查看详情
     */
    public function Rendetaial(){
        $ID=I('get.ID');
        if(!empty($ID)){
            $Info=M(self::T_TABLE)->where(array('ID'=>$ID))->find();
            $this->assign('Info',$Info);
        }
        //身份证认证
        $cardinfos=M('renzen_cards')->alias('a')
            ->field('a.ID,a.Status,a.RenzTime,a.CardFace,a.CardSide,a.Cardschi,b.Mobile,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $cardimgArr=array();
        $cardimgArr[]=$cardinfos['CardFace'];
        $cardimgArr[]=$cardinfos['CardSide'];
        $cardimgArr[]=$cardinfos['Cardschi'];
        //手机认证
        $mobileinfos=M('renzen_mobile')->alias('a')
            ->field('a.ZUserName,a.OpenDate,a.AccountBalance,a.Status,a.RenzTime,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //支付宝认证
        $alipayinfos=M('renzen_alipay')->alias('a')
            ->field('a.TaobaoName,a.Balance,a.HuabeiBalance,a.HuabeiLimit,a.HuabeiRet,a.ZFBMobile,a.Email,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //淘宝认证
        $taobaoinfos=M('renzen_taobao')->alias('a')
            ->field('a.BDMobile,a.Levels,a.Balance,a.JBalance,a.UserName,a.XFQuote,a.XYQuote,a.ZmScore,a.JieBei,a.YZStatus,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //基本信息认证
        $jibeninfos=M('renzen_memberinfo')->alias('a')
            ->field('a.*,b.Mobile,b.TrueName,b.NickName,b.Sex')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        //社交认证
        $socialinfos=M('renzen_social')->field('ID,QQ,WeChat,Contents,Status,RenzTime')->where(array('UserID'=>$ID,'IsDel'=>'0'))->find();
        if($socialinfos['Contents']){
            $socialinfos['Contents']=unserialize($socialinfos['Contents']);
        }
        //print_r($socialinfos['Contents']);
        //银行卡认证
        $bankinfos=M('renzen_bank')->alias('a')
            ->field('a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        // 黑名单认证
        $blacklistInfo = M('renzen_blacklist')->alias('a')
            ->field('a.Response,a.Status,a.UpdateTime')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $blacklistInfo['Response'] = json_decode($blacklistInfo['Response'],true);
        // 小额模型认证
        $pdscoreInfo = M('renzen_pdscore')->alias('a')
            ->field('a.Response,a.Status,a.UpdateTime')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID'=>$ID))->find();
        $pdscoreInfo['Response'] = json_decode($pdscoreInfo['Response'],true);
        $this->assign(compact('cardinfos','cardimgArr','mobileinfos','alipayinfos','taobaoinfos','jibeninfos',
            'socialinfos','bankinfos','blacklistInfo','pdscoreInfo'));
        $this->display();
    }


    /**
     * 通话记录
     */
    public function CallBill(){
        $UID=I('get.UID');
        if(!empty($UID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $CallBill=M(self::T_MOBILE)->where(array("UserID"=>$UID,'Status'=>1))->getField('CallBill');
            if($CallBill){
                $CallBill=unserialize($CallBill);
                //数据分页
                $array='';
                $array=array_slice($CallBill,$start,$rows);
                $result=array();
                if($array){
                    foreach ($array as $val){
                        $result['rows'][]=$val;
                    }
                    $result['total']=count($CallBill);
                }

            }else{
                $result['rows'][]=array();
                $result['total']=0;
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     *手机通讯录
     */
    public function Phonelist(){
        $UserID=I('get.UID');
        //排序
        $sort=I('post.sort');
        $order=I('post.order');
        if(!empty($UserID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $Phonelist=M('renzen_social')->where(array("UserID"=>$UserID))->getField('Phonelist');
            if($Phonelist){
                $Phonelist=unserialize($Phonelist);
                //排序功能
                if($sort=='sums'){
                    if($order=='desc'){
                        $Phonelist=$this->arraySequence($Phonelist, 'sums', $sort = 'SORT_DESC');
                    }elseif($order=='asc'){
                        $Phonelist=$this->arraySequence($Phonelist, 'sums', $sort = 'SORT_ASC');
                    }
                }
                //数据分页
                $array='';
                $array=array_slice($Phonelist,$start,$rows);
                $result=array();
                if($array){
                    foreach ($array as $val){
                        $result['rows'][]=$val;
                    }
                    $result['total']=count($Phonelist);
                }

            }else{
                $result['rows'][]=array();
                $result['total']=0;
            }
            $this->ajaxReturn($result);
        }
    }

    /**
     * 借款申请
     */
    public function ApplyDetail(){
        $UID=I('get.UID');
        if(!empty($UID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $sort='UpdateTime Desc';

            $where['UserID']=$UID;
            //查询的列名
            $col='ID,LoanNo,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,LoanStatus,BackM,OrderSn,YyFkTime,SqAdminID,LoanType,Status,ServiceID,ShTime';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_APPLYLIST,$where,$page,$rows,$sort,$col);
            $LoanStatusArr = array(0=>"申请中",1=>"放款中",2=>"已放款",3=>"已完成",4=>"已取消",5=>"已拒绝");
            $StatusArr = array(0=>'待审核',1=>'审核成功',2=>'审核失败');
            $LoanTypeArr = array(0=>'普通',1=>'续借',2=>'分期');
            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['SqAdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['SqAdminID']),'TrueName');
                    $val['ServiceID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['ServiceID']),'TrueName');
                    $val['LoanStatus']=$LoanStatusArr[$val['LoanStatus']];
                    $val['Status']=$StatusArr[$val['Status']];
                    $val['LoanType']=$LoanTypeArr[$val['LoanType']];
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }


    /**
     * 还款记录
     */
    public function RepayDetail(){
        $UID=I('get.UID');
        if(!empty($UID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $sort='UpdateTime Desc';

            $where['UserID']=$UID;
            //查询的列名
            $col='ID,LoanNo,TotalMoney,HkTime,CostPayable,RatePayable,SeviceCostPayable,FinePayable,Accounts,TradeNo,TradeRemark,PayType,PayStatus,AdminID,ShTime,Status';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_REPAY,$where,$page,$rows,$sort,$col);
            $PayTypeArr = array(0=>"未付款",1=>"支付宝",2=>"微信",3=>"银联",4=>"代付");
            $PayStatusArr = array(0=>'待支付',1=>'已支付');
            $StatusArr = array(0=>'待审核',1=>'审核通过',2=>'审核未通过');
            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['AdminID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['AdminID']),'UserName');
                    $val['PayType']=$PayTypeArr[$val['PayType']];
                    $val['Status']=$StatusArr[$val['Status']];
                    $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }


    /**
     * 导出列表
     */
    //会员信息导出功能
    public function exportexcel(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort='RegTime Desc';
        }

        $UserName=I('post.UserName','','trim');
        if($UserName){$where['UserName']=array('like','%'.$UserName.'%');}

        $Mobile=I('post.Mobile','','trim');
        if($Mobile){$where['Mobile']=array('like','%'.$Mobile.'%');}

        $NickName=I('post.NickName','','trim');
        if($NickName){$where['NickName']=array('like','%'.$NickName.'%');}

        $TrueName=I('post.TrueName','','trim');
        if($TrueName){$where['TrueName']=array('like','%'.$TrueName.'%');}

        $LoginIP=I('post.LoginIP','','trim');
        if($LoginIP){$where['LoginIP']=array('like','%'.$LoginIP.'%');}

        $IpCity=I('post.IpCity','','trim');
        if($IpCity){$where['IpCity']=array('like','%'.$IpCity.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        //变更时间
        $StartTime=I('post.StartTime');  //按时间查询
        $EndTime=I('post.EndTime');
        $ToStartTime=$StartTime;
        $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
        if($StartTime!=null){
            if($EndTime!=null){
                //有开始时间和结束时间
                $where['RegTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['RegTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($EndTime!=null){
                $where['RegTime']=array('elt',$ToEndTime);
            }
        }


        $where['MemberType']=0; //会员
        $where['IsDel']=0;
        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['UID']=$val['ID'];
                $val['LimitBalcance']=number_format($val['LimitBalcance'],2)."元";
                $val['Balance']=number_format($val['Balance'],2)."元";
                if(!$val['TrueName']){
                    $val['TrueName']="待认证";
                }
                switch ($val['Status']){
                    case 1:$val['Status']="启用";break;
                    case 0:$val['Status']="禁用";break;
                }
                switch ($val['Sex']){
                    case 2:$val['Sex']="女";break;
                    case 1:$val['Sex']="男";break;
                    case 0:$val['Sex']="保密";break;
                }
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>会员账号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>手机号码</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>借款额度</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>性别</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>出生日期</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>会员状态</td>
            </tr>';

        foreach($result['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['UserName'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Mobile'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['LimitBalcance'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Sex'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['BorthDate'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Status'].'</td>
            </tr>';
        }

        $html .= '</table>';
//        ob_end_clean();
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        $str_filename = date('Y-m-d', time()).'会员信息列表';
        header("Content-type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=$str_filename.xls");

        echo $html;
        exit;
    }
    /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
    public function arraySequence($array, $field, $sort = 'SORT_DESC'){
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
    //设置客服页面
    public function setcustomer(){
        $id = I("request.ID", 0, 'intval');
        $res=M(self::T_TABLE)->field('ID,ZsAdminID')->where(array("ID"=>$id))->find();
        $kefuArr=M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID'=>'7','IsDel'=>'0'))->select();
        $this->assign(array(
            'res'=>$res,
            'kefuArr'=>$kefuArr,
        ));
        $this->display();
    }
    //设置客服保存，把此会员名下的所有的单子，都转到这个客服下面
    public function zordersave(){
        $ID=I('post.ID','');
        $ZsAdminID=I('post.ZsAdminID','0');
        if(!$ZsAdminID){
            $this->ajaxReturn(0,'很抱歉，请选择专属客服！');
        }
        $result=M(self::T_TABLE)->where(array('ID'=>$ID))->save(array('ZsAdminID'=>$ZsAdminID));
        if($result){
            //把此会员名下的所有借款申请都分配到此客服名下
            M('loans_applylist')->where(array('UserID'=>$ID))->save(array('SqAdminID'=>$ZsAdminID,'UpdateTime'=>date('Y-m-d H:i:s')));
            $this->ajaxReturn(1,'恭喜您，客服分配成功！');
        }else{
            $this->ajaxReturn(0,'很抱歉，客服分配失败！');
        }
    }

    //天机报告 独立页面
    public function tianji(){
        $id=I('post.ID',0,'intval');
        $mobileinfos=M('renzen_mobile')->field('ID,TUserID,TaskID')->where(array('UserID'=>$id,'IsDel'=>'0'))->find();
        if(!$mobileinfos['TaskID'] && !$mobileinfos['TUserID'] && !$mobileinfos['MobileData']){
            $this->ajaxReturn(0,'暂不能查看报告!');
        }
        $jumpurl="http://".$_SERVER['HTTP_HOST']."/index.php/Tianji/report?searchId=".$mobileinfos['TUserID']."&niqueId=".$mobileinfos['TaskID'];
        $this->ajaxReturn(1,$jumpurl);
    }

    /**
     * 有盾报表
     */
    public function udcredit()
    {
        $ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
        #获取有盾数据
        $rzCard = M(self::T_IDCARD)->where(['UserID'=>$ID])->find();
        $id = $rzCard['ID'];
        $this->redirect('/Renzen/Cards/udcredit',['ID'=>$id]);
    }

}
