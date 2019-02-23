<?php
/**
 * 功能说明: 银行卡认证控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 use Home\Controller\Curl;
 use Home\Controller\Crypt3Des;
 class BankController extends BaseController{

     const T_TABLE='renzen_bank';
     const T_ADMIN='sys_administrator';
     const T_MEMINFO='mem_info';
     const T_SHLIST='renzen_shlist';

     public function index(){
         $this->display();
     }

     /**
      * 后台用户管理的列表数据获取
      * @access   public
      * @return   object    返回json数据
      */
     public function DataList(){

         $page=I('post.page',1,'intval');
         $rows=I('post.rows',20,'intval');
         $sort=I('post.sort');
         $order=I('post.order');
         if ($sort && $order){
             $sort=$sort.' '.$order;
         }else{
             $sort='ID desc';
         }
         //搜索条件
         // $UserID=I('post.UserID','');
         // if($UserID){
         //    $where['UserID']=$UserID;
         // }
         $MemAccount=I('post.MemAccount','');
         if($MemAccount){
            $MemIDS=M(self::T_MEMINFO)->where(array('MemAccount'=>array('like','%'.$MemAccount.'%')))->field('ID')->select();
            $ids=array_column($MemIDS, 'ID');
            //根据ID查询会员
            if($ids){
                $where['UserID']=array('in',$ids);
            }else{
                $where['UserID']=null;
            }
        }
        $TrueName=I('post.TrueName','');
         if($TrueName){
            $MemIDS=M(self::T_MEMINFO)->where(array('TrueName'=>array('like','%'.$TrueName.'%')))->field('ID')->select();
            $ids=array_column($MemIDS, 'ID');
            //根据ID查询会员
            if($ids){
                $where['UserID']=array('in',$ids);
            }else{
                $where['UserID']=null;
            }
        }
        $IDCard=I('post.IDCard','');
         if($IDCard){
            $MemID=M(self::T_MEMINFO)->where(array('IDCard'=>array('eq',$IDCard)))->getField('ID');
            //根据ID查询会员
            if($MemID){
                $where['UserID']=array('eq',$MemID);
            }else{
                $where['UserID']=null;
            }
        }
         $BankNo=I('post.BankNo','');
         if($BankNo){
            $where['BankNo']=$BankNo;
         }
         $YMobile=I('post.YMobile','');
         if($YMobile){
            $where['YMobile']=$YMobile;
         }
         $Status=I('post.Status',-5,'int');
         if($Status!=-5){
            $where['Status']=$Status;
         }
         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,BankNo,OpenBankName,Address,YMobile,RenzTime,Status';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 if($val['Status']=='0'){
                    $val['Status']='待审核';
                 }elseif($val['Status']=='1'){
                    $val['Status']='<span style="color:green;">已认证</span>';
                 }elseif($val['Status']=='2'){
                    $val['Status']='<span style="color:red;">认证失败</span>';
                 }
                 $meminfo='';
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,TrueName,IDCard')->find($val['UserID']);
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['TrueName']=$meminfo['TrueName'];
                 $val['IDCard']=$meminfo['IDCard'];
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }
     //详情
     public function detail(){
        $ID=I('request.ID');
        if($ID){
            $infos=M('renzen_bank')->alias('a')
                   ->field('a.ID,a.UserID,a.OpenBankName,a.BankNo,a.Address,a.YMobile,a.RenzTime,a.Status,b.TrueName,b.IDCard')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.ID'=>$ID))->find();
            $this->assign('infos',$infos);
        }
        $this->display();
     }
     //审核
     public function aduit(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->where(array("ID"=>$id))->find();
        $this->assign("res",$res);
        $this->display();
    }
    //审核信息提交处理
    public function aduitsave(){
        $ID=I('post.ID','');
        $Status=I('post.Status','0');
        $Intro=I('post.Intro','');
        if($Status=='0'){
            $this->ajaxReturn(1,'恭喜您，审核操作成功！');
        }
        if($Status=='2'){
            //2认证失败
            if(!$Intro){
                $this->ajaxReturn(0,'很抱歉，请填写认证失败的原因！');
            }
        }
        //修改xb_renzen_cards
        $updata=array(
            'Status'=>$Status,
            'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
            'UpdateTime'=>date("Y-m-d H:i:s"),
            );
        $rest=M(self::T_TABLE)->where(array('ID'=>$ID))->save($updata);
        if($rest){
            //记录下 xb_renzen_shlist 
            $UserID=M(self::T_TABLE)->where(array("ID"=>$ID))->getField('UserID');
            $datas=array(
                'RenZenID'=>$ID,
                'Codes'=>'bank',
                'UserID'=>$UserID,
                'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                'UpdateTime'=>date("Y-m-d H:i:s"),
                );
            if($Status=='1'){
                $datas['Descs']='银行卡认证通过';
            }elseif($Status=='2'){
                $datas['Descs']='银行卡认证失败';
            }
            if($Intro){
                $datas['Intro']=$Intro;
            }
            $rest2=M(self::T_SHLIST)->add($datas);
            if($rest2){
                $this->ajaxReturn(1,'恭喜您，审核操作成功！');
            }else{
                $this->ajaxReturn(0,'很抱歉，审核记录插入失败！');
            }
        }else{
            $this->ajaxReturn(0,'很抱歉，审核操作失败！');
        }
    }
    /**
     *审核记录
     */
    public function shenhelist(){
        $RenZenID=I('get.RenZenID');
        if(!empty($RenZenID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $sort='ID Desc';

            $where['RenZenID']=$RenZenID;
            $where['Codes']=array('eq','bank');
            //查询的列名
            $col='';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_SHLIST,$where,$page,$rows,$sort,$col);

            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['Codes']='银行卡认证';
                    $val['UserID']=$query->GetValue(self::T_MEMINFO,array('ID'=>(int)$val['UserID']),'TrueName');
                    $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }

    public function jiebang()
    {
        $ID = (int)$_REQUEST['ID'];
        $bank = M(self::T_TABLE)->where(['ID'=>$ID])->find();
        $header = ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8'];
        $setsinfo=M('sys_inteparameter')->where(array('IntegrateID'=>'13'))->select();
        $setArr=array();
        foreach($setsinfo as $k=>$v){
            $setArr[$v['ParaName']]=$v['ParaValue'];
        }
        $VERSION = '1.0';
        $MCHNTCD = $setArr['mchntCd'];   //商户代码
        $key =  $setArr['key'];   //商户密钥
        $USERID = $bank['UserID'];
        $PROTOCOLNO = $bank['PROTOCOLNO'];

        //待签名数组
        $sign_arr = [$VERSION,$MCHNTCD, $USERID, $PROTOCOLNO,$key];
        $SIGN = md5(implode('|',$sign_arr));

        $APIFMS = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <REQUEST>
        <VERSION>'.$VERSION.'</VERSION>
        <MCHNTCD>'.$MCHNTCD.'</MCHNTCD>
        <USERID>'.$USERID.'</USERID>
        <PROTOCOLNO>'.$PROTOCOLNO.'</PROTOCOLNO>
        <SIGN>'.$SIGN.'</SIGN>
        </REQUEST>';

        $key = str_pad($key,64,'D');
        $param['MCHNTCD'] = $MCHNTCD;  //商户代码,分配给各合作商户的唯一识别码
        $param['APIFMS'] = Crypt3Des::encrypt_base64($APIFMS,$key);

        $result = Curl::curlPostHttps('https://mpay.fuiou.com/newpropay/unbind.pay',$param,$header);
        $xml_result = Crypt3Des::decrypt_base64($result,$key);
        if($xml_result === false){
            $xml_result = $result;
        }
        $arr_result = xml_to_array($xml_result);
        if($arr_result['RESPONSECODE'] == '0000'){
            M(self::T_TABLE)->where(['ID'=>$ID])->delete();
            echo $arr_result['RESPONSEMSG'];exit;
        }else{
            print_r($arr_result['RESPONSEMSG']);exit;
        }
    }

 }