<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2017/10/10
// +----------------------------------------------------------------------

namespace Api\Controller\Home;
use Think\Controller;

class PublicController extends Controller
{


    public function t(){

        echo 'a<br/>';

        $t = encrypt_pkcs7('123456789','KEY2A1CCAD42E78855BE9683BEDE000A','IV0D7D9F75ECC8FD');
        echo $t;
    }

    public function verify(){
        $id = I('get.mid','0','trim');

        $config = array(
            'fontSize' => 30,    // 验证码字体大小
            'length' => 4,     // 验证码位数

            // 验证码字体，不设置随机获取
            'fontttf'  => '4.ttf',
        );
        $Verify =new \Think\Imgcode($config);
        $Verify->codeSet = '12345689';

        ob_end_clean();
        $Verify->entry();
    }

}