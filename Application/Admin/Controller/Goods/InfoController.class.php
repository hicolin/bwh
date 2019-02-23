<?php
/**
   合肥讯邦网络科技有限公司 [http://www.ahxb.cc]

 * @作者：      王少武
 * @修改日期：  2018-06-14
 * @功能说明：  借款产品控制类
 */
namespace Admin\Controller\Goods;
use Admin\Controller\System\BaseController;
use XBCommon;
class InfoController extends BaseController {

    const T_TABLE           ='goods';                   //商品表
    const T_ADMIN           ='sys_administrator';       //管理员表

	/**
	 * @功能说明：显示商品列表
	 * @return [type]                           [description]
	 */
	public function index(){
		$this->display();
	}

    /**
     * 商品列表数据获取、检测、显示 （js插件处理）
     * @access   public
     * @return   object    返回json数据
     */
    public function DataList(){
        //排序及分页
        $rows=I("post.rows",20,'intval');
        $page=I("post.page",1,'intval');
        $sort=I("post.sort",'','trim');
        $order=I("post.order",'','trim');
        //排序
        if($sort && $order){
            $sort=$sort." ".$order;
        }else{
            $sort='Sort asc,ID desc';
        }
     
        //接受参数
        $IsShelves=I("request.IsShelves",-5,'intval');//是否上架  0：否 1 是
        //组装搜索条件
        if($IsShelves!=-5){
            $where['IsShelves']=$IsShelves;
        }
        $where['IsDel']=array("eq",0);

        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']) {
            foreach ($array['rows'] as $val) {
                $val['OperatorID']=$query->GetValue(self::T_ADMIN,array('ID'=>(int)$val['OperatorID']),'TrueName');
                if($val['CashCoupon']=='1'){
                    $val['CashCoupon']='<span style="color:green;">能用</span>';
                }elseif($val['CashCoupon']=='2'){
                    $val['CashCoupon']='<span style="color:red;">不能用</span>';
                }
                $result['rows'][]= $val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }


    /**
     * 编辑页面数据获取
     * @access   public
     * @param    intval  $id   编辑该条数据的id 用于打开相应页面并传递id值
     * @param    intval  $getid   用于获取该条数据的id
     * @return  返回处理结果
     */
    public function edit(){
        $id=I("request.ID",0,'intval');
        if($id){
            $this->assign("id",$id);
        }
        $this->display();
    }

    /**
     * 编辑数据 (商品)
     * @access   public
     * @param    string  $id   获取id组成的字符串
     * @return  返回处理结果
     */
    public function shows(){
        $mod = M(self::T_TABLE);
        $id=I("request.ID",0,'intval');
        if($id){					//获取该条数据
            $row=$mod->find($id);
            if(!$row){
                $this->error("没有该条数据");
            }
            $this->ajaxReturn($row);
        }
    }

	/**
	 * @功能说明：保存商品
	 * @return [type]                           [description]
	 */
	public function save(){
        $model=M(self::T_TABLE);
        if(IS_POST){
            //根据表单提交的POST数据和验证规则条件，创建数据对象
            $FormData=$model->create();
            if(!$FormData){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //记录操作者信息和更新操作时间
                $FormData['OperatorID']=$_SESSION['AdminInfo']  ['AdminID'];
                $FormData['UpdateTime']=date("Y-m-d H:i:s");
                //更新数据

                if($FormData['ID']>0){
                    if(false !==$model->where(array('ID'=>$FormData['ID']))->save($FormData)){
                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{  //添加
                    if($atrrID=$model->add($FormData)){
                        $this->ajaxReturn(1, '添加成功');
                    }else{
                        $this->ajaxReturn(0, '添加失败');
                    }
                }
            }
        }else{
            $this->ajaxReturn(0, '数据提交方式不对');
        }
	}

    /**
     * @功能说明：显示商品详情
     */
    public function detail(){
        $id=I("request.ID");
        $info=M(self::T_TABLE)
            ->where(array('ID'=>$id))
            ->find();
        $this->assign("info",$info);
        $this->display();
    }

	/**
	 * @功能说明：删除商品
	 * @return [type]                           [description]
	 */
	public function del(){

        //获取删除数据id (单条或数组)
        $id_str=I("post.ID",'','trim');
        $data = array('IsDel'=>1);
        $where['ID']  = array('in',$id_str);
        $res = M(self::T_TABLE)->where($where)->setField($data);
        if($res===false){
            $this->ajaxReturn(0,"删除数据失败！");
        }else{
            $this->ajaxReturn(1,"删除数据成功！");
        }
	}

}