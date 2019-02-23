<?php
/**
 * 
 *
 * 作    者:
 * 修改时间: 2017-06-12 13:36
 * 功能说明:
 */
namespace Admin\Controller\Help;

use Think\Controller;
use XBCommon;
use Admin\Controller\System\BaseController;
class MessageController extends  BaseController
{
    const T_TABLE = 'message';
    const T_ADMINISTRATOR='sys_administrator';

    public function index(){

        $this->display();
    }

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

        $Mobile = I('post.Mobile','','trim');
        if($Mobile){$where['m.Mobile'] = array('like','%'.$Mobile.'%');}

        $NickName = I('post.NickName',"","trim");
        if($NickName){$where['m.NickName'] = array('like','%'.$NickName.'%');}
        $Lead = I('post.Lead',"","trim");
        if($Lead){$where['f.Lead'] = array('like','%'.$Lead.'%');}
        $States = I('post.States',-5,"intval");
        if($States<>-5){$where['f.States'] = $States;}

        if(empty($where)){$where = '';}

        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();

        $array['total'] = M(self::T_TABLE.' as f')->field('f.*')->join('LEFT JOIN '.C('DB_PREFIX').'mem_info as m on m.ID=f.Uid ')->where($where)->count();
        $array['rows'] = M(self::T_TABLE.' as f')->field('f.*,m.NickName,m.Mobile')->join('LEFT JOIN '.C('DB_PREFIX').'mem_info as m on m.ID=f.Uid ')->where($where)->order($sort)->limit(($page-1)*$rows,$rows)->group('f.ID')->select();

        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $val['OperatorID'] = $query->GetValue(self::T_ADMINISTRATOR,array('ID'=>(int)$val['OperatorID']),'UserName');
                $val['States'] = $val['States']==1 ? '已处理' : '未处理';

                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }


    /**
     * 处理
     */
    public function handle(){
        $id=I("request.ID",0,"intval");
        $States=M(self::T_TABLE)->where(array('ID'=>$id))->getField('States');
        $this->assign(array(
            "States"=>$States,
            "ID"=>$id,
        ));
        $this->display();
    }

    /**
     * 数据返回
     */
    public function shows(){
        $id = I("request.ID", 0, 'intval');
        if ($id) {
            $model = M(self::T_TABLE);
            $result = $model->find($id);
            if(!$result==null){
                //对隐秘数据进行特殊化处理，防止泄露
                $this->ajaxReturn($result);
            }else{
                //没有查询到内容
                $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
            }
        }
    }


    /**
     *保存数据
     */
    public function savehandle(){
        if(!IS_POST){
            $this->ajaxReturn(0, '数据提交方式不对');
        }
        //数据保存前的验证规则
        $rules = array(
            array('States',array(0,1),'请选择已处理或未处理！',0,'in'), //默认情况下用正则进行验证
        );

        //根据表单提交的POST数据和验证规则条件，创建数据对象
        $model=D(self::T_TABLE);
        $FormData=$model->validate($rules)->create();
        if(!$FormData){
            //验证不通过,提示保存失败的JSON信息
            $this->ajaxReturn(0,$model->getError());
        }else{
            $data=array();  //创建新数组，用于存储保存的数据
            //更新数据判断
            if($FormData['ID']>0){
                $States=M(self::T_TABLE)->where(array('ID'=>$FormData['ID']))->getField('States');
                if($States==1){
                    $this->ajaxReturn(0,"已处理，不允许再次操作");
                }
                if($FormData['States']==1){
                    if(!$FormData['StatesDes']){
                        $this->ajaxReturn(0,"请填写处理描述");
                    }
                }else{
                    $FormData['StatesDes']='';
                }

                //记录操作者信息和更新操作时间
                $data=$FormData;
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['UpdateTime']=date("Y-m-d H:i:s");
                $res=$model->where(array('ID'=>$FormData['ID']))->save($data);
                if($res>0){
                    //查看授信额度 与原来的额度比较，记录变动明细--------start
                    $this->ajaxReturn(1, '操作成功');
                }else{
                    $this->ajaxReturn(0, '操作失败');
                }
            }
        }

    }

    public function Detail(){
        $id=I("request.ID",0,"intval");

        $rec = M(self::T_TABLE.' as f')->field('f.*,m.NickName,m.TrueName,m.Mobile')->join('LEFT JOIN '.C('DB_PREFIX').'mem_info as m on m.ID=f.Uid ')->where('f.ID=%d',$id)->find();

        if($rec){

            if($rec['Pic']){
                $ActImg = '';
                if($rec['Pic']){
                    $ActImg_1 = explode(',',$rec['Pic']);
                    if($ActImg_1){
                        for ($x=0; $x<count($ActImg_1); $x++) {
                            $ActImg[] = $ActImg_1[$x];
                        }
                    }
                }
                $rec['Images'] = $ActImg;
            }
            $rec['OperatorID']=M(self::T_ADMINISTRATOR)->where(array('ID'=>$rec['OperatorID']))->getField('UserName');
        }

        $this->assign("rec",$rec);
        $this->display();
    }

    /**
     * 数据删除处理 单条或多条
     * @access   public
     * @param    string  $id   获取id组成的字符串
     * @return  返回处理结果
     */
    public function del(){
        $mod = M(self::T_TABLE);
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组

        //根据选择的ID值，进行物理删除
        $con['ID']=array('in',$arr);
        $res=$mod->where($con)->delete();  //逻辑删除
        if($res){
            $this->ajaxReturn(1,"删除数据成功！");
        }else{
            $this->ajaxReturn(0,"删除数据时出错！");
        }
    }

}