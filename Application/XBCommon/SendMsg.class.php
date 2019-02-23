<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 用户消息中心,给用户发送信息
// +----------------------------------------------------------------------
// | Author: 蔡睿
// +----------------------------------------------------------------------
// | Date: 2018/3/16
// +----------------------------------------------------------------------
namespace XBCommon;

class SendMsg
{
	public $Uid;//用户 id为0是全部  不为0传的用户id
	public $Contents;//发送的内容
	public $Title;//发送的标题，一般为空
	public $Type;//发送信息的类型 0手机短信 1通知 2私信 3评论
    public $ObjectID;//会员组别  0是书友 1是书斋 2是书房
    public $user;//  发送的对象属性  为1说明正常发送  其余按照这里面的进行推送消息

    const T_Meminfo = 'mem_info';
    const T_Noticenum = 'notice_num';
    const T_Noticemessage = 'notice_message';

	public function __construct($Type=0,$Uid=0,$ObjectID=0,$Contents='',$Title='',$user=0){
		$this->Uid = $Uid;
		$this->Contents = $Contents;
		$this->Title = $Title;
		$this->Type = $Type;
        $this->ObjectID = $ObjectID;
        $this->user = $user;
	}

	//
	public function SendSms()
	{
        if($this->Uid) {
            $where['ID'] = $this->Uid;
            $where['Status']=array('neq','3');
            $where['IsDel']=0;
        }else{
            $where['Status']=array('neq','3');
            $where['IsDel']=0;

            //加入组别的条件筛选
            if($this->ObjectID){
            }
        }
        $user = M(self::T_Meminfo)->where($where)->field('ID,Mobile,DeviceToken,LoginClient')->select();

	    //发送短信
        if($this->Type == 0){
            if($user){
                foreach ($user as $val) {
                    $UserAll[] = $val['Mobile'];
                }
            }
            if($this->Contents && $UserAll){
                send_message($UserAll, $this->Contents);
            }
        }else{

            foreach ($user as $val) {

                $map = array(
                    'UID' => $val['ID'],
                    'Type' => $this->Type,
                );
                $find = M(self::T_Noticenum)->where($map)->find();

                if($find){
                    M(self::T_Noticenum)->where($map)->setInc('Num');
                }else{
                    $map['Num'] = 1;
                    $map['UpdateTime'] = date('Y-m-d H:i:s');
                    M(self::T_Noticenum)->add($map);
                }

                //$this->upush($this->Contents,$user['LoginClient'],$user['DeviceToken']);
            }
        }
	}

	//主要用于 API 消息的推送记录
    public function HomeSms()
    {
        $model = M();
        $model->startTrans();

        $where['ID'] = $this->Uid;
        $where['Status']=array('neq','3');
        $where['IsDel']=0;

        $user = M(self::T_Meminfo)->where($where)->field('ID,Mobile,DeviceToken,LoginClient')->find();

        if(!$user){
            return;
        }

        $map_where['UserID'] = $this->Uid;
        $map_where['ObjectID'] = $this->ObjectID;
        $map_where['Title'] = $this->Title;
        $map_where['Type'] = $this->Type;
        $map_where['Contents'] = $this->Contents;
        $map_where['SendTime'] = date('Y-m-d H:i:s');

        $insert_id = M(self::T_Noticemessage)->add($map_where);

        $map = array(
            'UID' => $user['ID'],
            'Type' => $this->Type,
        );
        $find = M(self::T_Noticenum)->where($map)->find();

        if($find){
            $insert_id1 = M(self::T_Noticenum)->where($map)->setInc('Num');
        }else{
            $map['Num'] = 1;
            $map['UpdateTime'] = date('Y-m-d H:i:s');

            $insert_id1 = M(self::T_Noticenum)->add($map);
        }

        if($insert_id && $insert_id1){
            if($this->user == 1){
                $this->upush($this->Contents,$user['LoginClient'],$user['device_tokens']);
            }

            $model->commit();
        }else{
            $model->rollback();
        }

        return true;
    }

    protected function upush($Contents,$client='',$device_tokens=''){
        vendor('UmengSDKV15.Demo');

        if($device_tokens && $Contents){
            if($client == 'ios'){
                $demo = new \Demo();
                $demo->sendIOSUnicast($device_tokens,$Contents);
            }else{
                $demo = new \Demo('5ae3e897b27b0a6db2000130','qygspnxsopj60asne8zfcpl9byqwg1jr');
                $T = $demo->sendAndroidUnicast($device_tokens,$Contents);
            }
        }
    }

//主要用于 API 消息的推送记录
    public function CySms($ObjectID,$msg)
    {
        $data=array(
            'ObjectID' => $ObjectID,
            'Type' => 1,
            'Mode'=>2,
            'SendMess'=>$msg,
            'Status'=>2,
            'SendTime'=>date('Y-m-d H:i:s'),
            'Obj'=>2
        );

        $send = M('sys_sms')->add($data);

        if($send){
            return true;
        }
    }



}

