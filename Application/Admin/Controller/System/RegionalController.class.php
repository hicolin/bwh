<?php
/**


 * @作者：      傅世敏
 * @修改日期：  2017-04-24
 * @继承版本:   1.1
 * @功能说明：  后台首页控制类
 */
namespace Admin\Controller\System;
use XBCommon\XBQuery;
use XBCommon;

class RegionalController extends BaseController {
    const TABLE='sys_areas';
    const ADMIN_TABLE='sys_administrator';
	/**
	 * @功能说明：显示区域信息页
	 * @return [type]                           [description]
	 */
	public function index(){
		$this->display();
	}

	/**
	 * @功能说明：显示编辑数据
	 * @return [type]                           [description]
	 */
	public function shows(){
		$id=I("request.ID");
		$data=M(self::TABLE)->where("ID=".$id)->find();
        $data['id']=$data['ID'];
        $this->ajaxreturn($data);
	}

	/**
	 * 区域列表数据获取、检测、显示 （js插件处理）
	 * @access   public
	 * @return   object    返回json数据
	 */
    public function DataList(){
        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',10,'intval');
        //排序
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort = 'Sort desc,Code Asc';
        }

        $Name=I('post.Name');
        if($Name){$where['Name'] = array('like','%'.$Name.'%');}

        $Code=I('post.Code');
        if($Code){$where['Code']=array('like','%'.$Code.'%');}

        $Status=I('post.Status',-5,'intval');
        if($Status!=-5){$where['Status']=$Status;}

        //点击父节点传递的ID
        $id=I("post.id");

        $query=new XBQuery();
        if($id){
            //点击父节点时新增子节点,同时携带查询条件[Name,Code,Status等]
            $where['Pid'] = $id;
            $result = M(self::TABLE)->where($where)->order($sort)->select();
            if($result){
                foreach ($result as $k=>$val){
                    $result[$k]['UpdateTime']=date('Y-m-d H:i:s',$val['UpdateTime']);
                    $result[$k]['Status']=$val['Status']==1?'正常':'禁用';
                    $result[$k]['OperaterID']=$query->GetValue(self::ADMIN_TABLE,array('ID'=>$val['OperaterID']),'UserName');
                }
            }
        }else{
            //直接点击菜单或使用条件搜索
            if(!empty($where['Name']) || !empty($where['Code'])){
                //存在搜索条件时，遍历搜索到的结果
                $col='';//获取最原始的数据列表
                $array=$query->GetDataList(self::TABLE,$where,$page,$rows,$sort,$col);
                //查询数据重新处理
                $result=array();
                if($array['rows']){
                    foreach ($array['rows'] as $val){
                        $val['UpdateTime']=date('Y-m-d H:i:s',$val['UpdateTime']);
                        $val['Status']=$val['Status']==1?'正常':'禁用';
                        $val['OperaterID']=$query->GetValue(self::ADMIN_TABLE,array('ID'=>$val['OperaterID']),'UserName');
                        $result['rows'][] = $val;
                    }
                    $result['total']=$array['total'];
                }

            }else{
                //不存在搜索条件时，只显示省份
                $where['Pid']=1;
                $col='';//获取最原始的数据列表
                $array=$query->GetDataList(self::TABLE,$where,$page,$rows,$sort,$col);
                //查询数据重新处理
                $result=array();
                if($array['rows']){
                    foreach ($array['rows'] as $val){
                        $val['UpdateTime']=date('Y-m-d H:i:s',$val['UpdateTime']);
                        $val['Status']=$val['Status']==1?'正常':'禁用';
                        $val['OperaterID']=$query->GetValue(self::ADMIN_TABLE,array('ID'=>$val['OperaterID']),'UserName');
                        $result['rows'][] = $val;
                    }
                    $result['total']=$array['total'];
                }
            }
        }

        //返回信息
        $this->ajaxReturn($result);
    }


    /**
     * @功能说明：编辑区域信息
     * @return [type]                           [description]
     */
	public function edit(){
	    $id=I("request.ID");
        $this->assign('id',$id);
        $this->display();
    }

    /**
     * @功能说明：获取所有区域信息
     * @return [type]                           [description]
     */
	public function getarea()
    {
        $cache=new XBCommon\CacheData();
        $arr1=$cache->Areas();
        //遍历处理源数据库数据
        if ($arr1) {
            foreach ($arr1 as $val) {
                $province['text'] = $val['Name'];
                $province['id'] = $val['ID'];
                $province['state']=$val['state'];
                $arr_city = array();
                foreach ($val['children'] as $city) {
                    $citys['text'] = $city['Name'];
                    $citys['id'] = $city['ID'];
                    $citys['state']=$city['state'];
                    $arr_country = array();
                    foreach ($city['children'] as $country) {
                        $countrys['text'] = $country['Name'];
                        $countrys['id'] = $country['ID'];
                        $countrys['state']=$country['state'];
                        $arr_country[] = $countrys;
                    }
                    $citys['children'] = $arr_country;
                    $arr_city[] = $citys;
                }
                $province['children'] = $arr_city;
                $arr_one[] = $province;
            }
        }
        echo json_encode($arr_one);
    }

    /**
     * @功能说明：保存编辑信息
     * @return [type]                           [description]
     */
    public function Save(){
        if(empty(I("request.Pid")) || empty(I("request.Name"))){
            $this->ajaxReturn(0,"所属区域和区域名称为必填项！");
        }
        $ID=I("post.ID");
        unset($_POST['ID']);
        $_POST['Pid']=I("request.Pid");
        $_POST['OperaterID']=$_SESSION['AdminInfo']['AdminID'];
        $_POST['UpdateTime']=time();
        $mod=D(self::TABLE);
        if(FALSE===$mod->create()){
            $this->ajaxReturn($mod->getError());
        }
        if(empty($ID)){
            $result=$mod->add();
            if($result){
                //更新当前区域缓存信息
                $cache=new XBCommon\CacheData();
                $cache->UpdateArea();
                $this->ajaxReturn(1,"添加成功！");
            }else{
                $this->ajaxReturn(0,"添加失败！");
            }
        }else{
            $result=$mod->where("ID=".$ID)->save();
            if($result){
                //更新当前区域缓存信息
                $cache=new XBCommon\CacheData();
                $cache->UpdateArea();
                $this->ajaxReturn(1,"更新成功！");
            }else{
                $this->ajaxReturn(0,"更新失败！");
            }
        }
    }

    /**
     * 功能说明：区域删除
     * @return   object    返回json数据
     */
    public  function del(){
        $id=I("post.ID");
        $result=M(self::TABLE)->where("Pid=".$id)->find();
        if($result){
            $this->ajaxReturn(0,'父目录不允许删除');
        }else{
            $res=M(self::TABLE)->delete($id);
            if($res) {
                //更新当前区域缓存信息
                $cache=new XBCommon\CacheData();
                $cache->UpdateArea();
                $this->ajaxReturn(1, '删除成功！');
            }else{
                $this->ajaxReturn(0,'删除失败！');
            }
        }
    }

    /**
     * 获取所有的省份
     */
    public function AllPro(){
        $where['Pid']=1;
        $where['Status']=1;
        $Pro=M(self::TABLE)->where($where)->order('Sort desc,Code Asc')->select();
        $result=array();
        foreach ($Pro as $val){
            $data['value']=(int)$val['ID'];
            $data['text']=$val['Name'];
            $result[]=$data;
        }
        $this->ajaxReturn($result);
    }

    /**
     * 获取指定省份的市
     */
    public function AllCity(){
        $ParentID=I('request.ParentID',0);
        $result=array();
        if(!empty((int)$ParentID)){
            $where['Pid']=(int)$ParentID;
            $where['Status']=1;
            $City=M(self::TABLE)->where($where)->order('Sort desc,Code Asc')->select();
            foreach ($City as $val){
                $data['value']=(int)$val['ID'];
                $data['text']=$val['Name'];
                $result[]=$data;
            }
        }
        $this->ajaxReturn($result);
    }

    /**
     * 获取指定的区域/县
     */
    public function AllCount(){
        $ParentID=I('request.ParentID',0);
        $result=array();
        if(!empty((int)$ParentID)){
            $where['Pid']=(int)$ParentID;
            $where['Status']=1;
            $Count=M(self::TABLE)->where($where)->order('Sort desc,Code Asc')->select();
            foreach ($Count as $val){
                $data['value']=(int)$val['ID'];
                $data['text']=$val['Name'];
                $result[]=$data;
            }
        }
        $this->ajaxReturn($result);
    }
}