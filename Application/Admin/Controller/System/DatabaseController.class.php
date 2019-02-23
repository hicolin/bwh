<?php
/**
 * 
 *
 *
 * 修改时间: 2017-05-25 10:18
 * 功能说明:数据库备份还原模块
 */
namespace Admin\Controller\System;

use Think\Controller;
use XBCommon;
use Think\Db;
use OT\Database;
class DatabaseController extends  BaseController
{
    private $_dir;  //备份路径
    public function __construct()
    {
        parent::__construct();
        $this->_dir=$_SERVER['DOCUMENT_ROOT'].'\\BackUp\\Database\\';  //备份路径赋值
        header("Content-type: text/html;charset=utf-8");
    }

    /**
     * 检查备份目录是否存在，若不存在则进行创建
     */
    public function CheckDir(){
        //获取指定目录的文件，并以json的形式返回total和rows
        //如果目录不存在，自动生成
        if(!file_exists($this->_dir)){
            $result=mkdir($this->_dir);
            if(!$result){
                $this->ajaxReturn(false,'自动创建目录失败，可能权限不足！');
            }
        }
    }

    /**
     * 数据库备份列表
     */
    public function index(){
        $this->display();
    }

    public function DataList(){
        //拼接分页条件
        $page=I('post.page',1,'intval');
        $rows=I('post.rows',20,'intval');

        //检查备份目录
        $this->CheckDir();

        //遍历某个目录下的所有匹配扩展名的文件，返回ID,NAME,SIZE,DATETIME几个字段！
        $files= glob($this->_dir.'*.gz');

        //把一维转带键名的二维数组
        $list  = array();
        foreach($files as $val){
            $arr['ID']=basename($val);  //以文件名为ID
            $arr['Name']=basename($val);  //获取文件名称
            $arr['Size']=(filesize($val) >> 10).'KB';  //获取文件大小
            $arr['DateTime']=date('Y-m-d H:i:s',filectime($val));  //获取文件生成时间，并转换为日期时间格式
            $list[]=$arr;
        }

        //将二维数组转化为EasyUI能识别的json数组输出
        $result['rows']=$list;
        $result['total']=count($list);
        $this->ajaxReturn($result);
    }

    /**
     * 备份数据库
     * @param  String  $tables 表名
     * @param  Integer $id     表ID
     * @param  Integer $start  起始行数
     */
    public function Backup($id = null, $start = null){
        $Db    = Db::getInstance();
        $list  = $Db->query('SHOW TABLE STATUS');
        $list  = array_map('array_change_key_case', $list);

        $tables=array();
        foreach ($list as $val){
            $tables[]=$val['name'];
        }
        $total=count($tables);

        //var_dump($tables['0']);exit;
        if(IS_POST && !empty($tables) && is_array($tables)){ //初始化
            $path = $this->_dir;
            if(!is_dir($path)){
                mkdir($path, 0755, true);
            }
            //读取备份配置
            $config = array(
                'path'     => realpath($path) . DIRECTORY_SEPARATOR,  //压缩包路径
                'part'     => 20971520,   //一个压缩包20M
                'compress' => 1,
                'level'    => 9,          //压缩等级
            );

            //检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if(is_file($lock)){
                $this->ajaxReturn(false,'检测到有一个备份任务正在执行，请稍后再试！');
            } else {
                //创建锁文件
                file_put_contents($lock, NOW_TIME);
            }

            //检查备份目录是否可写
            is_writeable($config['path']) || $this->ajaxReturn(false,'备份目录不存在或不可写，请检查后重试！');
            session('backup_config', $config);

            //生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', NOW_TIME),
                'part' => 1,
            );
            session('backup_file', $file);

            //缓存要备份的表
            session('backup_tables', $tables);

            //创建备份文件
            $Database = new Database($file, $config);
            if(false !== $Database->create()){
                $id=0;
                $start=0;
                if(is_numeric($id) && is_numeric($start)){

                    $tables = session('backup_tables');
                    //备份指定表
                    $Database = new Database(session('backup_file'), session('backup_config'));

                    //循环导出数据库中的表数据
                    $j=1;
                    for($i=0;$i<$total;$i++){
                        //备份数据库start
                        $Database->backup($tables[$i], $start);

                        if(empty($tables[$j++])){
                            //备份完成，清空缓存
                            unlink(session('backup_config.path') . 'backup.lock');
                            session('backup_tables', null);
                            session('backup_file', null);
                            session('backup_config', null);
                            $this->ajaxReturn(true,'备份完成');
                        }
                        //备份数据库end
                    }
                }
            } else {
                $this->ajaxReturn(false,'初始化失败，备份文件创建失败！');
            }
        } else { //出错
            $this->ajaxReturn(false,'参数错误');
        }
    }

    /**
     * 数据库备份删除
     */
    public function Del(){
        $IDS=I('post.ID');
        if(!empty($IDS)){
            $FileNames=explode(',',$IDS);  //转化为一维数组
            $tag=0; //删除标记
            foreach ($FileNames as $filename){
                $FilePath=$this->_dir.$filename;
                if(file_exists($FilePath) && unlink($FilePath)){
                   $tag=1;
                }
            }
            if($tag==1){
                $this->ajaxReturn(true,'删除成功！');
            }else{
                $this->ajaxReturn(false,'删除失败,可能文件不存在或者权限不足！');
            }

        }else{
            $this->ajaxReturn(false,'未指定删除文件，操作失败！');
        }
    }



    /**
     * 数据库备份文件下载到本地
     */
    public function Download(){
        if(IS_GET){
            $name=I('get.ID');
            $path=$this->_dir;
            download($path,$name);  //调用下载函数
            } else {
                $this->ajaxReturn(false,'参数传递错误');
            }

    }



    /**
     * 还原数据库
     */
    public function Restore($time = 0, $part = null, $start = null){
        $this->ajaxReturn(true,'请下载数据库备份,使用数据库进行工具恢复！');
    }

}