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

class ZhimaController extends BaseController
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
        parent::_initialize(); // TODO: Change the autogenerated stub

        $para = get_json_data();
        $request = $para['data'];
        $UserID = get_login_info('ID');

        if($this->IsCertifyIDCard($UserID)) {

            if($memInfo = M('mem_info')->field('TrueName,IDCard,Mobile')->where('IsDel=0')->find($UserID)) {#获取身份证号

                $cert_no = $memInfo['IDCard'];
                $cert_name = $memInfo['TrueName'];
                $cert_phone = $memInfo['Mobile'];

            } else {

                return AjaxJson(1,0,'会员账号信息读取失败, 请刷新重试!');
            }

        } else {

            return AjaxJson(1,0,'此项认证, 需要先成功认证身份证信息');
        }

        $appKey = C('Zhima_appKey');
        $appSecret = C('Zhima_appSecret');

        if(empty($UserID)) return AjaxJson(1,0,'会员信息读取失败');

        $zhima = new Zhima($appKey, $appSecret);

        $domain = Request::instance()->domain();

        $redirect =  $domain.'/api.php/Center/Zhima/notify';#用户授权结束之后, 接收授权结果的异步回调地址, 须urlEncode,
        $extras = isset($request['extras']) ? $request['extras'] : '';#合作方自定义数据, 会作为异步回调地址的参数返回给合作方
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
        if ($this->addOrder($UserID, $order_id)) {

            $url = $zhima->accessAuthUrl($param);
            $responseData['result'] = 1;
            $responseData['data']['redirect_url'] = $url;
            $responseData['message'] = '获取授权地址成功';

            #$zhima->recordNotifyStep('结束.新增订单', 'zhima_order@ID:'.$OrderID, true);

            return AjaxJson(1, $responseData['result'], $responseData['message'], $responseData['data']);
        } else {
            $responseData['result'] = 0;
            $responseData['message'] = '添加订单失败!';

            #$zhima->recordNotifyStep('结束.添加订单失败', 'zhima_order@ID:'.$OrderID, true);

            return AjaxJson(1, $responseData['result'], $responseData['message']);
        }
    }

    /**
     * 模拟回调请求
     * http://tkj.cashtikuanji.com/api.php/Center/Zhima/MockNotifySender
     */
    public function MockNotifySender()
    {

        $OrderNo = '20180905184138531153614409833699';
        if(empty($OrderNo)) exit('OrderNo is must!');
        if(!$find = M(self::T_ZHIMA_ORDER)
            ->where(['OrderNo' => $OrderNo])
            ->find()) exit('OrderNo is note exist');

        $data = array(
            'order_id' => $OrderNo,
            'status' => 'success',
            'msg' => '操作成功',

        );

        $domain = Request::instance()->domain();
        $redirect =  $domain.'/api.php/Center/Zhima/notify';

        $times = 1;

        while($times<=4) {
            $res = $this->httpRequest($redirect, 'post',$data, 'json');
            dump($res);
            if ($res || $times==4) {
                echo 'send ok'.PHP_EOL;
                dump($res);
                break;
            } elseif ($times <= 2) {
                echo 'send f sleep 3s';
                sleep(10);
            } elseif ($times == 3) {
                echo 'send f sleep 5s';
                sleep(30);
            }
            $times++;
        }
    }



    //芝麻回调页面
    public function notify()
    {
        $contents = file_get_contents('php://input');

        /*@@@@*/$this->recordNotifyStep('1.回调', $contents);

        $data = json_decode($contents, true);

        $order_id = $data['order_id'];
        $status = $data['status'];
        $msg = $data['msg'];

        #获取状态 1认证成功 0认证失败
        $statusValue = $status == 'success' ? 1 : 0;
        $timestamp = date('Y-m-d H:i:s');

        #模型
        $zhimaModel = M(self::T_ZHIMA_AUTH);
        $zhimaOrderModel = M(self::T_ZHIMA_ORDER);

        #查询订单
        $zhimaInfo = $zhimaModel
            ->field('ID,UserID')
            ->where([
                'OrderNo' => $order_id
            ])
            ->find();
        $UserID = $zhimaInfo['UserID'];
        $zhimaID = $zhimaInfo['ID'];

        if($statusValue && $zhimaInfo){
            #查询芝麻分
            list($result, $info) = $this->getZhimaCreditScore($UserID);
            /*@@@@*/$this->recordNotifyStep('Result.查询芝麻分结果', $info);

            if($result == 1) {//查询成功

                $data = array(
                    'Score' => $info['Score'],
                    'WhichMonth' => $info['WhichMonth'],
                    #'CreateTime' => $info['create_time'],
                    'WatchList' => $info['WatchList'],
                    'UpdatedTime' => $timestamp,
                    'Status' => 1,
                );
                #更新结果
                if($recordRes = $zhimaModel->where('ID='.$zhimaID)->save($data)) {
                    /*@@@@*/$this->recordNotifyStep('End.芝麻分认证成功', 'end', true);
                    die('芝麻分认证成功');
                }
            }
        }
        #认证失败
        $zhimaRes = $zhimaModel
            ->where('IsDel=0 and UserID='.$UserID)
            ->save([
                'Status' => 2,
                'UpdatedTime' => $timestamp,
            ]);
        /*@@@@*/$this->recordNotifyStep('End.芝麻分认证失败', $info.'$UserID:'.$UserID, true);

        throw new \Exception('既没更新也没新增');
    }


    /**
     * TODO: This method had been Deprecated
     * 弃用
     */
    public function Deprecated_auth()
    {
        $appKey = C('Zhima_appKey');
        $appSecret = C('Zhima_appSecret');

        $UserID = get_login_info('ID');
//        $UserID = 1;
        if(empty($UserID))die('UserID is must');

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
        if ($OrderID = $this->addOrder($UserID, $order_id)) {

            if($OrderID)

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

    /**
     * [获取芝麻分数据]
     * @param $UserID
     * @return 1-芝麻分查询成功,0-信息存储失败,请稍后再试|无此订单数据
     */
    private function getZhimaCreditScore($UserID)
    {
        if(empty($UserID)) return false;

        $zhimaAuthModel = M(self::T_ZHIMA_AUTH);
        $zhimaAuthFind = $zhimaAuthModel->field('ID,OrderNo,Status')->where([
            'UserID' => $UserID
        ])->find();

        #if (!$zhimaAuthFind || $zhimaAuthFind['Status'] == 0) {

            #return $this->ajaxReturnError('该用户还未授权芝麻信用');
            #/*@@@@*/$this->recordNotifyStep('|--该用户还未授权芝麻信用', "'UserID' => $UserID");
            #return [0, '该用户还未授权芝麻信用'];
        #}

        $OrderNo = $zhimaAuthFind['OrderNo'];

        $zhima = new Zhima();
        $zhima->setOrderId($OrderNo);
        $res = $zhima->accessCreditScore();

        $timestamp = date('Y-m-d H:i:s');

        /*@@@@*/$this->recordNotifyStep('|--获取芝麻分数据', json_encode($res, \JSON_UNESCAPED_UNICODE));


        if ($res->code == 0) {
            $score = $res->data->score;
            $which_month = $res->data->which_month;
            #$create_time = $res->data->which_month;
            $order_id = $res->data->order_id;

            #更新芝麻信用授权状态
            $data = array(
                'Score' => $score,
                'WhichMonth' => $which_month,
                'CreatedTime' => $timestamp,
                'UpdatedTime' => $timestamp,
            );
            $watchListResult = $zhima->watchList();

            /*@@@@*/$this->recordNotifyStep('|--获取行业关注名单数据', json_encode($watchListResult, \JSON_UNESCAPED_UNICODE), true);

            if($watchListResult->code == 0){
                $data['WatchList'] = serialize($watchListResult);
            }
            #保存一个快照 不参与流程
            M(self::T_ZHIMA_ORDER)->where('OrderNo='.$OrderNo)->save($data);
            #芝麻分 行业关注名单
            return [1, $data];
            /*if ($saveRes) {
                M(self::T_ZHIMA_ORDER)->where('OrderNo='.$OrderNo)->save($data);
                return $this->ajaxReturnSuccess('芝麻分查询成功', $data);
            }
            return $this->ajaxReturnError('信息存储失败,请稍后再试');*/
        }
        #return $this->ajaxReturnError('无此订单数据');
        return [0, '第三方无此订单数据'];
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
        $zhimaModel = M(self::T_ZHIMA_AUTH);

        if(empty($mem_id) || empty($orderno)) return fasle;

        $timestamp = date('Y-m-d H:i:s');

        $data = [
            'UserID' => $mem_id,
            'OrderNo' => $orderno,
            'Status' => 0,
            'UpdatedTime' => $timestamp,
            'CreatedTime' => $timestamp,

        ];

        $timestamp = date('Y-m-d H:i:s');
        $InsertZhimaOrderID = $zhimaOrderModel->add($data);;

        if($zhimaModel->field('ID')->where('IsDel=0 and UserID='.$mem_id)->find()) {
            $data = array(
                'OrderNo' => $orderno,
                #'Status' => 0,
                'UpdatedTime' => $timestamp,
            );
            $zhimaRes = $zhimaModel->where('IsDel=0 and UserID='.$mem_id)->save($data);
        } else {
            #insert
            $data = [
                'UserID' => $mem_id,
                'OrderNo' => $orderno,
                'Status' => -1,
                'UpdatedTime' => $timestamp,
                'CreatedTime' => $timestamp,
            ];
            $zhimaRes = $zhimaModel->add($data);
        }
        return $zhimaRes && $InsertZhimaOrderID;
    }

    /**
     * ajax 错误返回
     * @param string $msg
     * @param array $data
     */
    private function ajaxReturnError($msg, $data=[], $toString = true)
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
    private function ajaxReturnSuccess($msg, $data=[], $toString = true)
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

    #记录回调的每一步
    public function recordNotifyStep($stepName, $putContents, $flur = false)
    {
        static $logStack=[];

        $curYmd = date('Ymd');
        $curDate = date('Y-m-d H:i:s');

        if(is_array($putContents)) $putContents = json_encode($putContents, \JSON_UNESCAPED_UNICODE);

        $logStack[] = [$stepName, $putContents];

        $txt = '';

        if($flur) {
            foreach ($logStack as $index => $item) {
                list($stepName, $putContents) = $item;
                $txt .= '     '.$stepName.PHP_EOL.'            '.$putContents.PHP_EOL;

            }
            file_put_contents(
                'zhimalog/notify_result'.$curYmd.'.txt',
                '['.$curDate.']'.PHP_EOL.'>>>>>>>>>'.PHP_EOL.$txt.PHP_EOL.'>>>>>>>>>'.PHP_EOL,
                \FILE_APPEND
            );
        }
    }

    private function IsCertifyIDCard($UserID)
    {
        $IsCertify = M('renzen_cards')
            ->where([
                'UserID' => $UserID,
                'IsDel' => 0,
                'Status' => 1,
            ])
            ->getField('Status');
        ;

        return !!$IsCertify;
    }

    /**
     * [CURLSend 使用curl向服务器传输数据]
     * @param $url  [请求的地址]
     * @param string $method [请求方式GET,POST]
     * @param array $data [数据]
     * @param bool $doJson [json格式]
     * @return mixed
     */
    private function httpRequest($url, $method='get', $data=array(), $doJson = false, $head = [])
    {


        $ch = curl_init();//初始化

        if($doJson) {

            $headers = array('Accept-Charset: utf-8', 'Content-Type: application/json');
            $head && $headers = $head;
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
            $headers = array('Accept-Charset: utf-8');
            $head && $headers = $head;
        }

        //设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);//指定请求的URL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));//提交方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//不验证SSL
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//设置HTTP头字段的数组

        // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');//头的字符串

        #curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie_file); //存储cookies

        #curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); //使用上面获取的cookies

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//自动设置header中的Referer:信息
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//提交数值
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//是否输出到屏幕上,true不直接输出
        $temp = curl_exec($ch);//执行并获取结果
        curl_close($ch);
        return $temp;//return 返回值
    }


}