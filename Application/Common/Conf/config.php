<?php
return array(
    
	'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_DSN'                =>  '',
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'p70_',     // 数据库表前缀
    
    'DEFAULT_FILTER'        =>  'trim,htmlspecialchars',
    'DB_NAME'               =>  'p70',          // 数据库名
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8
    
    'IMAGE_CONFIG'          =>  array(
        'maxSize'           =>  1024*1024,
        'exts'              =>  array('jpg', 'gif', 'png', 'jpeg'),
        'rootPath'          =>  './Public/Uploads/',
        'viewPath'          =>  '/Public/Uploads/',
    ),
     
);