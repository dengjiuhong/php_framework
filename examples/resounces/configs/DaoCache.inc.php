<?php
/**
 * UC乐园   DAO层缓存配置
 *
 * @category   configs
 * @package    resources
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
return array(
	//新鲜事
	'FeedCache' => array(
		'useCache' => true,//是否使用缓存
		'cache_feeds_my_feed_list_'		=> 86400 * 5,//我的新鲜事列表缓存及过期时间
		'cache_feeds_friend_feed_list_' => 86400 * 5,//好友的新鲜事列表缓存及过期时间
		'cache_feeds_feed_list_' 		=> 86400 * 5,//我和好友新鲜事列表缓存及过期时间
	),
	//说说
	'MoodCache' => array(
		'useCache' => true,//是否使用缓存
		'cache_moods_mood_'				=> 86400 * 5,//说说内容的缓存及过期时间
		'cache_moods_mood_vn_'			=> 86400 * 5,//说说浏览数的缓存及过期时间
		'cache_moods_pv_' 				=> 0,//我的说说总Pv缓存及过期时间，0代表不过期
	),
	//评论回复
	'CommentCache' => array(
		'useCache' => true,//是否使用缓存
		'cache_coms_comment_'			=> 86400 * 4,//评论/回复内容的缓存及过期时间
		'cache_comment_count_'			=> 86400 * 8,//某主题的评论/回复数的缓存及过期时间，不包括未审核通过的
	),	
	//评论回复
	'SmsCache' => array(
		'useCache' => false,//是否使用缓存
		'cache_smses_sms_'				=> 86400 * 4,//评论/回复内容的缓存及过期时间
	),
	//消息中心
	'MessageCache' => array(
		'useCache' => true,//是否使用缓存
		'cache_messages_total_'			=> 1800,//用户对应类型的消息总数过期时间
		'cache_messages_unread_'		=> 1800,//用户对应类型的未读数过期时间
	),	
    //用户信息查询
	'UsersCache' => array(
		'useCache' => true,//是否使用缓存
		'cache_userprivilege_total_'	=> 0,//用户特权缓存过期时间
	),	
	// 城市中用户的缓存列表
	'datalevel\dao\impls\cache\CityUserCache' => array(
	   'useCache' => true, //是否使用缓存
	   'cache_cityuser_list_' => 864000, // 城市中用户的缓存过期时间
	),
);
?>

