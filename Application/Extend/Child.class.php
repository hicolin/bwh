<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | 功能说明: 处理给定的数组
// +----------------------------------------------------------------------
// | Author: XB.ghost.42 / 吴
// +----------------------------------------------------------------------
// | Date: 2017/9/9
// +----------------------------------------------------------------------

namespace Extend;

class Child
{


    public $array; //数组
    public $pid; // 父ID
    public $id; // ID


    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($array, $pid='ParentID', $id ='ID' ) {

        $this->array   =   $array;
        $this->pid     =   $pid;
        $this->id      =   $id;
    }

    //取出所有分类,返回一个数组
    //@param $widthself 查下级分类的时候，是否包含自己，默认true包含。
    public function getAllCategory($pid = 0,$widthself=true)
    {
        $result = array();
        $resArr = $this->array;
        if(empty($resArr)) return array();
        //取得根元素
        if($widthself)
        {
            foreach ($resArr as $item)
            {
                if($item[$this->id] == $pid)
                {
                    $result[] = $item;
                    break;
                }
            }
        }

        return array_merge($result,$this->getChildren($resArr,$pid));
    }

    function getAllCategoryID($pid = 0){

        $idArr = array();
        $cate = $this->getAllCategory($pid);

        foreach($cate as $item)
        {
            $idArr[] = $item[$this->id];
        }

        return $idArr;
    }

    private function getChildren($catArr,$pid=0)
    {
        $resultArr = array();
        $childArr = array();

        //遍历当前父ID下的所有子分类
        foreach($catArr as $item)
        {
            if($item[$this->pid] == $pid )
            {
                $childArr[] = $item;//将子分类加入数组
            }
        }

        if(count($childArr) == 0)//不存在下一级，无需继续
        {
            return array();
        }

        foreach($childArr as $item)
        {
            $resultArr[] = $item;
            $temp = $this->getChildren($catArr,$item[$this->id]);

            if(!empty($temp)) $resultArr = array_merge($resultArr, $temp);
        }

        return $resultArr;
    }


}