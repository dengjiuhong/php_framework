<?php
/**
 * UC乐园  任务管理器  - 任务派发配置
 *
 * @category   configs
 * @package    tasks
 * @author     Jiuhong Deng <dengjh@ucewb.com>
 * @version    $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace resources\configs;
use \tasks\WorkerTypes as Type;
return array(
    // 处理新鲜事灌水
    Type::INSERT_DATA_TO_NEW_FRAME => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'feedJobServer',//这个需要和旧系统的一致否则无法灌水
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'insertDataToNewFrame',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\feeds\Feed', 'pushFeedByWorker'),
        // worker的描述信息
        'desc'       => '处理新鲜事灌水'
    ),
    Type::PREMERGE_FOR_NEW_FRAME => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'feedJobServer',//这个需要和旧系统的一致否则无法灌水 这里要用default的
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'preMergeForNewFrame',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\feeds\Feed', 'preMergeByWorker'),
        // worker的描述信息
        'desc'       => '处理新鲜事灌水'
    ),
    // 处理我自己的新鲜事合并
    Type::FEED_MERGE_NEW_MY_FEED => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'feedJobServer',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'feedMergeNewMyFeed',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\feeds\Feed', 'mergeMyFeedByWorker'),
        // worker的描述信息
        'desc'       => '合并我自己的新鲜事'
    ),
    // 处理好友新鲜事合并
    Type::FEED_MERGE_NEW_FRIEND_FEED => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'feedJobServer',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'feedMergeNewFriendFeed',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\feeds\Feed', 'mergeFriendFeedByWorker'),
        // worker的描述信息
        'desc'       => '合并好友新鲜事'
    ),
    // 处理图片上传
    Type::IMAGE_WORKER => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'resizeserver_imageworker',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array(),
        // worker的描述信息
        'desc'       => '处理图片上传请求'
    ),
    // 处理图片缩放
    Type::IMAGE_RESIZE_BACKGROUND => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'resizeserver_background_imageworker',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\image\Image', 'resizeImage'),
        // worker的描述信息
        'desc'       => '处理图片缩放'
    ),
    // 更新用户缓冲区域
    Type::USER_CACHE_UPDATE_BUFFER => array(
        // 指定派发到与user_cache_update相同的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为5
        // 注意，threadNum不要太多，这个worker速度很快的。
        'threadNum'  => 1,
        // worker 生存时间 1800s
        'lifeTime'   => 7200,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000000,
        // 注册监听的事件
        'event'      => 'user.update.usercache.buffer',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('apps\cache\indexcache\IndexCache', 'doBufferTask'),
        // worker的描述信息
        'desc'       => '更新首页缓存任务缓冲区'
    ),
    // 更新用户缓存块
    Type::USER_CACHE_UPDATE => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为20个
        'threadNum'  => 1,
        // worker 生存时间 1800s
        'lifeTime'   => 7200,
        // 每个worke r处理的任务数量
        'handleNum'  => 1000000,
        // 注册监听的事件
        'event'      => 'user.update.usercache',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('apps\cache\indexcache\IndexCache', 'doUpdateTask'),
        // worker的描述信息
        'desc'       => '更新首页缓存'
    ),
    // 新框架写日志
    Type::LOGGER => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'writeLog',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\WriteLog', 'excue'),
        // worker的描述信息
        'desc'       => '日志处理'
    ),
    
    // 发送消息
    Type::MESSAGE_ADD => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'messageAdd',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\messages\Message', 'addMessage'),
        // worker的描述信息
        'desc'       => '发送消息'
    ),
    // 设置对应类型的消息为已读
    Type::MESSAGE_SET_READED_BYGROUP => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'messageSetReadedByGroup',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\messages\Message', 'setReadedByGroup'),
        // worker的描述信息
        'desc'       => '设置对应类型的消息为已读'
    ),
    // 处理的sphinx全文查询
    TYPE::SPHINX_SEARCH_DEFAULT => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 7200,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'sphinxSearchDefault',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('datalevel\dao\impls\fullsearch\SphinxWorkerDefault', 'dealGmnClientReq'),
        // worker的描述信息
        'desc'       => '处理sphinx全文查询'
    ),
    // 更新可能认识的人
    Type::RECUSER_UPDATE => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum' => 1,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'search_buildrecuser',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array(),
        // worker的描述信息
        'desc'       => '更新可能认识的人列表'
    ),
    // 更新常在大学及更新推荐uids列表
    Type::USUALUNIV_UPDATE => array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'usualUnivUpdate',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('services\modules\usualrecommend\UsualUnivWorker', 'run'),
        // worker的描述信息
        'desc'       => '更新常在大学及更新推荐uids列表'
    ),
    // 处理其他平台图片缩放
    Type::IMG_RESIZE_OTHERPY=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'resizeimgbackground_imageworker',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ImgResizeOtherPfWorker', 'resize'),
        // worker的描述信息
        'desc'       => '处理其他平台图片缩放'
    ),
    // 删除Fastdfs里的图片
    Type::DEL_FASTDFS_IMG=> array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'snsup_file_delfromfastdfs',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\DeleteFastDFSImgWorker', 'deleteImg'),
        // worker的描述信息
        'desc'       => '删除Fastdfs里的图片'
    ),
    // 日志即时统计
    Type::LOGGER_INSTANT_STAT=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'instantStat',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\InstantStatWorker', 'stat'),
        // worker的描述信息
        'desc'       => '日志即时统计'
    ),
    // 旧框架写日志
    Type::LOGGER_WRITELOG_OLD=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'logger',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\WriteLogOldWorker', 'write'),
        // worker的描述信息
        'desc'       => '日志即时统计'
    ),
    // 更新LV
    Type::LV_UPDATE=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'ucsns_lv_update',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\LvUpdateWorker', 'update'),
        // worker的描述信息
        'desc'       => '更新LV'
    ),
    // 更新用户信息，用于更新索引
    Type::SEARCH_UPDATE_USER_INDEX=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'snsapi_search_user',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\SearchUpdateUserWorker', 'update'),
        // worker的描述信息
        'desc'       => '更新用户信息，用于更新索引'
    ),
    // 异步通知相关业务退出
    Type::SSO_LOGOUT_CALLBACK=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'snsapi_sso_logoutcallback',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\SsoLogoutCallbackWorker', 'logout'),
        // worker的描述信息
        'desc'       => '异步通知相关业务退出'
    ),
    // 任务更新
    Type::TASK_UPDATE=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'ucsns_task_update',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\TaskUpdateWorker', 'update'),
        // worker的描述信息
        'desc'       => '任务更新'
    ),
    // 分享点击记录
    Type::CLICK_SHARE=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'ucsns_shareClick',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ClickShareWorker', 'click'),
        // worker的描述信息
        'desc'       => '分享点击记录'
    ),
    // 照片点击记录
    Type::CLICK_PHOTO=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'ucsns_photoClick',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ClickPhotoWorker', 'click'),
        // worker的描述信息
        'desc'       => '照片点击记录'
    ),
    // 访客点击记录
    Type::CLICK_VISIT=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'ucsns_visitAdd',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ClickVisitWorker', 'click'),
        // worker的描述信息
        'desc'       => '访客点击记录'
    ),
    // API发送好友请求
    Type::API_SENDFRIENDSREQUEST=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'api.friends.sendRequest',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ApiSendFriendsRequestWorker', 'doTask'),
        // worker的描述信息
        'desc'       => 'API发送好友请求'
    ),
    // API日志打印
    Type::API_LOGGER=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'api.logger',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ApiLoggerWorker', 'doTask'),
        // worker的描述信息
        'desc'       => 'API日志打印'
    ),
    // API发布图片
    Type::REC_BUILD=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'dobuildRcJob',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\RecBuildWorker', 'build'),
        // worker的描述信息
        'desc'       => '更新新鲜事'
    ),
    // API发布图片
    Type::API_PUSH_PHOTO=>array(
        // 指定派发到默认的jobServer
        'jobServer'  => 'default',
        // 每个worker的进程数量, 生产环境为10个
        'threadNum'  => 2,
        // worker 生存时间 1800s
        'lifeTime'   => 1800,
        // 每个worke r处理的任务数量
        'handleNum'  => 10000,
        // 注册监听的事件
        'event'      => 'api.photo.push',
        // 自动调用下面的方法处理这个worker的任务, 如果留空，则不处理, 交由第三方接口处理
        'handler'    => array('workers\script\ApiPushPhotoWorker', 'doTask'),
        // worker的描述信息
        'desc'       => 'API发布图片'
    ),
);


