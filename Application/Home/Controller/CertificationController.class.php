<?php
/**
 * 功能说明: 首页部分
 */

namespace Home\Controller;

use XBCommon\XBUpload;

class CertificationController extends HomeController
{
    public $Appid = '2010343';
    public $uid;//用户 session中的id
    public $member;
    public $token;
    public $returnUrl = 'http://bwh.feiyuandai.com';

    public function __construct()
    {
        parent::__construct();
        $this->token = I('post.token');
        if(!$this->token){
            $this->ajaxReturn(0,'请先登录');
        }
        $this->member = M('mem_info')->where(array('Token'=>$this->token,'IsDel'=>'0'))->find();
        $this->uid = $this->member['ID'];
        if(!$this->member){
            $this->ajaxReturn(0,'token失效，请重新登录');
        }
        $expire = $this->member['Expire'];
        if(time() > $expire){
            $this->ajaxReturn(0,'token已过期，请重新登录');
        }
    }

    /**
     * 认证首页
     */
    public function index()
    {
        $userId = $this->uid;
        $rzMust = M('renzen_parameter')->where(['IsShow' => 1, 'IsMust' => 1])->order('Sort asc')->select();
        $rzNotMust = M('renzen_parameter')->where(['IsShow' => 1, 'IsMust' => 0])->order('Sort asc')->select();
        $rzCard = M('renzen_cards')->where(['UserID'=>$userId,'IsDel' => 0])->find();
        $rzBank = M('renzen_bank')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzSocial = M('renzen_social')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzMobile = M('renzen_mobile')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzTaobao = M('renzen_taobao')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzAlipay = M('renzen_alipay')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzBlacklist = M('renzen_blacklist')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzZhiMa = M('renzen_zhima')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        $rzMust = $this->addRzStatus($rzMust, $userId);
        $rzNotMust = $this->addRzStatus($rzNotMust, $userId);
        if ($rzMobile['SearchID'] && !$rzBlacklist) {  // 黑名单和小额贷款同时认证的，判断一个即可
            $this->blacklist();
            $this->pdscorev4();
        }
        $data = compact('rzMust','rzNotMust','rzCard','rzBank','rzSocial','rzMobile','rzTaobao','rzAlipay','rzZhiMa');
        $this->ajaxReturn('200','请求成功',$data);
    }

    /**
     * 为用户添加认证状态
     * @param $data
     * @param $userId
     * @return mixed
     */
    function addRzStatus($data, $userId)
    {
        foreach ($data as &$list) {
            $model = M('renzen_' . $list['Flag'])->where(['UserID' => $userId,'IsDel' => 0])->find();
            if ($model['Status'] == 1) {
                $list['Status'] = '已认证';
            } else {
                $list['Status'] = '未认证';
            }
        }
        return $data;
    }

    /**
     * 身份证认证
     */
    public function idcer()
    {
        $userId = $this->uid;
        $rzCard = M('renzen_cards')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        if($rzCard['Status'] == 1){
            $this->ajaxReturn(100, '您已通过认证');
        }
        $idcer = I('post.result');
        file_put_contents('1229.txt',$idcer);
        $idcer = htmlspecialchars_decode($idcer);
        file_put_contents('12330.txt',$idcer.'-'.getSystem().PHP_EOL,FILE_APPEND);
        $post = json_decode($idcer, true);
        if ($post['ret_code'] == '900001') {
            $this->ajaxReturn(100, '请重新认证哦', $post);
        }
        if ($post['result_auth'] != 'T') {
            $this->ajaxReturn(100, '认证失败，人脸比对相似度过低', $post);
        }
        $data['UserID'] = $userId;
        $data['Cardschi'] = $post['url_photoliving'];
        $data['CardFace'] = $post['url_frontcard'];
        $data['CardSide'] = $post['url_backcard'];
        $data['Status'] = 1;
        $data['UpdateTime'] = date('Y-m-d H:i:s');
        $RenzResult = serialize($post);
        $data['RenzResult'] = $RenzResult;
        $result = $this->udun($post['id_no']);
        if ($result['header']['ret_code'] == '000000') {
            $Yddatas = serialize($result['body']);
            $data['Yddatas'] = $Yddatas;
        } else {
            $this->ajaxReturn(100, $result['header']['ret_msg']);
        }

        if ($rzCard) {
            $res = M('renzen_cards')->where(['UserID' => $userId, 'IsDel' => 0])->save($data);
            if (!$res) {
                $this->ajaxReturn(100, '保存失败', $post);
            }
        } else {
            $res = M('renzen_cards')->add($data);
            if (!$res) {
                $this->ajaxReturn(100, '保存失败');
            }
        }
        $mem['TrueName'] = $post['id_name'];
        $mem['IDCard'] = $post['id_no'];
        M('mem_info')->where(['ID' => $userId,'IsDel' => 0])->save($mem);
        $this->ajaxReturn(200, '认证成功', $post);
    }

    /**
     * 身份证图片上传
     */
    public function uploadIdCard()
    {
        if (IS_POST) {
            $xbUpload = new XBUpload();
            $res = $xbUpload->uploadimage();
            if ($res['result'] != 'success') {
                $this->ajaxReturn(100, '上传失败');
            }
            $this->ajaxReturn(200, '上传成功', $res['path']);
        }
    }

    /**
     * 基本信息认证
     */
    public function basecer()
    {
        $education = ['1' => '博士', '2' => '硕士', '3' => '本科', '4' => '专科', '5' => '高中', '6' => '中学及中学以下'];
        if (IS_AJAX) {
            $post = I('post.');
            $res = M('renzen_memberinfo')->where(['UserID' => $this->uid,'IsDel' => 0])->find();
            $post['Status'] = 1;
            $post['UserID'] = $this->uid;
            $post['Education'];
            if ($res) {
                $r = M('renzen_memberinfo')->where(['UserID' => $this->uid,'IsDel' => 0])->save($post);
                if ($r) {
                    $this->ajaxReturn('1', '修改成功');
                } else {
                    $this->ajaxReturn('0', '修改失败');
                }
            } else {
                $r = M('renzen_memberinfo')->add($post);
                if ($r) {
                    $this->ajaxReturn('1', '认证成功');
                } else {
                    $this->ajaxReturn('0', '认证失败');
                }
            }
        }
        $basecer = M('renzen_memberinfo')->where(['UserID' => $this->uid,'IsDel' => 0])->find();
        $this->assign([
            'education' => $education,
            'basecer' => $basecer,
        ]);
        $this->display();
    }

    /**
     * 银行卡认证
     */
    public function bankcer()
    {
        $member = $this->member;
        $bank = M('renzen_bank')->where(array('UserID' => $member['ID']))->find();
        $data = array(
            'userName'=>$member['TrueName'],
            'IDCard'=>$member['IDCard'],
            'Mobile'=>$member['Mobile'],
            'bankCode'=>$bank['bankNo']
        );
        $this->ajaxReturn('200', '获取成功',$data);
        if(IS_AJAX){
            $status = I('post.status','',trim);
            $isbank_yes = M('renzen_bank')->where(array('UserID' => $this->uid,'Status'=>$status,'IsDel'=>0))->find();
            if(!$isbank_yes){
                $this->ajaxReturn('100', '未认证！');
            }else{
                $this->ajaxReturn('200', '您已通过认证！');
            }
        }
    }

    /**
     * 社交认证
     */
    public function socialcer()
    {
        $post = I('post.');
        $data['QQ'] = $post['QQ'];
        $data['WeChat'] = $post['WeChat'];
        $content = [
            'name' => $post['name'],
            'guanxi' => $post['guanxi'],
            'tel' => $post['tel']
        ];
        $s = serialize($content);
        $data['Contents'] = $s;
        $data['Status'] = 1;
        $data['UserID'] = $this->uid;
        $data['RenzTime'] = date('Y-m-d H:i:s');
        $data['UpdateTime'] = date('Y-m-d H:i:s');
        $res = M('renzen_social')->where(['UserID' => $this->uid,'IsDel'=>0])->find();
        if($res['Phonelist']==null){
            $this->ajaxReturn(100, '需要打开通讯录权限才能认证成功！');
        }
        if ($res) {
            $r = M('renzen_social')->where(['UserID' => $this->uid,'IsDel'=>0])->save($data);
            if ($r) {
                $this->ajaxReturn(200, '修改成功');
            } else {
                $this->ajaxReturn(100, '修改失败');
            }
        } else {
            $r = M('renzen_social')->add($data);
            if ($r) {
                $this->ajaxReturn(200, '认证成功');
            } else {
                $this->ajaxReturn(100, '认证失败');
            }
        }
    }

    public function phonelist()
    {
        $arr = $_REQUEST['s'];
        $a = json_decode($arr,true);
        $tel = [];
        foreach ($a as $k => $list) {
            $tel[$k]['name'] = $list['name'];
            $tel[$k]['tel'] = $list['phoneNumber'];
            $tel[$k]['updatetime'] = date('Y-m-d H:i:s');
        }
        $s = serialize($tel);
        $data['Phonelist'] = $s;
        $data['UserID'] = $this->uid;
        $res = M('renzen_social')->where(['UserID' => $this->uid,'IsDel'=>0])->find();
        if ($res) {
            $r = M('renzen_social')->where(['UserID' => $this->uid,'IsDel'=>0])->save($data);
            echo 2;
            exit;
        } else {
            $r = M('renzen_social')->add($data);
            if ($r) {
                $this->ajaxReturn('1', '认证成功');
            } else {
                $this->ajaxReturn('0', '认证失败');
            }
        }

    }

    public function mobile(){
        if($_POST){
            $status = I('post.status','',trim);
            $ismobile_yes = M('renzen_mobile')->where(array('UserID' => $this->uid,'Status'=>$status,'IsDel'=>0))->find();
//            var_dump($ismobile_yes);exit;
            if(!$ismobile_yes){
                $this->ajaxReturn('100', '未认证！');
            }else{
                $this->ajaxReturn('200', '您已通过认证！');
            }
        }
    }

    public function taobao(){
        if($_POST){
            $status = I('post.status','',trim);
            $istaobao_yes = M('renzen_taobao')->where(array('UserID' => $this->uid,'Status'=>$status,'IsDel'=>0))->find();
            if(!$istaobao_yes){
                $this->ajaxReturn('100', '未认证！');
            }else{
                $this->ajaxReturn('200', '您已通过认证！');
            }
        }
    }

    public function alipay(){
        if($_POST){
            $status = I('post.status','',trim);
            $isalipay_yes = M('renzen_alipay')->where(array('UserID' => $this->uid,'Status'=>$status,'IsDel'=>0))->find();
            if(!$isalipay_yes){
                $this->ajaxReturn('100', '未认证！');
            }else{
                $this->ajaxReturn('200', '您已通过认证！');
            }
        }
    }
    public function renzen(){
        if(IS_POST){
            $table = I('post.table','',trim);
            $status = I('post.status','',trim);
            $model = M('renzen_'.$table)->where(['UserID' => $this->uid,'Status'=>$status,'IsDel'=>0])->find();
            if(!$model){
                $this->ajaxReturn('100', '未认证！');
            }else{
                $this->ajaxReturn('200', '您已通过认证！');
            }
        }
    }

    /**
     * 运营商认证
     */
    public function mobilerz()
    {
        vendor('Tianji.OpenapiDevBase');
        $sample = new \OpenapiDevBase();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $bank = M('renzen_bank')->where(array('UserID' => $this->uid, 'IsDel' => '0'))->find();
        if (!$bank) {
            $this->ajaxReturn('100', '请先进行实名认证');
        }
        $member = M('mem_info')->where(array('ID' => $this->uid, 'IsDel' => '0'))->find();
        $bizData['type'] = "mobile";
        $bizData['platform'] = "web";
        $bizData['userId'] = $this->uid;
        $bizData['outUniqueId'] = getOrderSn();
        $bizData['name'] = $member['TrueName'];
        $bizData['idNumber'] = $member['IDCard'];
//        $bizData['phone'] = $bank['YMobile'];
        $bizData['phone'] = $member['Mobile'];
        $bizData['notifyUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/tianji/index";
//        $bizData['returnUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/index/tianjiReturn";
        $bizData['returnUrl'] = $this->returnUrl."/#/pages/certificate";
        $bizData['version'] = "2.0";
        $method = 'tianji.api.tianjireport.collectuser';
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret = $sample->sendRequest($bizData, $method, $AppID, $urls, $orgPrivateKey);
        if ($ret['error'] == 200) {
            $data['UserID'] = $this->uid;
            $data['TaskID'] = $ret['request_id'];
            $data['TUserID'] = $ret['tianji_api_tianjireport_collectuser_response']['outUniqueId'];
          	$data['Status'] = 1;
            $re = M('renzen_mobile')->where(['UserID' => $data['UserID'],'IsDel' => 0])->find();
            if ($re) {
                M('renzen_mobile')->where(['UserID' => $data['UserID'],'IsDel' => 0])->save($data);
            } else {
                M('renzen_mobile')->add($data);
            }
            $this->ajaxReturn('200', $ret['tianji_api_tianjireport_collectuser_response']['redirectUrl']);
        } else {
            $this->ajaxReturn('100', $ret['msg']);
        }
    }

    /**
     * 机构R黑名单
     */
    public function blacklist()
    {
        $userId = $this->uid;
        $member = M('mem_info')->where(array('ID' => $userId, 'IsDel' => '0'))->find();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $bizData['name'] = $member['TrueName'];
        $bizData['idNumber'] = $member['IDCard'];
        $bizData['phone'] = $member['Mobile'];
        $detailmethod = 'tianji.api.agentr.blacklist';//method  参数
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        vendor('Tianji.OpenapiDevBase');
        $openapiDevBase = new \OpenapiDevBase();
        $res = $openapiDevBase->sendRequest($bizData, $detailmethod, $AppID, $urls, $orgPrivateKey);
        if ($res['error'] == 200) {
            $data['UserID'] = $userId;
            $data['RequestID'] = $res['request_id'];
            $data['Response'] = json_encode($res['tianji_api_agentr_blacklist_response']);
            $data['Status'] = 1;
            $data['UpdateTime'] = date('Y-m-d H:i:s');
            $model = M('renzen_blacklist');
            $blacklist = $model->where(['UserID' => $userId, 'IsDel' => 0])->find();
            if ($blacklist) {
                $model->where(['UserID' => $userId, 'IsDel' => 0])->save($data);
            } else {
                $model->add($data);
            }
        }
    }

    /**
     * 小额贷款模型
     */
    public function pdscorev4()
    {
        $userId = $this->uid;
        $rzMobile = M('renzen_mobile')->where(['UserID' => $userId])->find();
        $member = M('mem_info')->where(array('ID' => $userId, 'IsDel' => '0'))->find();
        vendor('Tianji.OpenapiDevBase');
        $sample = new \OpenapiDevBase();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $bizData['name'] = $member['TrueName'];
        $bizData['idNumber'] = $member['IDCard'];
        $bizData['phone'] = $member['Mobile'];
        $bizData['userId'] = $rzMobile['SearchID']; // 在融360平台已经认证过运营商的用户标识
        $method = 'tianji.api.tianjiscore.pdscorev4';
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $res = $sample->sendRequest($bizData, $method, $AppID, $urls, $orgPrivateKey);
        if ($res['error'] == 200 || $res['error'] == 10056) {  // 200:msg为空，response不为空；10065：msg为未命中，response为空
            $data['UserID'] = $userId;
            $data['RequestID'] = $res['request_id'];
            $data['Response'] = json_encode($res['tianji_api_tianjiscore_pdscorev4_response']);
            $data['Status'] = 1;
            $data['UpdateTime'] = date('Y-m-d H:i:s');
            $model = M('renzen_pdscore');
            $pdscore = $model->where(['UserID' => $userId, 'IsDel' => 0])->find();
            if ($pdscore) {
                $model->where(['UserID' => $userId, 'IsDel' => 0])->save($data);
            } else {
                $model->add($data);
            }
        }
    }


    /**
     * 淘宝认证
     */
    public function taobaorz()
    {
        vendor('Tianji.OpenapiDevBase');
        $sample = new \OpenapiDevBase();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $bank = M('renzen_bank')->where(array('UserID' => $this->uid,'IsDel' => '0'))->find();
        if (!$bank) {
            $this->ajaxReturn(100, '请先进行实名认证');
        }
        $member = M('mem_info')->where(array('ID' => $this->uid, 'IsDel' => '0'))->find();
        $bizData['type'] = "taobao";
        $bizData['platform'] = "web";
        $bizData['userId'] = $this->uid;
        $bizData['outUniqueId'] = getOrderSn();
        $bizData['name'] = $member['TrueName'];
        $bizData['idNumber'] = $member['IDCard'];
        $bizData['phone'] = $bank['YMobile'];
        $bizData['notifyUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/tianji/taobaoinfo";
//        $bizData['returnUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/certification/index";
        $bizData['returnUrl'] = $this->returnUrl."/#/pages/certificate";
        $bizData['version'] = "2.0";
        $method = 'tianji.api.tianjireport.collectuser';
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret = $sample->sendRequest($bizData, $method, $AppID, $urls, $orgPrivateKey);
        $res = json_encode($ret);
        file_put_contents('1.txt',$res);
        if ($ret['error'] != 200) {
            $this->ajaxReturn(100, $ret['msg']);
        }
        $data['UserID'] = $this->uid;
        $data['TaskID'] = $ret['request_id'];
        $data['TUserID'] = $ret['tianji_api_tianjireport_collectuser_response']['outUniqueId'];
      	$data['Status'] = 1;
        $re = M('renzen_taobao')->where(['UserID' => $data['UserID'],'IsDel' => 0])->find();
        if ($re) {
            M('renzen_taobao')->where(['UserID' => $data['UserID'],'IsDel' => 0])->save($data);
        } else {
            M('renzen_taobao')->add($data);
        }
        $this->ajaxReturn(200, $ret['tianji_api_tianjireport_collectuser_response']['redirectUrl']);
    }

    /**
     * 支付宝认证
     */
    public function alipayrz()
    {
        vendor('Tianji.OpenapiDevBase');
        $sample = new \OpenapiDevBase();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $bank = M('renzen_bank')->where(array('UserID' => $this->uid))->find();
        if (!$bank) {
            $this->ajaxReturn(100, '请先进行实名认证');
        }
        $member = M('mem_info')->where(array('ID' => $this->uid, 'IsDel' => '0'))->find();
        $bizData['type'] = "alipay";
        $bizData['platform'] = "web";
        $bizData['userId'] = $this->uid;
        $bizData['outUniqueId'] = getOrderSn();
        $bizData['name'] = $member['TrueName'];
        $bizData['idNumber'] = $member['IDCard'];
        $bizData['phone'] = $bank['YMobile'];
        $bizData['notifyUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/tianji/alipayinfo";
//        $bizData['returnUrl'] = "http://" . $_SERVER['HTTP_HOST'] . "/certification/index";
        $bizData['returnUrl'] = $this->returnUrl."/#/pages/certificate";
        $bizData['version'] = "2.0";
        $method = 'tianji.api.tianjireport.collectuser';
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret = $sample->sendRequest($bizData, $method, $AppID, $urls, $orgPrivateKey);
        if ($ret['error'] != 200) {
            $this->ajaxReturn(100, $ret['msg']);
        }
        $data['UserID'] = $this->uid;
        $data['TaskID'] = $ret['request_id'];
        $data['TUserID'] = $ret['tianji_api_tianjireport_collectuser_response']['outUniqueId'];
      	$data['Status'] = 1;
        $re = M('renzen_alipay')->where(['UserID' => $data['UserID'],'IsDel' => 0])->find();
        if ($re) {
            M('renzen_alipay')->where(['UserID' => $data['UserID'],'IsDel' => 0])->save($data);
        } else {
            M('renzen_alipay')->add($data);
        }
        $this->ajaxReturn(200, $ret['tianji_api_tianjireport_collectuser_response']['redirectUrl']);
    }

    /**
     * 芝麻信用认证
     */
    public function zhimarz()
    {
        $userId = $this->uid;
        $rzTaobao = M('renzen_taobao')->where(['UserID' => $userId, 'IsDel' => 0])->find();
        if (!$rzTaobao || $rzTaobao['Status'] != 1) {
            $this->ajaxReturn(100, '请先进行淘宝认证');
        }
        vendor('Tianji.OpenapiDevBase');
        $sample = new \OpenapiDevBase();
        $orgPrivateKey = file_get_contents("./Tian/rsa_private_key.pem");
        $method = 'wd.api.taobao.getZhimaData';
        $bizData['user_id'] = $rzTaobao['TUserID'];
        $bizData['merchant_id'] = $this->Appid;
        $AppID = $this->Appid; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret = $sample->sendRequest2($bizData, $method, $AppID, $urls, $orgPrivateKey);
        if ($ret['error'] != 200) {
            $this->ajaxReturn(200, $ret['msg']);
        }
        $zhima = $ret['wd_api_taobao_getZhimaData_response']['data']['zhima'];
        $data['UserID'] = $this->uid;
        $data['Score'] = $zhima['grade'];
        $data['LoginName'] = $zhima['login_name'];
        $data['WhichMonth'] = $zhima['evaluate_date'];
        $data['Status'] = 1;
        $data['CreatedTime'] = date('Y-m-d H:i:s');
        $data['UpdatedTime'] = date('Y-m-d H:i:s');
        $re = M('renzen_zhima')->where(['UserID' => $data['UserID'],'IsDel' => 0])->find();
        $data2['ZmScore'] = $zhima['grade'];
        M('renzen_taobao')->where(['UserID' => $data['UserID'],'IsDel' => 0])->save($data2);
        if ($re) {
            M('renzen_zhima')->where(['UserID' => $data['UserID'],'IsDel' => 0])->save($data);
        } else {
            M('renzen_zhima')->add($data);
        }
        $this->ajaxReturn(200, '芝麻信用认证成功');
    }


}
