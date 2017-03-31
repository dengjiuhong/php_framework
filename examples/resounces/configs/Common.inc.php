<?php
/**
 * UC乐园  配置  基础配置文件
 *
 * @category   configs
 * @package    resources
 * @author Jiuhong Deng <dengjh@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
return array(
    // 是否为debug模式(开发模式), true会抛出所有的错误，false会只向用户返回友好提示, 生成环境设为false
    'debug'     => true,
    // 模板的缓存路径, 最好放进内存分区, 需要有PHP可写的权限
    'cachePath' => '/tmp/',
    // 基础域名配置
    'domain' => array(
        // 基础域名, 请根据实际情况修改里面的 u.uc.cn, 注意后面要加 uc_param_str=dnsspfveligi
        'base'   => 'http://u.uc.cn/?uc_param_str=dnsspfveligiwi',
        // 图片的基础域名，请配置一个与base不同域的域名, 生产环境为 http://img.u.ucfly.com/
        'static' => 'http://img.u.ucfly.com/',
        // 乐园中转页面的域名, 生产环境为 ext:e:http://cpara.uc.cn/
        'sso'    => 'http://cpara.uc.cn/',
        // 乐园http api 的地址, 生产环境为 http://api.u.uc.cn/
        'api'    => 'http://api.u.uc.cn/',
        // 乐园 动态图片的基础url, 生产环境为 http://u.uc.cn/
        'img'    => 'http://u.uc.cn/',
        // 乐园小应用的入口地址,  生产环境为 http://u.uc.cn/apps.php?uc_param_str=dnfrvepfssligiwi
        'apps'   => 'http://u.uc.cn/apps.php?uc_param_str=dnfrvepfssligiwi',
    ),
    // 接口的配置
    'protocol' => array(
        // 用户中心提供的http接口的基础地址, 生产环境为 http://reg.uc.cn/
        'sso' => 'http://regtest.uc.cn:8088/sso2/web/',
        // 获取手机号的接口, 生产环境为 http://sd.uc.cn:8197/ucuser/getpn.php, 上线之前需要和钱杨打招呼
        'pn'  => 'http://localhost/uzone/uzone/testcase/tools/mobi_gate.php',
    ),
    // cookie配置
    'cookie'     => array(
        // session的cookie配置
        'sess' => array(
            // cookie作用域，线上为u.uc.cn
            'domain'     => 'u.uc.cn',
            'expiretime' =>  360 * 24 * 60 * 60,
            'path'       => '/'
        )
    ),
    //  用户中心基本配置
    'sso' => array(
        // 用户中心分配的appId
        'appId'      => '20100127',
        // 用户中心分配的vkey
        'vKey'       => 'uczone*&^3_2010',
        // 用户中心分配的privateKey
        'privateKey' => __DIR__ . '/../key/SsoProtocol.key',
    ),
    // 输出配置
    'output' => array(
        // 输出字符集
        'charset' => 'utf-8'
    ),
    // 旧版本的路径
    'bridge' => array(
        'path' => dirname(__FILE__) . '/../../../'
    ),
    // lbs 接口的配置
    'ucpos' => array(
         //是否使用lbs查询服务接口，如果设置为false则获取不了location。
         'useLbs' => true,      
         //是否使用nearpoi接口获取附近的poi点
         'useNearPoi' => true,         
         // 位置查询服务的接口地址，新旧系统切换
         //'api' => 'http://ucpos.uc.cn/',
         'api' => 'http://ucpos.test.uc.cn:7070/',
         //php程序名路径,新旧系统切换，旧的是location，新的是ucbs
         //'appName' => 'location',
         'appName'	=> 'ucbs',
         // 位置查询服务的密钥
        'key' => 'uccheck',
        //位置接口缓存（单位：秒） 如果cache为0, 代表不缓存, 生产环境设为2分钟 120秒
        'cacheExpire' => 120,
        //地图查询接口
        'mapApi' => 'http://ucpos.uc.cn/wmap/get_map.php?',        
    ),
    // 全局配置
    'global'    => '',
    // logger配置
    'logger'    => array(
        // 日志的目录
        'path'  => __DIR__ . '/../../../logs/',
        // 日志的等级
        'level' => array(
            // 是否开启debug日志, 生产环境要设置为false
            'debug' => true,
            // 是否开启info日志, 生产环境要设置为true
            'info'  => true,
            // 是否开启warn日志, 生产环境要设置为true
            'warn'  => true,
            // 是否开启error日志, 生产环境要设置为true
            'error' => true,
            // 是否记录php错误, 生产环境要设置为true
            'php'   => true,
        )
    ),
    // 是否启用 fastcgiFinishRequest, 生产环境要设为true
    'fastcgiFinishRequest' => true,
);

