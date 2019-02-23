<?php
/**
 * 
 *
 *
 * 修改时间: 2017-06-27 08:30
 * 功能说明:富文本编辑器类库。
 */
namespace XBCommon;

use Think\Controller;
class Editor
{
    /**
     * 功能说明:富文本编辑器
     * @param $type 编辑器的名称: 例如ueditor,kindeditor
     * @param  $mode 编辑器的类型: 0 默认模式 1 简单模式
     * @param  $name  页面中被加载的name名称,多个使用逗号分隔
     * @param $width 富文本编辑框的宽度，默认740px
     * @param $height 富文本编辑器的高度，默认300px
     * @return string
     */
    public function editor($type='kindeditor',$mode=0,$name,$width=740,$height=300){
        $str=''; //定义一个返回的字符串
        if($name!=null){
            $names=explode(',',$name);  //可能传递的富文本编辑框的名称是多个
            foreach ($names as $items){
                switch ($type){
                    case 'ueditor':
                        //百度UE编辑器
                        switch ($mode){
                            case 0:
                                //UE编辑器-默认模式
                                $str.='var ue = UE.getEditor("'.$items.'", {'."\r\n";
                                $str.='serverUrl:\'/admin.php/Attachment/UEditor/Upload\','."\r\n";
                                $str.='initialFrameWidth:'.$width.','."\r\n";
                                $str.='initialFrameHeight:'.$height.','."\r\n";
                                $str.='elementPathEnabled:false,'."\r\n";
                                $str.='autoFloatEnabled:false,'."\r\n";
                                $str.='scaleEnabled:true,'."\r\n";
                                $str.='wordCount:false,'."\r\n";
                                $str.='fullscreen:false'."\r\n";
                                $str.='});'."\r\n";
                                break;
                            case 1:
                                //UE编辑器-精简模式
                                $str.='var ue = UE.getEditor("'.$items.'", {'."\r\n";
                                $str.='toolbars: [[\'fullscreen\', \'source\', \'|\', \'undo\', \'redo\', \'|\', \'bold\', \'italic\', \'underline\', \'fontborder\', \'strikethrough\', \'superscript\', \'subscript\', \'removeformat\', \'formatmatch\', \'autotypeset\', \'blockquote\', \'pasteplain\', \'|\', \'forecolor\', \'backcolor\', \'insertorderedlist\', \'insertunorderedlist\', \'selectall\', \'cleardoc\']],'."\r\n";
                                $str.='initialFrameWidth:'.$width.','."\r\n";
                                $str.='initialFrameHeight:'.$height.','."\r\n";
                                $str.='elementPathEnabled:false,'."\r\n";
                                $str.='autoFloatEnabled:false,'."\r\n";
                                $str.='scaleEnabled:true,'."\r\n";
                                $str.='wordCount:false,'."\r\n";
                                $str.='fullscreen:false'."\r\n";
                                $str.='});'."\r\n";
                                break;
                            default:
                                $str='UE编辑器的模式错误,例如:0,1 默认模式和简单模式';
                                break;
                        }
                        break;
                    case 'kindeditor':
                        //默认KE编辑器
                        switch ($mode){
                            case 0:
                                //KE编辑器-默认模式
                                $str.='KindEditor.create(\'textarea[name="'.$items.'"]\', {'."\r\n";
                                $str.='uploadJson : \'/admin.php/Attachment/KindEditor/Upload\','."\r\n";
                                $str.='fileManagerJson : \'/admin.php/Attachment/KindEditor/DataList\','."\r\n";
                                $str.='width :'.$width.','."\r\n";
                                $str.='height :'.$height.','."\r\n";
                                $str.='afterChange: function(){this.sync();},'."\r\n";
                                $str.='themeType : \'simple\','."\r\n";
                                $str.='allowFileManager : true'."\r\n";
                                $str.='});'."\r\n";
                                break;
                            case 1:
                                //KE编辑器-精简模式
                                $str.='KindEditor.create(\'textarea[name="'.$items.'"]\', {'."\r\n";
                                $str.='width :'.$width.','."\r\n";
                                $str.='height :'.$height.','."\r\n";
                                $str.='afterBlur: function () {this.sync(); },'."\r\n";
                                $str.='resizeType : 1,'."\r\n";
                                $str.='allowPreviewEmoticons : false,'."\r\n";
                                $str.='allowImageUpload : false,'."\r\n";
                                $str.='themeType : \'simple\','."\r\n";
                                $str.='items : ['."\r\n";
                                $str.='\'fontname\', \'fontsize\', \'|\', \'forecolor\', \'hilitecolor\', \'bold\', \'italic\', \'underline\', \'removeformat\', \'|\', \'justifyleft\', \'justifycenter\', \'justifyright\', \'insertorderedlist\', \'insertunorderedlist\', \'|\', \'emoticons\', \'image\', \'link\']'."\r\n";
                                $str.='});'."\r\n";
                                break;
                            default:
                                $str='KE编辑器的模式错误,例如:0,1 默认模式和简单模式';
                                break;
                        }
                        break;
                    default:
                        $str='编辑器的名称错误,例如ueditor,kindeditor';
                        break;
                }
            }
        }else{
            $str='文本编辑框的name不能为空！';
        }
        return $str;
    }

}