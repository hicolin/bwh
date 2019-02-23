<?php
namespace Home\Controller;

use XBCommon\XBUpload;

class MemberController extends HomeController{
    public $token;//用户登录token
    public $member;//  用户所有信息
    public $uid;//  用户ID

    public function __construct(){
        parent::__construct();
        $this->token = I('post.token');
        if(!$this->token){
            $this->ajaxReturn(0,'请先登录');
        }
        $this->member = M('mem_info')->where(array('Token'=>$this->token,'IsDel'=>'0'))->find();
        $this->uid = $this->member['ID'];
        if(!$this->member){
            $this->ajaxReturn(0,'token失效，请重新登录');
        }
        $expire = $this->member['Expire'];
        if(time() > $expire){
            $this->ajaxReturn(0,'token已过期，请重新登录');
        }
    }

    /**
     * 个人中心首页
     */
    public function index(){
        $this->ajaxReturn(1,'获取个人信息成功',$this->member);
    }

    /**
     * 头像上传
     */
    public function uploadHeadImg(){
        if(IS_POST){
            $xbUpload = new XBUpload();
            $res = $xbUpload->uploadimage();
            if($res['result'] != 'success'){
                $this->ajaxReturn(100,'上传失败');
            }
            $result = M('mem_info')->where(array('ID'=>$this->member['ID']))
                ->save(array('HeadImg'=>$res['path'],'UpdateTime'=>date('Y-m-d H:i:s')));
            if(!$result){
                $this->ajaxReturn(100,'保存失败');
            }
            $this->ajaxReturn(200,'上传成功',$res['path']);
        }
    }

    /**
     * 借款记录
     */
    public function loanRecord(){
        $pageSize = 5;
        $loanStatus = (int)I('post.Status');
        $page = I('post.pages');
        if($loanStatus==null || $loanStatus == 0){
            $map['LoanStatus'] = array('in','0,1,2,3,4,5');
        }else if($loanStatus == -1){
            $map['LoanStatus'] = 0;
        }else{
            $map['LoanStatus'] = $loanStatus;
        }
        $map['UserID'] = $this->uid;
        $map['IsDel'] = 0;

        $records = M('loans_applylist')
            ->where($map)
            ->limit($pageSize*($page-1),$pageSize)
            ->order('ID desc')->select();
        $data = [];
        $Lstatu = [0=>'申请中',1=>'放款中',2=>'已放款',3=>'已完成',4=>'已取消',5=>'已拒绝'];
        foreach ($records as $k=>$list){
            $data[$k]['LoanType'] = $list['LoanType'] == 0 ? '普通' : '续借';
            $data[$k]['ApplyTime'] = $list['ApplyTime'];
            $data[$k]['OrderSn'] = $list['OrderSn'];
            $data[$k]['ApplyMoney'] = $list['ApplyMoney'];
            $data[$k]['BackM'] = $list['BackM'];
            $data[$k]['ApplyDay'] = $list['ApplyDay'];
            $data[$k]['YyFkTime'] = $list['YyFkTime'];
            $data[$k]['LoanStatus'] = $Lstatu[$list['LoanStatus']];
        }
        $this->ajaxReturn(1,'获取成功',$data);
    }



    /**
     * 还款记录
     */
    public function hkrecord(){
        $pageSize = 5;
        $Status = I('post.Status');
        $page = I('post.pages');
        if($Status==null){
            $map['Status'] = array('in','0,1,2');
        }else{
            $map['Status'] = $Status;
        }
       
        $map['UserID'] = $this->uid;
        $map['IsDel'] = 0;
        $records = M('loans_hklist')
            ->where($map)
            ->limit($pageSize*($page-1),$pageSize)
            ->order('ID desc')->select();
//            ->getField('Status,CostPayable,TotalMoney,OrderSn,HkTime,PayStatus');
//        print_r($records);exit;
        $data = [];
        foreach ($records as $k=>$list){
            $data[$k]['Status'] = $list['Status'] == 1 ? '审核成功':($list['Status']==2?'审核失败':'待审核');
            $data[$k]['PayStatus'] = $list['PayStatus'] == 1 ? '已支付':'待支付';
            $data[$k]['HkTime'] = $list['HkTime'];
            $data[$k]['CostPayable'] = $list['CostPayable'];
            $data[$k]['TotalMoney'] = $list['TotalMoney'];
            $data[$k]['OrderSn'] = $list['OrderSn'];
        }

        $this->ajaxReturn(1,'获取成功',$data);
    }


    /**
     * 我的银行卡
     */
    public function myBank(){
        $userId = $this->uid;
        $banks = M('renzen_bank')->alias('a')
            ->field('a.ID,a.UserID,a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
            ->join('left join xb_mem_info b on a.UserID = b.ID')
            ->where("a.UserID = {$userId} and a.IsDel = 0")
            ->find();
        if($banks){
            $this->ajaxReturn('1','成功',$banks);
        }
        $this->ajaxReturn('0','没有数据哦！');
    }

    /**
     * 添加银行卡
     */
    public function bankAdd()
    {
        echo 'add';
    }

    /**
     * 基本信息
     */
    public function basic(){
        $myBank = M('renzen_bank')->where(array('UserID'=>$this->uid,'IsDel'=>0))->find();
        $info = array_merge($this->member,$myBank);
        $this->ajaxReturn(1,'获取基本信息成功',$info);
    }

    /**
     * 我的消息
     */
    public function mynews(){
        $userId = $this->uid;
        $model = M('notice_message');
        $noticeNews = $model->where(['UserID'=>$userId,'Status'=>1])
            ->order('ID desc')->select();
        $this->ajaxReturn(1,'获取信息成功',$noticeNews);
    }
    /**
     * 消息详情
     */
    public function notice(){
        $id = (int)I('get.id');
        $model = M('notice_message');
        $article = $model->where(['ID'=>$id])->find();

        if(!$article){
            echo '页面不存在';exit;
        }
        $this->assign('article',$article);
        $this->assign('title','消息详情');
        $this->display();
    }




     //设置

    public function setting()
    {
        global $BasicInfo;
        $this->ajaxReturn(1,'获取成功',$BasicInfo);
    }

    /**
     * 关于我们、版本介绍
     */
    public function page(){
        $id = (int)I('get.id');
        $model = M('sys_contentmanagement');
        $article = $model->where(['ID'=>$id])->find();
        if(!$article){
           echo '页面不存在';exit;
        }
        $this->assign('article',$article);
        $this->assign('title','页面');
        $this->display();
    }

    /**
     * 修改密码
     */
    public function changepsd(){
        if(IS_AJAX){
            if(getGroupMd5($_POST['psd'])!=$this->member['Password']){
                $this->ajaxReturn(0,"原始密码不对哦！");
            }
            $newPsd = getGroupMd5($_POST['psd1']);
            $res = M('mem_info')->where(array('ID'=>$this->uid))->save(array('Password'=>$newPsd));
            if($res !==false){
                $this->ajaxReturn(1,"密码已修改，前往登录页面！");
            }else{
                $this->ajaxReturn(0,"修改失败！");
            }
        }
        $this->assign('title','修改密码');
        $this->display();
     }

     
//     下载APP
     public function download(){
         $this->display();
     }

}