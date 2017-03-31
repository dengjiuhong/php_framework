<?php
return array(
	//服务器
	'A001'=>array(
		'master'=>'server01',//主服务器,与ucdbi.ini 里面的proxy.server01 的server01对应。
		'slaver'=>'server01',//从服务器,可以是多个服务器以逗号","分隔。也可以设置为空。
		'connectType'=>1,//或者是0。1-长连接, 0-短连接
	),
	'B001' => array(
		'master'=>'127.0.0.1:1611',//主服务器
		'slaver'=>'127.0.0.1:1611',//从服务器,可以是多个服务器以逗号","分隔。也可以设置为空。
		'connectType'=>1,//或者是0。1-长连接, 0-短连接
	),
);
?>