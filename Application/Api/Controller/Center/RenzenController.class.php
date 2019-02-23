<?php
namespace Api\Controller\Center;
use Api\Controller\Core\BaseController;
use XBCommon\XBCache;

class RenzenController extends BaseController  {

    const T_TABLE='mem_info';
    const T_PARAMETER='renzen_parameter';
    const T_CARDS='renzen_cards';//身份证认证表
    const T_MOBILE='renzen_mobile';//手机认证表
    const T_ALIPAY='renzen_alipay';//支付宝认证表
    const T_TAOBAO='renzen_taobao';//淘宝认证表
    const T_MEMBERINFO='renzen_memberinfo';//基本信息认证表
    const T_SOCIAL='renzen_social';//社交认证表
    const T_BANK='renzen_bank';//社交认证表
    const T_ZHIMA = 'renzen_zhima';//芝麻认证表
    /**
     * @功能说明: 获取所有认证信息列表
     * @传输方式: post
     * @提交网址: /center/Renzen/getlists
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function getlists(){
        $UserID=get_login_info('ID');
    	$listinfo=M(self::T_PARAMETER)->field('ID,Name,Codes,ImgUrl,ImgUrl1,IsShow,IsMust,Sort,Intro')->where(array('IsShow'=>'1','IsDel'=>'0'))->order('Sort asc,ID desc')->select();
        if(!$listinfo){
            exit(json_encode(array('result'=>0,'message'=>'没有查询到认证信息列表!')));
        }
        foreach($listinfo as $k=>&$v){
        	if($v['ImgUrl']){
        		$v['ImgUrl']="http://".$_SERVER['HTTP_HOST'].$v['ImgUrl'];
        	}
        	if($v['ImgUrl1']){
        		$v['ImgUrl1']="http://".$_SERVER['HTTP_HOST'].$v['ImgUrl1'];
        	}
            //判断是否认证
            if($v['Codes']=='card'){
                $CardStatus=M(self::T_CARDS)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//身份证认证
                if(!$CardStatus && $CardStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$CardStatus;
                }
            }elseif($v['Codes']=='mobile'){
                $MobileStatus=M(self::T_MOBILE)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//手机认证
                if(!$MobileStatus && $MobileStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$MobileStatus;
                }
            }elseif($v['Codes']=='alipay'){
                $AlipayStatus=M(self::T_ALIPAY)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//支付宝认证
                if(!$AlipayStatus && $AlipayStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$AlipayStatus;
                }
            }elseif($v['Codes']=='taobao'){
                $TaobaoStatus=M(self::T_TAOBAO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//淘宝认证
                if(!$TaobaoStatus && $TaobaoStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$TaobaoStatus;
                }
            }elseif($v['Codes']=='memberinfo'){
                $MemberinfoStatus=M(self::T_MEMBERINFO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//基本信息认证
                if(!$MemberinfoStatus && $MemberinfoStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$MemberinfoStatus;
                }
            }elseif($v['Codes']=='social'){
                $SocialStatus=M(self::T_SOCIAL)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//社交认证
                if(!$SocialStatus && $SocialStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$SocialStatus;
                }
            }elseif($v['Codes']=='bank'){
                $BankStatus=M(self::T_BANK)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//银行卡认证
                if(!$BankStatus && $BankStatus!='0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao']=$BankStatus;
                }
            }elseif($v['Codes'] == 'zhimafen'){
                $ZhimaStatus = M(self::T_ZHIMA)
                    ->where([
                        'UserID'=>$UserID,
                        'IsDel'=>'0'
                    ])
                    ->order('ID desc')
                    ->getField('Status');//芝麻分认证
                if(!$ZhimaStatus && $ZhimaStatus != '0'){
                    $v['IsTiJiao']='-1';//没有此记录,,没认证
                }else{
                    $v['IsTiJiao'] = $ZhimaStatus;
                }
            }
        }
        selfshenhe_mems($UserID);//所有认证结束，自动审核会员
        //查询身份认证接口(人脸),公钥
        $idCerKey=M('sys_inteparameter')->where(array('IntegrateID'=>'9','ParaName'=>'api_key'))->getField('ParaValue');
         exit(json_encode(array('result'=>1,'message'=>'查询成功','idCerKey'=>$idCerKey,'data'=>$listinfo)));
        //AjaxJson(1,1,'恭喜您，数据查询成功！',$data,0);
    }
    /**
     * @功能说明: 查看会员所有认证的状态
     * @传输方式: post
     * @提交网址: /center/Renzen/rzstates
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function rzstates(){
    	$UserID=get_login_info('ID');
    	$data=array('UserID'=>$UserID);
    	$CardStatus=M(self::T_CARDS)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//身份证认证
    	if(!$CardStatus && $CardStatus!='0'){
    		$data['CardStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['CardStatus']=$CardStatus;
    	}
    	$MobileStatus=M(self::T_MOBILE)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//手机认证
    	if(!$MobileStatus && $MobileStatus!='0'){
    		$data['MobileStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['MobileStatus']=$MobileStatus;
    	}
    	$AlipayStatus=M(self::T_ALIPAY)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//支付宝认证
    	if(!$AlipayStatus && $AlipayStatus!='0'){
    		$data['AlipayStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['AlipayStatus']=$AlipayStatus;
    	}
    	$TaobaoStatus=M(self::T_TAOBAO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//淘宝认证
    	if(!$TaobaoStatus && $TaobaoStatus!='0'){
    		$data['TaobaoStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['TaobaoStatus']=$TaobaoStatus;
    	}
    	$MemberinfoStatus=M(self::T_MEMBERINFO)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//基本信息认证
    	if(!$MemberinfoStatus && $MemberinfoStatus!='0'){
    		$data['MemberinfoStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['MemberinfoStatus']=$MemberinfoStatus;
    	}
    	$SocialStatus=M(self::T_SOCIAL)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//社交认证
    	if(!$SocialStatus && $SocialStatus!='0'){
    		$data['SocialStatus']='-1';//没有此记录,,没认证
    	}else{
    		$data['SocialStatus']=$SocialStatus;
    	}
        $BankStatus=M(self::T_BANK)->where(array('UserID'=>$UserID,'IsDel'=>'0'))->order('ID desc')->getField('Status');//银行卡认证
        if(!$BankStatus && $BankStatus!='0'){
            $data['BankStatus']='-1';//没有此记录,,没认证
        }else{
            $data['BankStatus']=$BankStatus;
        }
        $ZhimaStatus = M(self::T_ZHIMA)
            ->where([
                'UserID'=>$UserID,
                'IsDel'=>'0'
            ])
            ->order('ID desc')
            ->getField('Status');//芝麻分认证
        if(!$ZhimaStatus && $ZhimaStatus!='0'){
            $data['ZhimaStatus']='-1';//没有此记录,,没认证
        }else{
            $data['ZhimaStatus'] = $ZhimaStatus;
        }
        selfshenhe_mems($UserID);//所有认证结束，自动审核会员
    	exit(json_encode(array('result'=>1,'message'=>'查询成功','data'=>$data)));
    }
    /**
     * @功能说明: 身份证认证数据保存
     * @传输方式: post
     * @提交网址: /center/Renzen/cardsrz
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function cardsrz(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');

        
        if($para['data']['ret_code']=='000000'){
            if($para['data']['id_no']){
                //保存身份证号码
                $mreslt=M(self::T_TABLE)->where(array('ID'=>$UserID))->save(array('IDCard'=>$para['data']['id_no'],'TrueName'=>$para['data']['id_name'],'UpdateTime'=>date('Y-m-d H:i:s')));
                if(!$mreslt){
                    exit(json_encode(array('result'=>0,'message'=>'身份证号保存失败!')));
                }
                //创建图片保存路径
                $SmallPaths = THINK_PATH.'../Upload/file/'.$UserID.'/';
                if(!is_dir($SmallPaths)){
                    mkdir($SmallPaths,0644,true);
                }
                $sdata=array();
                $sdata['UserID']=$UserID;
                //保存三张照片
                $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                    ),
                );
                if($para['data']['url_frontcard']){
                    //身份证正面
                    $fronturl=$SmallPaths.'sfzzm_'.$UserID.'.jpg';
                    $img0 = file_get_contents($para['data']['url_frontcard'], false, stream_context_create($arrContextOptions));
                    file_put_contents($fronturl,$img0); 
                    $sdata['CardFace']="/Upload/file/".$UserID.'/'.'sfzzm_'.$UserID.".jpg";//保存的路径
                }
                if($para['data']['url_backcard']){
                    //身份证背面
                    $backurl=$SmallPaths.'sfzfm_'.$UserID.'.jpg';
                    $img = file_get_contents($para['data']['url_backcard'], false, stream_context_create($arrContextOptions));
                    file_put_contents($backurl,$img); 
                    $sdata['CardSide']="/Upload/file/".$UserID.'/'.'sfzfm_'.$UserID.".jpg";//保存的路径
                }
                if($para['data']['url_photoliving']){
                    //人脸采集照片
                    $handurl=$SmallPaths.'sfz_'.$UserID.'.jpg';
                    $img1 = file_get_contents($para['data']['url_photoliving'], false, stream_context_create($arrContextOptions));
                    file_put_contents($handurl,$img1); 
                    $sdata['Cardschi']="/Upload/file/".$UserID.'/'.'sfz_'.$UserID.".jpg";//保存的路径
                }
                $sdata['Birthday']=str_replace('.','-',$para['data']['date_birthday']);
                $sdata['Status']='1';
                $sdata['RenzTime']=date('Y-m-d H:i:s');
                $sdata['RenzResult']=serialize($para['data']);
                //------获取有盾数据
                $ydundata=$this->getcardinfos($para['data']['id_no']);
                if($ydundata['header']['ret_code']=='000000'){
                    //表示数据请求成功
                    $sdata['Yddatas']=serialize($ydundata['body']);
                }
                //查看有没有,有就更新,没有就添加
                $checkrest=M(self::T_CARDS)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
                if($checkrest){
                    $result=M(self::T_CARDS)->where(array('ID'=>$checkrest['ID']))->save($sdata);
                }else{
                    $result=M(self::T_CARDS)->add($sdata);
                }
                if($result){
                    exit(json_encode(array('result'=>1,'message'=>'认证成功!')));
                }else{
                    exit(json_encode(array('result'=>0,'message'=>'认证添加数据失败!')));
                }
            }else{
                exit(json_encode(array('result'=>0,'message'=>'身份证号码不正确!')));
            }
        }else{
            exit(json_encode(array('result'=>0,'message'=>'认证失败!')));
        }
    }
    /**
     * @功能说明: 基本信息认证数据保存
     * @传输方式: post
     * @提交网址: /center/Renzen/memberinforz
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function memberinforz(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');
        $para2=$para['data'];
        //校验
        if(!$para2['InCome']){
            exit(json_encode(array('result'=>0,'message'=>'收入不能空!')));
        }
        if(!$para2['Education']){
            exit(json_encode(array('result'=>0,'message'=>'学历不能空!')));
        }
        if(!$para2['ZhiYe']){
            exit(json_encode(array('result'=>0,'message'=>'职业不能空!')));
        }
        if(!$para2['JAddress']){
            exit(json_encode(array('result'=>0,'message'=>'家庭地址不能空!')));
        }
        if(!$para2['JZTime']){
            exit(json_encode(array('result'=>0,'message'=>'居住时长不能空!')));
        }
        if(!$para2['CompanyName']){
            exit(json_encode(array('result'=>0,'message'=>'单位名称不能空!')));
        }
        if(!$para2['CompanyMobile']){
            exit(json_encode(array('result'=>0,'message'=>'单位电话不能空!')));
        }
        if(!$para2['CompanyAddress']){
            exit(json_encode(array('result'=>0,'message'=>'单位地址不能空!')));
        }
        $sdata=array(
            'UserID'=>$UserID,
            'InCome'=>$para2['InCome'],
            'Education'=>$para2['Education'],
            'ZhiYe'=>$para2['ZhiYe'],
            'JTProvinceID'=>$para2['JTProvinceID'],
            'JTCityID'=>$para2['JTCityID'],
            'JTDisID'=>$para2['JTDisID'],
            'JAddress'=>$para2['JAddress'],
            'JZTime'=>$para2['JZTime'],
            'CompanyName'=>$para2['CompanyName'],
            'CompanyMobile'=>$para2['CompanyMobile'],
            'CompanyAddress'=>$para2['CompanyAddress'],
            'GZProvinceID'=>$para2['GZProvinceID'],
            'GZCityID'=>$para2['GZCityID'],
            'GZDisID'=>$para2['GZDisID'],
            'Status'=>'1',
            'RenzTime'=>date('Y-m-d H:i:s'),
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_MEMBERINFO)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_MEMBERINFO)->where(array('ID'=>$checkrest['ID']))->save($sdata);
        }else{
            $result=M(self::T_MEMBERINFO)->add($sdata);
        }
        if($result){
            exit(json_encode(array('result'=>1,'message'=>'认证成功!')));
        }else{
            exit(json_encode(array('result'=>0,'message'=>'认证添加数据失败!')));
        }
    }
     /**
     * @功能说明: 社交认证关系数据保存
     * @传输方式: post
     * @提交网址: /center/Renzen/socialgx
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1","data":} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function socialgx(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');
        $para2=$para['data'];
        //校验
        if(!$para2['qinshu']){
            exit(json_encode(array('result'=>0,'message'=>'亲属关系数据不能空!')));
        }
        if(!$para2['shehui']){
            exit(json_encode(array('result'=>0,'message'=>'社会关系数据不能空!')));
        }

        //过滤通讯录姓名中的表情
        foreach ($para2['qinshu'] as $index => &$item) {
            $item['name'] = emoji_reject($item['name']);
        }
        foreach ($para2['shehui'] as $index => &$item) {
            $item['name'] = emoji_reject($item['name']);
        }
        $Contents = serialize($para2);

        $sdata=array(
            'UserID'=>$UserID,
            'Contents'=>$Contents,
            'Status'=>'0',
            'RenzTime'=>date('Y-m-d H:i:s'),
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_SOCIAL)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_SOCIAL)->where(array('ID'=>$checkrest['ID']))->save($sdata);
        }else{
            $result=M(self::T_SOCIAL)->add($sdata);
        }
        if($result){
            exit(json_encode(array('result'=>1,'message'=>'认证成功!')));
        }else{
            exit(json_encode(array('result'=>0,'message'=>'认证添加数据失败!')));
        }
    }
     /**
     * @功能说明: 社交认证手机通讯保存
     * @传输方式: post
     * @提交网址: /center/Renzen/socialtx
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1","QQ":"123456","WeChat":"123456@qq.com","data":} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function socialtx(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');
        $para2=$para['data'];
        //校验
        if(!$para['QQ']){
            exit(json_encode(array('result'=>0,'message'=>'QQ必须提交!')));
        }
        if(!$para['WeChat']){
            exit(json_encode(array('result'=>0,'message'=>'微信必须提交!')));
        }
        if(!$para2){
            exit(json_encode(array('result'=>0,'message'=>'手机通讯录必须提交!')));
        }
        //过滤通讯录姓名中的表情
        foreach ($para2 as $index => &$item) {
            $item['name'] = emoji_reject($item['name']);
        }
        $Contents = serialize($para2);
        $sdata=array(
            'QQ'=>$para['QQ'],
            'WeChat'=>$para['WeChat'],
            'Phonelist'=>$Contents,
            'Status'=>'1',
            'RenzTime'=>date('Y-m-d H:i:s'),
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_SOCIAL)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_SOCIAL)->where(array('ID'=>$checkrest['ID']))->save($sdata);
            if($result){
                exit(json_encode(array('result'=>1,'message'=>'认证成功!')));
            }else{
                exit(json_encode(array('result'=>0,'message'=>'认证添加数据失败!')));
            }
        }else{
            exit(json_encode(array('result'=>0,'message'=>'未查询到认证记录!')));
        }
    }
    /**
     * @功能说明: 获取手机认证h5地址
     * @传输方式: post
     * @提交网址: /center/Renzen/mobilezr
     * @提交方式: {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function mobilezr(){
        $para = get_json_data();
        $UserID=get_login_info('ID');
        $meminfos=M(self::T_TABLE)->field('ID,Mobile,IDCard,TrueName')->where(array("ID"=>$UserID))->find();
        if(!$meminfos['Mobile'] || !$meminfos['IDCard'] || !$meminfos['TrueName']){
            exit(json_encode(array('result'=>0,'message'=>'先进行身份证认证!')));
        }
        $infos=M('sys_inteparameter')->where(array('IntegrateID'=>'10'))->select();
        if($infos){
            $urls='';
            $token='';
            foreach($infos as $k=>$v){
                if($v['ParaName']=='url'){
                    $urls=$v['ParaValue'];
                }
                if($v['ParaName']=='token'){
                    $token=$v['ParaValue'];
                }
            }
            $cburl="http://".$_SERVER['HTTP_HOST']."/Register/index?BackSuccess?type=1";
            $newurl=$urls.'?box_token='.$token.'&real_name='.$meminfos['TrueName'].'&identity_code='.$meminfos['IDCard'].'&user_mobile='.$meminfos['Mobile'].'&cb='.urlencode($cburl);
            $retdata=array(
                'urls'=>$newurl,
                );
            $data=array(
                'result'=>1,
                'message'=>'恭喜您，获取成功！',
                'data'=>$retdata,
            );
            //判断有没有此记录，状态要是 认证失败 就修改为  待审核
            // $checkdata=M('renzen_mobile')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
            // if($checkdata['Status']=='2'){
            //     M('renzen_mobile')->where(array('ID'=>$checkdata['ID']))->save(array('Status'=>'0'));
            // }
            exit(json_encode($data));
        }else{
            $data=array(
                'result'=>0,
                'message'=>'暂无内容！',
            );
            exit(json_encode($data));
        }
    }
    /**
     * @功能说明: 手机认证(手机认证成功之后调)
     * @传输方式: post
     * @提交网址: /center/Renzen/mobilezr2
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function mobilezr2(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');

        $sdata=array(
            'UserID'=>$UserID,
            'Status'=>'1',
            'RenzTime'=>date('Y-m-d H:i:s'),
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_MOBILE)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_MOBILE)->where(array('ID'=>$checkrest['ID']))->save($sdata);
        }else{
            $result=M(self::T_MOBILE)->add($sdata);
        }
        if($result){
            exit(json_encode(array('result'=>1,'message'=>'保存成功!')));
        }else{
            exit(json_encode(array('result'=>0,'message'=>'保存数据失败!')));
        }
    }
    /**
     * @功能说明: 淘宝认证taskid保存
     * @传输方式: post
     * @提交网址: /center/Renzen/taobaorz
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1","taskid":"123456"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function taobaorz(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');
        //校验
        if(!$para['taskid']){
            exit(json_encode(array('result'=>0,'message'=>'taskid不能为空!')));
        }
        $sdata=array(
            'UserID'=>$UserID,
            'TaskID'=>$para['taskid'],
            'Status'=>'0',
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_TAOBAO)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_TAOBAO)->where(array('ID'=>$checkrest['ID']))->save($sdata);
        }else{
            $result=M(self::T_TAOBAO)->add($sdata);
        }
        if($result){
            exit(json_encode(array('result'=>1,'message'=>'保存成功!')));
        }else{
            exit(json_encode(array('result'=>0,'message'=>'保存数据失败!')));
        }
    }
    /**
     * @功能说明: 支付宝认证taskid保存
     * @传输方式: post
     * @提交网址: /center/Renzen/alipayrz
     * @提交信息:  {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1","taskid":"123456"} 
     * @提交信息说明:
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function alipayrz(){
        //获取数据流
        $para = get_json_data();
        $UserID=get_login_info('ID');
        //校验
        if(!$para['taskid']){
            exit(json_encode(array('result'=>0,'message'=>'taskid不能为空!')));
        }
        $sdata=array(
            'UserID'=>$UserID,
            'TaskID'=>$para['taskid'],
            'Status'=>'0',
            );
        //查看有没有,有就更新,没有就添加
        $checkrest=M(self::T_ALIPAY)->field('ID')->where(array('UserID'=>$UserID,'IsDel'=>'0'))->find();
        if($checkrest){
            $result=M(self::T_ALIPAY)->where(array('ID'=>$checkrest['ID']))->save($sdata);
        }else{
            $result=M(self::T_ALIPAY)->add($sdata);
        }
        if($result){
            exit(json_encode(array('result'=>1,'message'=>'保存成功!')));
        }else{
            exit(json_encode(array('result'=>0,'message'=>'保存数据失败!')));
        }
    }
    /**
     * @功能说明: 银行卡认证(获取手机验证码)
     * @传输方式: post
     * @提交网址: /center/Renzen/bankrz1
     * @提交信息:  {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"BankNo":"6217856100055140922","YMobile":"18225901593","ProvinceID":"0","CityID":"0","CountyID":"0"}}
     * @提交信息说明:  msgcode:手机验证码  如果是空，就是获取手机验证   不为空，就是提交保存信息
     *   BankNo：银行卡号  YMobile：银行预留手机号码
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function bankrz1(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $meminfos=M('mem_info')->field('Mobile,TrueName,IDCard')->where(array('ID'=>$mem['ID']))->find();
        if(!$meminfos['TrueName'] || !$meminfos['IDCard']){
            AjaxJson(0,0,'请先身份认证！',$retdata);
        }
        if(!$para['BankNo']){
            AjaxJson(0,0,'银行卡号必须填写！');
        }
        if(!$para['YMobile']){
            AjaxJson(0,0,'预留手机号码必须填写！');
        }
        if(!$para['ProvinceID']){
            AjaxJson(0,0,'开户行省份必须选择！');
        }
        if(!$para['CityID']){
            AjaxJson(0,0,'开户行市区必须选择！');
        }
        //预签约保存银行卡
        $config=$this->getsets();
        $merchant_id    =$config['merchant_id'];              //商户号
        $merchantPrivateKey=$config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$config['apiKey'];                   //apiKey
        //持卡人账户基本信息一致性与有效性的验证,
        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'member_id' => $mem['ID'],    //会员ID
            'order_no' =>date(ymd).rand(1,9).date(His).rand(111,999),             //订单号
            'card_no' => $para['BankNo'],                 //银行卡号
            'owner' => $meminfos['TrueName'],                   //银行卡开户姓名
            'cert_no' => $meminfos['IDCard'],                   //身份证号
            'cert_type' => '01',                            //身份证号
            'phone'=> $para['YMobile'],                      //银行预留手机号
            'sign_type' => 'RSA',                           //签名类型
            'version' => '1.0.0'                            //版本号
        );
        //请求接口
        $url = $config['url'].'/delivery/authentication';
        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);
        if ($jsonObject){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)) {
                //短信发送成功  保存此记录到 xb_renzen_bank
                $provincename=M('sys_areas')->where(array('ID'=>$para['ProvinceID']))->getField('Name');
                $cityname=M('sys_areas')->where(array('ID'=>$para['CityID']))->getField('Name');
                $countyname=M('sys_areas')->where(array('ID'=>$para['CountyID']))->getField('Name');
                $bankdata=array(
                    'OrderSn'=>$paramArr['order_no'],
                    'BankNo'=>$para['BankNo'],
                    'YMobile'=>$para['YMobile'],
                    'BankName'=>$jsonObject['bank_name'],
                    'OpenBankName'=>$jsonObject['bank_name'],
                    'Address'=>$provincename.$cityname.$countyname,
                    'ProvinceID'=>$para['ProvinceID'],
                    'CityID'=>$para['CityID'],
                    'CountyID'=>$para['CountyID'],
                    'Status'=>'0',
                    );
                //查询是否已有认证数据，有就更新，没有就添加
                $checkdata=M('renzen_bank')->field('ID')->where(array('UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
                $returnid='';
                if($checkdata){
                    //更新
                    $results=M('renzen_bank')->where(array('ID'=>$checkdata['ID']))->save($bankdata);
                    $returnid=$checkdata['ID'];
                }else{
                    //添加
                    $bankdata['UserID']=$mem['ID'];
                    $results=M('renzen_bank')->add($bankdata);
                    $returnid=$results;
                }
                if($results){
                    $retdata=array(
                        'id'=>$returnid,
                        'ordersn'=>$paramArr['order_no'],
                        );
                    AjaxJson(0,1,'短信发送成功!',$retdata);
                }else{
                    AjaxJson(0,0,'数据保存失败，请联系客服!');
                }
            }else{
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            AjaxJson(0,0,'提交的数据有误');
        }
    }
    /**
     * @功能说明: 银行卡认证(预签约短信验证,提交保存操作)
     * @传输方式: post
     * @提交网址: /center/Renzen/bankrz2
     * @提交信息:  {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"msgcode":"6217856100055140922","id":"2","ordersn":"26584598520"}}
     * @提交信息说明:  msgcode:手机验证码  id：认证id  ordersn：认证编号
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function bankrz2(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $bankrzs=M('renzen_bank')->where(array('ID'=>$para['id'],'UserID'=>$mem['ID'],'OrderSn'=>$para['ordersn'],'IsDel'=>'0'))->find();
        if(!$bankrzs){
            AjaxJson(0,0,'银行卡认证信息异常!');
        }
        if(!$para['msgcode']){
            AjaxJson(0,0,'验证码必须提交!');
        }
        //预签约短信验证
        $config=$this->getsets();
        $merchant_id    =$config['merchant_id'];              //商户号
        $merchantPrivateKey=$config['merchantPrivateKey'];    //商户私钥
        $reapalPublicKey=$config['reapalPublicKey'];          //融宝公钥
        $apiKey         =$config['apiKey'];                   //apiKey
        //参数数组
        $paramArr = array(
            'merchant_id' => $merchant_id,
            'order_no' =>$bankrzs['OrderSn'],
            'check_code' => $para['msgcode'],
            'sign_type' => 'RSA',
            'version' => '1.0.0'

        );
        //访问储蓄卡签约服务
        $url = $config['url'].'/delivery/sign';

        $Rbpay=new \Extend\Rbpay();
        $jsonObject = $Rbpay->verifypay($paramArr, $url, $merchantPrivateKey , $reapalPublicKey, $merchant_id);

        if($jsonObject){
            $sign= $jsonObject['sign'];
            if($jsonObject['result_code']=="0000" && $Rbpay->verify_rsa($jsonObject,$sign,$reapalPublicKey,$merchantPrivateKey)){
                //签约成功 更新xb_renzen_bank
                M('renzen_bank')->where(array('ID'=>$bankrzs['ID']))->save(array('Status'=>'1','RenzTime'=>date('Y-m-d H:i:s')));
                //把此银行卡添加到xb_rongbao_agree中
                $meminfos=M('mem_info')->field('Mobile,TrueName,IDCard')->where(array('ID'=>$mem['ID']))->find();
                $data=array(
                    'UserID'=>$mem['ID'],
                    'RealName'=>$meminfos['TrueName'],
                    'CardID'=>$meminfos['IDCard'],
                    'BankCode'=>$bankrzs['BankNo'],
                    'Mobile'=>$bankrzs['YMobile'],
                    'AgreeNo'=>$jsonObject['sign_no'],
                    'Addtime'=>date('Y-m-d H:i:s'),
                    'Status'=>'3',
                );
                M('rongbao_agree')->where(array('UserID'=>$mem['ID'],'BankCode'=>$bankrzs['BankNo']))->delete();
                M('rongbao_agree')->add($data);
                AjaxJson(0,1,'认证成功!');
            }else{
                //记录预签约状态
                M('renzen_bank')->where(array('ID'=>$bankrzs['ID']))->save(array('Status'=>'2','RenzTime'=>date('Y-m-d H:i:s')));
                AjaxJson(0,0,$jsonObject['result_msg']);
            }
        }else{
            M('renzen_bank')->where(array('ID'=>$bankrzs['ID']))->save(array('Status'=>'2','RenzTime'=>date('Y-m-d H:i:s')));
            AjaxJson(0,0,'请求的参数有误');
        }
    }
    //获取配置信息 融宝支付
    public function getsets(){
        //配置参数
        $setsinfo=M('sys_inteparameter')->where(array('IntegrateID'=>'11'))->select();
        $setArr=array();
        foreach($setsinfo as $k=>$v){
            $setArr[$v['ParaName']]=$v['ParaValue'];
        }
        $config = array(
            'merchant_id' => $setArr['merchant_id'],    //商户号ID
            'seller_email' => $setArr['seller_email'],    //商户邮箱
            'merchantPrivateKey' => "./rongbao/user-rsa.pem",   //商户私钥路径
            'reapalPublicKey' => "./rongbao/public-rsa.pem",    //融宝公钥路径
            'apiKey' => $setArr['apiKey'],      //apikey
            'url'=>$setArr['url'],
        );
        return $config;
    }
    /**
     * @功能说明: 获取基本信息选项列表
     * @传输方式: get提交
     * @提交网址: /center/Renzen/itemlist
     * @提交方式: client=android&package=android.ceshi&ver=v1.1&propertyid=1
     *  propertyid 属性设置id   1职业 2收入 3亲属关系 4社会关系 5学历
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function itemlist(){
        $para = I('get.');
        if(!$para['propertyid']){
            $para['propertyid']=1;
        }
        $infoslist=M('sys_propertyparam')->field('ID,Name')->where(array('PropertyID'=>$para['propertyid'],'Status'=>'1','IsDel'=>'0'))->order('Sort asc,ID DESC')->select();
        if($infoslist){
            $data=array(
                'result'=>1,
                'message'=>'恭喜您，获取成功！',
                'data'=>$infoslist
            );
            exit(json_encode($data));
        }else{
            $data=array(
                'result'=>0,
                'message'=>'暂无内容！',
            );
            exit(json_encode($data));
        }
    }
    //---------有盾数据获取
    //获取有盾认证信息 $cardid
    public function getcardinfos($cardid){
        $setinfos=M('sys_inteparameter')->where(array('IntegrateID'=>'9'))->select();
        $secretkey='';
        $pubkey='';
        foreach($setinfos as $k=>$v){
            if($v['ParaName']=='api_key'){
                $pubkey=$v['ParaValue'];
            }
            if($v['ParaName']=='security_key'){
                $secretkey=$v['ParaValue'];
            }
        }
        $data=array(
            'id_no'=>$cardid,
            );
        $str = json_encode($data,JSON_UNESCAPED_UNICODE);
        $url = 'https://api4.udcredit.com/dsp-front/4.1/dsp-front/default/pubkey/'.$pubkey.'/product_code/Y1001005/out_order_id/';
        $url .= md5(time() . mt_rand(0,1000)).'/signature/'.strtoupper(md5($str."|".$secretkey));
        $header = ['Content-Type: application/json; charset=utf-8'];
        $res =$this->curl_send_post($url,$data,$header);
        return $res;
    }
    public function curl_send_post($url,$data,$header = array()){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>json_encode($data,JSON_UNESCAPED_UNICODE),
            CURLOPT_SSL_VERIFYPEER => false, // 跳过证书检查
            CURLOPT_SSL_VERIFYHOST => CURLOPT_SSL_VERIFYHOST,
            CURLOPT_HTTPHEADER =>$header
        ));
        $res = curl_exec($curl);
        curl_close($curl);
        return json_decode($res,true);
    }
     /**
     * @功能说明: 银行卡认证-直接数据保存
     * @传输方式: post
     * @提交网址: /center/Renzen/bankrz3
     * @提交信息:  {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"BankNo":"6217856100055140922","YMobile":"18225901593","OpenBankName":"招商银行","Address":"省市区名称拼在一起","ProvinceID":"0","CityID":"0","CountyID":"0"}}
     * @提交信息说明:BankNo：银行卡号  YMobile：银行预留手机号码 OpenBankName开户行名称
     *   Address 省市区拼接到一起的文字(安徽省合肥市瑶海区)
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function bankrz3(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $meminfos=M('mem_info')->field('Mobile,TrueName,IDCard')->where(array('ID'=>$mem['ID']))->find();
        if(!$meminfos['TrueName'] || !$meminfos['IDCard']){
            AjaxJson(0,0,'请先身份认证！',$retdata);
        }
        if(!$para['BankNo']){
            AjaxJson(0,0,'银行卡号必须填写！');
        }
        if(!is_numeric($para['BankNo'])){
            AjaxJson(0,0,'银行卡号填写不正确！');
        }
        if(!$para['YMobile']){
            AjaxJson(0,0,'预留手机号码必须填写！');
        }
        if(!$para['ProvinceID']){
            AjaxJson(0,0,'开户行省份必须选择！');
        }
        if(!$para['CityID']){
            AjaxJson(0,0,'开户行市区必须选择！');
        }
        if(!$para['OpenBankName']){
            AjaxJson(0,0,'开户行名称必须填写！');
        }
        if(!$para['Address']){
            AjaxJson(0,0,'开户行地址必须选择！');
        }

        $bankdata=array(
            'BankNo'=>$para['BankNo'],
            'YMobile'=>$para['YMobile'],
            'OpenBankName'=>$para['OpenBankName'],
            'Address'=>$para['Address'],
            'ProvinceID'=>$para['ProvinceID'],
            'CityID'=>$para['CityID'],
            'Status'=>'1',
            'RenzTime'=>date('Y-m-d H:i:s'),
            'UpdateTime'=>date('Y-m-d H:i:s'),
            );
        if($para['CountyID']){
            $bankdata['CountyID']=$para['CountyID'];
        }
        //查询是否已有认证数据，有就更新，没有就添加
        $checkdata=M('renzen_bank')->field('ID')->where(array('UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
        if($checkdata){
            //更新
            $results=M('renzen_bank')->where(array('ID'=>$checkdata['ID']))->save($bankdata);
        }else{
            //添加
            $bankdata['UserID']=$mem['ID'];
            $results=M('renzen_bank')->add($bankdata);
        }
        if($results){
            AjaxJson(0,1,'银行卡认证成功!');
        }else{
            AjaxJson(0,0,'银行卡认证失败!');
        }      
    }
    //------银行卡认证--------合利宝----------------------------------------start
    /**
     * @功能说明: 银行卡认证(鉴权绑卡短信)
     * @传输方式: post
     * @提交网址: /center/Renzen/heli_bankrz1
     * @提交信息:  {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"BankNo":"6217856100055140922","YMobile":"18225901593","ProvinceID":"0","CityID":"0","CountyID":"0"}}
     * @提交信息说明:
     *   BankNo：银行卡号  YMobile：银行预留手机号码
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function heli_bankrz1(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $meminfos=M('mem_info')->field('Mobile,TrueName,IDCard')->where(array('ID'=>$mem['ID']))->find();
        if(!$meminfos['TrueName'] || !$meminfos['IDCard']){
            AjaxJson(0,0,'请先身份认证！',$retdata);
        }
        if(!$para['BankNo']){
            AjaxJson(0,0,'银行卡号必须填写！');
        }
        if(!$para['YMobile']){
            AjaxJson(0,0,'预留手机号码必须填写！');
        }
        // if(!$para['ProvinceID']){
        //     AjaxJson(0,0,'开户行省份必须选择！');
        // }
        // if(!$para['CityID']){
        //     AjaxJson(0,0,'开户行市区必须选择！');
        // }
        //预签约保存银行卡
        $config=$this->helibaosets();
        $merchant_id    =$config['merchant_id'];              //商户号
        $signkey_quickpay=$config['signkey'];    //签名密钥
        $url=$config['url'];          //请求地址

        //鉴权绑卡短信
        $P1_bizType = 'AgreementPayBindCardValidateCode';
        $P2_customerNumber =$merchant_id;//商户号
        $P3_userId =  $mem['ID'];
        $P4_orderId =  date('ymd').rand(1,9).date('His').rand(111,999);
        $P5_timestamp = date('Ymdhis',time());//时间戳
        $P6_cardNo= trim($para['BankNo']);//银行卡号
        $P7_phone = trim($para['YMobile']);//银行预留手机号
        $P8_idCardNo = trim($meminfos['IDCard']);//身份证号
        $P9_idCardType='IDCARD';
        $P10_payerName=$meminfos['TrueName'];

        //构造支付签名串
        $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_cardNo&$P7_phone&$P8_idCardNo&$P9_idCardType&$P10_payerName&$signkey_quickpay";
        
        $sign= md5($signFormString);//MD5签名 request url
        //post的参数
        $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_cardNo'=>$P6_cardNo,'P7_phone'=>$P7_phone,'P8_idCardNo'=>$P8_idCardNo,'P9_idCardType'=>$P9_idCardType,'P10_payerName'=>$P10_payerName,'sign'=>$sign);
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        $retdata=array(
            'timestamps'=>$P5_timestamp,//时间戳
            'ordersn'=>$P4_orderId,//订单号
            );
        if($result['rt2_retCode']=='0000'){
            AjaxJson(0,1,$result['rt3_retMsg'],$retdata);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg'],$retdata);
        }
    }
    /**
     * @功能说明: 银行卡认证(鉴权绑卡)
     * @传输方式: post
     * @提交网址: /center/Renzen/heli_bankrz2
     * @提交信息:  {"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"BankNo":"6217856100055140922","YMobile":"18225901593","ProvinceID":"0","CityID":"0","CountyID":"0","timestamps":"","ordersn":"","msgcode":"","OpenBankName":""}}
     * @提交信息说明:
     *   BankNo：银行卡号  YMobile：银行预留手机号码  timestamps时间戳(上接口返回) ordersn订单编号(上接口返回)  msgcode短信验证码  OpenBankName开户行
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function heli_bankrz2(){
        $para=get_json_data();//接收参数
        $mem = getUserInfo(get_login_info('ID'));
        //校验
        $meminfos=M('mem_info')->field('Mobile,TrueName,IDCard')->where(array('ID'=>$mem['ID']))->find();
        if(!$meminfos['TrueName'] || !$meminfos['IDCard']){
            AjaxJson(0,0,'请先身份认证！',$retdata);
        }
        if(!$para['BankNo']){
            AjaxJson(0,0,'银行卡号必须填写！');
        }
        if(!$para['YMobile']){
            AjaxJson(0,0,'预留手机号码必须填写！');
        }
        // if(!$para['ProvinceID']){
        //     AjaxJson(0,0,'开户行省份必须选择！');
        // }
        // if(!$para['CityID']){
        //     AjaxJson(0,0,'开户行市区必须选择！');
        // }
        if(!$para['timestamps']){
            AjaxJson(0,0,'时间戳必须传递！');
        }
        if(!$para['ordersn']){
            AjaxJson(0,0,'订单编号必须传递！');
        }
        if(!$para['msgcode']){
            AjaxJson(0,0,'短信验证码必须填写！');
        }
        // if(!$para['OpenBankName']){
        //     AjaxJson(0,0,'开户行必须填写！');
        // }
        //预签约保存银行卡
        $config=$this->helibaosets();
        $merchant_id    =$config['merchant_id'];              //商户号
        $signkey_quickpay=$config['signkey'];    //签名密钥
        $url=$config['url'];          //请求地址

        $P1_bizType='QuickPayBindCard';
        $P2_customerNumber=$merchant_id;
        $P3_userId=$mem['ID'];
        $P4_orderId=$para['ordersn'];//订单号
        $P5_timestamp=$para['timestamps'];//时间戳
        $P6_payerName=$meminfos['TrueName'];
        $P7_idCardType='IDCARD';
        $P8_idCardNo=$meminfos['IDCard'];
        $P9_cardNo=$para['BankNo'];
        $P10_year='';
        $P11_month='';
        $P12_cvv2='';
        $P13_phone=$para['YMobile'];
        $P14_validateCode=$para['msgcode'];//短信验证码

        //构造支付签名串
        $signFormString = "&$P1_bizType&$P2_customerNumber&$P3_userId&$P4_orderId&$P5_timestamp&$P6_payerName&$P7_idCardType&$P8_idCardNo&$P9_cardNo&$P10_year&$P11_month&$P12_cvv2&$P13_phone&$P14_validateCode&$signkey_quickpay";
        
        $sign= md5($signFormString);//MD5签名
        //post的参数
        $params = array('P1_bizType'=>$P1_bizType,'P2_customerNumber'=>$P2_customerNumber,'P3_userId'=>$P3_userId,'P4_orderId'=>$P4_orderId,'P5_timestamp'=>$P5_timestamp,'P6_payerName'=>$P6_payerName,'P7_idCardType'=>$P7_idCardType,'P8_idCardNo'=>$P8_idCardNo,'P9_cardNo'=>$P9_cardNo,'P10_year'=>$P10_year,'P11_month'=>$P11_month,'P12_cvv2'=>$P12_cvv2,'P13_phone'=>$P13_phone,'P14_validateCode'=>$P14_validateCode,'sign'=>$sign);
        //$pageContents = \Extend\HttpClient::quickPost($url, $params);  //发送请求 send request
        $pageContents = $this->sendHttpRequest($params,$url);  //发送请求 send request
        $result=json_decode($pageContents,true);
        if($result['rt2_retCode']=='0000'){
            // $provincename=M('sys_areas')->where(array('ID'=>$para['ProvinceID']))->getField('Name');
            // $cityname=M('sys_areas')->where(array('ID'=>$para['CityID']))->getField('Name');
            // $countyname=M('sys_areas')->where(array('ID'=>$para['CountyID']))->getField('Name');
            $bankdata=array(
                'OrderSn'=>$para['ordersn'],
                'BankNo'=>$para['BankNo'],
                'YMobile'=>$para['YMobile'],
                'BankName'=>$result['rt8_bankId'],
                //'OpenBankName'=>$para['OpenBankName'],
                //'Address'=>$provincename.$cityname.$countyname,
                // 'ProvinceID'=>$para['ProvinceID'],
                // 'CityID'=>$para['CityID'],
                // 'CountyID'=>$para['CountyID'],
                'Status'=>'1',
                'RenzTime'=>date('Y-m-d H:i:s'),
                );
            //查询是否已有认证数据，有就更新，没有就添加
            $checkdata=M('renzen_bank')->field('ID')->where(array('UserID'=>$mem['ID'],'IsDel'=>'0'))->find();
            $returnid='';
            if($checkdata){
                //更新
                $results2=M('renzen_bank')->where(array('ID'=>$checkdata['ID']))->save($bankdata);
            }else{
                //添加
                $bankdata['UserID']=$mem['ID'];
                $results2=M('renzen_bank')->add($bankdata);
            }
            if($results2){
                //保存到 xb_rongbao_agree表里
                $bankdata=array(
                    'UserID'=>$mem['ID'],
                    'RealName'=>$meminfos['TrueName'],
                    'CardID'=>$meminfos['IDCard'],
                    'BankCode'=>$para['BankNo'],
                    'Mobile'=>$para['YMobile'],
                    'AgreeNo'=>$result['rt10_bindId'],
                    'Addtime'=>date('Y-m-d H:i:s'),
                    'Status'=>'3',
                    );
                M('rongbao_agree')->where(array('UserID'=>$mem['ID'],'BankCode'=>$para['BankNo']))->delete();
                M('rongbao_agree')->add($bankdata);
            }
            AjaxJson(0,1,$result['rt3_retMsg']);
        }else{
            AjaxJson(0,0,$result['rt3_retMsg']);
        }
    }
    //获取合利宝配置
    public function helibaosets(){
        $paraters=M('sys_inteparameter')->field('ParaName,ParaValue')->where(array('IntegrateID'=>'14'))->select();
        $merchant_id='';
        $signkey='';
        $url='';
        foreach($paraters as $k=>$v){
            if($v['ParaName']=='merchant_id'){
                $merchant_id=$v['ParaValue'];
            }elseif($v['ParaName']=='signkey'){
                $signkey=$v['ParaValue'];
            }elseif($v['ParaName']=='url'){
                $url=$v['ParaValue'];
            }
        }
        return array('merchant_id'=>$merchant_id,'signkey'=>$signkey,'url'=>$url);
    }
    //可用
   private function sendHttpRequest($data = null,$url)
    {
        $data = $this->buildQueryString ( $data );
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type:application/x-www-form-urlencoded;charset=UTF-8"));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //$output = trim($output, "\xEF\xBB\xBF");//php去除bom头

        return $output;
       // return json_decode($output,true);
    }
    function buildQueryString($data) {
        $querystring = '';
        if (is_array($data)) {
            // Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        $querystring .= urlencode($key).'='.urlencode($val2).'&';
                    }
                } else {
                    $querystring .= urlencode($key).'='.urlencode($val).'&';
                }
            }
            $querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
        } else {
            $querystring = $data;
        }
        return $querystring;
    }
    //------银行卡认证--------合利宝----------------------------------------end



    /**
     * @功能说明: 获取(天机)手机认证h5地址
     * @传输方式: post
     * @提交网址: /center/Renzen/mobiletianji
     * @提交方式: {"token":"bce2675771dc92aa4d1818cf3c5e6c6fe7d9ca5e8b3d9044e1b1b57ddc11","client":"android","package":"android.ceshi","ver":"v1.1"}
     * @返回方式: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function mobiletianji(){
        $para = get_json_data();
        $UserID=get_login_info('ID');
        $meminfos=M(self::T_TABLE)->field('ID,Mobile,IDCard,TrueName')->where(array("ID"=>$UserID))->find();
        if(!$meminfos['Mobile'] || !$meminfos['IDCard'] || !$meminfos['TrueName']){
            exit(json_encode(array('result'=>0,'message'=>'先进行身份证认证!')));
        }
        $infos=M('sys_inteparameter')->where(array('IntegrateID'=>'15'))->select();

        if($infos){
            $urls='';
            $token='';
            foreach($infos as $k=>$v){
                if($v['ParaName']=='AppID'){
                    $AppID=$v['ParaValue'];
                }
                if($v['ParaName']=='url'){
                    $urls=$v['ParaValue'];
                }
                if($v['ParaName']=='method'){
                    $method=$v['ParaValue'];
                }
            }
            $orgPrivateKey=file_get_contents("./tianji/rsa_private_key.pem");
            $bizData['name']=$meminfos['TrueName'];
            $bizData['idNumber']=$meminfos['IDCard'];
            $bizData['phone']=$meminfos['Mobile'];
            $bizData['userId']=$meminfos['ID'];
            $bizData['type']="mobile";
            $bizData['platform']="web";
            $bizData['outUniqueId']=date("YmdHis");
            $bizData['notifyUrl']="http://".$_SERVER['HTTP_HOST'].'/index.php/Tianji/index';
            $bizData['returnUrl']="http://".$_SERVER['HTTP_HOST'].'/index.php/register/index?BackSuccess?type=1';
//            exit(json_encode(array('result'=>0,'message'=>'success','data'=>$bizData)));
//        exit(json_encode(array('result'=>0,'message'=>'success','data'=>$bizData)));

            vendor('Tianji.OpenapiDevBase');
            $openapiDevBase = new \OpenapiDevBase();
            $TianjiData=$openapiDevBase->sendRequest($bizData, $method,$AppID,$urls,$orgPrivateKey);

            if($TianjiData['error']==200){
                $url=$TianjiData['tianji_api_tianjireport_collectuser_response']['redirectUrl'];
                $retdata=array(
                    'urls'=>$url,
                );
                $data=array(
                    'result'=>1,
                    'message'=>'恭喜您，获取成功！',
                    'data'=>$retdata,
                );

            }else{
                $data=array(
                    'result'=>0,
                    'message'=>'配置信息有误，请联系管理员！',
                    'data'=>array(),
                );
            }
            exit(json_encode($data));
        }else{
            $data=array(
                'result'=>0,
                'message'=>'请联系管理员配置相关信息！',
            );
            exit(json_encode($data));
        }
    }



}