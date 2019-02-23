<?php
/**
 * 功能说明: 身份证认证控制器
 */
 namespace Admin\Controller\Renzen;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class CardsController extends BaseController{

     const T_TABLE='renzen_cards';
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
         $Status=I('post.Status',-5,'int');
         if($Status!=-5){
            $where['Status']=$Status;
         }
         $where['IsDel']=0;
         //查询的数据表字段名
         $col='ID,UserID,RenzTime,Status';//默认全字段查询

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
                 $meminfo=M(self::T_MEMINFO)->field('MemAccount,Mobile,TrueName,IDCard')->find($val['UserID']);
                 $val['MemAccount']=$meminfo['MemAccount'];
                 $val['Mobile']=$meminfo['Mobile'];
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
            $infos=M('renzen_cards')->alias('a')
                   ->field('a.*,b.Mobile,b.TrueName,b.IDCard')
                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                   ->where(array('a.ID'=>$ID))->find();
            if($infos['Yddatas']){
                $infos['Yddatas']=unserialize($infos['Yddatas']);
            }
            $cardimgArr=array();
            $cardimgArr[]=$infos['CardFace'];
            $cardimgArr[]=$infos['CardSide'];
            $cardimgArr[]=$infos['Cardschi'];
            $this->assign(array(
                'infos'=>$infos,
                'cardimgArr'=>$cardimgArr,
                ));
        }
        $this->display();
     }

     //审核
     public function aduit(){
        $id=I('get.ID',0,'intval');
        $res=M(self::T_TABLE)->where(array("ID"=>$id))->find();
        $cardimgArr=array();
        $cardimgArr[]=$res['CardFace'];
        $cardimgArr[]=$res['CardSide'];
        $cardimgArr[]=$res['Cardschi'];
        $this->assign(array(
            "res"=>$res,
            "cardimgArr"=>$cardimgArr,
            ));
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
                'Codes'=>'card',
                'UserID'=>$UserID,
                'OperatorID'=>$_SESSION['AdminInfo']['AdminID'],
                'UpdateTime'=>date("Y-m-d H:i:s"),
                );
            if($Status=='1'){
                $datas['Descs']='身份证认证通过';
            }elseif($Status=='2'){
                $datas['Descs']='身份证认证失败';
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
            $where['Codes']=array('eq','card');
            //查询的列名
            $col='';
            //获取最原始的数据列表
            $query=new XBCommon\XBQuery();
            $array=$query->GetDataList(self::T_SHLIST,$where,$page,$rows,$sort,$col);

            //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
            $result=array();
            if($array['rows']){
                foreach ($array['rows'] as $val){
                    $val['Codes']='身份证认证';
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
     * 有盾 沙盒模式 数据都是模拟的
     */
    private $udcredit_sanbox = false;#true
     /**
      * 有盾报表
      */
    public function udcredit()
    {
        $ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;

        if(empty($ID)) return $this->ajaxReturn(0,'暂不能查看报告!');

        #获取有盾数据
        $res = $this->getUdcreditYunhuiyanData($ID);

        if($res['status'] == 'good') {
            #这里判断是 显示数据||返回跳转连接
            $action = I('get.action', '');
            if('showinfo' == $action) {//显示数据

                $this->assign($res['data']);
                return $this->display();
            }
            return $this->ajaxReturn(1,'/admin.php/Renzen/Cards/udcredit?action=showinfo&ID='.$ID);
        }
        return $this->ajaxReturn(0,'暂不能查看报告!');
    }

    private function getUdcreditYunhuiyanData($cardID)
     {
         $docspath = APP_PATH.'/../Public/Admin/udcredit/';
         if($this->udcredit_sanbox) {
             #测试
             $res = file_get_contents($docspath.'realdata.json');
         } else {

             $res = M(self::T_TABLE)->where("ID=".$cardID)->getField('Yddatas');
             #没查到 尝试重新查询
             if(!$res) {
                 $res = $this->updatebaogao($cardID);
             }
         }

         if( empty($res)) {
             return array(
                 'status' => 'bad',
             );
         }#return

         $res = json_decode( json_encode(unserialize($res))  );
//         echo '<pre>';
//         print_r($res);exit;
         #获取配置信息
         $udConfigPath = $docspath.'config.json';
         if(!file_exists($udConfigPath)) return $this->ajaxReturn(0,'udcredit/config.json缺少有盾的配置文件!');
         $config = json_decode( file_get_contents($udConfigPath) );

         #ud数据处理
            #$body = $res->body;
         $body = $res;

         #组装用户特征
         $user_features = [];
         foreach ($body->user_features as $value) {
             $user_features[$value->user_feature_type] = $value->last_modified_date;
         }

         $body->user_features = $user_features;#index.php Line:140

         #$dataJsonStrify = json_encode($data, JSON_UNESCAPED_UNICODE);

         #echart
         #申请借款,借款
         $xAxis = ['总计','近6月','近3月','近1月'];
         $echarts1 = array(
             [
                 'legend' => '申请借款',
                 'data' => [
                     $body->loan_detail->loan_platform_count,
                     $body->loan_detail->loan_platform_count_6m,
                     $body->loan_detail->loan_platform_count_3m,
                     $body->loan_detail->loan_platform_count_1m,
                 ]
             ],
             [
                 'legend' => '借款',
                 'data' => [
                     $body->loan_detail->actual_loan_platform_count,
                     $body->loan_detail->actual_loan_platform_count_6m,
                     $body->loan_detail->actual_loan_platform_count_3m,
                     $body->loan_detail->actual_loan_platform_count_1m,
                 ]
             ]
         );
         #还款 图标
         $echarts2 = array(
             [
                 'legend' => '还款',
                 'data' => [
                     $body->loan_detail->repayment_platform_count,
                     $body->loan_detail->repayment_platform_count_6m,
                     $body->loan_detail->repayment_platform_count_3m,
                     $body->loan_detail->repayment_platform_count_1m,
                 ]
             ]
         );

         return array(
             'status' => 'good',
             'ret_code' => $res->header->ret_code,
             'ret_msg' => $res->header->ret_msg,
             'data' => compact('config', 'body', 'echarts1', 'echarts2')
         );
     }

     private function curl_send_post($url,$data,$header = array()){
         $curl = curl_init();
         curl_setopt_array($curl, array(
             CURLOPT_URL => $url,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => "",
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 30,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => "POST",
             CURLOPT_POSTFIELDS =>json_encode($data,JSON_UNESCAPED_UNICODE),
             CURLOPT_SSL_VERIFYPEER => false, // 跳过证书检查
             CURLOPT_SSL_VERIFYHOST => CURLOPT_SSL_VERIFYHOST,
             CURLOPT_HTTPHEADER =>$header
         ));
         $res = curl_exec($curl);
         curl_close($curl);
         return $res;
     }
    //更新图片功能
     public function updateimgs(){
        $id=I("post.ID",'','trim');
        $cardinfos=M('renzen_cards')->field('ID,UserID,RenzResult')->where(array('ID'=>$id))->find();
        if(!$cardinfos){
            $this->ajaxReturn(0,'很抱歉，数据查询失败！');
        }
        $RenzResult=unserialize($cardinfos['RenzResult']);
        if(!$RenzResult){
            $this->ajaxReturn(0,'很抱歉，有盾数据失败！');
        }
        //创建图片保存路径
        $SmallPaths = THINK_PATH.'../Upload/file/'.$cardinfos['UserID'].'/';
        if(!is_dir($SmallPaths)){
            mkdir($SmallPaths,0644,true);
        }
        $sdata=array();
        $UserID=$cardinfos['UserID'];
        //保存三张照片
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );  
        if($RenzResult['url_frontcard']){
            //身份证正面
            $fronturl=$SmallPaths.'sfzzm_'.$UserID.'.jpg';
            $img0 = file_get_contents($RenzResult['url_frontcard'], false, stream_context_create($arrContextOptions));
            $dowresult='';
            $dowresult=json_decode($img0,true);
            if($dowresult['ret_code']=='700005'){
                $this->ajaxReturn(0,$dowresult['ret_msg']);
            }
            file_put_contents($fronturl,$img0); 
            $sdata['CardFace']="/Upload/file/".$UserID.'/'.'sfzzm_'.$UserID.".jpg";//保存的路径
        }
        if($RenzResult['url_backcard']){
            //身份证背面
            $backurl=$SmallPaths.'sfzfm_'.$UserID.'.jpg';
            $img = file_get_contents($RenzResult['url_backcard'], false, stream_context_create($arrContextOptions));
            $dowresult='';
            $dowresult=json_decode($img,true);
            if($dowresult['ret_code']=='700005'){
                $this->ajaxReturn(0,$dowresult['ret_msg']);
            }
            file_put_contents($backurl,$img); 
            $sdata['CardSide']="/Upload/file/".$UserID.'/'.'sfzfm_'.$UserID.".jpg";//保存的路径
        }
        if($RenzResult['url_photoliving']){
            //人脸采集照片
            $handurl=$SmallPaths.'sfz_'.$UserID.'.jpg';
            $img1 = file_get_contents($RenzResult['url_photoliving'], false, stream_context_create($arrContextOptions));
            $dowresult='';
            $dowresult=json_decode($img1,true);
            if($dowresult['ret_code']=='700005'){
                $this->ajaxReturn(0,$dowresult['ret_msg']);
            }
            file_put_contents($handurl,$img1); 
            $sdata['Cardschi']="/Upload/file/".$UserID.'/'.'sfz_'.$UserID.".jpg";//保存的路径
        }
        $sdata['UpdateTime']=date('Y-m-d H:i:s');
        $uptrest=M('renzen_cards')->where(array('ID'=>$cardinfos['ID']))->save($sdata);
        if($uptrest){
            $this->ajaxReturn(1,'认证照片更新成功！');
        }else{
            $this->ajaxReturn(0,'认证照片更新失败！');
        }
     }

     //更新有盾报告功能
     private function updatebaogao($RenzenCardsID){

         $UserID=M('renzen_cards')->where(array('ID'=>$RenzenCardsID))->getField('UserID');
         if(!$UserID){
             $this->ajaxReturn(0,'很抱歉，数据查询失败！');
         }
         $IDCard=M('mem_info')->where(array('ID'=>$UserID))->getField('IDCard');
         if(!$IDCard){
             $this->ajaxReturn(0,'很抱歉，身份证号获取失败！');
         }
         //-----------------------请求有盾接口获取数据----------start
         $setinfos=M('sys_inteparameter')->where(array('IntegrateID'=>'9'))->select();
         $secretkey='';
         $pubkey='';
         foreach($setinfos as $k=>$v){
             if($v['ParaName']=='api_key'){
                 $pubkey=$v['ParaValue'];
             }
             if($v['ParaName']=='security_key'){
                 $secretkey=$v['ParaValue'];
             }
         }
         $data=array(
             'id_no'=>$IDCard,
         );
         $str = json_encode($data,JSON_UNESCAPED_UNICODE);
         $url = 'https://api4.udcredit.com/dsp-front/4.1/dsp-front/default/pubkey/'.$pubkey.'/product_code/Y1001005/out_order_id/';
         $url .= md5(time() . mt_rand(0,1000)).'/signature/'.strtoupper(md5($str."|".$secretkey));
         $header = ['Content-Type: application/json; charset=utf-8'];
         $ydundata =$this->curl_send_post($url,$data,$header);
         $ydundata=json_decode($ydundata,true);
         if($ydundata['header']['ret_code']=='000000'){
             //表示数据请求成功
             $sdata['Yddatas']=serialize($ydundata['body']);
         }
         //-----------------------请求有盾接口获取数据----------end
         $sdata['UpdateTime']=date('Y-m-d H:i:s');
         $uptrest = M('renzen_cards')->where(array('ID'=>$RenzenCardsID))->save($sdata);
         if($uptrest!==false) {
             return $sdata['Yddatas'];
         }
         return false;
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