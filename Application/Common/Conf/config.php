<?php
return array(
    /*数据库配置*/
	'DB_TYPE'   => 'mysql',         // 数据库类型我们是mysql，就对于的是mysql
	'DB_HOST'   => 'localhost',   // 服务器地址，就是我们配置好的php服务器地址，也可以使用localhost，
    'DB_NAME'   => 'api_feiyuandai',      // 数据库名：mysq创建的要连接我们项目的数据库名称
    'DB_USER'   => 'api_feiyuandai',           // 用户名：mysql数据库的名称
    'DB_PWD'    => 'p4HFmLPasjd6MWd6',                 //mysql数据库的 密码
	'DB_PORT'   => '3306',            // 端口服务端口一般选3306
	'DB_PREFIX' => 'xb_',            //  数据库表前缀
	'DB_CHARSET'=> 'utf8',         // 字符集
	'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),
    'URL_CASE_INSENSITIVE' => false,
    'DB_RW_SEPARATE'        => false,     // 数据库读写是否分离 主从式有效

     /*邮箱配置*/
    'MAIL_SMTPAUTH' =>TRUE, //启用smtp认证
    'MAIL_CHARSET' =>'utf-8',//设置邮件编码
    'MAIL_ISHTML' =>TRUE, // 是否HTML格式邮件
    //配置文件信息
    'LOAD_EXT_CONFIG' => array('Help'=>'help'),
//    'SHOW_PAGE_TRACE' => TRUE,

    //芝麻接口
    'Zhima_appKey' => '2125573CE2B645A3A788D4BF7E4C34F1',
    'Zhima_appSecret' => '42A4FE71283B4B4CA017B2CF87750705EE7A705A',
);