<?php
/**
   
 
 * @作者：      胡 锐
 * @修改日期：  2018-08-03 15:26
 * @功能说明：  芝麻信用接口
 */

namespace Api\Controller\Center;
use Api\Controller\Core\BaseController;
use XBCommon\XBCache;
use Think\Request;
use Extend\Zhima;

class ZhimaTestController extends BaseController
{
    const T_ZHIMA_AUTH = 'renzen_zhima';
    const T_ZHIMA_ORDER = 'zhima_order';

    public function _initialize()
    {

    }

    /**
     * @功能说明: 获取芝麻分认证地址
     * @传输方式: get
     * @提交网址: /center/zhima/getAuthUrl
     * @提交方式: {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function getAuthUrl()
    {
        #parent::_initialize(); // TODO: Change the autogenerated stub

        $appKey = C('Zhima_appKey');
        $appSecret = C('Zhima_appSecret');

        $request = [];
        $MemID = 20;

        if(empty($MemID)) return AjaxJson(1,0,'会员信息读取失败');

        $zhima = new Zhima($appKey, $appSecret);

        $domain = Request::instance()->domain();

        $redirect =  $domain.'/api.php/Center/Zhima/notify';#用户授权结束之后, 接收授权结果的异步回调地址, 须urlEncode,
        $extras = $request['extras'];#合作方自定义数据, 会作为异步回调地址的参数返回给合作方
        $channel = 'H5';#'唤醒APP';#默认使用唤醒APP的方式进行授权, 传入H5将只跳转到支付宝的H5页面进行授权 强烈建议非微信内的应用，使用唤醒APP的方式。H5授权方式有可能芝麻信用后续不再提供支持。

        #$cert_no = isset($request['cert_no']) ? $request['cert_no'] : '';#用户的身份证号
        #$cert_name = isset($request['cert_name']) ? $request['cert_name'] : '';#用户的姓名
        #$cert_phone = isset($request['cert_phone']) ? $request['cert_phone'] : '';#用户的手机号

        $readonly = 1;#默认为0, 当值为1时, 传入的用户三要素不能被用户修改
//        $customUrl = $domain.'/api.php/Home/Zhima/index';#用户授权结束展示的页面, 默认为我方的结果页, 须urlEncode
        $customUrl = isset($request['return_url']) ? $request['return_url'] : $domain.'/register/tuiguang';#用户授权结束展示的页面, 默认为我方的结果页, 须urlEncode
        $modify_no = isset($cert_no) && $cert_no ? 0 : 1;#不管readonly是否为1, 该值为1, 表示用户可以修改身份证
        $modify_name = isset($cert_name) && $cert_name ? 0 : 1;#不管readonly是否为1, 该值为1, 表示用户可以修改姓名
        $modify_phone = isset($cert_phone) && $cert_phone ? 0 : 1;#不管readonly是否为1, 该值为1, 表示用户可以修改手机号
        $desensitized = 1;#该值为1, 表示在页面上显示的用户三要素是脱敏的(

        $order_id = $zhima->withOrderId();#订单号 调用生成自动生成

        if(isset($redirect) && $redirect) $zhima->setRedirect($redirect);
        if(isset($extras) && $extras) $zhima->setExtras($extras);
        if(isset($channel) && $channel) $zhima->setChannel($channel);
        if(isset($cert_no) && $cert_no) $zhima->setCertNo($cert_no);
        if(isset($cert_name) && $cert_name) $zhima->setCertName($cert_name);
        if(isset($cert_phone) && $cert_phone) $zhima->setCertPhone($cert_phone);
        if(isset($readonly) && $readonly) $zhima->setReadonly($readonly);
        if(isset($customUrl) && $customUrl) $zhima->setCustomUrl($customUrl);
        if(isset($modify_no) && $modify_no) $zhima->setModifyNo($modify_no);
        if(isset($modify_name) && $modify_name) $zhima->setModifyName($modify_name);
        if(isset($modify_phone) && $modify_phone) $zhima->setModifyPhone($modify_phone);
        if(isset($desensitized) && $desensitized) $zhima->setDesensitized($desensitized);

        $param = $zhima->checkAccess();
        ##插入订单
        $responseData = array(
            'result' => '',
            'data' => [],
            'message' => '',
        );
        if ($OrderID = $this->addOrder($MemID, $order_id)) {

            $url = $zhima->accessAuthUrl($param);
            $responseData['result'] = 1;
            $responseData['data']['redirect_url'] = $url;
            $responseData['message'] = '获取授权地址成功';

            $this->recordNotifyStep('结束.新增订单', 'zhima_order@ID:'.$OrderID, true);

            return AjaxJson(1, $responseData['result'], $responseData['message'], $responseData['data']);
        } else {
            $responseData['result'] = 0;
            $responseData['message'] = '添加订单失败!';

            $this->recordNotifyStep('结束.添加订单失败', 'zhima_order@ID:'.$OrderID, true);

            return AjaxJson(1, $responseData['result'], $responseData['message']);
        }
    }


    /**
     * TODO: This method had been Deprecated
     * 弃用
     */
    public function Deprecated_auth()
    {
        $appKey = C('Zhima_appKey');
        $appSecret = C('Zhima_appSecret');

        $MemID = get_login_info('ID');
//        $MemID = 1;
        if(empty($MemID))die('MemID is must');

        $zhima = new Zhima($appKey, $appSecret);

        $domain = Request::instance()->domain();

        $redirect =  $domain.'/api.php/Home/Zhima/notify';#用户授权结束之后, 接收授权结果的异步回调地址, 须urlEncode,
        $extras = I('get.extras', '');#合作方自定义数据, 会作为异步回调地址的参数返回给合作方
        $channel = 'H5';#'唤醒APP';#默认使用唤醒APP的方式进行授权, 传入H5将只跳转到支付宝的H5页面进行授权 强烈建议非微信内的应用，使用唤醒APP的方式。H5授权方式有可能芝麻信用后续不再提供支持。
        $cert_no = I('get.cert_no', '');#用户的身份证号
        $cert_name = I('get.cert_name', '');#用户的姓名
        $cert_phone = I('get.cert_phone', '');#用户的手机号
        $readonly = 1;#默认为0, 当值为1时, 传入的用户三要素不能被用户修改
//        $customUrl = $domain.'/api.php/Home/Zhima/index';#用户授权结束展示的页面, 默认为我方的结果页, 须urlEncode
        $customUrl = I('get.return_url', '');#用户授权结束展示的页面, 默认为我方的结果页, 须urlEncode
        $modify_no = 0;#不管readonly是否为1, 该值为1, 表示用户可以修改身份证
        $modify_name = 0;#不管readonly是否为1, 该值为1, 表示用户可以修改姓名
        $modify_phone = 0;#不管readonly是否为1, 该值为1, 表示用户可以修改手机号
        $desensitized = 1;#该值为1, 表示在页面上显示的用户三要素是脱敏的(

        $order_id = $zhima->withOrderId();#订单号 调用生成自动生成
        isset($redirect) and $zhima->setRedirect($redirect);
        isset($extras) and $zhima->setExtras($extras);
        isset($channel) and $zhima->setChannel($channel);
        isset($cert_no) and $zhima->setCertNo($cert_no);
        isset($cert_name) and $zhima->setCertName($cert_name);
        isset($cert_phone) and $zhima->setCertPhone($cert_phone);
        isset($readonly) and $zhima->setReadonly($readonly);
        isset($customUrl) and $zhima->setCustomUrl($customUrl);
        isset($modify_no) and $zhima->setModifyNo($modify_no);
        isset($modify_name) and $zhima->setModifyName($modify_name);
        isset($modify_phone) and $zhima->setModifyPhone($modify_phone);
        isset($desensitized) and $zhima->setDesensitized($desensitized);

        $param = $zhima->checkAccess();
        ##插入订单
        $responseData = array(
            'result' => '',
            'data' => [],
            'message' => '',
        );
        if ($this->addOrder($MemID, $order_id)) {

            $url = $zhima->accessAuthUrl($param);
            $responseData['result'] = 1;
            $responseData['data']['redirect_url'] = $url;
            $responseData['message'] = '获取授权地址成功';
            exit(json_encode($responseData, \JSON_UNESCAPED_UNICODE));
//            header('location:'.$url);
        } else {
            $responseData['result'] = 0;
            $responseData['message'] = '添加订单失败!';
            exit(json_encode($responseData, \JSON_UNESCAPED_UNICODE));
        }
    }

    //芝麻回调页面
    public function notify()
    {
        $contents = file_get_contents('php://input');

        $this->recordNotifyStep('1.回调', 'zhima_order@ID:'.$OrderID, true);
        $data = json_decode($contents, true);

        $order_id = $data['order_id'];
        $status = $data['status'];
        $msg = $data['msg'];

        if(empty($order_id)) die('order_id is empty!');
        #更新订单
        $zhimaOrderModel = M(self::T_ZHIMA_ORDER);
        $data = [
            'Status' => $status,
            'UpdateTime' => time()
        ];
        if ($zhimaOrderFind = $zhimaOrderModel->where([
            'OrderNo' => $order_id
        ])->find()) {
            $zhimaOrderModel->where('ID='.$zhimaOrderFind['ID'])->save($data);
            echo '$zhimaOrderModel save '.PHP_EOL;
        } else {
            exit('order_id not exist');
//            $zhimaOrderModel->add($data);
//            echo '$zhimaOrderModel add '.PHP_EOL;
        }
        $MemID = $zhimaOrderFind['MemID'];
        #更新芝麻信用授权状态
        $zhimaAuthModel = M(self::T_ZHIMA_AUTH);
        $statusValue = $status == 'success' ? 1 : 0;

        $data = [
            'MemID' => $MemID,
            'OrderNo' => $order_id,
            'Status' => $statusValue,
            'UpdateTime' => time()
        ];
        if ($zhimaAuthFind = $zhimaAuthModel->field('ID')->where([
            'MemID' => $MemID
        ])->find()) {
            $recordRes = $zhimaAuthModel->where('ID='.$zhimaAuthFind['ID'])->save($data);
            #echo '$zhimaAuthModel save '.PHP_EOL;
        } else {
            $recordRes = $zhimaAuthModel->add($data);
            #echo '$zhimaAuthModel add '.PHP_EOL;
        }

        if($recordRes) {
            #继续查询芝麻分
            $res = $this->getZhimaCreditScore($MemID);
            if($res['result'] == 1) {
                echo $res['message'];
                die;
            } else {
                file_put_contents('zhima_notify.log', $res['message'].PHP_EOL, \FILE_APPEND);
                die;
            }
        }
        file_put_contents('zhima_notify.log', '及没更新也没新增'.PHP_EOL, \FILE_APPEND);
        //$contents = file_get_contents('php://input');
        //file_put_contents('zhima_notify.txt', $contents);
    }

    /**
     * [获取芝麻分数据]
     * @param $MemID
     * @return 1-芝麻分查询成功,0-信息存储失败,请稍后再试|无此订单数据
     */
    private function getZhimaCreditScore($MemID)
    {
        if(empty($MemID)) return false;

        $zhimaAuthModel = M(self::T_ZHIMA_AUTH);
        $zhimaAuthFind = $zhimaAuthModel->field('ID,OrderNo,Status')->where([
            'MemID' => $MemID
        ])->find();
        #if (!$zhimaAuthFind || $zhimaAuthFind['Status'] == 0) return $this->ajaxReturnError('该用户还未授权芝麻信用');

        $OrderNo = $zhimaAuthFind['OrderNo'];

        $zhima = new Zhima();
        $zhima->setOrderId($OrderNo);
        $res = $zhima->accessCreditScore();

        if ($res->code == 0) {
            $score = $res->data->score;
            $which_month = $res->data->which_month;
            $create_time = $res->data->create_time;
            $order_id = $res->data->order_id;

            #更新芝麻信用授权状态
            $data = array(
                'Score' => $score,
                'WhichMonth' => $which_month,
                'CreateTime' => $create_time,
                'UpdateTime' => time(),
            );
            $watchListResult = $zhima->watchList();
            if($watchListResult->code == 0){
                $data['WatchList']=serialize($watchListResult);
            }
            $saveRes = $zhimaAuthModel->where('ID='.$zhimaAuthFind['ID'])->save($data);
            if ($saveRes) {
                M(self::T_ZHIMA_ORDER)->where('OrderNo='.$OrderNo)->save($data);
                return $this->ajaxReturnSuccess('芝麻分查询成功', $data);
            }
            return $this->ajaxReturnError('信息存储失败,请稍后再试');
        } else {
            return $this->ajaxReturnError('无此订单数据');
        }
    }

    /**
     * [添加订单]
     * @param $mem_id
     * @param $orderno
     * @return mixed
     */
    private function addOrder($mem_id, $orderno)
    {
        #更新订单
        $zhimaOrderModel = M(self::T_ZHIMA_ORDER);

        $data = [
            'MemID' => $mem_id,
            'OrderNo' => $orderno,
            'Status' => 0,
            'UpdateTime' => time()
        ];

        return $zhimaOrderModel->add($data);
    }

    /**
     * ajax 错误返回
     * @param string $msg
     * @param array $data
     */
    private function ajaxReturnError($msg, $data=[], $toString = false)
    {
        $responseData = array(
            'result' => 0,
            'data' => $data,
            'message' => $msg,
        );

        $res = json_encode($responseData, \JSON_UNESCAPED_UNICODE);
        if($toString) {
            return $res;
        }
        exit($res);
    }
    /**
     * ajax 成功返回
     * @param string $msg
     * @param array $data
     */
    private function ajaxReturnSuccess($msg, $data=[], $toString = false)
    {
        $responseData = array(
            'result' => 1,
            'data' => $data,
            'message' => $msg,
        );

        $res = json_encode($responseData, \JSON_UNESCAPED_UNICODE);
        if($toString) {
            return $res;
        }
        exit($res);
    }

}