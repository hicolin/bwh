<?php
/**
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class BlacklistController extends BaseController{

     const T_TABLE='renzen_blacklist';
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
         $Mobile=I('post.Mobile','');
         if($Mobile){
            $MemIDS=M(self::T_MEMINFO)->where(array('Mobile'=>array('like','%'.$Mobile.'%')))->field('ID')->select();
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
         $Status=I('post.Status',-5,'int');
         if($Status!=-5){
            $where['Status']=$Status;
         }
         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,UpdateTime,Status,Response';//默认全字段查询

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
                 $response = json_decode($val['Response'],true);
                 if(empty($response)){
                     $val['Response'] = '<span style="color:green;">未命中</span>';
                 }else{
                     $val['Response'] = '<span style="color:red;">命中</span>';
                 }
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,Mobile,TrueName')->find($val['UserID']);
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['Mobile']=$meminfo['Mobile'];
                 $val['TrueName']=$meminfo['TrueName'];
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
            $info = M(self::T_TABLE)->find($ID);
            $resp = json_decode($info['Response'],true);
            $this->assign('resp',$resp);
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
                'Codes'=>'social',
                'UserID'=>$UserID,
                'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                'UpdateTime'=>date("Y-m-d H:i:s"),
                );
            if($Status=='1'){
                $datas['Descs']='社交认证通过';
            }elseif($Status=='2'){
                $datas['Descs']='社交认证失败';
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
            $where['Codes']=array('eq','social');
            //查询的列名
            $col='';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_SHLIST,$where,$page,$rows,$sort,$col);

            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['Codes']='社交认证';
                    $val['UserID']=$query->GetValue(self::T_MEMINFO,array('ID'=>(int)$val['UserID']),'TrueName');
                    $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }
    /**
     *手机通讯录
     */
    public function Phonelist(){
        $listID=I('get.listID');
        //排序
        $sort=I('post.sort');
        $order=I('post.order');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $Phonelist=M(self::T_TABLE)->where(array("ID"=>$listID))->getField('Phonelist');
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
                $this->ajaxReturn($result);
            }
        }
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

     public function callPhoneHistory($MemID, $phone)
     {
         $mobileInfo = M('renzen_mobile')->field('ID,UserID,CallBill')->where(array('UserID'=>$MemID,'IsDel'=>'0'))->find();

         if( $mobileInfo['CallBill'] ){
             #从通话记录中 查询指定号码的通话记录
             $callHistoryData = unserialize($mobileInfo['CallBill']);
             #dump($callHistoryData);exit;
             /* [0] => array(6) {
                ["tel"] => string(11) "18246907070"
                ["times"] => string(41) "2018-04-01 07:26:39 - 2018-08-04 09:58:45"
                ["conways"] => string(9) "黑龙江"
                ["contype"] => int(249)
                ["longs"] => int(23026)
                ["consite"] => int(181)
              }*/
             $tels = array_column($callHistoryData, 'tel');
             $foundKey = array_search($phone, $tels);
             return $foundKey===false ? 0 : $callHistoryData[$foundKey]['contype'];

         }
         return '运营商未认证';
         /*return array(
             'status' => 'bad',
             'errorMsg' => '暂未获取到通话记录!',
         );*/

         /*//查询 社交认证表 中的手机通讯录
         $socialinfo = M('renzen_social')->field('ID,Phonelist')->where(array('UserID'=>$mobileinfos['UserID'],'IsDel'=>'0'))->find();
         if(!$socialinfo['Phonelist']){
             $this->ajaxReturn(0,'暂未获取到手机通讯录');
         }*/
     }

     // 删除
     public function del()
     {
         //获取要删除的记录的id(单条或者多条)
         $ids=I('request.ID','','trim');
         $idArr=explode(',',$ids);
         $result=M(self::T_TABLE)->where(array('ID'=>array('in',$idArr)))->setField('IsDel',1);
         if($result){
             $this->ajaxReturn(1,'删除成功');
         }else{
             $this->ajaxReturn(0,'删除失败');
         }
     }

 }