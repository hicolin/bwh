<?php
/**
 * Date: 2018/12/2
 * Time: 11:48
 * 测试控制器
 */
namespace Home\Controller;
use Org\Util\Ucpaas;
use Think\Controller;

class TestController extends HomeController
{
    public function index(){
        $param = I('get.');
        print_r($param);exit;
        $this->ajaxReturn('1','1',$param);
        $post = $_POST;
        $aa = json_encode($post);
        file_put_contents('4.txt',$aa);
        if($post['state'] == 'report'){
            $detail=$this->detail($post['userId'],$post['outUniqueId']);
            file_put_contents('514.txt',$detail);
        }
        exit;
        $mobile = '18341329120';
        $options['accountsid'] = 'cafcdc6987fbc7b99b043af5a9f496db';
        $options['token'] = '5fa27aecff06d0616a84e8aaf77b6573';
        $ucpass = new Ucpaas($options);

        //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
        $appId = "84456dce5eec4b7190f64146fe2da272";
        $mobile_code = rand(100000, 999999);
        $to = $mobile;
        //$templateId = "101647";
        $templateId = '190517';
        $param = "$mobile_code";

        $_SESSION['code'] = $param;
        $_SESSION['code_time'] = time() + 60 * 3;
        $_SESSION['mobile'] = $mobile;
        $res = $ucpass->templateSMS($appId, $to, $templateId, $param);
        echo '<pre>';
        print_r(json_decode($res,true));
    }
    public function detail($userId,$outUniqueId){
        $strDir = dirname(__FILE__);
        include ($strDir.'/tianji/OpenapiClient.php');

        $sample= new \OpenapiClient();
        $sample->setMethod('tianji.api.tianjireport.detail');
        $sample->setField('userId', $userId);
        $sample->setField('outUniqueId',$outUniqueId);
        $sample->setField('reportType','html');
        $ret= $sample->execute();
        return json_encode($ret);
    }

    public function test(){
//        $strDir = dirname(__FILE__);
//        include ($strDir.'/Tian/OpenapiClient.php');
//
//        $sample = new \OpenapiClient();
//        $sample->setMethod('tianji.api.tianjireport.collectuser');
//        $sample->setField('type', 'mobile');
//        $sample->setField('platform', 'web');
//        $sample->setField('userId', 8006);
//        $sample->setField('outUniqueId', time());
//        $sample->setField("name", "陆晓丹");
//        $sample->setField("phone", "13731207099");
//        $sample->setField("idNumber", "320582198705205114");
//        $sample->setField('notifyUrl',"http://cunguanchou.com/test/index");
//        $sample->setField('returnUrl', "http://cunguanchou.com/test/index");
//        $sample->setField('version', '2.0');
//        $ret= $sample->execute();
//        echo '<pre>';
//        print_r($ret);
        echo 2222;
        echo '<script>location.href = "http://www.baidu.com"</script>';
        exit;
        $this->redirect('http://www.baidu.com');exit;

    }

    public function rh()
    {
        $strDir = dirname(__FILE__);
        include ($strDir.'/Tian/OpenapiClient.php');
        $sample= new \OpenapiClient();
        $sample->setMethod('tianji.api.agentr.blacklist');
        $sample->setField("name", "陆晓丹");
        $sample->setField("idNumber", "320582198705205114");
        $sample->setField("phone", "13731207099");
        $ret= $sample->execute();
        echo '<pre>'; print_r($ret);
    }

    /**
     * 机构R黑名单示例
     */
    public function zz()
    {
        $orgPrivateKey=file_get_contents("./Tian/rsa_private_key.pem");
        $bizData['name']="王丹丹";
        $bizData['idNumber']="320582198705205114";
        $bizData['phone']="13731207099";
        vendor('Tianji.OpenapiDevBase');
        $openapiDevBase = new \OpenapiDevBase();
        $detailmethod = 'tianji.api.agentr.blacklist';//method  参数
        $AppID = '2010343'; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $details=$openapiDevBase->sendRequest($bizData, $detailmethod,$AppID,$urls,$orgPrivateKey);

    }


    public function html()
    {
        vendor('Tianji.OpenapiDevBase');
        $orgPrivateKey=file_get_contents("./Tian/rsa_private_key.pem");
        $bizData['userId']="8306";
        $bizData['outUniqueId']="2018120515070153102495";
        $bizData['reportType']="html";
        $detailmethod = 'tianji.api.tianjireport.detail';
        $sampl = new \OpenapiDevBase();
        $AppID = '2010343'; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $details=$sampl->sendRequest($bizData, $detailmethod,$AppID,$urls,$orgPrivateKey);

        print_r($details);exit;
        if($details['error']==200 && $details['tianji_api_tianjireport_detail_response']){
            $report=$details['tianji_api_tianjireport_detail_response'];
            if($report){
                $sdata['MobileData']=serialize($report);
                if($report['json']['basic_info']['reg_time']){
                    $sdata['OpenDate']=$report['json']['basic_info']['reg_time'];
                }
                if($report['json']['basic_info']['real_name']){
                    $sdata['ZUserName']=$report['json']['basic_info']['real_name'];
                }
                if($report['json']['basic_info']['current_balance']){
                    $sdata['AccountBalance']=$report['json']['basic_info']['current_balance'];
                }
                if($report['json']['call_log']){
                    //6个月的通话记录
                    $billArr=array();
                    foreach($report['json']['call_log'] as $k=>$v){
                        $onebill['tel']=$v['phone'];  //（被）呼叫电话
                        $onebill['times']=$v['first_contact_date']." - ".$v['last_contact_date'];  //联系时间段
                        $onebill['conways']=$v['phone_location'];    //号码归属地
                        $onebill['contype']=$v['call_cnt'];   //主叫次数
                        $onebill['longs']=$v['talk_seconds'];     //联系总时长
                        $onebill['consite']=$v['called_cnt'];  //被叫次数
                        $billArr[]=$onebill;
                    }
                    echo '<pre>';
                    print_r($billArr);exit;
                    if($billArr){
                        $sdata['CallBill']=serialize($billArr);
                    }
                }
            }
        }
    }

    /**
     * 天机详情报告
     */
    public function report(){
        $searchId='2018120515575551975110';
        $niqueId='15439966758029788567';
        $mobileinfos=M('renzen_mobile')->field('ID,TUserID,TaskID,MobileData')->where(array('TUserID'=>$searchId,'TaskID'=>$niqueId,'IsDel'=>'0'))->find();

        if($mobileinfos['MobileData']){
            $MobileData=unserialize($mobileinfos['MobileData']);
            $html=$MobileData['html'];
        }else{
            $html="参数错误，暂无数据";
        }
        $this->assign(array(
            "html"=>$html
        ));
        print_r($html);exit;
    }
    /**
     * 小额
     */
    public function xe()
    {
        $infos=M('sys_inteparameter')->where(array('IntegrateID'=>'15'))->select();
        print_r($infos);exit;
        vendor('Tianji.OpenapiDevBase');
        $sample= new \OpenapiDevBase();
        $orgPrivateKey=file_get_contents("./Tian/rsa_private_key.pem");
        $bizData['name']="陆晓丹";
        $bizData['idNumber']="612522198902030821";
        $bizData['phone']="18256930711";
        $bizData['userId']="8306";
        $method = 'tianji.api.tianjiscore.pdscorev4';
        $AppID = '2010343'; //appid
        $urls = 'http://openapi.rong360.com/gateway'; //url
        $ret=$sample->sendRequest($bizData, $method,$AppID,$urls,$orgPrivateKey);

        echo '<pre>'; print_r($ret);
    }

    public function cz()
    {

        $social = M('renzen_social')->where(array('UserID' =>'8306'))->find();
        echo '<pre>';
        print_r($social);exit;
        $aa = '{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"oid":"2","ordersn":"12352222","paytype":"1","traderemark":"我用支付宝182@qq.com账号支付了100元"}}';
        $url = '/center/Order/gethkmoney';
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $aa
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        //$result = json_decode($result,true);
        print_r($result);exit;
        file_put_contents('22.txt','2');exit;
        file_put_contents('1.txt',json_decode($_REQUEST));
        $s = '{
    "error": 200,
    "msg": null,
    "tianji_api_agentr_blacklist_response": [
        {
            "feature1": "20160714",
            "feature2": "20161215",
            "feature3": "6",
            "feature4": "4",
            "feature5": "0"
        }
    ],
    "request_id":"15137663280110806563"
}';
        $s = json_decode($s, true);
        echo '<pre>';
        print_r($s);
    }
    public function tel()
    {
        $arr = file_get_contents('9999.txt');
        $a = json_decode($arr,true);
        echo '<pre>';
        print_r($a);exit;
        $report=$a['tianji_api_tianjireport_detail_response']['json'];
        $billArr=array();
//        echo '<pre>';
//        print_r($report);exit;
        foreach ($report['addrs_info']['most_used_addrs'] as $k=>$list){
            $a['zip'] = '';
            $a['tel'] = $list['telephone'];
            $a['name'] = $list['name'];
            $a['address'] = $list['address'];
            $billArr[] = $a;
        }
//        echo '<pre>';
//        print_r($billArr);exit;
        $sdata['Receivers']=serialize($billArr);


        echo '<pre>';
        print_r($a);exit;
        $s = serialize($a->s);
        $s = unserialize($s);
        print_r($s);exit;
        //$Phonelist = unserialize($Phonelist);
        echo '<pre>';
        var_dump($Phonelist);exit;
        foreach ($Phonelist as $k=>$list){
            $tel[$k]['name'] = $list[0];
            $tel[$k]['tel'] = $list[1];
            $tel[$k]['updatetime'] = date('Y-m-d H:i:s');
        }
//        $Phonelist =  json_decode($Phonelist,true);
        echo '<pre>';
        print_r($tel);exit;

//        file_put_contents('1035.txt',$_REQUEST);
//        file_put_contents('1036.txt',json_encode($_REQUEST));
//        echo 2;exit;
        $Phonelist=M('renzen_social')->where(array("ID"=>'5'))->getField('Phonelist');
        $Phonelist = unserialize($Phonelist);
        $Phonelist= json_encode($Phonelist);
//        $Phonelist =  json_decode($Phonelist,true);
        echo '<pre>';
        print_r(json_decode($Phonelist,true));exit;


    }

    /**
     * 测试代收付
     */
    public function daifu()
    {
        $ver="1.00";
        $amt="1";
        $cityno="1000";
        $entseq="test";
        $bankno="0102";
        $merdt=date('Ymd');
        $accntno="6210810590001226066";
        $orderno=getOrderSn();
        $branchnm="";
        $accntnm="常杰";
        $mobile="18341329120";
        $memo="备注";
        $mchntcd="0003610F1971380";
        $mchntkey="1e4bx4326tbfffcp60xuof7om2t8n65j";
        $addDesc = '1';
        $reqtype="payforreq";//代付
//        $reqtype="sincomeforreq";//代收
        $xml="<?xml version='1.0' encoding='utf-8' standalone='yes'?><payforreq><ver>".$ver."</ver><merdt>".$merdt."</merdt><orderno>".$orderno."</orderno><bankno>".$bankno."</bankno><cityno>".$cityno."</cityno><accntno>".$accntno."</accntno><accntnm>".$accntnm."</accntnm><branchnm>".$branchnm."</branchnm><amt>".$amt."</amt><mobile>".$mobile."</mobile><entseq>".$entseq."</entseq><memo>".$memo."</memo></payforreq>";
        $macsource=$mchntcd."|".$mchntkey."|".$reqtype."|".$xml;
        $mac=md5($macsource);
        $mac=strtoupper($mac);
        $list=array("merid"=>$mchntcd,"reqtype"=>$reqtype,"xml"=>$xml,"mac"=>$mac);
        $url="https://fht-api.fuiou.com/req.do";

        $query = http_build_query($list);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($query)."\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
                'method'  => "POST",
                'content' => $query,
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context, -1, 40000);
        $re = xml_to_array($result);
        echo '<pre>';
        print_r($re);
        //echo $result;
    }

    /**
     * 测试代付查询
     */
    public function daifucx()
    {
        $ver="1.1";
        $busicd="AP01";
        $orderno="1812135121647967";
        $startdt=date('Ymd',strtotime("-14 day"));
        $enddt=date('Ymd');
        $mchntcd = '0003610F1971380';
        $mchntkey = '1e4bx4326tbfffcp60xuof7om2t8n65j';
        $xml="<?xml version='1.0' encoding='utf-8' standalone='yes'?><qrytransreq><ver>".$ver."</ver><busicd>".$busicd."</busicd><orderno>".$orderno."</orderno><startdt>".$startdt."</startdt><enddt>".$enddt."</enddt><transst>1</transst></qrytransreq>";
        $macsource=$mchntcd."|".$mchntkey."|".$xml;
        $mac=md5($macsource);
        $mac=strtoupper($mac);
        $list=array("merid"=>$mchntcd,"xml"=>$xml,"mac"=>$mac);
        $url="https://fht-api.fuiou.com/qry.do";
        $query = http_build_query($list);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($query)."\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
                'method'  => "POST",
                'content' => $query,
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context, -1, 40000);
        $re = xml_to_array($result);
        echo '<pre>';
        print_r($re);
        //echo $result;
    }
    public function qianyue()
    {
        $ver ="1.00";
        $mchntCd ="0002900F0345178";
        $contractNo = "000036227864";
        $startdt ="20170626";
        $enddt ="20170627";
        $mobileNo = "13521233364";
        $userNm ="张衍";
        $acntNo ="6227002942040547542";
        $credtNo = "372929199611103614";

        $data['ver']  ="1.00";
        $data['mchntCd']  =   "0002900F0345178";
        $data['contractNo']   =  "000036227864";
        $data['startdt']   =  "20170626";
        $data['enddt']  =  "20170627";
        $data['mobileNo']   =  "13521233364";
        $data['userNm']    = "张衍";
        $data['acntNo']     =  "6227002942040547542";
        $data['credtNo']     =  "372929199611103614";

        sort($data,SORT_STRING);
        $str=implode('|', $data);
        $shi=sha1($str);
        $key="123456";
        $string=$shi.'|'.$key;
        $signature=sha1($string);
//        echo $signature;
//        echo "\n";
        $xml="<?xml version='1.0' encoding='utf-8' standalone='yes'?><custmrBusi><ver>".$ver."</ver><mchntCd>".$mchntCd."</mchntCd><contractNo>".$contractNo."</contractNo><startdt>".$startdt."</startdt><enddt>".$enddt."</enddt><userNm>".$userNm."</userNm><credtNo>".$credtNo."</credtNo><acntNo>".$acntNo."</acntNo><mobileNo>".$mobileNo."</mobileNo><signature>".$signature."</signature></custmrBusi>";

//        echo $xml;
//        echo "\n";

        $list=array("xml"=>$xml);
        $url="https://fht-test.fuiou.com/fuMer/api_queryContracts.do";
        $query = http_build_query($list);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($query)."\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
                'method'  => "POST",
                'content' => $query,
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context, -1, 40000);
        $re = xml_to_array($result);
        echo '<pre>';
        print_r($re);exit;
    }

    /**
     * 身份证接口
     */
    public function redic()
    {
        $a = M('renzen_cards')->where(['UserID'=>'8306','IsDel'=>0])->find();
        $id = $_REQUEST['result'];
        $idcer = json_decode($id,true);
        serialize($idcer);
        $data = [
            'zm'=>$idcer['url_frontcard'],
            'fm'=>$idcer['url_backcard'],
            'ht'=>$idcer['url_photoliving'],
            'name'=>$idcer['url_photoliving']
        ];
        echo 2;exit;
    }

    public function sms()
    {
        $pukey = '66853252-57fd-488a-979a-27e0815646d2';
        $product_code = 'Y1001005';
        $out_order_id = getOrderSn();
        $secretkey = 'd30649d1-d4d1-4429-9a76-57a33090725a';
        $data['id_no'] = '34082219850722625X';
//        $data['id_no'] = '340881199307031217';
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
        $result = serialize($result['body']);
        $add['Yddatas'] = $result;
        M('renzen_cards')->where(['UserID'=>8329])->save($add);
        //        echo '<pre>';
//        print_r($result);exit;

    }


    
}