<?php

namespace XBCommon;

use Think\Controller;
use Think;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class XBUpload extends Controller
{
    /**
     * 功能说明：input图片上传处理方法
     */
    public function uploadimage(){
        $upload = new Think\Upload(); // 实例化上传类
        $upload->savePath   = 'image/';// 设置原图上传目录
        $upload->replace = true; //存在同名文件是否是覆盖
        // 是否使用子目录保存上传文件
        $upload->autoSub = true;
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $data['result']='fail';
            $data['error']=$upload->getError();
        }else{
            // 上传成功 获取上传文件信息
            // 保存表单数据 包括附件数据
            foreach ($info as $v){
                //判断图片格式和大小限制
                $PicSize=get_basic_info('PicSize')*1000;
                if($v['size']>$PicSize){
                    $arr=array('result'=>0,'message'=>'上传的图片大小,大于设置的值:'.get_basic_info('PicSize').'KB');
                    echo json_encode($arr);
                    exit;
                }
                $PicExt=explode(',',get_basic_info('PicExt'));

                if(!in_array(strtolower($v['ext']),$PicExt)){
                    $arr=array('result'=>0,'message'=>'不支持的图片格式,请上传'.get_basic_info('PicExt').'格式的图片!');
                    echo json_encode($arr);
                    exit;
                }

                $thumbnail=1; //是否生成缩略图，后期可将此开关放在后台设置
                if($thumbnail){
                    //获取原图保存地址
                    $timage ="./Upload/".$v['savepath'].$v['savename'];
                    $image = new Think\Image();
                    $image->open($timage);
                    $size = $image->size(); // 返回图片的尺寸数组 0 图片宽度 1 图片高度
                    //处理路径,过滤第一个反斜杠前面的字符
                    $SavePath=strstr($v['savepath'],'/');
                    $SaveName=$v['savename'];
                    //----- 创建缩略图 -----//
                    // 当图片宽度大于800时，对图片进行压缩
                    $LargePath = "./Upload/large".$SavePath; //生成的中图路径
                    if(!is_dir($LargePath)){
                        mkdir($LargePath,0644,true);
                    }
                    if((int)$size[0]>800) {
                        $this->thumbs($timage, $LargePath.$SaveName, 800, 800);  //压缩
                    }else{
                        $this->thumbs($timage, $LargePath.$SaveName,0,0);  //不压缩
                    }

                    $SmallPaths = "./Upload/small".$SavePath; //生成的小图路径
                    if(!is_dir($SmallPaths)){
                        mkdir($SmallPaths,0644,true);
                    }
                    if((int)$size[0]>400) {
                        $this->thumbs($timage, $SmallPaths.$SaveName, 400, 400);  //压缩
                    }else{
                        $this->thumbs($timage, $SmallPaths.$SaveName,0,0);  //不压缩
                    }
                }
                //记录原图位置，以备返回
                $image_path= "/Upload/" .$v['savepath'].$v['savename'];
            }
            $data['result']='success';
            $data['path']=$image_path;
        }
        return $data;

    }

    /**
     * @功能说明：图片等比例压缩
     * @param $image 需要处理的图片路径
     * @param $spath 压缩图片保存的路径
     * @param int $height 压缩的图片宽度
     * @param int $width  压缩的图片长度
     * @return mixed
     */
    public function thumbs($image,$spath,$width=300,$height=300){ //传入图片
        $Images = new Think\Image(); // 给avator.jpg 图片添加logo水印
        $Images->open($image);
        // 生成一个等比的缩略图并保存
        $Images->thumb($width,$height,\Think\Image::IMAGE_THUMB_SCALE)->save($spath);
        return  $spath; //时间戳加后缀
    }

    /**
     * 功能说明：input文件上传处理方法
     */
    public function uploadfile(){
        $savepath=I("request.Path",'Files','trim');
        $upload = new Think\Upload(); // 实例化上传类
        $upload->savePath   = $savepath.'/'; // 设置附件上传目录
        $upload->replace = true; //存在同名文件是否是覆盖
        // 是否使用子目录保存上传文件
        $upload->autoSub = true;
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $data['result']='fail';
            $data['error']=$upload->getError();
        }else{// 上传成功 获取上传文件信息
            // 保存表单数据 包括附件数据
            foreach ($info as $v){
                //判断文件格式和大小限制
                $FileSize=get_basic_info('FileSize')*1000;
                if($v['size']>$FileSize){
                    $arr=array('result'=>0,'message'=>'上传的文件大小,大于设置的值:'.get_basic_info('FileSize').'KB');
                    echo json_encode($arr);
                    exit;
                }
                $FileExt=explode(',',get_basic_info('FileExt'));
                if(!in_array($v['ext'],$FileExt)){
                    $arr=array('result'=>0,'message'=>'不支持的文件格式,请上传'.get_basic_info('FileExt').'格式的文件!');
                    echo json_encode($arr);
                    exit;
                }
                //文件保存地址
                $image_paths= "./Upload/" .$v['savepath'].$v['savename'];//文件路径
                $res=get_file_ext($image_paths);
                if(empty($res)){
                    $this->DelFile($image_paths);
                    $data['result']='fail';
                    $data['error']='未知文件类型';
                    return $data;
                }
                $image_path= "/Upload/" .$v['savepath'].$v['savename'];//保存文件的路径

            }
            $data['result']='success';
            $data['path']=$image_path;
        }
        return $data;

    }

    public function uploadvideo(){
        $savepath=I("request.Path",'video','trim');
        $upload = new Think\Upload(); // 实例化上传类
        $upload->savePath   = $savepath.'/'; // 设置附件上传目录
        $upload->replace = true; //存在同名文件是否是覆盖
        // 是否使用子目录保存上传文件
        $upload->autoSub = true;
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $data['result']='fail';
            $data['error']=$upload->getError();
        }else{// 上传成功 获取上传文件信息
            // 保存表单数据 包括附件数据
            foreach ($info as $v){
                //判断文件格式和大小限制
                $FileSize=get_basic_info('FileSize')*1000;
                if($v['size']>$FileSize){
                    $arr=array('result'=>0,'message'=>'上传的文件大小,大于设置的值:'.get_basic_info('FileSize').'KB');
                    echo json_encode($arr);
                    exit;
                }
                //文件保存地址
                $image_paths= "./Upload/" .$v['savepath'].$v['savename'];//文件路径
                $res=get_file_ext($image_paths);
                if(empty($res)){
                    $this->DelFile($image_paths);
                    $data['result']='fail';
                    $data['error']='未知文件类型';
                    return $data;
                }
                $image_path= "/Upload/" .$v['savepath'].$v['savename'];//保存文件的路径

            }
            $data['result']='success';
            $data['path']=$image_path;
        }
        return $data;
    }

    /**
     * @功能说明: 分块上传需要与前端的js相匹配使用
     * @return string
     */
    public function cutupload(){
        //获取前端传递的参数
        header('Content-Type:application/json; charset=utf-8');
        $data=I('request.');
        $chunk=$data['chunk']; //当前块数
        $chunks=$data['chunks']; //总的块数
        $NameAdd=$data['NameAdd']; //防重复添加前缀
        $filename=md5($data['name']); //文件名+扩展名的MD5
        $ext=end(explode('.',$data['name'])); //扩展名
        $name=$NameAdd.$filename.'.'.$ext; //前缀_文件名.扩展名
        $path='/Upload/'.$data['Path'].'/'.date('Y-m-d').'/'; //虚拟路径，保存碎片文件
        $uploadpath=$_SERVER['DOCUMENT_ROOT'].'\\Upload\\'.$data['Path'].'\\'.date('Y-m-d').'\\'; //物理路径
        //判断路径是否存在,不存在创建
        if(!file_exists($uploadpath)){
            mkdir($uploadpath);
        }
        $uploadFile=file_get_contents($_FILES['file']['tmp_name']);
        $file='';
        //判断是否需要分块处理
        if($data['chunks']<=1){
            //未分块,直接保存文件
            $file=$uploadpath.$name; //文件路径
            file_put_contents($file, $uploadFile,FILE_APPEND);
           // file_put_contents($file,$uploadFile);
        }else{
            //有分块,保存分块文件
            $file=$uploadpath.$name.'_'.$chunk; //分块文件路径
            file_put_contents($file,$uploadFile,FILE_APPEND);
            //判断是不是最后一个分块
            if($chunks-$chunk==1){
                //合并文件保存
                $newfile=$uploadpath.$name;
                for ($i=0;$i<$chunks;++$i){
                    $part=$uploadpath.$name.'_'.$i;
                    $bytes=file_get_contents($part);
                    file_put_contents($newfile,$bytes,FILE_APPEND); //将分块文件逐个合并
                    unlink($part); //删除已合并的分块文件
                }
            }
        }
        //返回结果
        $data['result']='success';
        $data['path']=$path.$name;
        return $data;
    }

    /**
     * @功能说明:七牛云存储
     * @return bool|string
     */

    public function QiniuUpload($filetype,$file=''){
        if(empty($file)){
            $file=$_FILES;
        }
        //获取配置信息
        $where=array("IntegrateID"=>'6');
        $info=M('sys_inteparameter')->where($where)->select();
        foreach($info as $val){
            if($val['ParaName']=='AppKey') $secretKey=$val['ParaValue'];
            if($val['ParaName']=='AppScript') $accessKey=$val['ParaValue'];
            if(empty($domin)) {
                if ($val['ParaName'] == 'Domain') $domin = $val['ParaValue'];
            }
            if(empty($bucket)) {
                if ($val['ParaName'] =='Bucket') $bucket = $val['ParaValue'];
            }
        }
        $config=array(
            'rootPath' => './Upload/',
            'savePath'=>'image/',
            'saveName' => array ('uniqid', ''),
            'driver' => 'Qiniu',
            'autoSub'=>true,
            'driverConfig' => array (
                'secretKey' => $secretKey,
                'accessKey' => $accessKey,
                'domain' => $domin,
                'bucket' => $bucket,
            ),
        );
        $Upload = new Think\Upload($config);
        $info = $Upload->upload($file);
        if($info){
            foreach($info as $val){
                if($filetype=='image'){
                    //判断图片格式和大小限制
                    $PicSize=get_basic_info('PicSize')*1000;
                    if($val['size']>$PicSize){
                        $arr=array('result'=>0,'message'=>'上传的图片大小,大于设置的值:'.get_basic_info('PicSize').'KB');
                        echo json_encode($arr);
                        exit;
                    }
                    $PicExt=explode(',',get_basic_info('PicExt'));
                    if(!in_array($val['ext'],$PicExt)){
                        $arr=array('result'=>0,'message'=>'不支持的图片格式,请上传'.get_basic_info('PicExt').'格式的图片!');
                        echo json_encode($arr);
                        exit;
                    }
                    //如果是图片，对图片进行压缩处理！
                }else{
                    //判断文件格式和大小限制
                    $FileSize=get_basic_info('FileSize')*1000;
                    if($val['size']>$FileSize){
                        $arr=array('result'=>0,'message'=>'上传的文件大小,大于设置的值:'.get_basic_info('FileSize').'KB');
                        echo json_encode($arr);
                        exit;
                    }
                    $FileExt=explode(',',get_basic_info('FileExt'));
                    if(!in_array($val['ext'],$FileExt)){
                        $arr=array('result'=>0,'message'=>'不支持的文件格式,请上传'.get_basic_info('FileExt').'格式的文件!');
                        echo json_encode($arr);
                        exit;
                    }
                }
                $path=$val['url'];
            }
            $data['result']='success';
            $data['path']=$path;
        }else{
            $data['result']='fail';
            $data['error']=$Upload->getError();
        }
        return $data;
    }

    /**
     * @功能说明:阿里OSS存储
     * @return bool|string
     */
    public function OSSUpload($filetype,$file=''){
        //暂未集成
        $arr=array('result'=>0,'message'=>'阿里OSS存储失败,暂未开放此功能!');
        echo json_encode($arr);
        exit;
    }

    /**
     * @功能说明:ke编辑器的上传方法
     */
    public function ke_upload_image(){
        require_once '/Editor/kindeditor/php/JSON.php';
        $php_path = dirname(__FILE__) . '/';
        $php_url = dirname($_SERVER['PHP_SELF']) . '/';

        //文件保存目录路径
        // $save_path = $php_path . '../attached/';
        $save_path = $_SERVER['DOCUMENT_ROOT'].'/Upload/';
        //文件保存目录URL
        // $save_url = $php_url . '../attached/';
        $save_url = '/Upload/';
        //定义允许上传的文件扩展名
        $FileExt=explode(',',get_basic_info('FileExt'));  //从系统设置中获取允许的文件扩展名
        $PicExt=explode(',',get_basic_info('PicExt'));    //从系统设置中获取允许的图片扩展名
        $ext_arr = array(
            'image' => $PicExt,
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => $FileExt,
        );
        //最大文件大小
        $img_max_size = get_basic_info('PicSize')*1000;  //从系统设置中获取，单位KB
        $file_max_size=get_basic_info('FileSize')*1000;  //从系统设置中获取，单位KB

        $save_path = realpath($save_path) . '/';

        //PHP上传失败
        if (!empty($_FILES['imgFile']['error'])) {
            switch($_FILES['imgFile']['error']){
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '图片只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择图片。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            alert($error);
        }

        //有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $file_name = $_FILES['imgFile']['name'];
            //服务器上临时文件名
            $tmp_name = $_FILES['imgFile']['tmp_name'];
            //文件大小
            $file_size = $_FILES['imgFile']['size'];
            //检查文件名
            if (!$file_name) {
                alert("请选择文件。");
            }
            //检查目录
            if (@is_dir($save_path) === false) {
                alert("上传目录不存在。");
            }
            //检查目录写权限
            if (@is_writable($save_path) === false) {
                alert("上传目录没有写权限。");
            }
            //检查是否已上传
            if (@is_uploaded_file($tmp_name) === false) {
                alert("上传失败。");
            }
            //检查文件大小
            if($_GET['dir']=='image'){
                if ($file_size > $img_max_size) {
                    alert("上传图片大小超过限制。");
                }
            }else{
                if ($file_size > $file_max_size) {
                    alert("上传文件大小超过限制。");
                }
            }
            //检查目录名
            $dir_name = empty($_GET['dir']) ? 'Pic' : trim($_GET['dir']);
            if (empty($ext_arr[$dir_name])) {
                alert("目录名不正确。");
            }
            //获得文件扩展名
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            //检查扩展名
            if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
                alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");
            }
            //创建文件夹
            if ($dir_name !== '') {
                $save_path .= $dir_name . "/";
                $save_url .= $dir_name . "/";
                if (!file_exists($save_path)) {
                    mkdir($save_path);
                }
            }
            $ymd = date("Ymd");
            $save_path .= $ymd . "/";
            $save_url .= $ymd . "/";
            if (!file_exists($save_path)) {
                mkdir($save_path);
            }
            //新文件名
            $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            //移动文件
            $file_path = $save_path . $new_file_name;
            if (move_uploaded_file($tmp_name, $file_path) === false) {
                alert("上传文件失败。");
            }
            @chmod($file_path, 0644);
            $file_url = $save_url . $new_file_name;

            header('Content-type: text/html; charset=UTF-8');
            $json = new \Services_JSON();
            echo $json->encode(array('error' => 0, 'url' => $file_url));
            exit;
        }
    }

    /**
     * @功能说明:ke编辑器已上传的文件管理
     */
    public function ke_upload_manage(){
        require_once '/Editor/kindeditor/php/JSON.php';
        $php_path = dirname(__FILE__) . '/';
        $php_url = dirname($_SERVER['PHP_SELF']) . '/';

        //根目录路径，可以指定绝对路径，比如 /var/www/attached/
        // $root_path = $php_path . '../attached/';
        $root_path = $_SERVER['DOCUMENT_ROOT'].'/Upload/';

        //根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
        // $root_url = $php_url . '../attached/';
        $root_url = '/Upload/';

        //图片扩展名
        $ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');

        //目录名
        $dir_name = empty($_GET['dir']) ? '' : trim($_GET['dir']);
        if (!in_array($dir_name, array('', 'image', 'flash', 'media', 'file'))) {
            echo "Invalid Directory name.";
            exit;
        }
        if ($dir_name !== '') {
            $root_path .= $dir_name . "/";
            $root_url .= $dir_name . "/";
            if (!file_exists($root_path)) {
                mkdir($root_path);
            }
        }

        //根据path参数，设置各路径和URL
        if (empty($_GET['path'])) {
            $current_path = realpath($root_path) . '/';
            $current_url = $root_url;
            $current_dir_path = '';
            $moveup_dir_path = '';
        } else {
            $current_path = realpath($root_path) . '/' . $_GET['path'];
            $current_url = $root_url . $_GET['path'];
            $current_dir_path = $_GET['path'];
            $moveup_dir_path = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
        }
        echo realpath($root_path);
        //排序形式，name or size or type
        $order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);

        //不允许使用..移动到上一级目录
        if (preg_match('/\.\./', $current_path)) {
            echo 'Access is not allowed.';
            exit;
        }
        //最后一个字符不是/
        if (!preg_match('/\/$/', $current_path)) {
            echo 'Parameter is not valid.';
            exit;
        }
        //目录不存在或不是目录
        if (!file_exists($current_path) || !is_dir($current_path)) {
            echo 'Directory does not exist.';
            exit;
        }

        //遍历目录取得文件信息
        $file_list = array();
        if ($handle = opendir($current_path)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if ($filename{0} == '.') continue;
                $file = $current_path . $filename;
                if (is_dir($file)) {
                    $file_list[$i]['is_dir'] = true; //是否文件夹
                    $file_list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件
                    $file_list[$i]['filesize'] = 0; //文件大小
                    $file_list[$i]['is_photo'] = false; //是否图片
                    $file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $file_list[$i]['is_dir'] = false;
                    $file_list[$i]['has_file'] = false;
                    $file_list[$i]['filesize'] = filesize($file);
                    $file_list[$i]['dir_path'] = '';
                    $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
                    $file_list[$i]['filetype'] = $file_ext;
                }
                $file_list[$i]['filename'] = $filename; //文件名，包含扩展名
                $file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($handle);
        }

        //排序
        function cmp_func($a, $b) {
            global $order;
            if ($a['is_dir'] && !$b['is_dir']) {
                return -1;
            } else if (!$a['is_dir'] && $b['is_dir']) {
                return 1;
            } else {
                if ($order == 'size') {
                    if ($a['filesize'] > $b['filesize']) {
                        return 1;
                    } else if ($a['filesize'] < $b['filesize']) {
                        return -1;
                    } else {
                        return 0;
                    }
                } else if ($order == 'type') {
                    return strcmp($a['filetype'], $b['filetype']);
                } else {
                    return strcmp($a['filename'], $b['filename']);
                }
            }
        }
        usort($file_list, 'cmp_func');

        $result = array();
        //相对于根目录的上一级目录
        $result['moveup_dir_path'] = $moveup_dir_path;
        //相对于根目录的当前目录
        $result['current_dir_path'] = $current_dir_path;
        //当前目录的URL
        $result['current_url'] = $current_url;
        //文件数
        $result['total_count'] = count($file_list);
        //文件列表数组
        $result['file_list'] = $file_list;

        //输出JSON字符串
        header('Content-type: application/json; charset=UTF-8');
        $json = new \Services_JSON();
        echo $json->encode($result);
    }

    /**
     * @功能说明:ue编辑器上传控制器
     */
    public function ue_upload_controller(){
        //header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
        //header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
        date_default_timezone_set("Asia/chongqing");
        error_reporting(E_ERROR);
        header("Content-Type: text/html; charset=utf-8");

        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("/Editor/ueditor/php/config.json",true)), true);
        //重新定义允许上传的图片扩展名和大小
        $PicExt=explode(',',get_basic_info('PicExt'));
        foreach ($PicExt as $key=>$value){
            //因ue扩展名分隔要求和ke扩展名不同，需要在后缀前加一个点，这里做下特殊处理
            $PicExt[$key]='.'.$value;
        }
        $CONFIG['imageAllowFiles']=$PicExt;
        $CONFIG['imageMaxSize']=get_basic_info('PicSize')*1000;  //单位KB

        //重新定义允许上传的文件扩展名和大小
        $FileExt=explode(',',get_basic_info('FileExt'));
        foreach ($FileExt as $key=>$value){
            //因ue扩展名分隔要求和ke扩展名不同，需要在后缀前加一个点，这里做下特殊处理
            $FileExt[$key]='.'.$value;
        }
        $CONFIG['fileAllowFiles']=$FileExt;
        $CONFIG['fileMaxSize']=get_basic_info('FileSize')*1000;  //单位KB
        $CONFIG['videoMaxSize']=get_basic_info('FileSize')*1000; //单位KB，视频大小限制

        //以下是正式执行部分
        $action = $_GET['action'];
        switch ($action) {
            case 'config':
                $result =  json_encode($CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = include_once("/Editor/ueditor/php/action_upload.php");
                break;

            /* 列出图片 */
            case 'listimage':
                $result = include_once("/Editor/ueditor/php/action_list.php");
                break;
            /* 列出文件 */
            case 'listfile':
                $result = include_once("/Editor/ueditor/php/action_list.php");
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = include_once("/Editor/ueditor/php/action_crawler.php");
                break;

            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }


    /**
     * @功能说明:七牛云存储  2017/09/21
     */

    public function QnUpload($filepath,$savepath){

        vendor('Qiniu.autoload');

        //获取配置信息
        $configInfo = M('sys_inteparameter')->where('IntegrateID=6 ')->select();
        $config = array();
        foreach ($configInfo as $k => $v) {
            $config[$v['ParaName']] = $v['ParaValue'];
        }

        $accessKey =$config['AccessKey'];  //AccessKey 必要条件
        $secretKey =$config['SecretKey'];  //SecretKey 必要条件
        $bucket    =$config['Bucket'];     //Bucket 必要条件
        $CName     =$config['CName'];       //CName 必要条件

        if(!$accessKey){
            return json_encode(array('result'=>0,'message'=>'缺少必要的AccessKey'));
        }
        if(!$secretKey){
            return json_encode(array('result'=>0,'message'=>'缺少必要的SecretKey'));
        }
        if(!$bucket){
            return json_encode(array('result'=>0,'message'=>'缺少必要的Bucket'));
        }
        if(!$CName){
            return json_encode(array('result'=>0,'message'=>'缺少必要的CName'));
        }

        $auth = new Auth($accessKey, $secretKey);

        $token = $auth->uploadToken($bucket);

        //要上传文件的本地路径
        $filePath = $filepath;
        // 上传到七牛后保存的文件名
        $key = $savepath;

        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== NULL) {
            //上传失败return $err;
            return json_encode(array('result'=>0,'message'=>'Error'));
        } else {
            //上传成功return $ret;
            return json_encode(array('result'=>1,'message'=>'Success','path'=>$CName.'/'.$key));
        }
    }

}