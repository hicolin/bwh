<?php
/**
 * 
 *
 *
 * 修改时间: 2017-05-09 10:42
 * 功能说明:缓存类库，处理频繁调用且更新无需实时变动的数据。
 */
namespace XBCommon;

use Think\Controller;

class XBQuery extends Controller
{
    /**
     * 获取一张表中指定条件的行,默认按ID升序排列 带分页参数
     * @access public
     * @param string $table 要查询的表，必填否则返回false
     * @param array $where 查询条件
     * @param int $page 分页数
     * @param int $rows 每页显示的行数
     * @param string $sort  排序字段和方式 默认 ID ASC
     * @param string $cols 指定输出的字段或列名
     * @param $bool  排除标记:默认false表示查询cols指定的列，为true时表示查询cols之外的列
     * @return array $data 返回二维数组数据
     */
    public function GetDataList($table,$where=null,$page=1,$rows=20,$sort=null,$cols=null,$bool=false){
        $data=array();
        //参数中表为空时，或表不存在实例化失败,返回false
        if($table){
            //表不为空时，进行实例化
            $model=M($table);
            //若排序参数为空，默认ID升序
            if(!$sort){
                $sort='ID ASC';
            }
            //判断是否查询指定字段，默认返回所有字段
            if($cols==null){
                //若条件为空，返回指定分页的所有行，默认第一页前20行
                if($where){
                    $data['rows']=$model->where($where)->order($sort)->limit(($page-1)*$rows,$rows)->select();
                }else{
                    $data['rows']=$model->order($sort)->limit(($page-1)*$rows,$rows)->select();
                }
            }else{
                if($where){
                    if($bool==true){
                        $data['rows']=$model->where($where)->order($sort)->field($cols,true)->limit(($page-1)*$rows,$rows)->select();
                    }else{
                        $data['rows']=$model->where($where)->order($sort)->field($cols)->limit(($page-1)*$rows,$rows)->select();
                    }
                }else{
                    if($bool==true){
                        $data['rows']=$model->order($sort)->field($cols,true)->limit(($page-1)*$rows,$rows)->select();
                    }else{
                        $data['rows']=$model->order($sort)->field($cols)->limit(($page-1)*$rows,$rows)->select();
                    }
                }
            }
            $data['total']=$model->where($where)->count();
            return $data;
        }else{
            return false;
        }
    }

    /**
     * 根据指定的条件，将查询结果以一维数组的形式全部返回 不带分页参数
     * @access public
     * @param string $table 要查询的表，必填否则返回false
     * @param array $where 查询条件
     * @param string $sort  排序字段和方式 默认 ID ASC
     * @param string $cols 指定输出的字段或列名
     * @param $bool  排除标记:默认false表示查询cols指定的列，为true时表示查询cols之外的列
     * @return array $data 返回一维数组数据
     */
    public function GetList($table,$where=null,$sort=null,$cols=null,$bool=false){
        if($table){
            //表不为空时，进行实例化
            $model=M($table);
            //创建空数组，用于整合返回值
            $data=array();
            //若排序参数为空，默认ID升序
            if(!$sort){
                $sort='ID ASC';
            }
            //判断是否查询指定字段，默认返回所有字段
            if($cols==null){
                //若条件为空，返回指定分页的所有行，默认第一页前20行
                if($where){
                    $data=$model->where($where)->order($sort)->select();
                }else{
                    $data=$model->order($sort)->select();
                }
            }else{
                if($where){
                    if($bool==true){
                        $data=$model->where($where)->order($sort)->field($cols,true)->select();
                    }else{
                        $data=$model->where($where)->order($sort)->field($cols)->select();
                    }
                }else{
                    if($bool==true){
                        $data=$model->order($sort)->field($cols,true)->select();
                    }else{
                        $data=$model->order($sort)->field($cols)->select();
                    }
                }
            }
            return $data;
        }else{
            return false;
        }
    }

    /**
     * 根据指定的条件，获取一个字段的值
     * @access public
     * @param string $table 要查询的表，必填否则返回null
     * @param array $where 查询条件，为空时默认返回首行
     * @param string $col 指定输出的一个字段或列名，为空则返回null
     * @return string $data 返回一个string类型的值
     */
    public function GetValue($table,$where=null,$col=null){
        if($table && $col){
            $model=M($table);
                $data=$model->where($where)->getField($col);
            return $data;
        }else{
            return null;
        }
    }

    /**
     * 获取自定分类的所有子分类ID号
     * @access public
     * @param string $table 要查询的表
     * @param string $PName 父级ID名称
     * @param string $categoryID 要遍历的ID值
     * @param int $rt 返回数据类型，默认0返回数组，1返回字符串
     * @return array $array 默认0返回数组，1返回字符串
     */
    public function GetAllChildCateIds($table,$PName,$categoryID=null,$rt=null){
        //初始化ID数组
        $array[] = $categoryID;
        if($table && $PName){
            do
            {
                $ids = '';
                $where[$PName] = array('in',(string)$categoryID);
                $model=M($table);
                $cate = $model->where($where)->select();
                foreach ($cate as $k=>$v)
                {
                    $array[] = $v['ID'];
                    $ids .= ',' . $v['ID'];
                }
                $ids = substr($ids, 1, strlen($ids));
                $categoryID = $ids;
            }
            while (!empty($cate));
        }
        if($rt==null){
            return $array;
        }else{
            $ids = implode(',', $array);
            return $ids;    //  返回字符串
        }
    }

}