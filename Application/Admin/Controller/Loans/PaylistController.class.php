<?php
/**
 * 
 *
 * 作    者: 叶程鹏
 * 修改时间: 2018-07-02 09:21
 * 功能说明: 支付记录控制器
 */
 namespace Admin\Controller\Loans;
 
 use Admin\Controller\System\BaseController;
 use XBCommon;
 class PaylistController extends BaseController{

     const T_TABLE='loans_paylist';
     const T_MEMINFO='mem_info';
     const T_ADMIN='sys_administrator';

     public function _initialize(){
        parent::_initialize();
        $this->TradeType = array(1=>"续借",2=>"还款",3=>"放款",4=>"购买商品");
        $this->PayType = array(0=>'未付款',1=>'支付宝',2=>'微信',3=>'银联',4=>'代付');
        $this->PayStatus = array(0=>'待支付',1=>'已支付');
     }

     public function index(){
         $this->assign(array(
            'TradeType'=>$this->TradeType,
            'PayType'=>$this->PayType,
            'PayStatus'=>$this->PayStatus,
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
         $LoanNo=I('post.LoanNo','');
         if($LoanNo){
            $where['LoanNo']=$LoanNo;
         }
         $OrderSn=I('post.OrderSn','');
         if($OrderSn){
            $where['OrderSn']=$OrderSn;
         }
         $TradeType=I('post.TradeType',-5,'int');
         if($TradeType!=-5){
            $where['TradeType']=$TradeType;
         }
         $PayType=I('post.PayType',-5,'int');
         if($PayType!=-5){
            $where['PayType']=$PayType;
         }
         $PayStatus=I('post.PayStatus',-5,'int');
         if($PayStatus!=-5){
            $where['PayStatus']=$PayStatus;
         }
        //审核时间
        $StartTime=I('post.StartTime');  //按时间查询
        $EndTime=I('post.EndTime');
        $ToStartTime=$StartTime;
        $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
        if($StartTime!=null){
            if($EndTime!=null){
                //有开始时间和结束时间
                $where['UpdateTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['UpdateTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($EndTime!=null){
                $where['UpdateTime']=array('elt',$ToEndTime);
            }
        }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

         $TradeTypeArr = $this->TradeType;
         $PayTypeArr = $this->PayType;
         $PayStatusArr = $this->PayStatus;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['TrueName']=$meminfo['TrueName'];
                 //$val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');

                 $val['TradeType']=$TradeTypeArr[$val['TradeType']];
                 $val['PayType']=$PayTypeArr[$val['PayType']];
                 $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                 $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
         }
         $this->ajaxReturn($result);
     }
    //导出功能
    public function exportexcel(){
         $sort=I('post.sort');
         $order=I('post.order');
         if ($sort && $order){
             $sort=$sort.' '.$order;
         }else{
             $sort='ID desc';
         }

         //搜索条件
         $LoanNo=I('post.LoanNo','');
         if($LoanNo){
            $where['LoanNo']=$LoanNo;
         }
         $OrderSn=I('post.OrderSn','');
         if($OrderSn){
            $where['OrderSn']=$OrderSn;
         }
         $TradeType=I('post.TradeType',-5,'int');
         if($TradeType!=-5){
            $where['TradeType']=$TradeType;
         }
         $PayType=I('post.PayType',-5,'int');
         if($PayType!=-5){
            $where['PayType']=$PayType;
         }
         $PayStatus=I('post.PayStatus',-5,'int');
         if($PayStatus!=-5){
            $where['PayStatus']=$PayStatus;
         }
        //审核时间
        $StartTime=I('post.StartTime');  //按时间查询
        $EndTime=I('post.EndTime');
        $ToStartTime=$StartTime;
        $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
        if($StartTime!=null){
            if($EndTime!=null){
                //有开始时间和结束时间
                $where['UpdateTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['UpdateTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($EndTime!=null){
                $where['UpdateTime']=array('elt',$ToEndTime);
            }
        }

         $where['IsDel']=0;
         //查询的数据表字段名
         $col='';//默认全字段查询

         //获取主表的数据
         $query=new XBCommon\XBQuery;
         $array['rows'] =M(self::T_TABLE)->where($where)->order($sort)->select();

         $TradeTypeArr = $this->TradeType;
         $PayTypeArr = $this->PayType;
         $PayStatusArr = $this->PayStatus;
         //重组数据返还给前段
         $result=array();
         if($array['rows']){
             foreach ($array['rows'] as $val) {
                 $meminfo=M(self::T_MEMINFO)->field('TrueName,Mobile')->where(array('ID'=>$val['UserID']))->find();
                 $val['TrueName']=$meminfo['TrueName'];
                 //$val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');

                 $val['TradeType']=$TradeTypeArr[$val['TradeType']];
                 $val['PayType']=$PayTypeArr[$val['PayType']];
                 $val['PayStatus']=$PayStatusArr[$val['PayStatus']];
                 $result['rows'][]=$val;
            }
         }
         
         //导出拼装
        $html = '<table cellpadding="1" cellspacing="1" border="1" width="100%" bgcolor="#000000;">
            <tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" _REQUEST>序号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>订单号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>申请号</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>真实姓名</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付金额</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>类型</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付类型</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>支付状态</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>变更描述</td>
                <td bgcolor="#FFFFFF" align="center" _REQUEST>操作时间</td>
            </tr>';

        foreach($result['rows'] as $key=>$row){
            $html .= '<tr bgcolor="#FFFFFF">
                <td bgcolor="#FFFFFF" align="center" >'.intval($key+1).'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OrderSn'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['LoanNo'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TrueName'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['OrderAmount'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['TradeType'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['PayType'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['PayStatus'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['Description'].'</td>
                <td bgcolor="#FFFFFF" align="center">'.$row['UpdateTime'].'</td>
            </tr>';
        }

        $html .= '</table>';
        $str_filename = date('Y-m-d', time()).'支付记录列表';
        //$str_filename = iconv('UTF-8', 'GB2312//IGNORE',$str_filename);
        $html = iconv('UTF-8', 'GB2312//IGNORE',$html);
        ob_end_clean();//清除缓存区的内容
        header("Content-type: application/vnd.ms-excel; charset=GBK");
        //header('Content-Type:text/html;charset=utf-8');
        header("Content-Disposition: attachment; filename=$str_filename.xls");
        echo $html;
        exit;
    }

 }