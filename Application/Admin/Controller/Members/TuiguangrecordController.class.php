<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-05-18 16:50
 * 功能说明: 渠道明细统计控制器
 */
 namespace Admin\Controller\Members;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;

 class TuiguangrecordController extends BaseController{

     const T_TABLE='tg_admin';
     const T_MEMINFO='mem_info';
     const T_ADMIN='sys_administrator';
     const T_TGKOULIANG='tg_kouliang';

     public function index(){
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
             $sort='ID desc';
         }

         //搜索条件
         $TgadminID=I('post.TgadminID',-5,'int');

         //判断是不是推广渠道会员
         $tdmemid=M(self::T_TABLE)->where(array('UserName'=>$_SESSION['AdminInfo']['Admin'],'IsDel'=>'0'))->getField('ID');
         $isflag=false;
         if($tdmemid){
            $TgadminID=$tdmemid;
            $isflag=true;
         }
         
         if($TgadminID!=-5){
            $where['TgadminID']=$TgadminID;
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

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_MEMINFO,$where,$page,$rows,$sort,$col);

         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             //扣量--------start
            if($TgadminID!=-5){
                if($isflag){
                    //推广会员登录 
                    // $val['Applynumbs']=$this->getkouliang($TgadminID,$val['Times'],$val['Applynumbs']);
                    // $val['Downs']=$this->getkouliang($TgadminID,$val['Times'],$val['Downs']);
                    // $val['Regists']=$this->getkouliang($TgadminID,$val['Times'],$val['Regists']);
                    $dataArr=array();
                    $newArr=array();
                    foreach($array['rows'] as $k=>$v){
                        $dataArr[date('Y-m-d',strtotime($v['RegTime']))]['datas'][]=$v;
                    }
                    //计算每天的扣量后的数量
                    foreach($dataArr as $k=>$v){
                        $dataArr[$k]['numbs']=$this->getkouliang($TgadminID,$k,count($v['datas']));
                    }
                    //重新组织新数据
                    foreach($dataArr as $k=>$v){
                        $i=$v['numbs'];
                        $j=0;
                        foreach($v['datas'] as $k2=>$v2){
                            if($j<$i){
                                $newArr[]=$v2;
                            }
                            $j++;
                        }
                    }
                    $array['rows']=$newArr;
                }
            }
            //var_dump($newArr);exit;
             //扣量--------end
             foreach ($array['rows'] as $val) {
             	 $val['Typname']='注册';
             	 if($val['TgadminID']){
             	 	$val['TgadminID']=$query->GetValue(self::T_TABLE,array('ID'=>(int)$val['TgadminID'],'IsDel'=>'0'),'Name');
             	 }else{
             	 	$val['TgadminID']='';
             	 }
             	 if($val['Mobile']){
             	 	$val['Mobile']=substr_replace($val['Mobile'],'****',3,4);
             	 }
                 $result['rows'][]=$val;
            }
           $result['total']=$array['total'];
//           $result['total']='';
         }
         $this->ajaxReturn($result);
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
