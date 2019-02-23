<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明:   消息处理
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2018/6/15
// +----------------------------------------------------------------------
namespace Api\Controller\Center;
use Api\Controller\Core\BaseController;
class MessageController extends BaseController
{
    const T_MEM='mem_info';
    const T_NOTICE='notice_message';
    const T_NOTICENUM='notice_num';
    const T_READ = 'notice_read';


    /**
     * @功能说明: 提交留言
     * @传输格式: 私有token,明文传输，明文返回
     * @提交网址: /Center/Message/create
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","package":"11","version":"1.1","isaes":"0","data":{"lead":"感觉不怎么好", "pic":"/Upload/image/20170818/1503015742403379.jpg,/Upload/image/20170818/1503019069102097.jpg"}}
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function create(){

        $para = get_json_data();

        if(!file_exists("alipay.txt")){ $fp = fopen("alipay.txt","wb"); fclose($fp);  }
        $str = file_get_contents('alipay.txt');
        $str .= " - trade_no: - ".json_encode($para)." - ".date("Y-m-d H:i:s")."\r\n";
        $fp = fopen("alipay.txt","wb");
        fwrite($fp,$str);
        fclose($fp);

        $user = getUserInfo(get_login_info('ID'));

        if(!$para['lead']){
            AjaxJson(0,0,'抱歉，请输入留言内容！');
        }

        $recod = M('message')->add(array('Uid'=>$user['ID'],'Lead'=>$para['lead'],'Pic'=>$para['pic'],'Addtime'=>date('Y-m-d H:i:s')) );
        if($recod){
            AjaxJson(0,1,'留言提交成功！');
        }else{
            AjaxJson(0,0,'留言处理失败，请稍后重试！');
        }
    }


    /**
     * @功能说明: 获取消息类型
     * @传输格式: 私有token，密文返回
     * @提交网址: /Center/Message/Type
     * @提交信息：{"token":"b9QTd4+QWcXWv7N14TooigBmAyOT5EqnIY5aPO9FdjA=","client":"android","package":"11","version":"1.1","isaes":"0"}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function Type()
    {
        $para = get_json_data();

        $user = getUserInfo(get_login_info('ID'));

        //获取用户的注册时间
        $RegTime = strtotime($user['RegTime']);

        $datas = array(
            0=>array(
                'ID'=>1,
                'name'=>'通知',
                'pic'=> "http://".$_SERVER['HTTP_HOST']."/Public/images/icon_news1.png",
                'num'=>0,
                'contents' => '暂无新消息',
                'addtime' => ''
            ),
            1=>array(
                'ID'=>2,
                'name'=>'私信',
                'pic'=> "http://".$_SERVER['HTTP_HOST']."/Public/images/icon_news2.png",
                'num'=>0,
                'contents' =>'暂无新消息',
                'addtime' => ''
            ),
            //2=>array(
            //    'id'=>3,
            //    'name'=>'评论',
            //    'pic'=> "http://".$_SERVER['HTTP_HOST']."/Public/images/icon_news3.png",
            //    'num'=>0,
            //    'contents' =>'暂无新消息'
            //),
        );

        foreach ($datas as &$val){

            $where["UserID"] = array(array('eq', $user['ID']), array('eq', '0'), 'or');
            $where['Status'] = 1;
            $where['Type'] = $val['id'];
            $where["unix_timestamp(SendTime)"] = array('EGT', $RegTime);

            //获取当前消息类型下最新的消息
            $msg = M(self::T_NOTICE)->where($where)->limit(1)->field('ID,Contents,SendTime')->order("ID DESC")->find();
            //获取当前消息类型下未读消息数量
            $notice_num = M(self::T_NOTICENUM)->where(array('UID'=>$user['ID'],'Type'=>$val['id']))->getField('Num');

            if($msg){
                $val['contents'] = $msg['Contents'];
                $val['addtime'] = $msg['SendTime'];
            }
            if($notice_num) {
                $val['num'] = $notice_num;
            }
        }

        AjaxJson(1,1,'恭喜您，数据查询成功！',$datas,1,$user['KEY'],$user['IV']);
    }

    /**
     * @功能说明: 获取用户未读消息列表
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /Center/Message/index
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0","data":{"page":"1","row":"20","type":"1"}}
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function index()
    {
        $para = get_json_data();

        $user = getUserInfo(get_login_info('ID'));

        $RegTime = strtotime($user['RegTime']);

        //查询用户信息
        if ($para['page']) {
            $page = $para['page'] ? $para['page'] : 1;
            $row = $para['row'] = $para['row'] ? $para['row'] : 20;
            $limits = ($page - 1) * $row . ',' . $row;
        } else {
            $limits = 20;
        }
        //判断消息是否已读
        $read_list = M(self::T_READ)->where(array("UID" => $user['ID']))->field("MID")->select();
        $list = array();
        if ($read_list) {
            $reads = array_column($read_list,'MID');
        }
        //组装查询条件
        $where["UserID"] = array(array('eq', $user['ID']), array('eq', '0'), 'or');
        $where['Status'] = 1;
        $where['Type'] = $para['type'];
        $where["unix_timestamp(SendTime)"] = array('EGT', $RegTime);

        //查询数据和统计数据条数
        $msg = M(self::T_NOTICE)->where($where)->limit($limits)->field('ID,Contents,Title,SendTime')->order("ID DESC")->select();

        if($msg){
            foreach ($msg as $val) {
                if (in_array($val['ID'], $reads)) {
                    $val['Status'] = 1;
                } else {
                    $val['Status'] = 0;
                }
                $list[] = $val;
            }
        }else{
            $list = array();
        }

        AjaxJson(1,1,'恭喜您，数据查询成功！',$list,1,$user['KEY'],$user['IV']);

    }




    /**
     * @功能说明: 获取消息详情
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /Message/Message/msgdetail
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"1","data":{"id":"1"}}
     *   id  消息id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function msgdetail(){
        $para = get_json_data();
        $mem = getUserInfo(get_login_info('ID'));

        $user = M(self::T_MEM)->where(array('ID'=>$mem['ID'],'Status'=>array('neq','3'),'IsDel'=>0))->find();
        if(!$user){
            AjaxJson(0,0,'抱歉,未查找到相关的数据!');
        }
        //获取具体的消息
        $msg=M(self::T_NOTICE)->where(array("ID"=>$para['id'],'UserID'=>array(array('eq', $mem['ID']), array('eq', '0'), 'or')))->field("ID,Title,Contents,Type,SendTime")->find();
        if($msg==''){
            AjaxJson(0,0,'没有该消息记录！');
        }
        //查看用户有没有阅读过此消息
        $rec=M(self::T_READ)->where(array("MID"=>$msg['ID'],"UID"=>$user['ID']))->find();
        //如果没有阅读过此数据
        if(empty($rec)){
            //保存阅读记录
            M(self::T_READ)->data(array("UID"=>$user['ID'],"MID"=>$msg['ID'],"Time"=>date("Y-m-d H:i:s")))->add();
            //此消息类型的未读数量减一
            M(self::T_NOTICENUM)->where(array('UID'=>$user['ID'],'Type'=>$msg['Type']))->setDec('Num',1);

        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$msg,1,$mem['KEY'],$mem['IV']);
    }

    /**
     * @功能说明: 消息提醒设置
     * @传输格式: 私有token,明文传输，明文返回
     * @提交网址: /message/message/message
     * @提交信息：{"token":"4e6cdd8c5e951cbe107d4177a6426a57d4fe3a8117f65e97b0823659a914","client":"ios","version":"1.1","lead":"1"}
     * @返回信息: {'result'=>1,'message'=>'success',data=>array()}
     */
    public function changemes(){

        $para = get_json_data();

        $user = M(self::T_MEM)->where(array('ID'=>get_login_info('ID'),'Status'=>array('neq','3'),'IsDel'=>0))->find();
        if(!$user){
            exit(json_encode(array('result'=>-1,'message'=>'抱歉,未查找到相关的数据!')));
        }

        if(!$para['messtatus']){
            exit(json_encode(array('result'=>0,'message'=>'抱歉，暂未获取到提醒设置状态值!')));
        }

        if($para['messtatus']==2) {
            $data=array(
                'MessStatus'=>2,
                'UpdateTime'=>date('Y-m-d H:i:s')
            );
            $change = M(self::T_MEM)->where(array('ID' => $user['ID'], 'Status' => array('neq','3'), 'IsDel' => 0))->save($data);
            if ($change) {
                exit(json_encode(array('result' => 1, 'message' => '消息提醒关闭成功！')));
            } else {
                exit(json_encode(array('result' => 0, 'message' => '消息提醒关闭失败！')));
            }
        }

        if ($para['messtatus'] ==1) {
            $data=array(
                'MessStatus'=>1,
                'UpdateTime'=>date('Y-m-d H:i:s')
            );
            $change = M(self::T_MEM)->where(array('ID' => $user['ID'], 'Status' => array('neq','3'), 'IsDel' => 0))->save($data);
            if ($change) {
                exit(json_encode(array('result' => 1, 'message' => '消息提醒开启成功！')));
            } else {
                exit(json_encode(array('result' => 0, 'message' => '消息提醒开启失败！')));
            }
        }
    }

    /**
     * @功能说明: 删除消息
     * @传输格式: 私有token,有提交，明文返回
     * @提交网址: /Center/Message/del
     * @提交信息：{"token":"553bd49f30b06ba3bf37250f1c3645e49bef288c58ce41dff9d6000df619","client":"ios","version":"1.1","id":"1"}

     */
    public function del(){
        $para=get_json_data();

        $msg=M(self::T_NOTICE)->where(array("ID"=>$para['id'],'UserID'=>get_login_info('ID') ))->field("ID")->find();
        if($msg==''){
            exit(json_encode(array('result'=>0,'message'=>"没有该消息记录！")));
        }

        $find = M(self::T_NOTICE)->where(array("ID"=>$msg['ID']))->delete();

        if($find){
            $data=array(
                'result'=>1,
                'message'=>'恭喜您,删除成功!',
                'data'=>array()
            );
        }else{
            $message = '删除失败';
            if($msg['UserID'] == 0){
                $message = '系统推送消息，禁止删除';
            }
            $data=array(
                'result'=>0,
                'message'=>$message,
                'data'=>array()
            );
        }
        exit(json_encode($data));
    }

    /**
     * @功能说明: 查看会有是否有未读消息
     * @传输格式: 私有token,有提交，密文返回
     * @提交网址: /Center/Message/isnoread
     * @提交信息：{"token":"3182F49D44F72708AA00041EF2B0E5E1DEDD50FEC4E36FB12C85FFDA80DD","client":"pc","package":"11","version":"1.1","isaes":"0"}
     *  id 提额劵id
     * @返回信息: {'result'=>1,'message'=>'恭喜您，获取成功！','data'}
     */
    public function isnoread(){
        $mem = getUserInfo(get_login_info('ID'));

        $retdata=array();
        $infos=M(self::T_NOTICENUM)->where(array('UID'=>$mem['ID'],'Type'=>'1'))->find();
        if($infos){
            if($infos['Num']>0){
                $retdata['isnoread']='1';//有未读
            }else{
                $retdata['isnoread']='0';//没有未读的了
            }
        }else{
            $retdata['isnoread']='0';//没有未读的了
        }
        AjaxJson(0,1,'恭喜您，数据查询成功！',$retdata,1,$mem['KEY'],$mem['IV']);
    }

}


