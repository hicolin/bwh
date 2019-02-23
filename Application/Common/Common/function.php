<?php
/**
 * 
 *
 *
 * 修改时间: 2017-06-08 14:30
 * 功能说明:公共函数库
 */

/**
 * @功能说明:发送短信或自定消息
 * @param $name 手机号 或邮箱地址
 * @param $type 发送类型 0 短信 1 邮件 默认短信
 * @param $content 自定义消息  短信中 模板编号和自定义消息仅用一个不可同时使用  邮件内需要配合 title 邮件标题
 * @param $title 邮件标题
 * @return array|string  $message 错误信息 $res 接口返回结果
 */
function send_message($name,$content,$title='',$type=0)
{
    $message = '';
    //判断是短信还是邮件
    if($type == 1) {
        if (empty($name)) {
            $message = '邮件地址不能为空,';
        }
        if (!empty($title) && !empty($content)) {
            $mes = new \XBCommon\XBMessage($name, $content,$title, $type);
            $res = $mes->send_message();
        } else {
            $message = '邮件内容和邮件标题，不能为空';
        }
    }else{
        if (empty($name)) {
            $message = '手机号不能为空,';
        }
        if (!empty($content)) {
            $mes = new \XBCommon\XBMessage($name, $content,$title, $type);
            $res = $mes->send_message();
        } else {
            $message = '短信内容，不能为空';
        }
    }

    if(!empty($message)){
        $result['result'] = 'error';
        $result['error'] = $message;

        return $result;
    }
    return  $res;
}


/**
 * ############################# 常用验证函数 ################################
 */

/**
 * @功能说明: 判断是否是数字
 * @param  int  $val  判断的数据
 * @return bool
 */
function is_num($val=0){
    return  is_numeric($val);
}

/**
 *@功能说明:判断是否是手机
 * @param string $val 判断的数据
 * @return bool
 */
function is_mobile($val){
    return preg_match("/^1[3-9][0-9]{1}\d{8}$/",$val);
}

/**
 * @功能说明:判断是否是固话
 * @param  string  $val 判断的数据
 * @return bool
 */
function is_tel($val){
    return preg_match("/^0\d{2,3}-?\d{7,8}$/",$val);
}

/**
 * @功能说明:判断是否是电话号码 手机/固话
 * @param  string  $val 判断的数据
 * @return bool
 */
function is_phone($val){
    return preg_match("/(^0\d{2,3}-?\d{7,8})|(^0?1[3|4|5|7|8][0-9]\d{8})$/",$val);
}

/**
 * @功能说明:检测变量是否是邮件地址
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_email($val) {
    return preg_match('/^[\w-]+(\.[\w-]+)*\@[A-Za-z0-9]+((\.|-|_)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/', $val);
}

/**
 * @功能说明:检测变量是否是qq
 * @param   string $val 判断的数据
 * @return  bool
*/
function is_qq($val) {
    return preg_match('/^[1-9][0-9]{4,10}$/', $val);
}

/**
 * @功能说明:检测变量是否是邮编号码
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_zip($val) {
    return preg_match('/^[1-9]\d{5}$/', $val);
}

/**
 * @功能说明:检测变量是否符合用户名的规则 英文开头，允许数字下划线组合
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_username($val) {
    return preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $val);
}

/**
 * @功能说明:检测变量是否符密码的规则 英文+数字组合 6-20位
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_password($val){
    return preg_match('/^.{6,20}$/i',$val);
}

/**
 * @功能说明:检测变量是否全是英文
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_english($val){
    return preg_match('/^[A-Za-z]+$/',$val);
}

/**
 * @功能说明:检测变量是否为汉字
 * @param   string $val 判断的数据
 * @return  bool
 */
function is_chs($val) {
    return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $val);
}

/**
 * @功能说明:判断是否为网址,以http://开头
 * @param string $str 判断的数据
 * @return bool
 */
function is_url($str){
    return preg_match("/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $str);
}

 /**
  * @功能说明:检测变量长度区间
  * @param   string  $val  判断的数据
  * @param   int $min 最小长度
  * @param   int $max 最大长度
  * @return  bool
  */
function strlenth($val,$min,$max) {
    if(strlen($val) >= $min && strlen($val)<= $max) {
            return true;
    }else{
        return false;
    }
}

/**
 * @功能说明:判断是否是日期类型的数据
 * @param   string  $val  判断的数据
 * @param   int $mark 0是date类型 1是datetime类型
 * @return  bool
 */
function checkDateTime($val,$mark=0) {
    if($mark){
        $str = date('Y-m-d H:i:s',strtotime($val));
    }else{
        $str = date('Y-m-d',strtotime($val));
    }

    if($str == $val) {
        return 1;
    }else{
        return 0;
    }
}

/**
 * ############################# 检测状态函数 ################################
 */


/**
 * @功能说明：判断数据库链接,5秒钟未正常连接则返回false
 * @return bool
 */
function check_mysql(){
    $server=C('DB_HOST');
    $username=C('DB_USER');
    $password=C('DB_PWD');
    $db_name   =C('DB_NAME');
    $port = C('DB_PORT');
    //检测数据库是否能正常连接
    $mysqli = mysqli_init();
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $conn=$mysqli->real_connect($server,$username,$password,$db_name,$port);
    if($conn){
        return true;
    }else{
        return false;
    }
}

/**
 * ############################# 目录或文件操作 ################################
 */

/**
 * @功能说明:文件流下载,防盗链下载
 * @param string $path 文件路径，不包含文件名
 * @param string $name 文件名称
 * @return bool  下载失败返回 false
 */
function download($path,$name){
    $FilePath=$path.$name; //拼接文件物理地址
    if(!file_exists($FilePath)){
        //文件不存在
        return false;
    }else{
        $http=new Org\Net\Http();
        $http->download($FilePath,$name);
    }
}

/**
 * @功能说明: 获取文件真实扩展名，需php环境的finfo功能的支持
 * @param  string $file 文件路径
 * @return array  返回可能的扩展名组合 [多数情况下不同扩展名的MIME是相同的]
 */
function get_file_ext($file){
    $handle=finfo_open(FILEINFO_MIME_TYPE);
    $FileMime=finfo_file($handle,$file);  //获得文件的真实MIME_TYPE
    finfo_close($handle);

    //定义支持判断的扩展名和MIME对照表
    static $mime_types = array(
        //常见视频类型
        'flv'     => 'video/x-flv',
        '3gp'     => 'video/3gpp',
        'avi'     => 'video/x-msvideo',
        'mp3'     => 'audio/mpeg',
        'mp4'     => 'video/mp4',
        'mpeg'    => 'video/mpeg',
        'mpg'     => 'video/mpeg',
        'mpga'    => 'audio/mpeg',
        'rm'      => 'application/vnd.rn-realmedia',
        'swf'     => 'application/x-shockwave-flash',
        'wmv'     => 'video/x-ms-wmv',
        //常见文件类型
        'txt'     =>'text/plain',
        'ppt'     => 'application/vnd.ms-powerpoint',
        'pptx'    =>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc'     => 'application/msword',
        'docx'    =>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'     => 'application/vnd.ms-excel',
        'xlsx'    =>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'dot'     =>'application/msword',
        'exe'     => 'application/octet-stream',
        'html'    =>'text/html',
        'dmg'     => 'application/octet-stream',
        'apk'     => 'application/vnd.android.package-archive',
        'ipa'     =>'application/octet-stream.ipa',
        'js'      => 'application/x-javascript',
        'pdf'     => 'application/pdf',
        'svg'     => 'image/svg+xml',
        'xml'     =>'text/xml',
        'zip'     => 'application/zip',
        'rar'     =>'application/octet-stream',
        'rar'     =>'application/x-rar',
        'tar'     =>'application/x-tar',
        'gz'      => 'application/x-gzip',
        '7z'      =>'application/octet-stream',
        //常见图片类型
        'png'     => 'image/png',
        'ico'     => 'image/x-icon',
        'bmp'     => 'image/bmp',
        'gif'     => 'image/gif',
        'jpeg'    => 'image/jpeg',
        'jpg'     => 'image/jpeg'
    );
    //根据键值，找到符合条件的第一个键名并转化为字符串返回
    $arr=array_keys($mime_types,$FileMime,true);
    return $arr;
}

/**
 * ############################# 数据转换函数 ################################
 */

/**
 * @功能说明:根据IP地址，转换为IP所在城市 [省.市.区.网络服务商]
 * @param   string $ip  客户端ip
 * @return  json数据
 */
function ip_to_address($ip){
    $IpInfo=new \XBCommon\IpAddress();
    $data=$IpInfo->GetAreas($ip);

    if($data['code']==0){
        if($data['data']['CountryID']!='CN'){
            $address=$data['data']['Country'];   //不等于CN时，可能是内网或国外IP
        }else{
            $address=$data['data']['Region'].$data['data']['City'].$data['data']['County'].$data['data']['Isp'];
        }

        return  $address;
    }else{
        return  '2';
    }
}

/**
 * @功能说明:讲文本或字符串中的所有图片路径批量更换为指定路径
 * @param $type  替换类型: image 原图 large 大图 small 小图
 * @param $data  要转换的数据
 * @return   返回string
 */
function convert_pic($type,$data){
    $str=null;
    switch ($type){
        case 'image':
            $data=str_replace('/Upload/large','/Upload/image',$data);
            $str=str_replace('/Upload/small','/Upload/image',$data);
            break;
        case 'small':
            $data=str_replace('/Upload/image','/Upload/small',$data);
            $str=str_replace('/Upload/large','/Upload/small',$data);
            break;
        default:
            $data=str_replace('/Upload/image','/Upload/large',$data);
            $str=str_replace('/Upload/small','/Upload/large',$data);
    }

    return $str;
}

/**
 * ############################# 调用缓存函数 ################################
 */

/**
 * @功能说明:调用后台的基本设置中的缓存信息
 * @param string @name 缓存名称
 * @return string $value 返回缓存信息
 */
function get_basic_info($name){
    if(!empty($name)){
        $cache=new \XBCommon\CacheData();
        $info=$cache->BasicInfo();
        return $info[$name];
    }else{
        return '参数错误！';
    }
}

/**
 * ############################# 字符串处理 ################################
 */


/**
 * 作用: 过滤字符串中的emoji表情(3个字节的emoji无法剔除, 比如讯飞输入法的emoji表情)
 * 场景: 通讯录中的含有表情的名称在存储入utf类型的mysql数据库时  需要过滤表情(大于4个字符)
 */
if (!function_exists('emoji_reject')) {
    function emoji_reject($text)
    {
        $len = mb_strlen($text);
        $new_text = '';
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($text, $i, 1);
            if (strlen($word) <= 3) {
                $new_text .= $word;
            }
        }
        return $new_text;
    }
}


/**
 * @功能说明:过滤敏感词汇
 * @param string @value 需要过滤的内容
 * @return string $val 过滤之后的内容
 */
function  filter_word ($value){
    //调用快速缓存信息
    $cache= new XBCommon\CacheData();
    $info=$cache->BasicInfo();
    $words=explode(',',$info['StopWord']);
    $word=array();
    foreach($words as $k=>$v){
        $key=array("Word"=>$v);
        array_push($word,$key);
    }
    $val = $value;
    //替换敏感词汇
    foreach($word as $item){
        $count = mb_strlen($item['Word'],'utf8');
        $val = str_replace($item['Word'],str_repeat('*',$count),$val);   //将非法词汇，提出成星号*
    }
    return $val;
}

/**
 * 功能说明：字符串去掉HTML标签
 * @param $str 需要过滤的内容
 * @param string $tags 需要保留的html标签  参数传递例如:'<a><span><img>'
 * @return mixed|string 过滤后的内容
 */
function filter_html($str,$tags){
    $search = array(
        '@<script[^>]*?>.*?</script>@si',  // 过滤js脚本
        '@<style[^>]*?>.*?</style>@siU',    // 过滤标签样式
        '@<![\s\S]*?--[ \t\n\r]*>@'         // 过滤多行注释，包括CDATA
    );
    $str = preg_replace($search, '', $str); //过滤非法字符 html标签  css样式  js脚本程序
    $str = strip_tags($str,$tags); //脱掉html标签 除保留html标签外
    return $str;
}


/**
 * ############################# 富文本编辑器 ################################
 */


/**
 * 功能说明:加载编辑器js等引用文件
 * @param $type 编辑器名称
 * @return string
 */
function load_editor_js($type){
    $str='';
    switch ($type){
        case 'kindeditor':
            $str.='<script src="/Editor/kindeditor/kindeditor-min.js"></script>'."\r\n";
            $str.='<script src="/Editor/kindeditor/lang/zh_CN.js"></script>'."\r\n";
            break;
        case 'ueditor':
            $str.='<script type="text/javascript" src="/Editor/ueditor/ueditor.config.js"></script>'."\r\n";
            $str.='<script type="text/javascript" src="/Editor/ueditor/ueditor.all.js"></script>'."\r\n";
            $str.='<script src="/Editor/ueditor/lang/zh-cn/zh-cn.js"></script>'."\r\n";
            break;
        default:
            $str='加载编辑器js文件的参数传递错误！';
            break;
    }
    return $str;
}

/**
 * 功能说明:富文本编辑器
 * @param $type 编辑器的名称: 例如ueditor,kindeditor
 * @param  $mode 编辑器的类型: 0 默认模式 1 简单模式
 * @param  $name  页面中被加载的name名称,多个使用逗号分隔
 * @param $width 富文本编辑框的宽度，默认740px
 * @param $height 富文本编辑器的高度，默认300px
 * @return string
 */
function editor($type='kindeditor',$mode=0,$name,$width=740,$height=300){
    $editor=new \XBCommon\Editor();
    $result=$editor->editor($type,$mode,$name,$width,$height);
    return $result;
}

/**
 * 功能说明:ke编辑器上传图片报错提示
 */
function alert($msg) {
    header('Content-type: text/html; charset=UTF-8');
    $json = new Services_JSON();
    echo $json->encode(array('error' => 1, 'message' => $msg));
    exit;
}



/**
 * ############################# 与APP项目相关的 ################################
 */

/**
 * @功能说明:获取前端传递的json数据，并转化为数组形式
 * @return array
 */
function get_json_data($mark=0){
    $data = file_get_contents("php://input");//isset($GLOBALS['HTTP_RAW_POST_DATA'])?$GLOBALS['HTTP_RAW_POST_DATA']:file_get_contents("php://input");
    var_dump($data);exit;
//    if(!file_exists("alipay.txt")){ $fp = fopen("alipay.txt","wb"); fclose($fp);  }
//    $str = file_get_contents('alipay.txt');
//    $str .= " - trade_no: - ".json_encode($data)." - ".date("Y-m-d H:i:s")."\r\n";
//    $fp = fopen("alipay.txt","wb");
//    fwrite($fp,$str);
//    fclose($fp);

    if($data){
        $result = json_decode($data,true);

        if(!$result){
            AjaxJson(0, 0, '非法数据！');
        }

        if($mark == 1){
            return $result;
        }else{

            if($result['isaes'] == 1){

                $mem = getUserInfo($ID=get_login_info('ID'));

                if(!$result['data']){
                    file_put_contents(
                        'baoqianfeifashuju.log',
                        '错误码:001 ['.date('Y-m-d H:i:s').']'.PHP_EOL.$_SERVER['REQUEST_URI'].PHP_EOL.
                        '---KEY:'.$mem['KEY'].'---IV:'.$mem['IV'].'---UserID:'.$ID.PHP_EOL.
                        '>>>>'.$data.'>>>>'.PHP_EOL,
                        \FILE_APPEND
                    );
                    AjaxJson(0, 0, '抱歉，非法数据！');
                }

                $array = decrypt_pkcs7($result['data'],$mem['KEY'],$mem['IV']);
                if(!$array){
                    file_put_contents(
                        'baoqianfeifashuju.log',
                        '错误码:002 ['.date('Y-m-d H:i:s').']'.PHP_EOL.$_SERVER['REQUEST_URI'].PHP_EOL.
                        '---KEY:'.$mem['KEY'].'---IV:'.$mem['IV'].'---UserID:'.$ID.PHP_EOL.
                        '>>>>'.$data.'>>>>'.PHP_EOL,
                        \FILE_APPEND
                    );
                    AjaxJson(0, 0, '抱歉，非法数据！');
                }

                $arrayAjax = json_decode($array,true);
                $result_array = array_merge($result,$arrayAjax);

                return $result_array;
            }else{
                $result_array =  $result;
                if($result['data']){
                    $result_array = array_merge($result,$result['data']);
                }

                return $result_array;
            }
        }
    }else{
        AjaxJson(0, 0, '非法数据！');
    }
}


/**
 * @功能说明:AES CBC模式PKCS7 128位加密算法
 * @param $data 要加密的数据
 * @param $key 随机私有密钥
 * @param $iv 随机向量
 * @return string
 */
function encrypt_pkcs7($data,$key,$iv){
    //补码处理
    $blocksize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $len = strlen($data);
    $pad = $blocksize - ($len % $blocksize);
    $data .= str_repeat(chr($pad), $pad);
    //开始加密
    $encrypted=mcrypt_encrypt(MCRYPT_RIJNDAEL_128,$key,$data,MCRYPT_MODE_CBC,$iv);
    return base64_encode($encrypted);
}

/*
 * @功能说明:AES CBC模式PKCS7 128位解密算法
 * @param $data 要解密的数据
 * @param $key 随机私有密钥
 * @param $iv 随机向量
 * @return string
 */
function decrypt_pkcs7($data,$key,$iv){
    $encryptedData=base64_decode($data);
    $decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key,$encryptedData,MCRYPT_MODE_CBC,$iv);

    //php的pkcs7去码处理
    $length = strlen($decrypted);
    $unpadding = ord($decrypted[$length - 1]);
    return substr($decrypted, 0, $length - $unpadding);
}


/**
 * @功能说明：将xml转为array（微信）
 * @return array
 */
function xml_to_array($xml) {
    //将XML转为array
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

//通过api地址处理
function https_request($url,$data = null,$mark=1)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    if (!empty($data))
    {
        curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    if($mark == 1){
        return json_decode($output,true);
    }else{
        return $output;
    }
}

//清除空格
function trimHtml($str)
{
    $str = trim($str);
    $str = strip_tags($str,"");
    $str = str_replace("\t","",$str);
    $str = str_replace("\r\n","",$str);
    $str = str_replace("\r","",$str);
    $str = str_replace("\n","",$str);
    $str = str_replace(" ","",$str);
    $str = str_replace("&nbsp;","",$str);
    return trim($str);
}
/**
 * 功能说明：字符串去掉HTML标签
 * @param $str 需要过滤的内容
 * @param string $tags 需要保留的html标签
 * @return mixed|string 过滤后的内容
 */
function FilterHtml($str,$tags='<img><a>'){
    //过滤时默认保留html中的<a><img>标签
    $search = array(
        '@<script[^>]*?>.*?</script>@si',  // 过滤js脚本
        '@<style[^>]*?>.*?</style>@siU',    // 过滤标签样式
        '@<![\s\S]*?--[ \t\n\r]*>@'         // 过滤多行注释，包括CDATA
    );
    $str = preg_replace($search, '', $str); //过滤非法字符 html标签  css样式  js脚本程序
    $str = strip_tags($str,$tags); //脱掉html标签 除保留html标签外
    return $str;
}

//中英文字符串截取
function cur_str($string, $sublen, $start = 0, $code = 'UTF-8')
{
    if($code == 'UTF-8')
    {
        $pa ="/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string); if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen))."...";
        return join('', array_slice($t_string[0], $start, $sublen));
    }
    else
    {
        $start = $start*2;
        $sublen = $sublen*2;
        $strlen = strlen($string);
        $tmpstr = '';
        for($i=0; $i<$strlen; $i++)
        {
            if($i>=$start && $i<($start+$sublen))
            {
                if(ord(substr($string, $i, 1))>129)
                {
                    $tmpstr.= substr($string, $i, 2);
                }
                else
                {
                    $tmpstr.= substr($string, $i, 1);
                }
            }
            if(ord(substr($string, $i, 1))>129) $i++;
        }
        if(strlen($tmpstr)<$strlen ) $tmpstr.= "...";
        return $tmpstr;
    }
}

//替换默认头像并处理图片的返回路径  fal 1是默认图片 2是会员头像
function NoPhoto($val,$fal = 1){
    if($val){
        $str = 'http://'.$_SERVER['HTTP_HOST'].$val;
    }else{
        if($fal == 1){
            $str = 'http://'.$_SERVER['HTTP_HOST'].'/Upload/no.png';
        }else{
            $str = 'http://'.$_SERVER['HTTP_HOST'].'/Upload/photo.png';
        }

    }
    return $str;
}

//重新生成密码
function getGroupMd5($str=''){
    if(!$str){
        return '';
    }
    $md = md5($str);
    $md_1 = strtoupper(md5(strtoupper(substr($md,0,18).substr($md,-10))));

    return substr($md_1,4,21);
}

//---------------------具体项目相关-------------------------------------
 //get方式请求
function get_request($url){
    $oCurl = curl_init();//实例化
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );//是否返回值，1时给字符串，0输出到屏幕
    $sContent = curl_exec($oCurl);//获得页面数据
    $aStatus = curl_getinfo($oCurl);//获取CURL连接数据的信息
    curl_close($oCurl);//关闭资源
    //获取成功
    $output_array = json_decode($sContent,true);//转换json格式
    return $output_array;
}
/**
 * @功能说明:校验会员是否有此权限提交借款申请
 * @param  $uid   会员id
 * @return array
 */
function checkmembuy($uid){
    $retdata=array(
        'result'=>'1',
        'message'=>'可以下单!',
        );
    $meminfo=M('mem_info')->field('ID,Status,ForbidTime')->find($uid);
    if($meminfo['Status']!='2'){
        $retdata['result']='0';
        $retdata['message']='您没有购买权限!';
    }
    if($meminfo['ForbidTime'] && $meminfo['ForbidTime']>date('Y-m-d H:i:s')){
        $retdata['result']='0';
        $retdata['message']=$meminfo['ForbidTime'].'之前，暂不能下单!';
    }
    //判断是否有未结束的 借款订单
    $orderinfos=M('loans_applylist')->where(array('UserID'=>$uid,'LoanStatus'=>array('in',array('0','1','2')),'IsDel'=>'0'))->count("ID");
    if($orderinfos>0){
        $retdata['result']='0';
        $retdata['message']='还有未完成的订单，暂不能下单!';
    }
    return $retdata;
}
/**
 * @功能说明:发送站内通知消息 此消息，不需要友盟推送
 * @param  $uid   会员id
 * @param  $content  消息通知内容
 * @return array
 */
function send_mem_notics($uid,$content){
    $mdata=array(
        'UserID'=>$uid,
        'Title'=>'通知消息',
        'Type'=>'1',
        'Contents'=>$content,
        'SendTime'=>date('Y-m-d H:i:s'),
        );
    $result=M('notice_message')->add($mdata);
    if($result){
        $map = array(
            'UID' => $uid,
            'Type' =>'1',
        );
        $find = M('notice_num')->where($map)->count("ID");

        if($find>0){
            M('notice_num')->where($map)->setInc('Num');
        }else{
            $map['Num'] = 1;
            $map['UpdateTime'] = date('Y-m-d H:i:s');
            M('notice_num')->add($map);
        }
    }
}
/**
 * @功能说明:算出逾期天数，罚金，应还总金额
 * @param  $id   借款申请id
 * @return array
 */
 function getoverinfos($id){
    $infos=M('loans_applylist')->field('ID,YyFkTime,ApplyMoney,CoMoney,RongDay,RongP,OverdueP,BackM')->find($id);
    //过了当天夜里24点才算逾期
    $overtimes=date('Y-m-d',strtotime($infos['YyFkTime'])).' 23:59:59';
    $overdays=(time()-strtotime($overtimes))/86400;
    $overdays=ceil($overdays);//逾期天数
    $loandmoney=$infos['ApplyMoney']-$infos['CoMoney'];//应还的本金
    //计算罚金
    $famoney='';
    if($overdays<=$infos['RongDay']){
        $famoney=$loandmoney*($infos['RongP']/100)*$overdays;
    }elseif($infos['RongDay']<$overdays){
        $famoney=$loandmoney*($infos['RongP']/100)*$infos['RongDay']+$loandmoney*($infos['OverdueP']/100)*($overdays-$infos['RongDay']);
    }
    $famoney=round($famoney,2);
    $retdata=array(
        'overdays'=>$overdays,
        'famoney'=>$famoney,
        'realtotal'=>$famoney+$infos['BackM'],//应还总金额
        );
    return $retdata;
}
/**
 * @功能说明:所有必须认证通过，自动审核会员，并给默认额度
 * @param  $uid  会员id
 */
function selfshenhe_mems($uid){
    //查出会员状态 只有 1待审核  状态才能进行此操作
    $memstatus=M('mem_info')->where(array('ID'=>$uid))->getField('Status');
    if($memstatus=='1'){
        //查出必备验证列表
        $rzparametlist=M('renzen_parameter')->field('ID,Name,Codes')->where(array('IsShow'=>'1','IsMust'=>'1','IsDel'=>'0'))->select();
        $flags=true;//是否审核通过 默认是通过
        if($rzparametlist){
            foreach($rzparametlist as $k=>$v){
                if($v['Codes']=='card'){
                    //身份认证
                    $cardresult=M('renzen_cards')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$cardresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='mobile'){
                    //手机认证
                    $mobileresult=M('renzen_mobile')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$mobileresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='alipay'){
                    //支付宝认证
                    $alipayresult=M('renzen_alipay')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$alipayresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='taobao'){
                    //淘宝认证
                    $taobaoresult=M('renzen_taobao')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$taobaoresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='memberinfo'){
                    //基本信息认证
                    $memberinforesult=M('renzen_memberinfo')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$memberinforesult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='social'){
                    //社交认证
                    $socialresult=M('renzen_social')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$socialresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='bank'){
                    //银行卡认证
                    $bankresult=M('renzen_bank')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$bankresult){
                        $flags=false;//没认证通过
                        break;
                    }
                }elseif($v['Codes']=='zhimafen'){
                    //芝麻分认证
                    $ZhimaRes = M('renzen_zhima')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                    if(!$ZhimaRes){
                        $flags=false;//没认证通过
                        break;
                    }
                }
            }
        }else{
            $flags=false;
        }
        if($flags){
            //审核通过此会员,并给个默认额度 1200
            $bascidata=M('sys_basicinfo')->field('Mlimitbalance')->find();
            $sdata=array(
                'LimitBalcance'=>$bascidata['Mlimitbalance'],
                'Status'=>'2',
                );
            M('mem_info')->where(array('ID'=>$uid))->save($sdata);
            self_sendjuan($uid,'2');//完成所有认证立送
            self_sendjuan($uid,'4');//邀请的好友认证成功立送
        }
    }
}

/**
 * @功能说明:自动送券功能
 * @param  $uid  会员id
 * @param  $stype 送券的类型    1注册成功立送  2完成所有认证立送  3每邀请1名好友注册立送
 *         4邀请的好友认证成功立送  5邀请的好友申请专卖成功立送
 */
function self_sendjuan($uid,$stype){
    $jdata='';//劵信息
    $numbs='';//添加的个数
    $senduid='';//优惠劵获得者
    $sendmsg='';//通知信息
    if($stype=='1'){
        //----注册成功立送
        $juaninfos=M('juan_sendsets')->where(array('ID'=>'1','Status'=>'1','IsDel'=>'0'))->find();
        if($juaninfos && $juaninfos['Nunbs']>0){
            //送券操作
            $jdata=array(
                'UserID'=>$uid,
                'GetType'=>'1',
                'Title'=>$juaninfos['Name'],
                'Money'=>$juaninfos['Money'],
                'StartMoney'=>$juaninfos['StartMoney'],
                'StartTime'=>$juaninfos['StartTime'],
                'EndTime'=>$juaninfos['EndTime'],
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $numbs=$juaninfos['Nunbs'];
            $senduid=$uid;
            $sendmsg='恭喜您注册成功，赠送您'.$juaninfos['Nunbs'].'张，'.$juaninfos['Money'].'元优惠劵';
        }
    }elseif($stype=='2'){
        //----完成所有认证立送
        $juaninfos=M('juan_sendsets')->where(array('ID'=>'2','Status'=>'1','IsDel'=>'0'))->find();
        if($juaninfos && $juaninfos['Nunbs']>0){
            //送券操作
            $jdata=array(
                'UserID'=>$uid,
                'GetType'=>'1',
                'Title'=>$juaninfos['Name'],
                'Money'=>$juaninfos['Money'],
                'StartMoney'=>$juaninfos['StartMoney'],
                'StartTime'=>$juaninfos['StartTime'],
                'EndTime'=>$juaninfos['EndTime'],
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $numbs=$juaninfos['Nunbs'];
            $senduid=$uid;
            $sendmsg='恭喜您完成所有认证，赠送您'.$juaninfos['Nunbs'].'张，'.$juaninfos['Money'].'元优惠劵';
        }
    }elseif($stype=='3'){
        //----每邀请1名好友注册立送
        $juaninfos=M('juan_sendsets')->where(array('ID'=>'3','Status'=>'1','IsDel'=>'0'))->find();
        $parentmem=M('mem_info')->field('Referee')->where(array('ID'=>$uid))->find();
        if($juaninfos && $juaninfos['Nunbs']>0 && $parentmem['Referee']){
            //送券操作
            $jdata=array(
                'UserID'=>$parentmem['Referee'],
                'GetType'=>'1',
                'Title'=>$juaninfos['Name'],
                'Money'=>$juaninfos['Money'],
                'StartMoney'=>$juaninfos['StartMoney'],
                'StartTime'=>$juaninfos['StartTime'],
                'EndTime'=>$juaninfos['EndTime'],
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $numbs=$juaninfos['Nunbs'];
            $senduid=$parentmem['Referee'];
            $sendmsg='恭喜您邀请了一名好友注册成功，赠送您'.$juaninfos['Nunbs'].'张，'.$juaninfos['Money'].'元优惠劵';
        }
    }elseif($stype=='4'){
        //----邀请的好友认证成功立送
        $juaninfos=M('juan_sendsets')->where(array('ID'=>'4','Status'=>'1','IsDel'=>'0'))->find();
        $parentmem=M('mem_info')->field('Referee')->where(array('ID'=>$uid))->find();
        if($juaninfos && $juaninfos['Nunbs']>0 && $parentmem['Referee']){
            //送券操作
            $jdata=array(
                'UserID'=>$parentmem['Referee'],
                'GetType'=>'1',
                'Title'=>$juaninfos['Name'],
                'Money'=>$juaninfos['Money'],
                'StartMoney'=>$juaninfos['StartMoney'],
                'StartTime'=>$juaninfos['StartTime'],
                'EndTime'=>$juaninfos['EndTime'],
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $numbs=$juaninfos['Nunbs'];
            $senduid=$parentmem['Referee'];
            $sendmsg='恭喜您邀请的好友认证成功，赠送您'.$juaninfos['Nunbs'].'张，'.$juaninfos['Money'].'元优惠劵';
        }
    }elseif($stype=='5'){
        //----邀请的好友申请专卖成功立送
        $juaninfos=M('juan_sendsets')->where(array('ID'=>'5','Status'=>'1','IsDel'=>'0'))->find();
        $parentmem=M('mem_info')->field('Referee')->where(array('ID'=>$uid))->find();
        if($juaninfos && $juaninfos['Nunbs']>0 && $parentmem['Referee']){
            //送券操作
            $jdata=array(
                'UserID'=>$parentmem['Referee'],
                'GetType'=>'1',
                'Title'=>$juaninfos['Name'],
                'Money'=>$juaninfos['Money'],
                'StartMoney'=>$juaninfos['StartMoney'],
                'StartTime'=>$juaninfos['StartTime'],
                'EndTime'=>$juaninfos['EndTime'],
                'UpdateTime'=>date('Y-m-d H:i:s'),
                );
            $numbs=$juaninfos['Nunbs'];
            $senduid=$parentmem['Referee'];
            $sendmsg='恭喜您邀请的好友申请成功，赠送您'.$juaninfos['Nunbs'].'张，'.$juaninfos['Money'].'元优惠劵';
        }
    }
    if($jdata){
        //增加劵操作
        $addData=array();
        for($i=0;$i<$numbs;$i++){
            $addData[]=$jdata;
        }
        if($addData){
            $res=M('mem_coupans')->addAll($addData);
            if($res){
                //发送通知消息
                send_mem_notics($senduid,$sendmsg);
            }
        }
    }
}
/**
 * @功能说明:添加借款申请时，分配的相应客服
 * uid 会员id
 * @return array
 */
function getkefudata($uid){
    //客服专员
    //如果此会员有专属客服(且启用状态)，就直接分给他，如果没启用，就平均分配给其他客服
    //如果此会员的专属客服被删了，就平均分配给其他客服，并且，设置其专属客服
    $meminfos=M('mem_info')->field('ID,ZsAdminID')->find($uid);
    if($meminfos['ZsAdminID']){
        $zskfinfos=M('sys_administrator')->field('ID,Status,IsDel')->find($meminfos['ZsAdminID']);
    }
    if($meminfos['ZsAdminID'] && $zskfinfos['Status']=='1' && $zskfinfos['IsDel']=='0'){
        $min_kefuid=$meminfos['ZsAdminID'];
    }else{
        //算出平均分配的结果
        $kefuArr=M('sys_administrator')->field('ID')->where(array('RoleID'=>'7','Status'=>'1','IsDel'=>'0'))->select();
        if($kefuArr){
            //统计每个客服手里，未完成的任务个数
            $min_kefuid='0';
            $min_count='0';
            foreach($kefuArr as $k=>&$v){
                $nocouts='';
                $nocouts=M('loans_applylist')->where(array('LoanStatus'=>'0','SqAdminID'=>$v['ID'],'IsDel'=>'0'))->count("ID");
                if(!$nocouts){
                    $nocouts='0';
                }
                $v['nocouts']=$nocouts;
                //找出最少的
                if($k=='0'){
                    $min_kefuid=$v['ID'];
                    $min_count=$nocouts;
                }
                if($min_count>=$nocouts){
                    $min_kefuid=$v['ID'];
                    $min_count=$nocouts;
                }
            }
        }
        //如果此会员有专属客服，且专属客服被删除了，就给他设置此专属客服
        if(!$meminfos['ZsAdminID'] || $zskfinfos['IsDel']=='0'){
            M('mem_info')->where(array('ID'=>$uid))->save(array('ZsAdminID'=>$min_kefuid,'UpdateTime'=>date('Y-m-d H:i:s')));
        }
    }
    
    //放款专员
    $fangkuanArr=M('sys_administrator')->field('ID')->where(array('RoleID'=>'8','Status'=>'1','IsDel'=>'0'))->select();
    if($fangkuanArr){
        //统计每个客服手里，未完成的任务个数
        $min_fkid='0';
        $min_fkcount='0';
        foreach($fangkuanArr as $k=>&$v){
            $nocouts='';
            $nocouts=M('loans_applylist')->where(array('LoanStatus'=>array('in',array('0','1')),'FKadminID'=>$v['ID'],'IsDel'=>'0'))->count("ID");
            if(!$nocouts){
                $nocouts='0';
            }
            $v['nocouts']=$nocouts;
            //找出最少的
            if($k=='0'){
                $min_fkid=$v['ID'];
                $min_fkcount=$nocouts;
            }
            if($min_fkcount>=$nocouts){
                $min_fkid=$v['ID'];
                $min_fkcount=$min_fkcount;
            }
        }
    }
    //催收专员
    $csArr=M('sys_administrator')->field('ID')->where(array('RoleID'=>'10','Status'=>'1','IsDel'=>'0'))->select();
    if($csArr){
        //统计每个客服手里，未完成的任务个数
        $min_csid='0';
        $min_cscount='0';
        foreach($csArr as $k=>&$v){
            $nocouts='';
            $nocouts=M('loans_applylist')->where(array('LoanStatus'=>array('in',array('0','1','2')),'CsadminID'=>$v['ID'],'IsDel'=>'0'))->count("ID");
            if(!$nocouts){
                $nocouts='0';
            }
            $v['nocouts']=$nocouts;
            //找出最少的
            if($k=='0'){
                $min_csid=$v['ID'];
                $min_cscount=$nocouts;
            }
            if($min_cscount>=$nocouts){
                $min_csid=$v['ID'];
                $min_cscount=$min_cscount;
            }
        }
    }
    //返回分给的客服id
    $retdata=array(
        'kfid'=>$min_kefuid,
        'fkid'=>$min_fkid,
        'csid'=>$min_csid,
        );
    return $retdata;
}
/**
 * @功能说明:检查必须认证是否都认证通过
 * @param  $uid  会员id
 */
function checkmust_renz($uid){
    //查出必备验证列表
    $rzparametlist=M('renzen_parameter')->field('ID,Name,Codes')->where(array('IsShow'=>'1','IsMust'=>'1','IsDel'=>'0'))->select();
    $flags=true;//是否审核通过 默认是通过
    if($rzparametlist){
        foreach($rzparametlist as $k=>$v){
            if($v['Codes']=='card'){
                //身份认证
                $cardresult=M('renzen_cards')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$cardresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='mobile'){
                //手机认证
                $mobileresult=M('renzen_mobile')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$mobileresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='alipay'){
                //支付宝认证
                $alipayresult=M('renzen_alipay')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$alipayresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='taobao'){
                //淘宝认证
                $taobaoresult=M('renzen_taobao')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$taobaoresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='memberinfo'){
                //基本信息认证
                $memberinforesult=M('renzen_memberinfo')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$memberinforesult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='social'){
                //社交认证
                $socialresult=M('renzen_social')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$socialresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='bank'){
                //银行卡认证
                $bankresult=M('renzen_bank')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$bankresult){
                    $flags=false;//没认证通过
                    break;
                }
            }elseif($v['Codes']=='zhimafen'){
                //芝麻分认证
                $result=M('renzen_zhima')->where(array('UserID'=>$uid,'Status'=>'1','IsDel'=>'0'))->count('ID');
                if(!$result){
                    $flags=false;//没认证通过
                    break;
                }
            }
        }
    }
    return $flags;
}
/**
 * 获取年龄函数
 * @param  $birthday 出生时间 uninx时间戳
 * @param  $time 当前时间
 **/
function getage($birthday){
    //格式化出生时间年月日
    $byear=date('Y',$birthday);
    $bmonth=date('m',$birthday);
    $bday=date('d',$birthday);

    //格式化当前时间年月日
    $tyear=date('Y');
    $tmonth=date('m');
    $tday=date('d');

    //开始计算年龄
    $age=$tyear-$byear;
    if($bmonth>$tmonth || $bmonth==$tmonth && $bday>$tday){
        $age--;
    }
    return $age;
}

/**
 * 二维数组根据字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 */
 function arraySequence2($array, $field, $sort = 'SORT_DESC'){
     $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
 }
/**
 * @功能说明:统计会员完成的总的借款金额，并给期提升额度
 * @param  $uid  会员id
 */
function selfpromotes($uid){
    //统计会员总的申请金额
    $totalapplys=M('loans_applylist')->where(array('UserID'=>$uid,'LoanType'=>['in', '0,1'],'LoanStatus'=>'3','IsDel'=>'0'))->sum('ApplyMoney');
    //查询此会员的借款额度
    $LimitBalcance=M('mem_info')->where(array('ID'=>$uid))->getField('LimitBalcance');
    //借款金额列表
    $goodlists=M('goods')->field('ID,SalePrice,JSMoney')->where(array('IsShelves'=>'1','IsDel'=>'0'))->select();
    $goodlists=arraySequence2($goodlists,'SalePrice','SORT_DESC');

    $newbalance='0';//需要提升到的借款额度
    foreach($goodlists as $k=>$v){
        if($totalapplys>=$v['JSMoney']){
            $newbalance=$v['SalePrice'];break;
        }
    }
    //判断要不要更新会员表里的借款额度
    if($newbalance>$LimitBalcance){
        //提额，
        M('mem_info')->where(array('ID'=>$uid))->save(array('LimitBalcance'=>$newbalance,'UpdateTime'=>date('Y-m-d H:i:s')));
    }



}