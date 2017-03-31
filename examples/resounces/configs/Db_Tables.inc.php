<?php

return array(
	//////////////////////////////////////////
	//测试列表类数据
	'test_list'=> array(
		'db' 	  	=> 'mysql',
		'fields'	=> 'userId,itemId,createTime',//","号前后不能有空格
		'shardKey'	=> 'userId',
	 ),
	//测试详细信息类数据
	'test_content'=> array(
		'db' 	  	=> 'tc',
		'fields'	=> 'feild1,feild2',//","号前后不能有空格
		'shardKey'	=> 'key',
	 ),
);
?>