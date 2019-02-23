<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 用户消息中心
// +----------------------------------------------------------------------
// | Author: 蔡睿
// +----------------------------------------------------------------------
// | Date: 2018/3/16
// +----------------------------------------------------------------------
namespace Admin\Controller\Members;

use Think\Controller;
use XBCommon;
use Admin\Controller\System\BaseController;

class MessageController extends BaseController {

    const T_MESSAGE = 'notice_message';
    const T_DICTIONARY = 'sys_dictionary';
    const T_ADMINISTAROR = 'sys_administrator';
    const T_MEMBER = 'mem_info';


    /**
	 * 消息列表
	 */
	public function index(){
		$this->display();
	}


    /**
     * 显示消息列表数据
     */
    public function DataList(){


        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort','','trim');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort = 'ID desc';
        }

        $where=null;

        $Mobile = I('post.Mobile');
        if($Mobile) { $where['UserID'] = M('mem_info')->where(array('Mobile'=>$Mobile))->getField('ID'); }


        $Contents=I('post.Contents');
        if($Contents){$where['Contents'] = array('like','%'.$Contents.'%');}

        $Type=I('post.Type',-5,'intval');
        if($Type!=-5){$where['Type']=$Type;}

        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_MESSAGE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                if($val['UserID'] == 0){
                    $val['Mobile'] = '所有人';
                }else{
                    $val['Mobile'] = $query->GetValue(self::T_MEMBER,array('ID'=>$val['UserID']),'Mobile');
                }
                if($val['Type']=='0'){
                    $val['Type']='手机消息';
                }elseif($val['Type']=='1'){
                    $val['Type']='通知消息';
                }
                
                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }



    public function edit(){

        $this->display();
    }

	/**
	 * 保存编辑数据 添加数据
	 * @access   public
	 * @param    string  $id   获取id组成的字符串
	 * @return  返回处理结果
	 */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Mobile','require','发送用户手机不可为空！'), 
                array('Contents','require','发送内容可不为空！'), 
            );

            //处理表单接收的数据
            $model = D(self::T_MESSAGE);
            $data = $model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                $Obj = I("request.Obj",1,'intval');
                $type = I('request.Type',0,'intval');
                $Mobile = I('request.Mobile');
                $Contents = I('request.Contents');
                $Title = I('request.Title');
                if($type != -5){
                   //查找获取输入的手机号码用户
                    $user = M(self::T_MEMBER)->where(array('Mobile'=>$Mobile))->find();
                    //如果查找到这个用户
                    if($Obj == 2){
                       if($user['ID']){

                            $data['UserID'] = $user['ID'];
                            $data['SendTime'] = date("Y-m-d H:i:s");
                            $res = M(self::T_MESSAGE)->add($data);

                            if($res) {
                                $send = new XBCommon\SendMsg($type,$user['ID'],0,$Contents,$Title);
                                $send->SendSms();
                                $this->ajaxReturn(1,'发送成功！');
                            }else {
                                $this->ajaxReturn(1,'发送失败！'); 
                            }

                        }else{
                            $this->ajaxReturn(0,'没有此用户,请重新输入');
                        } 
                    }else{

                        $data['UserID'] = 0;
                        $data['SendTime'] = date("Y-m-d H:i:s");
                        $res = M(self::T_MESSAGE)->add($data);

                        if($res){
                            $send = new XBCommon\SendMsg($type,0,0,$Contents,$Title);
                            $send->SendSms();
                            $this->ajaxReturn(1,'发送成功!');
                        }else{
                            $this->ajaxReturn(1,'发送失败');
                        }
                    } 
                } else {
                    $this->ajaxReturn(0,'请选择接受模式');    
                }
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对');
        }
    }

}