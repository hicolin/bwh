<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：刁洪强
 * 修改时间：2017-06-15 17:40
 * 功能说明：资金明细
 */
namespace Admin\Controller\Api;
use XBCommon;
use Admin\Controller\System\BaseController;
class ApiController extends BaseController {

    const T_TABLE = 'api';
    const T_mem = 'mem_info';
    const T_Cate = 'api_column';

    public function _initialize()
    {
        parent::_initialize();

        $this->public = array(0=>'',1=>'公有',2=>'私有',9=>'不需要',10=>'可有可无');
        $this->transport = array(0=>'',1=>'明文',2=>'密文');
        $this->return = array(0=>'',1=>'明文',2=>'密文');
        $this->method = array(0=>'',1=>'POST',2=>'GET');
        $this->column = has_children_cate(self::T_Cate,'ParentID=0','sort asc');
    }

	public function index(){

        $this->assign('column',$this->column);

		$this->display();
	}

	public function lists(){

        $this->display();
    }

    public function DataList(){

        $categ =  M(self::T_Cate)->where(array('Status'=>1))->select();

        //接收POST信息,拼接查询条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');
        $sort=I('post.sort');
        $order=I('post.order');
        if($sort && $order){
            $sort = $sort.' '.$order;
        }else{
            $sort = 'ID desc';
        }

        $where = '';

        $Title = I('post.Title','','trim');
        if($Title){$where['Title'] = array('like','%'.$Title.'%');}

        $Url = I('post.Url','','trim');
        if($Url){$where['Url'] = array('like','%'.$Url.'%');}

        $Cid = I('post.Cid',0,'intval');
        if($Cid){
            //$where['Cid'] = $Cid;

            $child = new \Extend\Child($categ);
            $child_id  = $child->getAllCategoryID($Cid);

            $where['Cid'] = array('in',$child_id);
        }

        $GCid = I('get.Cid',0,'intval');
        if($Cid == 0 && $GCid){

            $child = new \Extend\Child($categ);
            $child_id  = $child->getAllCategoryID($GCid);

            $where['Cid'] = array('in',$child_id);
        }

        //查询的列名
        $col='';
        //获取最原始的数据列表
        $query=new XBCommon\XBQuery();
        $array=$query->GetDataList(self::T_TABLE,$where,$page,$rows,$sort,$col);

        //如果查询结果有些数据不需要输出，或者需要特殊处理，则进行重组后输出
        $result=array();
        if($array['rows']){
            foreach ($array['rows'] as $val){
                $fal = '';
                if($val['FormaToken'] == 2){
                    $fal = $this->public[$val['FormaToken']].'Token，';
                }
//                if($val['Submission'] == 1){
//                    $val['Format'] = $this->public[$val['FormaToken']].'Token，'.$this->transport[$val['FormaTransport']].'传输，'.$this->transport[$val['FormatReturn']].'返回';
//                }else{
//                    $val['Format'] = '';
//                }

                $val['Cid'] = $query->GetValue(self::T_Cate,array('ID'=>(int)$val['Cid']),'Name');

                $val['Submission'] = $this->method[$val['Submission']];

                $result['rows'][]=$val;
            }
            $result['total']=$array['total'];
        }
        $this->ajaxReturn($result);
    }


    /**
     * 编辑页面
     */
    public function Edit(){
        $ID = I("get.ID", 0, 'intval');
        $this->assign('ID',$ID);

        $this->assign('column',$this->column);

        if($ID){
            $result = M(self::T_TABLE)->where("ID=".$ID)->field('Submission ')->find();
        }else{
            $result['Submission'] = 1;
        }
        $this->assign('result',$result);

        $this->display();
    }

    public function shows(){
        $id=I("request.ID");
        if(!empty($id)){
            $centerModel=D(self::T_TABLE);
            $center_rec=$centerModel->where("ID=".$id)->find();
            $center_rec['Information']=htmlspecialchars_decode($center_rec['Information']);
            $center_rec['Parameter']=htmlspecialchars_decode($center_rec['Parameter']);
            $center_rec['messages']=htmlspecialchars_decode($center_rec['messages']);
            $center_rec['Error']=htmlspecialchars_decode($center_rec['Error']);
            $this->ajaxReturn($center_rec);
        }else{
            //没有查询到内容
            $this->ajaxReturn(array('result'=>false,'message'=>'不存在的记录！'));
        }

    }

    /**
     * 数据保存
     */
    public function Save(){
        if(IS_POST){
            //数据保存前的验证规则
            $rules = array(
                array('Title','require','功能说明必须填写！'),
                array('Url','require','提交网址必须填写！'),
                array('Information','require','提交参数必须填写！'),
            );

            //处理表单接收的数据
            $model=D(self::T_TABLE);
            $data=$model->validate($rules)->create();
            if(!$data){
                //验证不通过,提示保存失败的JSON信息
                $this->ajaxReturn(0,$model->getError());
            }else{
                //验证通过，执行后续保存动作
                $data['OperatorID']=$_SESSION['AdminInfo']['AdminID'];
                $data['Addtime']=date("Y-m-d H:i:s");

                if($data['Submission'] == 2){
                    $data['FormaToken'] = 0;
                    $data['FormaTransport'] = 1;
                    $data['FormatReturn'] = 1;
                }

                //保存或更新数据
                if($data['ID']>0){
                    $result=$model->where('ID='.$data['ID'])->save($data);
                    if($result>0){

                        $this->ajaxReturn(1, '修改成功');
                    }else{
                        $this->ajaxReturn(0, '修改失败');
                    }
                }else{
                    $result=$model->add($data);
                    if($result>0){
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
     * 查看详情
     */
    public function Detail(){
        $ID=I('get.ID');
        if(!empty($ID)){
            $Info = M(self::T_TABLE)->where(array('ID'=>$ID))->find();
            $Info['Format'] = $this->public[$Info['FormaToken']].'Token，'.$this->transport[$Info['FormaTransport']].'传输，'.$this->transport[$Info['FormatReturn']].'返回';

            $Info['Submission'] = $this->method[$Info['Submission']];

            $Info['Information']=htmlspecialchars_decode($Info['Information']);
            $Info['Parameter']=htmlspecialchars_decode($Info['Parameter']);
            $Info['messages']=htmlspecialchars_decode($Info['messages']);
            $Info['Error']=htmlspecialchars_decode($Info['Error']);

            $this->assign('Info',$Info);
        }
        $this->display();
    }

    /**
     * 数据删除处理 单条或多条
     */
    public function Del()
    {
        $mod = D(self::T_TABLE);
        //获取删除数据id (单条或数组)
        $ids = I("post.ID", '', 'trim');
        $arr=explode(',',$ids);  //转化为一维数组

        //根据选择的ID值，进行物理删除
        $con['ID']=array('in',$arr);
        $res=$mod->where($con)->delete();  //逻辑删除

        if ($res) {
            $this->ajaxReturn(true, "删除数据成功！");
        } else {
            $this->ajaxReturn(false, "删除数据时出错！");
        }
    }


    //侧边栏列表
    public function DataTree(){
        //实例化模型
        $arr = catemenu1(self::T_Cate,'ParentID=0');
        echo json_encode($arr);
    }

    public function examination(){
        $ID = I('get.ID',0,'intval');
        $Info = M(self::T_TABLE)->where(array('ID'=>$ID))->find();

        if(!$Info){
            $this->redirect('index');
        }
        $Info['Information']=htmlspecialchars_decode($Info['Information']);
        $Info['Parameter']=htmlspecialchars_decode($Info['Parameter']);
        $Info['messages']=htmlspecialchars_decode($Info['messages']);
        $Info['Error']=htmlspecialchars_decode($Info['Error']);

        $this->assign('Info',$Info);

        if($Info['Submission'] == 1){  //post
            $format = json_decode($Info['Information'],true);
            if($format['data']){  //加密并有加密参数
                //print_r($format['dynamic']);
                $format = array_merge($format,$format['data']);
                unset($format['data']);
            }
        }elseif($Info['Submission'] == 2){ //get
            parse_str($Info['Information'],$format);
        }

        $client = M('version')->where('Status = 1')->field('Client,Package,Ver')->find();
        if($client){
            $format['client'] = $client['Client'];
            $format['version'] = $client['Ver'];
            $format['package'] = $client['Package'];
        }

        $timestamp = M('sys_timestamp')->find();
        if(@$format['ticksid']){
            $format['ticksid'] = $timestamp['ID'];
            $format['ticks'] = $timestamp['Val'];
        }

        $this->assign('format',$format);

        $this->display();
    }


    public function check(){
        if(!IS_POST){
            exit(json_encode('数据提交方式不对',JSON_UNESCAPED_UNICODE));
        }

        $Title = I('post.Title','','trim');
        $Url = I('post.Url','','trim');
        $Submission = I('post.Submission',1,'intval');
        //$FormaToken = I('post.FormaToken',1,'intval');
        //$FormaTransport = I('post.FormaTransport',1,'intval');
        //$FormatReturn = I('post.FormatReturn',1,'intval');

        $format = I('post.format');
        $formatVal = I('post.formatVal');

        //$json_data = json_encode(array("username"=>"17602186118","pwd"=>"123456","ticksid"=>137,"ticks"=>"24c4c41a9e1e7738bf0cd5f543769f"));


        if(!$Title || !$Url ){
            exit(json_encode('参数不对，不能为空',JSON_UNESCAPED_UNICODE));
        }

        $array = array();
        for($i=0;$i<count($format);$i++){
            $array[$format[$i]] = $formatVal[$i];
        }

        if($Submission == 1){

            $uname = I('post.uname','','trim');
            $upass = I('post.upass','','trim');

            $maps['Token'] = $array['token'];
            $maps['IsDel'] = 0;
            $maps['Status'] = array('neq','3');
            $PublicKeyIv = M(self::T_mem)->where($maps)->field('Token,KEY,IV')->find();

            if($uname && $upass){
                $where=array(
                    'Mobile'=>$uname,
                    'Password'=>md5($upass),
                    'IsDel' => 0,
                );
                $PublicKeyIv = M(self::T_mem)->where($where)->field('Token,KEY,IV')->find();
            }

            //密文传输
            if($array['isaes'] == 1){
                $process['data'] = $array;
                unset($process['data']['token']);
                unset($process['data']['client']);
                unset($process['data']['version']);
                unset($process['data']['package']);
                unset($process['data']['isaes']);

                //if($FormaToken == 1){ //公有
                //    $PublicKeyIv = PublicKeyIv();
                //    $encrypt_p = encrypt_pkcs7(json_encode($process['dynamic'],JSON_UNESCAPED_UNICODE),$PublicKeyIv['key'],$PublicKeyIv['iv']);
                //    $encrypt['dynamic'] = $encrypt_p;
                //}elseif($FormaToken == 2){  //私有
                    $encrypt_p = encrypt_pkcs7(json_encode($process['data'],JSON_UNESCAPED_UNICODE),$PublicKeyIv['KEY'],$PublicKeyIv['IV']);
                    $encrypt['data'] = $encrypt_p;
                //}else{
                //    exit(json_encode('参数不对，不能为空',JSON_UNESCAPED_UNICODE));
                //}

                $array = array_merge($array,$encrypt);
            }

            if($PublicKeyIv){
                unset($array['token']);
                $array['token'] = $PublicKeyIv['Token'];
            }

            $json = json_encode($array);

            echo 'json数据：'.$json;
            echo '<br/><br/>';

        }else{
            $url = http_build_query($array);
            $Url = $Url.'?'.$url;

            $json = array();

            echo 'Url：'.'http://'.$_SERVER['HTTP_HOST'].'/api.php/'.$Url;
            echo '<br/><br/>';
        }

        $result = $result1 = https_request('http://'.$_SERVER['HTTP_HOST'].'/api.php/'.$Url,$json);
        if($result){

            if($result1['isaes'] == 1){
                $result1['data'] = decrypt_pkcs7($result1['data'],$PublicKeyIv['KEY'],$PublicKeyIv['IV']);

                echo 'json返回明文数据：'. json_encode($result1,JSON_UNESCAPED_UNICODE);
                echo '<br/><br/>';
            }

            exit(json_encode($result,JSON_UNESCAPED_UNICODE));
        }else{
            exit(json_encode('获取不到相关返回值',JSON_UNESCAPED_UNICODE));
        }

    }


}