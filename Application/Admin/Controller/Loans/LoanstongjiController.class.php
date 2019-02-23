<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-07-02 09:21
 * 功能说明: 统计报表控制器
 */
 namespace Admin\Controller\Loans;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class LoanstongjiController extends BaseController{
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
            if(!$EndTime){
                $endday=date('Y-m-d');
            }
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
         $array=array_slice($dataArr,$start,$rows);
         $result=array();
         if($array){
            foreach ($array as $val) {
            	//注册人数
                $rwhere=array();
                $rwhere['IsDel']=array('eq','0');
                $rwhere['RegTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Regists']=M(self::T_MEMINFO)->where($rwhere)->count('ID');

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

                //续期单数
                $xjwhere=array();
                $xjwhere['IsDel']=array('eq','0');
                $xjwhere['XjTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Xjcouonts']=M('loans_xjapplylist')->where($xjwhere)->count('ID');
                //续期金额
                $xjwhere2=array();
                $xjwhere2['IsDel']=array('eq','0');
                $xjwhere2['ShTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $xjwhere2['Status']=array('eq','1');
                $val['Xjmoney']=M('loans_xjapplylist')->where($xjwhere2)->sum('TotalMoney');
                if(!$val['Xjmoney']){
                    $val['Xjmoney']='0';
                }
                
                $result['rows'][]=$val;
            }
             $result['total']=count($dataArr);
         }
         //统计行
        if($result['total']){
            $swhere2=array();//申请单数
            $fwhere2=array();//放款单数
            $rwhere2=array();//注册人数

            $hkwhere3=array();//还款单数
            $hkwhere4=array();//还款单数

            $xjwhere3=array();//续借单数
            $xjwhere4=array();//续借金额

            //如有有时间条件的话
            $ToStartTime=$StartTime;
            //$ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
            $ToEndTime=$EndTime;
            if($StartTime!=null){
                if($EndTime!=null){
                    //有开始时间和结束时间
                    $swhere2['ApplyTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $fwhere2['OpenTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $rwhere2['RegTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $hkwhere3['HkTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $hkwhere4['ShTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $xjwhere3['XjTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                    $xjwhere4['ShTime']=array('between',$ToStartTime.' 00:00:00'.','.$ToEndTime.' 23:59:59');
                }else{
                    //只有开始时间
                    $swhere2['ApplyTime']=array('egt',$ToStartTime.' 00:00:00');
                    $fwhere2['OpenTime']=array('egt',$ToStartTime.' 00:00:00');
                    $rwhere2['RegTime']=array('egt',$ToStartTime.' 00:00:00');
                    $hkwhere3['HkTime']=array('egt',$ToStartTime.' 00:00:00');
                    $hkwhere4['ShTime']=array('egt',$ToStartTime.' 00:00:00');
                    $xjwhere3['XjTime']=array('egt',$ToStartTime.' 00:00:00');
                    $xjwhere4['ShTime']=array('egt',$ToStartTime.' 00:00:00');
                }
            }else{
                //只有结束时间
                if($EndTime!=null){
                    $swhere2['ApplyTime']=array('elt',$ToEndTime.' 23:59:59');
                    $fwhere2['OpenTime']=array('elt',$ToEndTime.' 23:59:59');
                    $rwhere2['RegTime']=array('elt',$ToEndTime.' 23:59:59');
                    $hkwhere3['HkTime']=array('elt',$ToEndTime.' 23:59:59');
                    $hkwhere4['ShTime']=array('elt',$ToEndTime.' 23:59:59');
                    $xjwhere3['XjTime']=array('elt',$ToEndTime.' 23:59:59');
                    $xjwhere4['ShTime']=array('elt',$ToEndTime.' 23:59:59');
                }
            }
            //注册人数
            $rwhere2['IsDel']=array('eq','0');
            $Regists='0';
            $Regists=M(self::T_MEMINFO)->where($rwhere2)->count('ID');

            //申请单数  借款单数
            $swhere2['IsDel']=array('eq','0');
            $Applynumbs='0';
            $Applynumbs=M('loans_applylist')->where($swhere2)->count('ID');
            //借款金额
            $Applymoney=M('loans_applylist')->where($swhere2)->sum('ApplyMoney');
            if(!$Applymoney){
            	$Applymoney='0';
            }

            //放款单数
            $fwhere2['IsDel']=array('eq','0');
            $fwhere2['LoanStatus']=array('in',array('2','3'));
            $Downs=M('loans_applylist')->where($fwhere2)->count('ID');
            //放款金额
            $Downsmoney=M('loans_applylist')->where($fwhere2)->sum('OpenM');
            if(!$Downsmoney){
            	$Downsmoney='0';
            }

            //还款单数
            $hkwhere3['IsDel']=array('eq','0');
            $Hkcouonts=M('loans_hklist')->where($hkwhere3)->count('ID');
            //还款金额
            $hkwhere4['IsDel']=array('eq','0');
            $hkwhere4['Status']=array('eq','1');
            $Hkmoney=M('loans_hklist')->where($hkwhere4)->sum('TotalMoney');
            if(!$Hkmoney){
            	$Hkmoney='0';
            }

            //续期单数
            $xjwhere3['IsDel']=array('eq','0');
            $Xjcouonts=M('loans_xjapplylist')->where($xjwhere3)->count('ID');
            //续期金额
            $xjwhere4['IsDel']=array('eq','0');
            $xjwhere4['Status']=array('eq','1');
            $Xjmoney=M('loans_xjapplylist')->where($xjwhere4)->sum('TotalMoney');
            if(!$Xjmoney){
                $Xjmoney='0';
            }

            $insertArray=array();//统计数组
            $insertArray['Times']='合计';
            $insertArray['Applynumbs']=$Applynumbs;
            $insertArray['Applymoney']=$Applymoney;
            $insertArray['Downs']=$Downs;
            $insertArray['Downsmoney']=$Downsmoney;

            $insertArray['Hkcouonts']=$Hkcouonts;
            $insertArray['Hkmoney']=$Hkmoney;

            $insertArray['Xjcouonts']=$Xjcouonts;
            $insertArray['Xjmoney']=$Xjmoney;

            $insertArray['Regists']=$Regists;
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
                $val['Regists']=M(self::T_MEMINFO)->where($rwhere)->count('ID');

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