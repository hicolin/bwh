<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-07-02 09:21
 * 功能说明: 利润控制器
 */
 namespace Admin\Controller\Loans;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class LiruntongjiController extends BaseController{
     const T_MEMINFO='mem_info';

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
         $start=($page-1)*$rows;//开始截取位置

         $startday='';
         $endday=date('Y-m');
         //获取开始月份
         $oneinfos=M('loans_applylist')->field('ApplyTime')->where(array('IsDel'=>'0'))->order('ApplyTime asc')->find();
         if(!$oneinfos['ApplyTime']){
            $oneinfos['ApplyTime']=date('Y-m-d H:i:s');
         }
         $startday=date('Y-m',strtotime($oneinfos['ApplyTime']));
         $startday2=date('Y-m',strtotime($oneinfos['ApplyTime']));
         $startday2=strtotime($startday2);
         //如何获取两个时间之间的年和月份
         $dataArr[]['Times'] = $startday; //起始月;
         while( ($startday2 = strtotime('+1 month', $startday2)) <= strtotime($endday)){
            $dataArr[]['Times'] = date('Y-m',$startday2); // 取得递增月;
         }
        //排序 按时间降序
        $dataArr=$this->arraySequence($dataArr, 'Times', $sort = 'SORT_DESC');

         //数据分页
         $array='';
         $array=array_slice($dataArr,$start,$rows);
         $result=array();
         if($array){
            foreach ($array as $val) {
                //借款金额/笔数
                $Applynumbs='';
                $Applymoney='';
                $swhere=array();
                $swhere['IsDel']=array('eq','0');
                $swhere["DATE_FORMAT(ApplyTime,'%Y-%m')"]=array('eq',$val['Times']);
                $Applynumbs=M('loans_applylist')->where($swhere)->count('ID');
                //借款金额
                $Applymoney=M('loans_applylist')->where($swhere)->sum('ApplyMoney');
                if(!$Applymoney){
                	$val['Jkmoney']='0/0';
                }else{
                    $val['Jkmoney']=$Applymoney.'/'.$Applynumbs;
                }

                //放款金额/笔数
                $Downs='';
                $Downsmoney='';
                $fwhere=array();
                $fwhere['IsDel']=array('eq','0');
                $fwhere['LoanStatus']=array('in',array('2','3'));
                $fwhere["DATE_FORMAT(OpenTime,'%Y-%m')"]=array('eq',$val['Times']);
                $Downs=M('loans_applylist')->where($fwhere)->count('ID');
                //放款金额
                $Downsmoney=M('loans_applylist')->where($fwhere)->sum('OpenM');
                if(!$Downsmoney){
                	$val['Fkmoney']='0/0';
                }else{
                    $val['Fkmoney']=$Downsmoney.'/'.$Downs;
                }

                //还款金额/笔数
                $Hkcouonts1='';
                $CostPayable='';
                $hkwhere1=array();
                $hkwhere1['IsDel']=array('eq','0');
                $hkwhere1["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $hkwhere1['Status']=array('eq','1');
                $Hkcouonts1=M('loans_hklist')->where($hkwhere1)->count('ID');
                //还款金额
                $hkwherej=array();
                $hkwherej['IsDel']=array('eq','0');
                $hkwherej["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $hkwherej['Status']=array('eq','1');
                $CostPayable=M('loans_hklist')->where($hkwherej)->sum('TotalMoney');
                if(!$CostPayable){
                    $val['Hkmoney']='0/0';
                }else{
                    $val['Hkmoney']=$CostPayable.'/'.$Hkcouonts1;
                }

//                //利息/笔数
//                $Hkcouonts='';
//                $RatePayable='';
//                $hkwhere=array();
//                $hkwhere['IsDel']=array('eq','0');
//                $hkwhere["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
//                $hkwhere['Status']=array('eq','1');
//                $Hkcouonts=M('loans_hklist')->where($hkwhere)->count('ID');
//                //还款金额
//                $hkwhere2=array();
//                $hkwhere2['IsDel']=array('eq','0');
//                $hkwhere2["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
//                $hkwhere2['Status']=array('eq','1');
//                $RatePayable=M('loans_hklist')->where($hkwhere2)->sum('RatePayable');
//                if(!$RatePayable){
//                	$val['Liximoney']='0/0';
//                }else{
//                    $val['Liximoney']=$RatePayable.'/'.$Hkcouonts;
//                }

                //续借费/笔数
                $Xjcouonts1='';
                $Xjmoney='';
                $xjwhere1=array();
                $xjwhere1['IsDel']=array('eq','0');
                $xjwhere1["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $xjwhere1['Status']=array('eq','1');
                $Xjcouonts1=M('loans_xjapplylist')->where($xjwhere1)->count('ID');
                //续借费
                $xjwherej=array();
                $xjwherej['IsDel']=array('eq','0');
                $xjwherej["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $xjwherej['Status']=array('eq','1');
                $Xjmoney=M('loans_xjapplylist')->where($xjwherej)->sum('TotalMoney');
                if(!$Xjmoney){
                    $val['Xujiemoney']='0/0';
                }else{
                    $val['Xujiemoney']=$Xjmoney.'/'.$Xjcouonts1;
                }

                //罚息/笔数
                $Fxcouonts='';
                $FinePayable='';
                $fxwhere=array();
                $fxwhere['IsDel']=array('eq','0');
                $fxwhere["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $fxwhere['Status']=array('eq','1');
                $fxwhere['FinePayable']=array('GT','0');
                $Fxcouonts=M('loans_hklist')->where($fxwhere)->count('ID');
                //还款金额
                $fxwhere2=array();
                $fxwhere2['IsDel']=array('eq','0');
                $fxwhere2["DATE_FORMAT(ShTime,'%Y-%m')"]=array('eq',$val['Times']);
                $fxwhere2['Status']=array('eq','1');
                $fxwhere2['FinePayable']=array('GT','0');
                $FinePayable=M('loans_hklist')->where($fxwhere2)->sum('FinePayable');
                if(!$FinePayable){
                    $val['Fajinmoney']='0/0';
                }else{
                    $val['Fajinmoney']=$FinePayable.'/'.$Fxcouonts;
                }
                //计算利润
                $val['Lirunmoney']=$CostPayable-$Downsmoney+$FinePayable+$Xjmoney;

                $result['rows'][]=$val;
            }
             $result['total']=count($dataArr);
         }
         //统计行
        if($result['total']){
             //借款金额/笔数
            $Applynumbs='';
            $Applymoney='';
            $Jkmoney='';
            $swhere=array();
            $swhere['IsDel']=array('eq','0');
            $Applynumbs=M('loans_applylist')->where($swhere)->count('ID');
            //借款金额
            $Applymoney=M('loans_applylist')->where($swhere)->sum('ApplyMoney');
            if(!$Applymoney){
                $Jkmoney='0/0';
            }else{
                $Jkmoney=$Applymoney.'/'.$Applynumbs;
            }

            //放款金额/笔数
            $Downs='';
            $Downsmoney='';
            $Fkmoney='';
            $fwhere=array();
            $fwhere['IsDel']=array('eq','0');
            $fwhere['LoanStatus']=array('in',array('2','3'));
            $Downs=M('loans_applylist')->where($fwhere)->count('ID');
            //放款金额
            $Downsmoney=M('loans_applylist')->where($fwhere)->sum('OpenM');
            if(!$Downsmoney){
                $Fkmoney='0/0';
            }else{
                $Fkmoney=$Downsmoney.'/'.$Downs;
            }

            //还款金额/笔数
            $Hkcouonts1='';
            $CostPayable='';
            $Hkmoney='';
            $hkwhere1=array();
            $hkwhere1['IsDel']=array('eq','0');
            $hkwhere1['Status']=array('eq','1');
            $Hkcouonts1=M('loans_hklist')->where($hkwhere1)->count('ID');
            //还款金额
            $hkwherej=array();
            $hkwherej['IsDel']=array('eq','0');
            $hkwherej['Status']=array('eq','1');
            $CostPayable=M('loans_hklist')->where($hkwherej)->sum('TotalMoney');
            if(!$CostPayable){
                $Hkmoney='0/0';
            }else{
                $Hkmoney=$CostPayable.'/'.$Hkcouonts1;
            }

//            //利息/笔数
//            $Hkcouonts='';
//            $RatePayable='';
//            $Liximoney='';
//            $hkwhere=array();
//            $hkwhere['IsDel']=array('eq','0');
//            $hkwhere['Status']=array('eq','1');
//            $Hkcouonts=M('loans_hklist')->where($hkwhere)->count('ID');
//            //还款金额
//            $hkwhere2=array();
//            $hkwhere2['IsDel']=array('eq','0');
//            $hkwhere2['Status']=array('eq','1');
//            $RatePayable=M('loans_hklist')->where($hkwhere2)->sum('RatePayable');
//            if(!$RatePayable){
//                $Liximoney='0/0';
//            }else{
//                $Liximoney=$RatePayable.'/'.$Hkcouonts;
//            }

            //续借费/笔数
            $Xjcouonts1='';
            $Xjmoney='';
            $Xujiemoney='';
            $xjwhere1=array();
            $xjwhere1['IsDel']=array('eq','0');
            $xjwhere1['Status']=array('eq','1');
            $Xjcouonts1=M('loans_xjapplylist')->where($xjwhere1)->count('ID');
            //续借费
            $xjwherej=array();
            $xjwherej['IsDel']=array('eq','0');
            $xjwherej['Status']=array('eq','1');
            $Xjmoney=M('loans_xjapplylist')->where($xjwherej)->sum('TotalMoney');
            if(!$Xjmoney){
                $Xujiemoney='0/0';
            }else{
                $Xujiemoney=$Xjmoney.'/'.$Xjcouonts1;
            }

            //罚息/笔数
            $Fxcouonts='';
            $FinePayable='';
            $Fajinmoney='';
            $fxwhere=array();
            $fxwhere['IsDel']=array('eq','0');
            $fxwhere['Status']=array('eq','1');
            $fxwhere['FinePayable']=array('GT','0');
            $Fxcouonts=M('loans_hklist')->where($fxwhere)->count('ID');
            //还款金额
            $fxwhere2=array();
            $fxwhere2['IsDel']=array('eq','0');
            $fxwhere2['Status']=array('eq','1');
            $fxwhere2['FinePayable']=array('GT','0');
            $FinePayable=M('loans_hklist')->where($fxwhere2)->sum('FinePayable');
            if(!$FinePayable){
                $Fajinmoney='0/0';
            }else{
                $Fajinmoney=$FinePayable.'/'.$Fxcouonts;
            }

            $insertArray=array();//统计数组
            $insertArray['Times']='合计';
            $insertArray['Jkmoney']=$Jkmoney;
            $insertArray['Fkmoney']=$Fkmoney;

            $insertArray['Hkmoney']=$Hkmoney;
            //$insertArray['Cuohemoney']=$Cuohemoney;

//            $insertArray['Liximoney']=$Liximoney;
            $insertArray['Xujiemoney']=$Xujiemoney;
            $insertArray['Fajinmoney']=$Fajinmoney;

            $insertArray['Lirunmoney']=$CostPayable-$Downsmoney+$FinePayable+$Xjmoney;
            array_push($result['rows'],$insertArray);
        }
         $this->ajaxReturn($result);
     }
     //会员导出功能
    public function exportexcel(){
        //查出相应信息
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
         $StartTime=I('post.StartTime','');
         $EndTime=I('post.EndTime','');
         
         $startday='';
         $endday='';
         //算出开始和结束时间
         if(!$StartTime && !$EndTime){
            $endday=date('Y-m-d');
         }
         if($StartTime && ($StartTime>date('Y-m-d'))){
            $endday=date('Y-m-d');
         }elseif($StartTime){
            $startday=$StartTime;
         }

         if($EndTime){
            $endday=$EndTime;
         }

         $dataArr=array();//总数据记录,单位每天
         if($startday && $endday){
            if($startday!=$endday){
                $dataArr[]['Times']=$startday;
                for($i=1;true;$i++){
                    $current='';
                    $current=date('Y-m-d',strtotime("+".$i."day",strtotime($startday)));
                    $dataArr[]['Times']=$current;
                    if($current>=$endday){
                        break;
                    }
                }
            }else{
                $dataArr[]['Times']=$startday;
            }
            //排序 按时间降序
            $dataArr=$this->arraySequence($dataArr, 'Times', $sort = 'SORT_DESC');
         }elseif($endday){
            $dataArr[]['Times']=$endday;
            //向后取31天
            for($i=1;true;$i++){
                $current='';
                $current=date('Y-m-d',strtotime("-".$i."day",strtotime($endday)));
                $dataArr[]['Times']=$current;
                if($i>=30){
                    break;
                }
            }
         }
         //数据分页
         $array='';
         $array=$dataArr;
         $result=array();
         if($array){
            foreach ($array as $val) {
                //注册人数
                $rwhere=array();
                $rwhere['IsDel']=array('eq','0');
                $rwhere['RegTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Regists']=M(self::T_MEMINFO)->where($rwhere)->count();

                //申请单数 借款单数
                $swhere=array();
                $swhere['IsDel']=array('eq','0');
                $swhere['ApplyTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Applynumbs']=M('loans_applylist')->where($swhere)->count('ID');
                //借款金额 统计实际会放款的金额
                $val['Applymoney']=M('loans_applylist')->where($swhere)->sum('ApplyMoney');
                if(!$val['Applymoney']){
                	$val['Applymoney']='0';
                }

                //放款单数
                $fwhere=array();
                $fwhere['IsDel']=array('eq','0');
                $fwhere['LoanStatus']=array('in',array('2','3'));
                $fwhere['OpenTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Downs']=M('loans_applylist')->where($fwhere)->count('ID');
                //放款金额
                $val['Downsmoney']=M('loans_applylist')->where($fwhere)->sum('OpenM');
                if(!$val['Downsmoney']){
                	$val['Downsmoney']='0';
                }

                //还款单数
                $hkwhere=array();
                $hkwhere['IsDel']=array('eq','0');
                $hkwhere['HkTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Hkcouonts']=M('loans_hklist')->where($hkwhere)->count('ID');
                //还款金额
                $hkwhere2=array();
                $hkwhere2['IsDel']=array('eq','0');
                $hkwhere2['ShTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $hkwhere2['Status']=array('eq','1');
                $val['Hkmoney']=M('loans_hklist')->where($hkwhere2)->sum('TotalMoney');
                if(!$val['Hkmoney']){
                	$val['Hkmoney']='0';
                }
                $result[]=$val;
            }
         }

        $data['rows']=$result;
        //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>日期</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>注册人数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>借款单数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>借款金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款单数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>还款单数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>还款金额</td>
                
            </tr>';

        foreach($data['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Times'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Regists'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Applynumbs'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Applymoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Downs'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Downsmoney'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Hkcouonts'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Hkmoney'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'统计报表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        header("Content-type: application/vnd.ms-excel; charset=GBK");
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

 }