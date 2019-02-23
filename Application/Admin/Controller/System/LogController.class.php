<?php
/**
 * 
 *
 * 作    者: 陆恒
 * 修改时间: 2017-04-26 13:36
 * 功能说明:管理员操作日志管理模块
 */
namespace Admin\Controller\System;

use Think\Controller;
use XBCommon;
class LogController extends  BaseController
{

    const T_TABLE = 'sys_log';

    public function index(){

        $this->display();
    }

    public function DataList(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort='ID desc';
        }

        $where=array();

        $UserName=I('post.UserName');
        if($UserName){$where['UserName'] = array('like','%'.$UserName.'%');}

        //查询时间
        $StartTime=I('post.StartTime');  //按时间查询
        $EndTime=I('post.EndTime');
        $ToStartTime=$StartTime;
        $ToEndTime=date('Y-m-d',strtotime($EndTime."+1 day"));
        if($StartTime!=null){
            if($EndTime!=null){
                //有开始时间和结束时间
                $where['DateTime']=array('between',$ToStartTime.','.$ToEndTime);
            }else{
                //只有开始时间
                $where['DateTime']=array('egt',$ToStartTime);
            }
        }else{
            //只有结束时间
            if($EndTime!=null){
                $where['DateTime']=array('elt',$ToEndTime);
            }
        }


        //var_dump($StartTime);exit;
        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                //若有特殊列需要处理，则可以在此编写代码
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }
}