<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-05-18 16:50
 * 功能说明: 推广渠道管理控制器
 */
 namespace Admin\Controller\Members;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;

 class TuiguangtjController extends BaseController{

     const T_TABLE='tg_admin';
     const T_MEMINFO='mem_info';
     const T_ADMIN='sys_administrator';
     const T_TGKOULIANG='tg_kouliang';

     public function index(){
        //$_SESSION['AdminInfo']['AdminID']
         //判断是不是推广渠道会员
         $tdmemid=M(self::T_TABLE)->where(array('UserName'=>$_SESSION['AdminInfo']['Admin'],'IsDel'=>'0'))->getField('ID');
         $adminlist=M(self::T_TABLE)->field('ID,Name')->where(array('Status'=>'1','IsDel'=>'0'))->select();
         $this->assign(array(
            'adminlist'=>$adminlist,
            'tdmemid'=>$tdmemid,
            ));
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
             $sort='Sort asc,ID desc';
         }
         $start=($page-1)*$rows;//开始截取位置

         //搜索条件
         $StartTime=I('post.StartTime','');
         $EndTime=I('post.EndTime','');
         $TgadminID=I('post.TgadminID',-5);

         //判断是不是推广渠道会员
         $tdmemid=M(self::T_TABLE)->where(array('UserName'=>$_SESSION['AdminInfo']['Admin'],'IsDel'=>'0'))->getField('ID');
         $isflag=false;
         if($tdmemid){
            $TgadminID=$tdmemid;
            $isflag=true;
         }

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
         $array=array_slice($dataArr,$start,$rows);
         $result=array();
         if($array){
            foreach ($array as $val) {
                //申请单数
                $swhere=array();
                if($TgadminID!=-5){
                    $swhere['b.TgadminID']=array('eq',$TgadminID);
                }
                $swhere['a.IsDel']=array('eq','0');
                $swhere['a.ApplyTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Applynumbs']=M('loans_applylist')->alias('a')
                                   ->where($swhere)
                                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                                   ->count();
                //放款单数
                $fwhere=array();
                if($TgadminID!=-5){
                    $fwhere['b.TgadminID']=array('eq',$TgadminID);
                }
                $fwhere['a.IsDel']=array('eq','0');
                $fwhere['a.LoanStatus']=array('in',array('2','3'));
                $fwhere['a.OpenTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Downs']=M('loans_applylist')->alias('a')
                              ->where($fwhere)
                              ->join('left join xb_mem_info b on a.UserID=b.ID')
                              ->count();
                //注册人数
                $rwhere=array();
                if($TgadminID!=-5){
                    $rwhere['TgadminID']=array('eq',$TgadminID);
                }
                $rwhere['IsDel']=array('eq','0');
                $rwhere['RegTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Regists']=M(self::T_MEMINFO)->where($rwhere)->count();
                //扣量--------start
                if($TgadminID!=-5){
                    if($isflag){
                        //推广会员登录 
                        $val['Applynumbs']=$this->getkouliang($TgadminID,$val['Times'],$val['Applynumbs']);
                        $val['Downs']=$this->getkouliang($TgadminID,$val['Times'],$val['Downs']);
                        $val['Regists']=$this->getkouliang($TgadminID,$val['Times'],$val['Regists']);
                    }else{
                        //管理员登录
                        $val['Applynumbs']=$val['Applynumbs'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Applynumbs']);
                        $val['Downs']=$val['Downs'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Downs']);
                        $val['Regists']=$val['Regists'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Regists']);
                    }
                }
                //扣量--------end
                $result['rows'][]=$val;
            }
             $result['total']=count($dataArr);
         }
         //统计行
        if($result['total']){
            $swhere2=array();//申请单数
            $fwhere2=array();//放款单数
            $rwhere2=array();//注册人数

            if($TgadminID!=-5){
                $swhere2['b.TgadminID']=array('eq',$TgadminID);
            }
            //如有有时间条件的话
            $ToStartTime=$StartTime;
            $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
            if($StartTime!=null){
                if($EndTime!=null){
                    //有开始时间和结束时间
                    $swhere2['a.ApplyTime']=array('between',$ToStartTime.','.$ToEndTime);
                    $fwhere2['a.OpenTime']=array('between',$ToStartTime.','.$ToEndTime);
                    $rwhere2['RegTime']=array('between',$ToStartTime.','.$ToEndTime);
                }else{
                    //只有开始时间
                    $swhere2['a.ApplyTime']=array('egt',$ToStartTime);
                    $fwhere2['a.OpenTime']=array('egt',$ToStartTime);
                    $rwhere2['RegTime']=array('egt',$ToStartTime);
                }
            }else{
                //只有结束时间
                if($EndTime!=null){
                    $swhere2['a.ApplyTime']=array('elt',$ToEndTime);
                    $fwhere2['a.OpenTime']=array('elt',$ToEndTime);
                    $rwhere2['RegTime']=array('elt',$ToEndTime);
                }
            }
            //申请单数
            $swhere2['a.IsDel']=array('eq','0');
            $Applynumbs='0';
            $Applynumbs2='0';//扣量后
            $Applynumbslist=M('loans_applylist')->alias('a')
                               ->field('count(*) as numbs,from_unixtime(unix_timestamp(a.ApplyTime), "%Y-%m-%d") as dates')
                               ->where($swhere2)
                               ->join('left join xb_mem_info b on a.UserID=b.ID')
                               ->group('from_unixtime(unix_timestamp(a.ApplyTime), "%Y-%m-%d")')
                               ->select();
            if($Applynumbslist){
                foreach($Applynumbslist as $k=>$v){
                     if($TgadminID!=-5){
                        $Applynumbs2+=$this->getkouliang($TgadminID,$v['dates'],$v['numbs']);//扣量
                     }
                     $Applynumbs+=$v['numbs'];
                }
            }              
            //放款单数
            if($TgadminID!=-5){
                $fwhere2['c.TgadminID']=array('eq',$TgadminID);
            }
            $fwhere2['a.IsDel']=array('eq','0');
            $fwhere2['a.LoanStatus']=array('in',array('2','3'));
            $Downs='0';
            $Downs2='0';//扣量后
            $Downslist=M('loans_applylist')->alias('a')
                          ->field('count(*) as numbs,from_unixtime(unix_timestamp(a.OpenTime), "%Y-%m-%d") as dates')
                          ->where($fwhere2)
                          ->join('left join xb_mem_info c on a.UserID=c.ID')
                          ->group('from_unixtime(unix_timestamp(a.OpenTime), "%Y-%m-%d")')
                          ->select();
            if($Downslist){
                foreach($Downslist as $k=>$v){
                     if($TgadminID!=-5){
                        $Downs2+=$this->getkouliang($TgadminID,$v['dates'],$v['numbs']);//扣量
                     }
                     $Downs+=$v['numbs'];
                }
            } 

            //注册人数
            if($TgadminID!=-5){
                $rwhere2['TgadminID']=array('eq',$TgadminID);
            }
            $rwhere2['IsDel']=array('eq','0');
            $Regists='0';
            $Regists2='0';//扣量后
            $Registslist=M(self::T_MEMINFO)->field('count(*) as numbs,from_unixtime(unix_timestamp(RegTime), "%Y-%m-%d") as dates')->where($rwhere2)->group('from_unixtime(unix_timestamp(RegTime), "%Y-%m-%d")')->select();
            if($Registslist){
                foreach($Registslist as $k=>$v){
                     if($TgadminID!=-5){
                        $Regists2+=$this->getkouliang($TgadminID,$v['dates'],$v['numbs']);//扣量
                     }
                     $Regists+=$v['numbs'];
                }
            } 

            $insertArray=array();//统计数组
            $insertArray['Times']='合计';
            if($TgadminID!=-5){
                if($isflag){
                    //推广会员登录 
                    $insertArray['Applynumbs']=$Applynumbs2;
                    $insertArray['Downs']=$Downs2;
                    $insertArray['Regists']=$Regists2;
                }else{
                    //管理员登录
                    $insertArray['Applynumbs']=$Applynumbs.'|'.$Applynumbs2;
                    $insertArray['Downs']=$Downs.'|'.$Downs2;
                    $insertArray['Regists']=$Regists.'|'.$Regists2;
                }
            }else{
                $insertArray['Applynumbs']=$Applynumbs;
                $insertArray['Downs']=$Downs;
                $insertArray['Regists']=$Regists;
            }
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
             $sort='Sort asc,ID desc';
         }

         //搜索条件
         $StartTime=I('post.StartTime','');
         $EndTime=I('post.EndTime','');
         $TgadminID=I('post.TgadminID',-5);

         //判断是不是推广渠道会员
         $tdmemid=M(self::T_TABLE)->where(array('UserName'=>$_SESSION['AdminInfo']['Admin'],'IsDel'=>'0'))->getField('ID');
         $isflag=false;
         if($tdmemid){
            $TgadminID=$tdmemid;
            $isflag=true;
         }
         
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
                //申请单数
                $swhere=array();
                if($TgadminID!=-5){
                    $swhere['b.TgadminID']=array('eq',$TgadminID);
                }
                $swhere['a.IsDel']=array('eq','0');
                $swhere['a.ApplyTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Applynumbs']=M('loans_applylist')->alias('a')
                                   ->where($swhere)
                                   ->join('left join xb_mem_info b on a.UserID=b.ID')
                                   ->count();
                //放款单数
                $fwhere=array();
                if($TgadminID!=-5){
                    $fwhere['c.TgadminID']=array('eq',$TgadminID);
                }
                $fwhere['a.IsDel']=array('eq','0');
                $fwhere['a.LoanStatus']=array('in',array('2','3'));
                $fwhere['a.OpenTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Downs']=M('loans_applylist')->alias('a')
                              ->where($fwhere)
                              ->join('left join xb_mem_info c on a.UserID=c.ID')
                              ->count();
                //注册人数
                $rwhere=array();
                if($TgadminID!=-5){
                    $rwhere['TgadminID']=array('eq',$TgadminID);
                }
                $rwhere['IsDel']=array('eq','0');
                $rwhere['RegTime']=array('between',$val['Times'].' 00:00:00'.','.$val['Times'].' 23:59:59');
                $val['Regists']=M(self::T_MEMINFO)->where($rwhere)->count();
                //扣量--------start
                if($TgadminID!=-5){
                    if($isflag){
                        //推广会员登录 
                        $val['Applynumbs']=$this->getkouliang($TgadminID,$val['Times'],$val['Applynumbs']);
                        $val['Downs']=$this->getkouliang($TgadminID,$val['Times'],$val['Downs']);
                        $val['Regists']=$this->getkouliang($TgadminID,$val['Times'],$val['Regists']);
                    }else{
                        //管理员登录
                        $val['Applynumbs']=$val['Applynumbs'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Applynumbs']);
                        $val['Downs']=$val['Downs'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Downs']);
                        $val['Regists']=$val['Regists'].'|'.$this->getkouliang($TgadminID,$val['Times'],$val['Regists']);
                    }
                }
                //扣量--------end
                $result[]=$val;
            }
         }

        $data['rows']=$result;
        //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>日期</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请单数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>放款单数</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>注册人数</td>
            </tr>';

        foreach($data['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Times'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Applynumbs'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Downs'].'</td>
                <td bgcolor="#FFFFFF" align="center" >'.$row['Regists'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'推广统计';
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
     /**
     * 算出扣量后的结果
     * @params array $id 渠道id
     * @params string $dates 日期 2018-06-05 格式
     * @params string $counts 扣量前的数量
     * @return string 扣量后的结果
     */
     public function getkouliang($id,$dates,$counts){
        $tgadmin=M('tg_admin')->find($id);
        //查出 当前这天有没有单独设置扣量比例
        $where=array();
        $where['Tgadmin']=array('eq',$id);
        $where['Status']=array('eq','1');
        $where['IsDel']=array('eq','0');
        $where['from_unixtime(AddTime, "%Y-%m-%d")']=$dates;
        $todayinfos=M('tg_kouliang')->where($where)->find();

        $klrate='0';
        if($todayinfos){
            //当天设置了扣量
            $klrate=$todayinfos['Kouliang']/100;
        }else{
            //按照 渠道管理中设置的来算
            $klrate=$tgadmin['Kouliang']/100;
        }
        $nowcount=$counts-($counts*$klrate);
        return ceil($nowcount);
     }
 }
