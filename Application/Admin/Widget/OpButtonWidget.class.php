<?php
/**
 * 版权所有：合肥讯邦网络科技有限公司 [http://www.ahxb.cc]
 * 公司电话：0551-63668080
 * 作    者：陆恒
 * 修改时间：2017-04-22
 * 继承版本：1.1
 * 功能说明：按钮显示挂件
 */
namespace Admin\Widget;
use Think\Controller;

class OpButtonWidget extends Controller{
	public function OpButton() {
		$nav_list = M("sys_operationbutton")->where(array('IsDel'=>0,'Status'=>1))->order('Sort ASC')->select();
//        foreach ($nav_list as $val){
//            var_dump($val['Sort']);
//        }
//       exit;
		$this->assign("nav_list",$nav_list);
		$this->display('OpButton/OpButton');
	}
}
