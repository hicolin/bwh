<?php
/**
 * 版权所有：
 * 公司电话：0551-63668080
 * 作    者：高伟
 * 修改时间：2017-04-18
 * 功能说明：加编辑按钮
 */
namespace Admin\Controller\System;
use Think\Controller;
class AidPageController extends BaseController {

	const T_OPERATIONBUTTON = 'sys_operationbutton';

	/**
	 * @功能说明：显示图标
	 * @return      [type]                           [description]
	 */
    public function icon(){
		$this->display();
	}

	/**
	 * @功能说明：显示按钮
	 * @return      [type]                           [description]
	 */
	public function menuButton(){
		$this->display();
	}

	/**
     * 高德地图
     */
	public function map(){
	    $X=I('request.X');
        $Y=I('request.Y');
        $Name=I('request.Name');
        $this->assign('X',$X);
        $this->assign('Y',$Y);
        $this->assign('Name',$Name);
        $this->display();
    }

}