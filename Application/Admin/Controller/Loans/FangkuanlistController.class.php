<?php
/**
 *
 * 功能说明: 放款记录控制器
 */

namespace Admin\Controller\Loans;

use Admin\Controller\System\BaseController;
use XBCommon;
use Home\Controller\Curl;
use Home\Controller\Crypt3Des;
class FangkuanlistController extends BaseController
{

    const T_TABLE = 'loans_applylist';
    const T_MEMINFO = 'mem_info';
    const T_ADMIN = 'sys_administrator';
    const T_ACCOUNTS = 'sys_accounts';
    const T_TGADMIN = 'tg_admin';


    const T_CARDS = 'renzen_cards';//身份证认证表
    const T_MOBILE = 'renzen_mobile';//手机认证表
    const T_ALIPAY = 'renzen_alipay';//支付宝认证表
    const T_TAOBAO = 'renzen_taobao';//淘宝认证表
    const T_MEMBERINFO = 'renzen_memberinfo';//基本信息认证表
    const T_SOCIAL = 'renzen_social';//社交认证表
    const T_BANK = 'renzen_bank';//银行卡认证表

    public function _initialize()
    {
        parent::_initialize();
        $this->LoanStatus = array(1 => "待放款", 2 => "已放款", 3 => "已完成");
        $this->Status = array(0 => '待审核', 1 => '审核成功', 2 => '审核失败');
        $this->LoanType = array(0 => '普通', 1 => '续期', 2 => '分期');
    }

    public function index()
    {
        $kefuArr = M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID' => '7', 'Status' => '1', 'IsDel' => '0'))->select();
        $RoleID = $_SESSION['AdminInfo']['RoleID'];

        $this->assign(array(
            'LoanStatus' => $this->LoanStatus,
            'Status' => $this->Status,
            'kefuArr' => $kefuArr,
            'RoleID' => $RoleID,
        ));
        $this->display();
    }

    /**
     * 后台用户管理的列表数据获取
     * @access   public
     * @return   object    返回json数据
     */
    public function DataList()
    {
        $page = I('post.page', 1, 'intval');
        $rows = I('post.rows', 20, 'intval');
        $sort = I('post.sort');
        $order = I('post.order');
        if ($sort && $order) {
            $sort = $sort . ' ' . $order;
        } else {
            $sort = 'LoanStatus asc,ApplyTime desc';
        }

        //搜索条件
        $TrueName = I('post.TrueName', '');
        if ($TrueName) {
            $memidArr = M(self::T_MEMINFO)->field('ID')->where(array('TrueName' => array('like', '%' . $TrueName . '%')))->select();
            if ($memidArr) {
                $memids = array();
                foreach ($memidArr as $k => $v) {
                    $memids[] = $v['ID'];
                }
                //$memids=array_column($memidArr, 'ID');
                $where['UserID'] = array('in', $memids);
            } else {
                $where['UserID'] = array('eq', '0');
            }
        }
        $Mobile = I('post.Mobile', '');
        if ($Mobile) {
            $memidArr = '';
            $memidArr = M(self::T_MEMINFO)->field('ID')->where(array('Mobile' => array('eq', $Mobile)))->select();
            if ($memidArr) {
                $memids = array();
                foreach ($memidArr as $k => $v) {
                    $memids[] = $v['ID'];
                }
                $where['UserID'] = array('in', $memids);
            } else {
                $where['UserID'] = array('eq', '0');
            }
        }
        $LoanStatus = I('post.LoanStatus', -5, 'int');
        if ($LoanStatus != -5) {
            $where['LoanStatus'] = $LoanStatus;
        } else {
            $where['LoanStatus'] = array('in', array('1', '2', '3'));
        }
        $LoanNo = I('post.LoanNo', '');
        if ($LoanNo) {
            $where['LoanNo'] = $LoanNo;
        }
        $OrderSn = I('post.OrderSn', '');
        if ($OrderSn) {
            $where['OrderSn'] = $OrderSn;
        }
        $ReplaymentType = I('post.ReplaymentType', -5, 'int');
        if ($ReplaymentType != -5) {
            $where['ReplaymentType'] = $ReplaymentType;
        }
        $LoanType = I('post.LoanType', -5, 'int');
        if ($LoanType != -5) {
            $where['LoanType'] = $LoanType;
        }
        $SqAdminID = I('post.SqAdminID', -5, 'int');
        if ($SqAdminID != -5) {
            $where['SqAdminID'] = $SqAdminID;
        }
        //放款时间
        $StartTime = I('post.StartTime');  //按时间查询
        $EndTime = I('post.EndTime');
        $ToStartTime = $StartTime;
        $ToEndTime = date('Y-m-d', strtotime($EndTime . "+1 day"));
        if ($StartTime != null) {
            if ($EndTime != null) {
                //有开始时间和结束时间
                $where['OpenTime'] = array('between', $ToStartTime . ',' . $ToEndTime);
            } else {
                //只有开始时间
                $where['OpenTime'] = array('egt', $ToStartTime);
            }
        } else {
            //只有结束时间
            if ($EndTime != null) {
                $where['OpenTime'] = array('elt', $ToEndTime);
            }
        }

        #还款时间
        $hkStartTime = I('post.hkStartTime');  //按时间查询
        $hkEndTime = I('post.hkEndTime');
        $ToStartTime = $hkStartTime;
        $ToEndTime = date('Y-m-d', strtotime($hkEndTime . "+1 day"));
        if ($hkStartTime != null) {
            if ($hkEndTime != null) {
                //有开始时间和结束时间
                $where['YyFkTime'] = array('between', $ToStartTime . ',' . $ToEndTime);
            } else {
                //只有开始时间
                $where['YyFkTime'] = array('egt', $ToStartTime);
            }
        } else {
            //只有结束时间
            if ($hkEndTime != null) {
                $where['YyFkTime'] = array('elt', $ToEndTime);
            }
        }

        #实际还款时间
        /*$RealHhkStartTime=I('post.RealHhkStartTime');  //按时间查询
        $RealHkEndTime=I('post.RealHkEndTime');
        $ToStartTime=$RealHhkStartTime;
        $ToEndTime=date('Y-m-d',strtotime($RealHkEndTime."+1 day"));
        if($RealHhkStartTime!=null){
            if($RealHkEndTime!=null){
                //有开始时间和结束时间
                $where['RealHkTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['RealHkTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($RealHkEndTime!=null){
                $where['RealHkTime']=array('elt',$ToEndTime);
            }
        }*/

        //不是管理员的话，只能看到自己的单子
        if ($_SESSION['AdminInfo']['RoleID'] != '2') {
            if ($_SESSION['AdminInfo']['RoleID'] == '10') {
                //催收专员
                $where['CsadminID'] = $_SESSION['AdminInfo']['AdminID'];
            } elseif ($_SESSION['AdminInfo']['RoleID'] == '7') {
                //客服专员
                $where['SqAdminID'] = $_SESSION['AdminInfo']['AdminID'];
            } elseif ($_SESSION['AdminInfo']['RoleID'] == '8') {
                //放款专员
                $where['FKadminID'] = $_SESSION['AdminInfo']['AdminID'];
            }
        }

        $where['IsDel'] = 0;
        //查询的数据表字段名
        $col = 'LoanType,ID,UserID,ApplyMoney,SqAdminID,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,OpenM,LoanStatus,FkServiceID,OpenTime,ReplaymentType,RepaymentAccount,TradeNum,UserAccount,OrderSn,LoanNo';//默认全字段查询

        //获取主表的数据
        $query = new XBCommon\XBQuery;
        $array = $query->GetDataList(self::T_TABLE, $where, $page, $rows, $sort, $col);

        $LoanStatusArr = $this->LoanStatus;
        $StatusArr = $this->Status;
        $LoanTypeArr = $this->LoanType;
        //重组数据返还给前段
        $result = array();
        if ($array['rows']) {
            foreach ($array['rows'] as $val) {
                $meminfo = M(self::T_MEMINFO)->field('TrueName,TgadminID,Mobile')->where(array('ID' => $val['UserID']))->find();
                $val['TrueName'] = $meminfo['TrueName'];
                $val['Mobile'] = $meminfo['Mobile'];
                $val['FkServiceID'] = $query->GetValue(self::T_ADMIN, array('ID' => (int)$val['FkServiceID']), 'TrueName');
                $val['SqAdminID'] = $query->GetValue(self::T_ADMIN, array('ID' => (int)$val['SqAdminID']), 'TrueName');
                $ls_loanstatus = '';
                $ls_loanstatus = $val['LoanStatus'];//代付用
                if ($val['LoanStatus'] == 1) {
                    $val['LoanStatus'] = '<span style="color:red;">' . $LoanStatusArr[$val['LoanStatus']] . '</span>';
                } else {
                    $val['LoanStatus'] = $LoanStatusArr[$val['LoanStatus']];
                }
                $val['Status'] = $StatusArr[$val['Status']];
                $val['LoanType'] = $LoanTypeArr[$val['LoanType']];
                if ($val['ReplaymentType'] == '0') {
                    $val['ReplaymentType'] = '未打款';
                } elseif ($val['ReplaymentType'] == '1') {
                    $val['ReplaymentType'] = '支付宝';
                } elseif ($val['ReplaymentType'] == '2') {
                    $val['ReplaymentType'] = '微信';
                } elseif ($val['ReplaymentType'] == '3') {
                    $val['ReplaymentType'] = '银联';
                } elseif ($val['ReplaymentType'] == '4') {
                    $val['ReplaymentType'] = '代付';
                    //代付临时状态
                    if ($ls_loanstatus == '1') {
                        $val['LoanStatus'] = '<span style="color:green;">代付审核中</span>';
                    }
                }
                $val['Tgadmin'] = $query->GetValue(self::T_TGADMIN, array('ID' => (int)$meminfo['TgadminID']), 'Name');

                $result['rows'][] = $val;
            }
            $result['total'] = $array['total'];
        }
        $this->ajaxReturn($result);
    }

    //确认放款页面
    public function cofirmloan()
    {
        $id = I('get.ID', 0, 'intval');
        $res = M(self::T_TABLE)->where(array("ID" => $id))->find();
        //查询会员账号
        $userbanks = M('mem_bank')->alias('a')
            ->field('a.ID,a.BankCode,b.Name')
            ->join('left join xb_mem_banktype b on a.BankType=b.ID')
            ->where(array('UserID' => $res['UserID']))->select();
        $this->assign(array(
            "res" => $res,
            "userbanks" => $userbanks,
        ));
        $this->display();
    }

    //确认放款信息提交处理
    public function aduitsave()
    {
        $ID = I('post.ID', '');
        $TradeNum = I('post.TradeNum', '');
        $ReplaymentType = I('post.ReplaymentType', '');
        $RepaymentAccount = I('post.RepaymentAccount', '');
        $UserAccount = I('post.UserAccount', '');
        $editdays = I('post.editdays', '');
        //审核校验
        $applyinfos = M(self::T_TABLE)->where(array("ID" => $ID))->find();
        if (!$applyinfos) {
            $this->ajaxReturn(0, '很抱歉，无此申请记录！');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，不能重复放款！');
        }

        if (!$TradeNum) {
            $this->ajaxReturn(0, '交易号不能为空!');
        }
        if (!$RepaymentAccount) {
            $this->ajaxReturn(0, '打款账号不能为空!');
        }
        // if(!$UserAccount){
        //     $this->ajaxReturn(0,'会员账号不能为空!');
        // }
        if ($editdays) {
            if (!is_numeric($editdays)) {
                $this->ajaxReturn(0, '修改时间必须为数字!');
            }
            if ($editdays <= 0) {
                $this->ajaxReturn(0, '修改时间必须大于0!');
            }
        }
        //查出会员的银行卡认证账户
        // $userbankinfo=M(self::T_BANK)->where(array('UserID'=>$applyinfos['UserID'],'IsDel'=>'0'))->order('ID desc')->find();
        // if(!$userbankinfo){
        //     $this->ajaxReturn(0,'银行卡没有认证,不能进行此操作!');
        // }
        // if($userbankinfo['Status']!='1'){
        //     $this->ajaxReturn(0,'银行卡没有认证通过!');
        // }
        $sdata = array();//修改的数据
        if ($editdays) {
            //修改放款时间，用于测试
            $sdata['ApplyTime'] = date("Y-m-d H:i:s", strtotime("-" . $editdays . " day"));
            $sdata['LoanStatus'] = '2';
            $sdata['ShTime'] = date("Y-m-d H:i:s", strtotime("-" . $editdays . " day"));
            $sdata['FkServiceID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['OpenTime'] = date("Y-m-d H:i:s", strtotime("-" . $editdays . " day"));

            $fktimes = date("Y-m-d H:i:s", strtotime("-" . $editdays . " day"));
            //$YyFkTime=strtotime($fktimes)+$applyinfos['ApplyDay']*86400;
            $YyFkTime = strtotime($fktimes) + ($applyinfos['ApplyDay'] - 1) * 86400;
            $sdata['YyFkTime'] = date("Y-m-d H:i:s", $YyFkTime);

            $sdata['ReplaymentType'] = $ReplaymentType;
            $sdata['RepaymentAccount'] = $RepaymentAccount;
            $sdata['TradeNum'] = $TradeNum;
            if ($UserAccount) {
                $sdata['UserAccount'] = $UserAccount;
            }
            $sdata['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['UpdateTime'] = date("Y-m-d H:i:s", strtotime("-" . $editdays . " day"));
        } else {
            //正常放款
            $sdata['LoanStatus'] = '2';
            $sdata['FkServiceID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['OpenTime'] = date('Y-m-d H:i:s');
            //$sdata['YyFkTime']=date("Y-m-d H:i:s",strtotime("+".$applyinfos['ApplyDay']." day"));
            $realdays = $applyinfos['ApplyDay']-1;
            $sdata['YyFkTime'] = date("Y-m-d H:i:s", strtotime("+" . $realdays . " day"));
            $sdata['ReplaymentType'] = $ReplaymentType;
            $sdata['RepaymentAccount'] = $RepaymentAccount;
            $sdata['TradeNum'] = $TradeNum;
//            $sdata['OpenM']=$applyinfos['ApplyMoney']-$applyinfos['AdoptMoney']-$applyinfos['FJMoney']-$applyinfos['Interest']; 已经在审核通过时进行计算
            if ($UserAccount) {
                $sdata['UserAccount'] = $UserAccount;
            }
            $sdata['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['UpdateTime'] = date('Y-m-d H:i:s');
        }
        $model = M();
        $model->startTrans();
        $result = $model->table('xb_loans_applylist')->where(array("ID" => $ID))->save($sdata);
        if ($result) {
            //发送消息通知信息
            $msgcont = '尊敬的会员，您提交的订单：' . $applyinfos['LoanNo'] . '，打款成功!金额为：' . $applyinfos['ApplyMoney'] . '元。';
            $mobile = M('mem_info')->where(array('ID' => $applyinfos['UserID']))->getField('Mobile');
            send_message($mobile, $msgcont);//发送短信消息
            send_mem_notics($applyinfos['UserID'], $msgcont);//发送站内通知消息
//            self_sendjuan($applyinfos['UserID'],'5');//邀请的好友申请专卖成功立送
            $this->getYun($mobile,$applyinfos['ID'] ,$applyinfos['ApplyMoney'] );
            $model->commit();
            $this->ajaxReturn(1, '恭喜您，放款操作成功！');
        } else {
            $model->rollback();
            $this->ajaxReturn(0, '很抱歉，放款操作失败！');
        }
    }

    public function detail()
    {
        $ID = I('request.ID');
        $infos = M(self::T_TABLE)->alias('a')
            ->field('a.*,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.ID' => $ID))->find();
        if ($infos['FkServiceID']) {
            $infos['FkServiceID'] = M(self::T_ADMIN)->where(array('ID' => $infos['FkServiceID']))->getField('TrueName');
        }
        $LoanStatusArr = $this->LoanStatus;
        $StatusArr = $this->Status;
        $LoanTypeArr = $this->LoanType;
        $infos['LoanStatus'] = $LoanStatusArr[$infos['LoanStatus']];
        $infos['Status'] = $StatusArr[$infos['Status']];
        $infos['LoanType'] = $LoanTypeArr[$infos['LoanType']];
        //身份证认证
        $cardinfos = M('renzen_cards')->alias('a')
            ->field('a.ID,a.Yddatas,a.Status,a.RenzTime,a.CardFace,a.CardSide,a.Cardschi,b.Mobile,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        if ($cardinfos['Yddatas']) {
            $cardinfos['Yddatas'] = unserialize($cardinfos['Yddatas']);
        }
        $cardimgArr = array();
        $cardimgArr[] = $cardinfos['CardFace'];
        $cardimgArr[] = $cardinfos['CardSide'];
        $cardimgArr[] = $cardinfos['Cardschi'];
        //手机认证
        $mobileinfos = M('renzen_mobile')->alias('a')
            ->field('a.ZUserName,a.OpenDate,a.AccountBalance,a.Status,a.RenzTime,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        //支付宝认证
        $alipayinfos = M('renzen_alipay')->alias('a')
            ->field('a.TaobaoName,a.Balance,a.HuabeiBalance,a.HuabeiLimit,a.HuabeiRet,a.ZFBMobile,a.Email,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        //淘宝认证
        $taobaoinfos = M('renzen_taobao')->alias('a')
            ->field('a.BDMobile,a.Levels,a.Balance,a.JBalance,a.UserName,a.XFQuote,a.XYQuote,a.ZmScore,a.JieBei,a.YZStatus,a.RenzTime,a.Status,b.Mobile,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        //基本信息认证
        $jibeninfos = M('renzen_memberinfo')->alias('a')
            ->field('a.*,b.Mobile,b.TrueName,b.NickName,b.Sex')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        //社交认证
        $socialinfos = M('renzen_social')->field('ID,QQ,WeChat,Contents,Status,RenzTime')->where(array('UserID' => $infos['UserID'], 'IsDel' => '0'))->find();
        if ($socialinfos['Contents']) {
            $socialinfos['Contents'] = unserialize($socialinfos['Contents']);
        }
        //银行卡认证
        $bankinfos = M('renzen_bank')->alias('a')
            ->field('a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array('a.UserID' => $infos['UserID']))->find();
        $this->assign(array(
            'infos' => $infos,
            'cardinfos' => $cardinfos,
            'cardimgArr' => $cardimgArr,
            'mobileinfos' => $mobileinfos,
            'alipayinfos' => $alipayinfos,
            'taobaoinfos' => $taobaoinfos,
            'jibeninfos' => $jibeninfos,
            'socialinfos' => $socialinfos,
            'bankinfos' => $bankinfos,
        ));
        $this->display();
    }

    //转单页面
    public function zorder()
    {
        $id = I('get.ID', 0, 'intval');
        $res = M(self::T_TABLE)->field('ID,FKadminID')->where(array("ID" => $id))->find();
        $kefuArr = M(self::T_ADMIN)->field('ID,TrueName')->where(array('RoleID' => '8', 'Status' => '1', 'IsDel' => '0'))->select();
        $this->assign(array(
            'res' => $res,
            'kefuArr' => $kefuArr,
        ));
        $this->display();
    }

    //转单保存
    public function zordersave()
    {
        $ID = I('post.ID', '');
        $FKadminID = I('post.FKadminID', '0');
        if (!$FKadminID) {
            $this->ajaxReturn(0, '很抱歉，请选择放款专员！');
        }
        $result = M(self::T_TABLE)->where(array('ID' => $ID))->save(array('FKadminID' => $FKadminID));
        if ($result) {
            $this->ajaxReturn(1, '恭喜您，转单成功成功！');
        } else {
            $this->ajaxReturn(0, '很抱歉，转单失败！');
        }
    }

    //取消放款页面
    public function cancelloan()
    {
        $id = I('get.ID', 0, 'intval');
        $res = M(self::T_TABLE)->field('ID')->where(array("ID" => $id))->find();
        $this->assign(array(
            'res' => $res,
        ));
        $this->display();
    }

    //取消放款保存
    public function cancelsave()
    {
        $ID = I('post.ID', '');
        $Remark = I('post.Remark', '');
        //审核校验
        $applyinfos = M(self::T_TABLE)->field('ID,UserID,LoanNo,LoanStatus')->where(array("ID" => $ID))->find();
        if (!$applyinfos) {
            $this->ajaxReturn(0, '很抱歉，无此申请记录！');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，只能取消未放款的申请！');
        }
        if (!$Remark) {
            $this->ajaxReturn(0, '很抱歉，请写明取消原因！');
        }
        //$result=M(self::T_TABLE)->where(array('ID'=>$ID))->save(array('FKadminID'=>$Remark));
        $sdata = array();//修改的数据
        $sdata['LoanStatus'] = '5';
        $sdata['ServiceID'] = $_SESSION['AdminInfo']['AdminID'];
        $sdata['ShTime'] = date('Y-m-d H:i:s');
        $sdata['Status'] = '2';
        $sdata['Remark'] = $Remark;
        $sdata['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
        $sdata['UpdateTime'] = date('Y-m-d H:i:s');

        $model = M();
        $model->startTrans();
        $result = $model->table('xb_loans_applylist')->where(array("ID" => $ID))->save($sdata);
        if ($result) {
            //发送消息通知信息
            $msgcont = '尊敬的会员，您提交的订单：' . $applyinfos['LoanNo'] . '，审核失败!失败原因：' . $Remark;
            send_mem_notics($applyinfos['UserID'], $msgcont);//发送站内通知消息

            $model->commit();
            $this->ajaxReturn(1, '恭喜您，取消操作成功！');
        } else {
            $model->rollback();
            $this->ajaxReturn(0, '很抱歉，取消操作失败！');
        }
    }

    //导出功能
    public function exportexcel()
    {
        $sort = I('post.sort');
        $order = I('post.order');
        if ($sort && $order) {
            $sort = $sort . ' ' . $order;
        } else {
            $sort = 'ID desc';
        }

        //搜索条件
        $TrueName = I('post.TrueName', '');
        if ($TrueName) {
            $memidArr = M(self::T_MEMINFO)->field('ID')->where(array('TrueName' => array('like', '%' . $TrueName . '%')))->select();
            if ($memidArr) {
                $memids = array();
                foreach ($memidArr as $k => $v) {
                    $memids[] = $v['ID'];
                }
                //$memids=array_column($memidArr, 'ID');
                $where['UserID'] = array('in', $memids);
            } else {
                $where['UserID'] = array('eq', '0');
            }
        }
        $Mobile = I('post.Mobile', '');
        if ($Mobile) {
            $memidArr = '';
            $memidArr = M(self::T_MEMINFO)->field('ID')->where(array('Mobile' => array('eq', $Mobile)))->select();
            if ($memidArr) {
                $memids = array();
                foreach ($memidArr as $k => $v) {
                    $memids[] = $v['ID'];
                }
                $where['UserID'] = array('in', $memids);
            } else {
                $where['UserID'] = array('eq', '0');
            }
        }
        $LoanStatus = I('post.LoanStatus', -5, 'int');
        if ($LoanStatus != -5) {
            $where['LoanStatus'] = $LoanStatus;
        } else {
            $where['LoanStatus'] = array('in', array('1', '2', '3'));
        }
        $LoanNo = I('post.LoanNo', '');
        if ($LoanNo) {
            $where['LoanNo'] = $LoanNo;
        }
        $OrderSn = I('post.OrderSn', '');
        if ($OrderSn) {
            $where['OrderSn'] = $OrderSn;
        }
        $ReplaymentType = I('post.ReplaymentType', -5, 'int');
        if ($ReplaymentType != -5) {
            $where['ReplaymentType'] = $ReplaymentType;
        }
        //放款时间
        $StartTime = I('post.StartTime');  //按时间查询
        $EndTime = I('post.EndTime');
        $ToStartTime = $StartTime;
        $ToEndTime = date('Y-m-d', strtotime($EndTime . "+1 day"));
        if ($StartTime != null) {
            if ($EndTime != null) {
                //有开始时间和结束时间
                $where['OpenTime'] = array('between', $ToStartTime . ',' . $ToEndTime);
            } else {
                //只有开始时间
                $where['OpenTime'] = array('egt', $ToStartTime);
            }
        } else {
            //只有结束时间
            if ($EndTime != null) {
                $where['OpenTime'] = array('elt', $ToEndTime);
            }
        }

        $where['IsDel'] = 0;
        //查询的数据表字段名
        $col = 'ID,UserID,ApplyMoney,AdoptMoney,FJMoney,Interest,CoMoney,ApplyDay,ApplyTime,OpenM,LoanStatus,FkServiceID,OpenTime,ReplaymentType,RepaymentAccount,TradeNum,UserAccount,OrderSn,LoanNo';//默认全字段查询

        //获取主表的数据
        $query = new XBCommon\XBQuery;
        $array['rows'] = M(self::T_TABLE)->where($where)->order($sort)->select();

        $LoanStatusArr = $this->LoanStatus;
        $StatusArr = $this->Status;
        $LoanTypeArr = $this->LoanType;
        //重组数据返还给前段
        $result = array();
        if ($array['rows']) {
            foreach ($array['rows'] as $val) {
                $meminfo = M(self::T_MEMINFO)->field('TrueName,Mobile')->where(array('ID' => $val['UserID']))->find();
                $val['TrueName'] = $meminfo['TrueName'];
                $val['Mobile'] = $meminfo['Mobile'];
                $val['FkServiceID'] = $query->GetValue(self::T_ADMIN, array('ID' => (int)$val['FkServiceID']), 'TrueName');
                $val['LoanStatus'] = $LoanStatusArr[$val['LoanStatus']];
                $val['Status'] = $StatusArr[$val['Status']];
                $val['LoanType'] = $LoanTypeArr[$val['LoanType']];
                if ($val['ReplaymentType'] == '0') {
                    $val['ReplaymentType'] = '未打款';
                } elseif ($val['ReplaymentType'] == '1') {
                    $val['ReplaymentType'] = '支付宝';
                } elseif ($val['ReplaymentType'] == '2') {
                    $val['ReplaymentType'] = '微信';
                } elseif ($val['ReplaymentType'] == '3') {
                    $val['ReplaymentType'] = '银联';
                } elseif ($val['ReplaymentType'] == '4') {
                    $val['ReplaymentType'] = '代付';
                }
                $result['rows'][] = $val;
            }
        }

        //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>手机号码</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>快速申请费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>用户管理费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>息费</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>优惠劵金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请天数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放贷人员</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>审核时间</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>打款方式</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>账号/卡号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>交易号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>会员账号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>订单号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请号</td>
            </tr>';

        foreach ($result['rows'] as $key => $row) {
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >' . intval($key + 1) . '</td>
                <td bgcolor="#FFFFFF" align="center">' . $row['TrueName'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['Mobile'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['ApplyMoney'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['AdoptMoney'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['FJMoney'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['Interest'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['CoMoney'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['ApplyDay'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['ApplyTime'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['OpenM'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['LoanStatus'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['FkServiceID'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['OpenTime'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['ReplaymentType'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['RepaymentAccount'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['TradeNum'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['UserAccount'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['OrderSn'] . '</td>
                <td bgcolor="#FFFFFF" align="center" >' . $row['LoanNo'] . '</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()) . '放款记录列表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE', $html);
        ob_end_clean();//清除缓存区的内容
        header("Content-type: application/vnd.ms-excel; charset=GBK");
        //header('Content-Type:text/html;charset=utf-8');
        header("Content-Disposition: attachment; filename=$str_filename.xls");
        echo $html;
        exit;
    }

    /**
     * @功能说明：获取账号 ycp
     */
    public function getaccuonts()
    {
        $types = I('post.types', '0');
        $id = I('post.id', '0');
        $list = M(self::T_ACCOUNTS)->where(array('Types' => $types, 'IsDel' => '0'))->select();
        if ($id) {
            $gdinfos = M(self::T_TABLE)->field('RepaymentAccount')->find($id);
        }
        $htmls = '';
        if ($list) {
            if ($gdinfos) {
                foreach ($list as $k => $v) {
                    if ($gdinfos['RepaymentAccount'] == $v['Name']) {
                        $htmls .= "<option value='" . $v['Name'] . "' selected>" . $v['Name'] . "</option>";
                    } else {
                        $htmls .= "<option value='" . $v['Name'] . "'>" . $v['Name'] . "</option>";
                    }
                }
            } else {
                foreach ($list as $k => $v) {
                    $htmls .= "<option value='" . $v['Name'] . "'>" . $v['Name'] . "</option>";
                }
            }
        }
        echo $htmls;
        return false;
    }

    //代扣页面
    public function daikou()
    {
        $id = I('get.ID', 0, 'intval');
        $res = M(self::T_TABLE)->alias('a')
            ->field('a.ID,a.UserID,a.OrderSn,a.ApplyMoney,a.LoanStatus,a.ReplaymentType,b.TrueName,c.BankNo')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->join('left join xb_renzen_bank c on c.UserID=b.ID')
            ->where(array("a.ID" => $id))->find();
        //查询会员认证的银行卡信息
        $bankinfos = M('renzen_bank')->field('ID,BankNo,BankName')->where(array('UserID' => $res['UserID'], 'Status' => '1', 'IsDel' => '0'))->find();
        $this->assign(array(
            "res" => $res,
            "bankinfos" => $bankinfos,
        ));
        $this->display();
    }

    public function daikousave()
    {
        $post = I('post.');
        $applyinfos = M(self::T_TABLE)->alias('a')
            ->field('a.ID,a.UserID,a.OrderSn,a.OpenM,a.LoanStatus,a.ReplaymentType,a.LoanNo,b.TrueName,c.BankNo,c.YMobile,c.PROTOCOLNO')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->join('left join xb_renzen_bank c on c.UserID=b.ID')
            ->where(array("a.ID" => $post['ID']))->find();

        if (!$applyinfos) {
            $this->ajaxReturn(0, '很抱歉，无此申请记录！');
        }
        if ($applyinfos['LoanStatus'] != '2') {
            $this->ajaxReturn(0, '很抱歉，不能对此订单进行操作！');
        }
        if(!$applyinfos['BankNo']){
            $this->ajaxReturn(0,"此用户还未绑定银行卡，请先绑定!");
        }
        $oderSn = getOrderSn();
        $configArr = $this->getfuyouset();
        $header = array(
            '0'=>'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        $VERSION = '1.0';
        $USERIP = get_client_ip();
        $MCHNTCD = $configArr['merchant_id'];
        $key = $configArr['key'];
        $TYPE = '03';
        $MCHNTORDERID = $oderSn;
        $USERID = $applyinfos['UserID'];
        $AMT =$post['ApplyMoney']*100; //订单金额 分  测试阶段 1分
        $PROTOCOLNO = $applyinfos['PROTOCOLNO'];
        $NEEDSENDMSG  = '0';
        $BACKURL = 'http://'.$_SERVER['HTTP_HOST'].'/fuyoupay/backhkquery';
        $SIGNTP = 'MD5';
        //待签名数组
        $sign_arr = [$TYPE,$VERSION, $MCHNTCD, $MCHNTORDERID, $USERID, $PROTOCOLNO, $AMT, $BACKURL, $USERIP,$key];
        $SIGN = md5(implode('|',$sign_arr));
        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <USERIP>'.$USERIP.'</USERIP>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <TYPE>'.$TYPE.'</TYPE>
        <MCHNTORDERID>'.$MCHNTORDERID.'</MCHNTORDERID>
        <USERID>'.$USERID.'</USERID>
        <AMT>'.$AMT.'</AMT>
        <PROTOCOLNO>'.$PROTOCOLNO.'</PROTOCOLNO>
        <NEEDSENDMSG>'.$NEEDSENDMSG.'</NEEDSENDMSG>
        <BACKURL>'.$BACKURL.'</BACKURL>
        <REM1></REM1>
        <REM2></REM2>
        <REM3></REM3>
        <SIGNTP>'.$SIGNTP.'</SIGNTP>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';
        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);
        $result = Curl::curlPostHttps("https://mpay.fuiou.com/newpropay/order.pay",$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
        if($arr_result['RESPONSECODE'] == '0000'){
            $hkdata=array(
                'UserID'=>$applyinfos[''],
                'ApplyID'=>$applyinfos['UserID'],
                'OrderSn'=>$oderSn,
                'LoanNo'=>$applyinfos['LoanNo'],
                'TotalMoney'=>$post['ApplyMoney'],
                'CostPayable'=>$applyinfos['ApplyMoney'],
                'HkTime'=>date('Y-m-d H:i:s'),
                'SeviceCostPayable'=>'',
                'PayType'=>'4',
                'TradeRemark'=>'系统代扣',
                'TradeNo'=>'',
                'PayStatus'=>'',
                'PayTime'=>'',
                'AdminID'=>'',
                'ShTime'=>'',
                'Status'=>'0',
                'Remark'=>'',
                'IsDel'=>'0',
                'OperatorID'=>'',
                'UpdateTime'=>'',
            );
            $hkdata['OrderSn'] = $arr_result['MCHNTORDERID']; //商户订单号
            $hkdata['TradeNo'] = $arr_result['ORDERID']; //富友订单号
            $hkdata['PROTOCOLNO'] = $arr_result['PROTOCOLNO']; //协议号
            $res = M('loans_hklist')->add($hkdata);
            $data['LoanStatus'] = '3';
            M(self::T_TABLE)->where(['ID'=>$post['ID']])->save($data);
            if($res!==false){
                $this->ajaxReturn(1,$arr_result['RESPONSEMSG']);
            }else{
                $this->ajaxReturn(0,"处理失败");
            }
        }else{
            $this->ajaxReturn(0,$arr_result['RESPONSEMSG']);
        }
    }

    //-----代付功能----start
    //代付页面
    public function daifuaction()
    {
        $id = I('get.ID', 0, 'intval');
        $res = M(self::T_TABLE)->alias('a')
            ->field('a.ID,a.UserID,a.OrderSn,a.OpenM,a.LoanStatus,a.ReplaymentType,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array("a.ID" => $id))->find();
        //查询会员认证的银行卡信息
        $bankinfos = M('renzen_bank')->field('ID,BankNo,BankName')->where(array('UserID' => $res['UserID'], 'Status' => '1', 'IsDel' => '0'))->find();
        $this->assign(array(
            "res" => $res,
            "bankinfos" => $bankinfos,
        ));
        $this->display();
    }

    //代付操作 富有支付
    public function daifusave()
    {
        $ID = I('post.ID', '');
        $dfpassword = I('post.dfpassword', '');
        //审核校验
        $applyinfos = M(self::T_TABLE)->alias('a')
            ->field('a.ID,a.UserID,a.OrderSn,a.OpenM,a.LoanStatus,a.ReplaymentType,b.TrueName,c.BankNo,c.YMobile')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->join('left join xb_renzen_bank c on c.UserID=b.ID')
            ->where(array("a.ID" => $ID))->find();
        if (!$applyinfos) {
            $this->ajaxReturn(0, '很抱歉，无此申请记录！');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，不能重复放款！');
        }
        //获取配置信息
        $configArr = $this->getfuyouset();

        //校验操作密码对不对
        if ($configArr['dfpassword'] != $dfpassword) {
            $this->ajaxReturn(0, '操作密码错误！');
        }
        //放款金额
        $Payceshi = M('sys_basicinfo')->where(array('ID' => '1'))->getField('Payceshi');
        $fkmoney = '1';//单位是分
        if ($Payceshi == '1') {
            //正式
            //$fkmoney = '1';
            $fkmoney=$applyinfos['OpenM']*100;
        }
        //组织下单操作
        $ver = "1.00";//版本号
        $amt = $fkmoney;//金额
        $cityno = "";//城市代码 对私填错不影响  对公则必须正确
        $entseq = $applyinfos['OrderSn'];//企业流水号
        $bankno = "";//总行代码  对私填错不影响  对公则必须正确
        $merdt = date('Ymd');
        $accntno = $applyinfos['BankNo'];//银行卡 详情
        $orderno = $applyinfos['OrderSn'];//请求流水
        $branchnm = "";//支行名称  对私填错不影响  对公则必须正确
        $accntnm = $applyinfos['TrueName']; //用户账号户名
        //$mobile = $applyinfos['YMobile']; //短信通知时使用
      	$mobile = ''; //短信通知时使用
        $memo = "代付打款";//备注
        $mchntcd = $configArr['merchant_id'];//商户号
        $mchntkey = $configArr['fyKey'];// 代付秘钥
        $reqtype = "payforreq";//代付
//        $reqtype="sincomeforreq";//代收
        $xml = "<?xml version='1.0' encoding='utf-8' standalone='yes'?><payforreq><ver>" . $ver . "</ver><merdt>" . $merdt . "</merdt><orderno>" . $orderno . "</orderno><bankno>" . $bankno . "</bankno><cityno>" . $cityno . "</cityno><accntno>" . $accntno . "</accntno><accntnm>" . $accntnm . "</accntnm><branchnm>" . $branchnm . "</branchnm><amt>" . $amt . "</amt><mobile>" . $mobile . "</mobile><entseq>" . $entseq . "</entseq><memo>" . $memo . "</memo></payforreq>";
        $macsource = $mchntcd . "|" . $mchntkey . "|" . $reqtype . "|" . $xml;
        $mac = md5($macsource);
        $mac = strtoupper($mac);
        $list = array("merid" => $mchntcd, "reqtype" => $reqtype, "xml" => $xml, "mac" => $mac);
        $url = "https://fht-api.fuiou.com/req.do";
        $query = http_build_query($list);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($query) . "\r\n" .
                    "User-Agent:MyAgent/1.0\r\n",
                'method' => "POST",
                'content' => $query,
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context, -1, 40000);
        $resultdf = xml_to_array($result);
        if ($resultdf['ret'] == '000000') {
            //代付申请提交成功  更新表
            M('loans_applylist')->where(array('ID' => $applyinfos['ID']))->save(array('ReplaymentType' => '4', 'RepaymentAccount' => '富有代付', 'TradeNum' => $applyinfos['OrderSn']));
            $this->ajaxReturn(1, $resultdf['memo']);
        } else {
            $this->ajaxReturn(0, $resultdf['memo']);
        }
    }

    //代付操作 合利宝
    public function daifuhelisave()
    {
        $ID = I('post.ID', '');
        $P6_bankAccountNo = I('post.P6_bankAccountNo', '');
        $P5_bankCode = I('post.P5_bankCode', '');
        $P8_biz = I('post.P8_biz', '');
        $P10_feeType = I('post.P10_feeType', '');
        $dfpassword = I('post.dfpassword', '');
        //审核校验
        $applyinfos = M(self::T_TABLE)->alias('a')
            ->field('a.ID,a.UserID,a.OrderSn,a.OpenM,a.LoanStatus,a.ReplaymentType,b.TrueName')
            ->join('left join xb_mem_info b on a.UserID=b.ID')
            ->where(array("a.ID" => $ID))->find();
        if (!$applyinfos) {
            $this->ajaxReturn(0, '很抱歉，无此申请记录！');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，不能重复放款！');
        }
        //获取配置信息
        $configArr = $this->gethelibaoset();
        //校验操作密码对不对
        if ($configArr['dfpassword'] != $dfpassword) {
            $this->ajaxReturn(0, '操作密码错误！');
        }
        //放款金额
        $Payceshi = M('sys_basicinfo')->where(array('ID' => '1'))->getField('Payceshi');
        $fkmoney = '0.1';
        if ($Payceshi == '1') {
            //正式
            $fkmoney = $applyinfos['OpenM'];
        }

        //组织下单操作
        $P1_bizType = "Transfer";
        $P2_orderId = $applyinfos['OrderSn'];
        $P3_customerNumber = $configArr['merchant_id'];//合利宝分配商户号
        $P4_amount = $fkmoney;
        $P7_bankAccountName = $applyinfos['TrueName'];
        $P9_bankUnionCode = '';
        $P11_urgency = 'true';
        $P12_summary = '';

        $privatekey = $configArr['privatekey'];//商户私钥
        $url = $configArr['dfurl'];//请求的页面地址  request url

        if ($P5_bankCode <> "" && $P4_amount <> "" && $P6_bankAccountNo <> "" && $P7_bankAccountName <> "") {

            $source = "&" . $P1_bizType . "&" . $P2_orderId . "&" . $P3_customerNumber . "&" . $P4_amount . "&" . $P5_bankCode . "&" . $P6_bankAccountNo . "&" . $P7_bankAccountName . "&" . $P8_biz . "&" . $P9_bankUnionCode . "&" . $P10_feeType . "&" . $P11_urgency . "&" . $P12_summary;

            vendor('Helibao.Crypt_RSA');
            $rsa = new \Crypt_RSA();
            $rsa->setHash('md5');
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->loadKey($privatekey);

            $sign = base64_encode($rsa->sign($source));

            //post的参数
            $params = array('P1_bizType' => $P1_bizType, 'P2_orderId' => $P2_orderId, 'P3_customerNumber' => $P3_customerNumber, 'P4_amount' => $P4_amount, 'P5_bankCode' => $P5_bankCode, 'P6_bankAccountNo' => $P6_bankAccountNo, 'P7_bankAccountName' => $P7_bankAccountName, 'P8_biz' => $P8_biz, 'P9_bankUnionCode' => $P9_bankUnionCode, 'P10_feeType' => $P10_feeType, 'P11_urgency' => $P11_urgency, 'P12_summary' => $P12_summary, 'sign' => $sign);

            $pageContents = $this->sendHttpRequest($params, $url);  //发送请求 send request
            $resultdf = json_decode($pageContents, true);

            if ($resultdf['rt2_retCode'] == '0000') {
                //代付申请提交成功  更新表
                M('loans_applylist')->where(array('ID' => $applyinfos['ID']))->save(array('ReplaymentType' => '4', 'RepaymentAccount' => $P6_bankAccountNo, 'TradeNum' => $resultdf['rt6_serialNumber']));
                $this->ajaxReturn(1, $resultdf['rt3_retMsg']);
            } else {
                $this->ajaxReturn(0, $resultdf['rt3_retMsg']);
            }
        }
    }

    //代付查询功能 富有支付查询
    public function daifucheck()
    {
        $id = I('post.ID', 0, 'intval');
        $applyinfos = M(self::T_TABLE)->field('ID,UserID,OrderSn,LoanNo,OpenM,LoanStatus,ReplaymentType,ApplyDay')->where(array("ID" => $id))->find();
        if ($applyinfos['ReplaymentType'] != '4') {
            $this->ajaxReturn(0, '代付订单才能进行此操作');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，不能进行此操作！');
        }
        //获取配置信息
        $configArr = $this->getfuyouset();
        $ver = "1.1";
        $busicd = "AP01";
        $orderno = $applyinfos['OrderSn'];
        $startdt = date('Ymd', strtotime("-14 day"));
        $enddt = date('Ymd');
        $mchntcd = $configArr['merchant_id'];
        $mchntkey = $configArr['fyKey'];
        $xml = "<?xml version='1.0' encoding='utf-8' standalone='yes'?><qrytransreq><ver>" . $ver . "</ver><busicd>" . $busicd . "</busicd><orderno>" . $orderno . "</orderno><startdt>" . $startdt . "</startdt><enddt>" . $enddt . "</enddt><transst>1</transst></qrytransreq>";
        $macsource = $mchntcd . "|" . $mchntkey . "|" . $xml;
        $mac = md5($macsource);
        $mac = strtoupper($mac);
        $list = array("merid" => $mchntcd, "xml" => $xml, "mac" => $mac);
        $url = "https://fht-api.fuiou.com/qry.do";
        $query = http_build_query($list);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($query) . "\r\n" .
                    "User-Agent:MyAgent/1.0\r\n",
                'method' => "POST",
                'content' => $query,
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context, -1, 40000);
        $resultdf = xml_to_array($result);
        if ($resultdf['ret'] == '000000' && $resultdf['trans']['transStatusDesc'] == 'success') {
            //对放款订单状态等更新下数据
            $sdata = array();//修改的数据
            //正常放款
            $sdata['LoanStatus'] = '2';
            $sdata['FkServiceID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['OpenTime'] = date('Y-m-d H:i:s');
            //$sdata['YyFkTime']=date("Y-m-d H:i:s",strtotime("+".$applyinfos['ApplyDay']." day"));
            $realdays = $applyinfos['ApplyDay'] - 1;
            $sdata['YyFkTime'] = date("Y-m-d H:i:s", strtotime("+" . $realdays . " day"));
            $sdata['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
            $sdata['UpdateTime'] = date('Y-m-d H:i:s');

            $model = M();
            $model->startTrans();
            $result = $model->table('xb_loans_applylist')->where(array("ID" => $applyinfos['ID']))->save($sdata);
            if ($result) {

                //发送消息通知信息
                $msgcont = '尊敬的会员，您提交的订单：' . $applyinfos['LoanNo'] . '，打款成功!金额为：' . $applyinfos['OpenM'] . '元。';
                $mobile = M('mem_info')->where(array('ID' => $applyinfos['UserID']))->getField('Mobile');
                //send_message($mobile,$msgcont);//发送短信消息
//                $this->getYun($mobile, $msgcont);//发送短信消息

//                send_mem_notics($applyinfos['UserID'], $msgcont);//发送站内通知消息
//                 self_sendjuan($applyinfos['UserID'],'5');//邀请的好友申请专卖成功立送
                $model->commit();
                $this->ajaxReturn(1, '恭喜您，更新数据成功！');
            } else {
                $model->rollback();
                $this->ajaxReturn(0, '很抱歉，更新数据失败！');
            }
        } else {
            $this->ajaxReturn(0, $resultdf['memo']);
        }
    }


    //代付查询功能 合利宝
    public function daifuhelicheck()
    {
        $id = I('post.ID', 0, 'intval');
        $applyinfos = M(self::T_TABLE)->field('ID,UserID,OrderSn,LoanNo,OpenM,LoanStatus,ReplaymentType,ApplyDay')->where(array("ID" => $id))->find();
        if ($applyinfos['ReplaymentType'] != '4') {
            $this->ajaxReturn(0, '代付订单才能进行此操作');
        }
        if ($applyinfos['LoanStatus'] != '1') {
            $this->ajaxReturn(0, '很抱歉，不能进行此操作！');
        }
        //获取配置信息
        $configArr = $this->gethelibaoset();
        $P1_bizType = "TransferQuery";
        $P2_orderId = $applyinfos['OrderSn']; //自己的订单号
        $P3_customerNumber = $configArr['merchant_id'];//合利宝分配商户号
        $privatekey = $configArr['privatekey'];//商户私钥
        $url = $configArr['dfurl'];
        if ($P2_orderId <> "") {
            $source = "&" . $P1_bizType . "&" . $P2_orderId . "&" . $P3_customerNumber;

            vendor('Helibao.Crypt_RSA');
            $rsa = new \Crypt_RSA();
            $rsa->setHash('md5');
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->loadKey($privatekey);

            $sign = base64_encode($rsa->sign($source));

            //post的参数
            $params = array('P1_bizType' => $P1_bizType, 'P3_customerNumber' => $P3_customerNumber, 'P2_orderId' => $P2_orderId, 'sign' => $sign);

            $pageContents = $this->sendHttpRequest($params, $url);  //发送请求 send request
            $resultdf = json_decode($pageContents, true);
            //当retCode为0000证明查询请求受理成功，订单是否支付成功根据r4_orderStatus判断，INIT:已接收;DOING: 处理中;SUCCESS:成功; FAIL:失败;CLOSE:关闭
            if ($resultdf['rt2_retCode'] == '0000' && $resultdf['rt7_orderStatus'] == 'SUCCESS') {
                //对放款订单状态等更新下数据
                $sdata = array();//修改的数据
                //正常放款
                $sdata['LoanStatus'] = '2';
                $sdata['FkServiceID'] = $_SESSION['AdminInfo']['AdminID'];
                $sdata['OpenTime'] = date('Y-m-d H:i:s');
                //$sdata['YyFkTime']=date("Y-m-d H:i:s",strtotime("+".$applyinfos['ApplyDay']." day"));
                $realdays = $applyinfos['ApplyDay'] - 1;
                $sdata['YyFkTime'] = date("Y-m-d H:i:s", strtotime("+" . $realdays . " day"));
                $sdata['OperatorID'] = $_SESSION['AdminInfo']['AdminID'];
                $sdata['UpdateTime'] = date('Y-m-d H:i:s');

                $model = M();
                $model->startTrans();
                $result = $model->table('xb_loans_applylist')->where(array("ID" => $applyinfos['ID']))->save($sdata);
                if ($result) {
                    //发送消息通知信息
                    $msgcont = '尊敬的会员，您提交的订单：' . $applyinfos['LoanNo'] . '，打款成功!金额为：' . $applyinfos['OpenM'] . '元。';
                    $mobile = M('mem_info')->where(array('ID' => $applyinfos['UserID']))->getField('Mobile');
                    //send_message($mobile,$msgcont);//发送短信消息
                    $this->getYun($mobile, $msgcont);//发送短信消息

                    send_mem_notics($applyinfos['UserID'], $msgcont);//发送站内通知消息
                    self_sendjuan($applyinfos['UserID'], '5');//邀请的好友申请专卖成功立送

                    $model->commit();
                    $this->ajaxReturn(1, '恭喜您，更新数据成功！');
                } else {
                    $model->rollback();
                    $this->ajaxReturn(0, '很抱歉，更新数据失败！');
                }
            } else {
                $this->ajaxReturn(0, $resultdf['rt3_retMsg']);
            }
        }
    }

    //获取
    public function getfuyouset()
    {
        $paraters = M('sys_inteparameter')->field('ParaName,ParaValue')->where(array('IntegrateID' => '13'))->select();
        $merchant_id = '';
        $fyKey = '';
        $ftPas = '';
        $key = '';
        foreach ($paraters as $k => $v) {
            if ($v['ParaName'] == 'mchntCd') {
                $merchant_id = $v['ParaValue'];
            } elseif ($v['ParaName'] == 'fyKey') {
                $fyKey = $v['ParaValue'];
            } elseif ($v['ParaName'] == 'ftPas') {
                $ftPas = $v['ParaValue'];
            }elseif ($v['ParaName'] == 'key') {
                $key = $v['ParaValue'];
            }
        }
        return array(
            'merchant_id' => $merchant_id,
            'fyKey' => $fyKey,
            'dfpassword' => $ftPas,
            'key' => $key,
        );
    }

    //获取 合利宝配置信息
    public function gethelibaoset()
    {
        $paraters = M('sys_inteparameter')->field('ParaName,ParaValue')->where(array('IntegrateID' => '14'))->select();
        $merchant_id = '';
        $dfurl = '';
        $dfpassword = '';
        foreach ($paraters as $k => $v) {
            if ($v['ParaName'] == 'merchant_id') {
                $merchant_id = $v['ParaValue'];
            } elseif ($v['ParaName'] == 'dfurl') {
                $dfurl = $v['ParaValue'];
            } elseif ($v['ParaName'] == 'dfpassword') {
                $dfpassword = $v['ParaValue'];
            }
        }
        return array(
            'merchant_id' => $merchant_id,
            'dfurl' => $dfurl,
            'dfpassword' => $dfpassword,
            'privatekey' => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANFUy8WWg0Eyn41W
daJlCvwcsVDM09gU701olNRSUtZwd0trWifF5zoyM7qTmEn8Lblh8VnG4yL/nxRN
KUE+JEvc0N4aKRQlbjW2s0uzhq54AmF2Yk6oFg/IItnsZ9YNrNHmHdN29QARdxc2
UAWFO7vr7GIgBwOekK1vFO1Vf2+xAgMBAAECgYB1uYOUDq6oZwj2Gs6zUrIX0Scj
ct4c4sEmPo+czOOLd9qPTsN4JMOCpiMTZdg6m5k3bc6nF8Q7tZjIeRCfgYw1HUyT
DORATZradan0u+5Tau1YBVcBuzsaGayTIi3i4CaS3mZDKF0Yx7UGSBx8yJAI5Ilg
SS+rBBEtgk80qUjLBQJBAPHTZQCvoGMx8dGfNsQmtN01XnQDGjwSiw/9aWJjqzgL
FeqvbAeKY6BDKLURcZkPmgs7xLx6++n0/sGpMIljjxcCQQDdmdHbLsbkwTh3KKqP
vP9I2XO022M4iGxeeMTujlpX4Cfe8h6gGPg3ymCBNhkH4yLaY2LWz+/7CW2nnb2A
mvR3AkBmW7A45h3hXtaYf+fShv+vUlO0j0ufecna7syYlM94XVjdiXsUhgM9Zq/o
VIXc37m4X4gar4PJt6XNmyusO7PpAkEAq0bemsh21gw59m+qFNsBfW8FLX58HA/l
osc5fyDr1wvcBUeiQB/MimKTYItNoXj/UUiL9nhVhfRtmqYi+CnMVQJACBxbuQqX
sr+I8q1nGIFjlF7z6+32HMSVmISPz1661cgqHE6a5U45sASKeMR+a6zmGQxjglZy
Ti/KiykAvm4ePQ==',
        );
    }

    //       //可用
    private function sendHttpRequest($data = null, $url)
    {
        $data = $this->buildQueryString($data);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type:application/x-www-form-urlencoded;charset=UTF-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //$output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        return $output;
        // return json_decode($output,true);
    }

    function buildQueryString($data)
    {
        $querystring = '';
        if (is_array($data)) {
            // Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $querystring .= urlencode($key) . '=' . urlencode($val2) . '&';
                    }
                } else {
                    $querystring .= urlencode($key) . '=' . urlencode($val) . '&';
                }
            }
            $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {
            $querystring = $data;
        }
        return $querystring;
    }
    //--------------------合利宝代付功能-------------------------------end
}
