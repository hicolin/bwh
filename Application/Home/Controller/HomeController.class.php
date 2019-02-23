<?php
/**
 *
 */

namespace Home\Controller;
use Think\Controller;
use XBCommon\CacheData;
use XBCommon\XBCache;

class HomeController extends Controller
{
    /* 空操作，用于跳转到首页 */
    public function _empty(){
    	$this->error("目前访问的地址有误,现返回到首页!",'/');
    }
    public function _initialize(){
    	//定义全局的配置缓存变量
       // 调用方法  tem  {$GLOBALS['BasicInfo']['SystemName']}
       global $BasicInfo;

       $cache=new XBCache();
       $BasicInfo = $cache->GetCache('BasicInfo');
        // 指定允许其他域名访问
        header('Access-Control-Allow-Origin:*');
        // 响应类型
        header('Access-Control-Allow-Methods:*');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');



//      header('Content-Type', 'application/json');
//        header("Access-Control-Allow-Headers", "Content-Type,Access-Token");
       if(!$BasicInfo)
       {
           $cache=new CacheData();
           $BasicInfo = $cache->BasicInfo();
       }

       if(!session('uid')){
            $this->checkCookieLogin();
       }
    }

    /**
     * 检测Cookie登录
     */
    public function checkCookieLogin()
    {
        $mobile = $_COOKIE['mobile'];
        $flag = $_COOKIE['flag'];
        if($mobile && $flag){
            $memInfo = M('mem_info')->where(['Mobile'=>$mobile])->find();
            $str = md5($memInfo['Mobile'].$memInfo['Password']);
            if($flag == $str){
               session('uid',$memInfo['ID']);
            }
        }
    }

    /**
      * AJAX返回数据标准
      * @param int $status  状态
      * @param string $msg  内容
      * @param mixed $data  数据
      * @param string $dialog  弹出方式
      */
     protected function ajaxReturn($status = 0, $msg = '成功', $data = '', $dialog = '')
     {
         $return_arr = array();
         if (is_array($status)) {
             $return_arr = $status;
         } else {
             $return_arr = array(
                 'result' => $status,
                 'message' => $msg,
                 'data' => $data,
                 'dialog' => $dialog
             );
         }
         ob_clean();
         echo json_encode($return_arr);
         exit;
     }

    /**
     * 返回json 数据
     * @param $status
     * @param $msg
     * @param string $data
     */
    function json($status, $msg, $data = '')
    {
        $data = compact('status', 'msg', 'data');
        echo json_encode($data);
        exit;
    }

     //收藏商品
     public function Collent(){
        $uid = session('logininfo')['UserID'];
        if(!$uid){
            $this->ajaxReturn(80, '当前还没登录，请重新登录', "/Login/index");
        }
        $gid = I('get.id',0,'intval');
        $find = M('goods')->where('ID='.$gid)->find();
        if(!$find){
            $this->ajaxReturn(0,"未找到商品信息");
        }
        $cind = M('mem_collect')->where('Gid='.$gid.' and Uid='.$uid)->find();
        if($cind){
            $this->ajaxReturn(0,"已经收藏过，不能重复收藏");
        }
        $data['Uid'] = $uid;
        $data['Gid'] = $gid;
        $data['Addtime'] = date('Y-m-d H:i:s');

        $result = M('mem_collect')->add($data);

        if($result){
            M('goods')->where(array('ID'=>$gid))->setInc('Collent',1);
            $this->ajaxReturn(1,'收藏成功');
        }else{
            $this->ajaxReturn(0,"收藏失败!");
        }
    }
    public function area(){
        $id = I('get.id',0,'intval');
        $province = M('sys_areas')->where(array('Pid'=>$id))->select();
        $this->ajaxReturn($province);
    }
    //收货地址的添加and修改页面
    public function address_add(){
        $uid = session('logininfo')['UserID'];
        $result = array();
        $where['Uid'] = $uid;
        $count = M('mem_address')->where($where)->count();
        $this->assign("count",$count);

        $province = M('sys_areas')->where(array('Pid'=>1))->select();
        $this->assign("province",$province);

        $id = I('get.id',0,'intval');
        if($id){
            $result = M('mem_address')->where('Uid='.$uid.' and ID='.$id)->find();


            $city = M('sys_areas')->where(array('Pid'=>$result['province']))->select();

            $county = M('sys_areas')->where(array('Pid'=>$result['city']))->select();

            $this->assign("result",$result);
            $this->assign("city",$city);
            $this->assign("county",$county);

            $this->display('address_edit');
        }else{
            $this->assign("result",$result);
            $this->display();
        }
    }

    /**
     * 获取ip名称
     * @param $cip
     * @return string
     */
    public function getLoginIp()
    {
        $cip = get_client_ip();
        $url = 'http://restapi.amap.com/v3/ip';
        $data = array(
            'output' => 'json',
            'key' => '16199cf2aca1fb54d0db495a3140b8cb',
            'ip' => $cip
        );

        $postdata = http_build_query($data);
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        $res = json_decode($result, true);
        if (count($res['province']) == 0) {
            $res['province'] = '北京市';
        }
        if (! empty($res['province']) && $res['province'] == "局域网") {
            $res['province'] = '北京市';
        }
        if (count($res['city']) == 0) {
            $res['city'] = '北京市';
        }
        return $res['city'];
    }

    public function udun($idcard)
    {
        $pukey = '69911ddc-7249-43f5-9e94-c5fb9517222c';
        $product_code = 'Y1001005';
        $out_order_id = getOrderSn();
        $secretkey = '8f04d60e-a935-4469-982c-e3fb31e49b94';
        $data['id_no'] = $idcard;
        $id_no = json_encode($data);
        $sign = $id_no.'|'.$secretkey;
        $signature = md5($sign);
        $url = "https://api4.udcredit.com/dsp-front/4.1/dsp-front/default/pubkey/{$pukey}/product_code/{$product_code}/out_order_id/{$out_order_id}/signature/{$signature}";
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json;charset=UTF-8;',
                'content' => $id_no
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result,true);
        return $result;
    }
}