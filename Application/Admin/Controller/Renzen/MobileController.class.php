<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-06-29 09:21
 * 功能说明: 手机认证控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class MobileController extends BaseController{

     const T_TABLE='renzen_mobile';
     const T_ADMIN='sys_administrator';
     const T_MEMINFO='mem_info';
     const T_SHLIST='renzen_shlist';

     public function index(){
         //contype 通讯类型
         // $aa=array(array('tel'=>'18355195992','times'=>'2017-01-01 12:46:39','conways'=>'本地','contype'=>'主叫','longs'=>'2分22秒','consite'=>'合肥'),array('tel'=>'18355195992','times'=>'2017-01-01 12:46:39','conways'=>'本地','contype'=>'主叫','longs'=>'2分22秒','consite'=>'合肥'));
         // echo serialize($aa);exit;
        //echo "<script language=\"javascript\">window.open ('http://www.baidu.com')</script>";
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
         $col='ID,UserID,ZUserName,OpenDate,AccountBalance,IsPP,Status,RenzTime,IsDel,OperatorID,UpdateTime';//默认全字段查询

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
                 if($val['IsPP']=='0'){
                    $val['IsPP']='<span style="color:red;">未匹配</span>';
                 }elseif($val['IsPP']=='1'){
                    $val['IsPP']='<span style="color:green;">已匹配</span>';
                 }
                 $meminfo='';
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
            $infos=M('renzen_mobile')->alias('a')
                   ->field('a.ID,a.ZUserName,a.OpenDate,a.AccountBalance,a.Status,a.RenzTime,b.Mobile,b.TrueName')
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
                'Codes'=>'mobile',
                'UserID'=>$UserID,
                'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                'UpdateTime'=>date("Y-m-d H:i:s"),
                );
            if($Status=='1'){
                $datas['Descs']='手机认证通过';
            }elseif($Status=='2'){
                $datas['Descs']='手机认证失败';
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
            $where['Codes']=array('eq','mobile');
            //查询的列名
            $col='';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_SHLIST,$where,$page,$rows,$sort,$col);

            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['Codes']='手机认证';
                    $val['UserID']=$query->GetValue(self::T_MEMINFO,array('ID'=>(int)$val['UserID']),'TrueName');
                    $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');
                    $result['rows'][]=$val;
                }
                $result['total']=$array['total'];
            }
            $this->ajaxReturn($result);
        }
    }
    //匹配通话
    public function getpp(){
        $id=I('post.ID',0,'intval');
        $mobileinfos=M('renzen_mobile')->field('ID,UserID,CallBill')->where(array('ID'=>$id,'IsDel'=>'0'))->find();
        if(!$mobileinfos['CallBill']){
            $this->ajaxReturn(0,'暂未获取到通话记录!');
        }
        //查询 社交认证表 中的手机通讯录
        $socialinfo=M('renzen_social')->field('ID,Phonelist')->where(array('UserID'=>$mobileinfos['UserID'],'IsDel'=>'0'))->find();
        if(!$socialinfo['Phonelist']){
            $this->ajaxReturn(0,'暂未获取到手机通讯录');
        }
        //进行数据匹配
        $callbills=unserialize($mobileinfos['CallBill']);
        $phonelists=unserialize($socialinfo['Phonelist']);
        foreach($phonelists as $k=>$v){
            $sums='0';//次数
            foreach($callbills as $k2=>$v2){
                if($v['tel']==$v2['tel']){
                    //(被)呼叫总次数
                    $sums=$v2['contype']+$v2['consite'];
                }
            }
            $phonelists[$k]['sums']=$sums;
            $phonelists[$k]['updatetime']=date('Y-m-d H:i:s');
        }
        $phonelists=serialize($phonelists);
        $updata=array(
            'Phonelist'=>$phonelists,
            );
        $result=M('renzen_social')->where(array('ID'=>$socialinfo['ID']))->save($updata);
        if($result){
            $result2=M('renzen_mobile')->where(array('ID'=>$id))->save(array('IsPP'=>'1'));
            $this->ajaxReturn(0,'匹配成功!');
        }else{
            $this->ajaxReturn(0,'匹配失败!');
        }
    }
    /**
     *通话记录
     */
    public function CallBill(){
        $listID=I('get.listID');
        $tel=I('post.tel');
//        $contype=I('post.contype','-5');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $CallBill=M(self::T_TABLE)->where(array("ID"=>$listID))->getField('CallBill');
            if($CallBill){
                $CallBill=unserialize($CallBill);
                //搜索条件
                if($tel){
                    foreach($CallBill as $k=>$v){
                        if($v['tel']!=$tel){
                            unset($CallBill[$k]);
                        }
                    }
                }
//                if($contype!='-5'){
//                    if($contype=='1'){
//                        //主叫
//                        foreach($CallBill as $k=>$v){
//                            if($v['contype']!='主叫'){
//                                unset($CallBill[$k]);
//                            }
//                        }
//                    }elseif($contype=='2'){
//                        //被叫
//                        foreach($CallBill as $k=>$v){
//                            if($v['contype']!='被叫'){
//                                unset($CallBill[$k]);
//                            }
//                        }
//                    }
//                }
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
                $this->ajaxReturn($result);
            }
        }
    }
     /**
     *手机通讯录
     */
    public function Phonelist(){
        $listID=I('get.listID');
        $name=I('post.name','');
        $tel=I('post.tel','');
        //排序
        $sort=I('post.sort');
        $order=I('post.order');

        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $UserID=M(self::T_TABLE)->where(array("ID"=>$listID))->getField('UserID');

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
                //搜索
                if($name){
                    foreach($Phonelist as $k=>$v){
                        if(strpos($v['name'],$name)===false){
                            unset($Phonelist[$k]);
                        }
                    }
                }
                if($tel){
                    foreach($Phonelist as $k=>$v){
                        if($v['tel']!=$tel){
                            unset($Phonelist[$k]);
                        }
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
    //通话排名
    public function tonghuapm(){
        $listID=I('get.listID');
        if(!empty($listID)){
            //接收POST信息,拼接查询条件
            $page=I('post.page',1,'intval');
            $rows=I('post.rows',20,'intval');
            $start=($page-1)*$rows;//开始截取位置

            $UserID=M(self::T_TABLE)->where(array("ID"=>$listID))->getField('UserID');

            $Phonelist=M('renzen_social')->where(array("UserID"=>$UserID))->getField('Phonelist');
            if($Phonelist){
                $Phonelist2=unserialize($Phonelist);
                //去除通话次数为0的
                $Phonelist=array();
                foreach($Phonelist2 as $k=>$v){
                    if($v['sums']>0){
                        $Phonelist[]=$v;
                    }
                }
                $Phonelist=$this->arraySequence($Phonelist,'sums','SORT_DESC');
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

     //天机报告 独立页面
     public function tianji(){
        $id=I('post.ID',0,'intval');
        $mobileinfos=M('renzen_mobile')->field('ID,TUserID,TaskID')->where(array('ID'=>$id,'IsDel'=>'0'))->find();
        if(!$mobileinfos['TaskID'] && !$mobileinfos['TUserID'] && !$mobileinfos['MobileData']){
            $this->ajaxReturn(0,'暂不能查看报告!');
        }
         $jumpurl="http://".$_SERVER['HTTP_HOST']."/index.php/Tianji/report?searchId=".$mobileinfos['TUserID']."&niqueId=".$mobileinfos['TaskID'];
         $this->ajaxReturn(1,$jumpurl);
     }

     //通过api地址处理
    public function https_request2($url){
        $oCurl = curl_init();//实例化
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );//是否返回值，1时给字符串，0输出到屏幕
        $sContent = curl_exec($oCurl);//获得页面数据
        $aStatus = curl_getinfo($oCurl);//获取CURL连接数据的信息
        curl_close($oCurl);//关闭资源
        //获取成功
        $output_array = json_decode($sContent,true);//转换json格式
        return $output_array;
    }

 }