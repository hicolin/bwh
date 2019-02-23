<?php
namespace Home\Controller;
use MongoDB\BSON\ObjectId;
use Think\Controller;

class TaiController extends HomeController {
    private $appKey = 'f795f01573ed49229e94b1ebe4061363';
    private $appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
    private $sign = 'sha256';
    private $signValue = '';
     //个人创建账户接口
     public function addpersonaccount()
    {
        //业务参数
        $params['phoneNo'] = '15155182723';
        $params['userName'] = '凌金飞';
        $params['idNo'] = '34262219910913710X';
        $params['personArea'] = '0';
        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $timestamp=time()*1000;
        $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/account/addPersonAccount?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $res = $this->sendHttpRequest($value,$url);
        print_r($res);

}

   //企业创建账户接口
    public function addorganaccount()
    {
        //业务参数
        $params['organName'] = '价值互联（广州）区块链科技有限责任公司';
        $params['organType'] = '0';
        $params['userType'] = '2';
        $params['legalName'] = '刘希诚';
        $params['legalIdNo'] = '622226197201280517';
        $params['legalArea'] = '0';
        $params['regType'] = '0';
        $params['unifyNo'] = '91440101MA5AN2W180';
        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/account/addOrganAccount?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $res = $this->sendHttpRequest($value,$url);
        print_r($res);

    }

        //创建个人模板印章（成功）
        public function addpersonsealtemplate(){
            //业务参数
            $params['acctId'] = 'SPA1812101072038235652055040';
            $params['templateType'] = 'RECTANGLE';
            $params['color'] = 'RED';
            $value = json_encode($params,JSON_UNESCAPED_UNICODE);
            //系统参数
            $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
            $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
            $timestamp=time()*1000;
            $this->sign = 'sha256';
            $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
            $this->signValue = hash('sha256', $str);
            $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/seal/addPersonSealTemplate?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
            $res = $this->sendHttpRequest($value,$url);
            $ress = json_decode($res[0],true);
            print_r($ress['data']);
        }


    //创建企业模板印章（成功）
    public function addorgansealtemplate(){
        //业务参数
        $params['acctId'] = 'SOA1812101072046664273715200';
        $params['templateType'] = 'STAR';
        $params['color'] = 'RED';
        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/seal/addOrganSealTemplate?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $res = $this->sendHttpRequest($value,$url);
        $ress = json_decode($res['0'],true);
        print_r($ress);
    }


    //上传印章
       public function uploadseal(){
        //业务参数
        $params['acctId'] = 'SPA1812101072038235652055040';
        $params['sealData'] = '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';
        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/seal/uploadSeal?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $res = $this->sendHttpRequest($value,$url);
        print_r($res);
    }


    //下载印章
    public function downloadseal(){
        //业务参数
        $params['acctId'] = 'SPA1812101072038235652055040';
        $params['sealId'] = 'SS1812131073101490184052736';
        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$value.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/seal/downloadSeal?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $res = $this->sendHttpRequest($value,$url);
        $ress = json_decode($res['0'],true);
        $base_img=$ress['data'];
        $path = "./Public/signImage/";
        $output_file = date('His').rand(1000,9990).'.jpg';
        $path = $path.$output_file;
        file_put_contents($path, base64_decode($base_img));

        print_r($output_file);

    }



    //上传文件(成功)
    public function uploadfile(){
        //业务参数
//        move_uploaded_file($_FILES['file']['tmp_name'],"/Upload/" . $_FILES["file"]["name"]);
//        $params['acctId'] = 'SPA1812101072038235652055040';
//        $params['file'] = "http://cunguanchou.com/Upload/" . $_FILES["file"]["name"];
//        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/apiCall/uploadFile?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $this->assign('url',$url);
        $this->display();
//        var_dump($url);exit;
//        $res = $this->sendHttpRequest($params,$url);
//        print_r($res);
//        $this->build_form($url,$params);
    }


    //下载文件（成功）
    public function getFile(){
        //业务参数
        $ossKey = 'SAPO1812131073101768572592128';
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.'ossKey'.$ossKey.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/apiCall/getFile?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256&ossKey=".$ossKey;
        var_dump($url);exit;
        $res = $this->sendHttpRequest($value,$url);
        print_r($res);
    }




    //信任签署
    public function sign(){
        //业务参数
        $obj = new \stdClass();
        $obj->addSignTime = false;
        $obj->posPage = '1';
        $obj->posType = 0;
        $obj->posX = 100;
        $obj->posY = 100;
        $obj->width = 100;

        $params['acctId'] = 'SPA1812101072038235652055040';//个人acctid
        $params['sealId'] = 'SS1812131073101490184052736';//个人上传印章返回的sealid
        $params['ossKey'] = 'SAPO1812131073101768572592128.pdf';//上传文档返回的
//        $params1['zaPosBean']['addSignTime'] = false;
//        $params1['zaPosBean']['posPage'] = '1';
//        $params1['zaPosBean']['posType'] = 0;
//        $params1['zaPosBean']['posX'] = 100;
//        $params1['zaPosBean']['posY'] = 100;
//        $params1['zaPosBean']['width'] = 100;
        $params['participateNum'] = 1;
        $params['signType'] = 1;
//        echo "<pre>";
//        $value = json_encode($params1['zaPosBean'],JSON_UNESCAPED_UNICODE);
        $params['zaPosBean'][]=$obj;
//        print_r($params);exit;
        $value2 = json_encode($params);

        print_r($value2);
        //系统参数
        $timestamp=time()*1000;
        $str = $this->appKey.$timestamp.$value2.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/apiCall/sign?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        print_r($url);exit;
        $res = $this->sendHttpRequest($value,$url);
        print_r($res);
    }




    //PDF验签服务
    public function verifySignFile(){
        //业务参数
//        $params['file'] = '';
//        $value = json_encode($params,JSON_UNESCAPED_UNICODE);
        //系统参数
        $this->appKey = 'f795f01573ed49229e94b1ebe4061363';
        $this->appSecret = 'e16f262f-81a8-439e-ba1f-82f1f49568c6';
        $timestamp=time()*1000;
        $this->sign = 'sha256';
        $str = $this->appKey.$timestamp.$this->appSecret.$this->sign;
        $this->signValue = hash('sha256', $str);
        $url = "https://trusti-api.anlink.tech/gateway/gw/v1/sign/api/verifySignFile?appKey=".$this->appKey."&timestamp=".$timestamp."&signValue=".$this->signValue."&sign=sha256";
        $this->assign('url',$url);
        $this->display();

    }


    //application/json方式
    private function sendHttpRequest($data = null,$url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data)
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return array($response);
        // return json_decode($output,true);
    }



}