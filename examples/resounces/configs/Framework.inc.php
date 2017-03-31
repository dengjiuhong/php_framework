<?php
/**
 * UC乐园 - 
 *
 * @category   -
 * @package    -
 * @author     Jiuhong Deng <dengjh@ucewb.com>
 * @version    $Id:$
 * @copyright  优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
return array(
    // 定义框架的根目录
    'basePath' => __DIR__ . '/../../../',
    // 定义框架的版本号
    'version'  => 'beta',
    // 基础层配置
    'base'     => array(
        // 是否启用xhprof性能日志
        'xhprof'       => true,
        // 事件
        'events'       => array(
            // 初始化的时侯触发的事件
            'onRun' => array(
                array('\base\events\AppStart', 'run')
            ),
            // 程序结束的时侯触发的事件
            'onFinish' => array(
                array('\\base\events\AppEnd', 'run')
            )
        ),
        // 配置相关
        'configs'      => array(
            // 配置文件的基础路径
            'basePath' => __DIR__,
        ),
        // i18n语言包配置
        'i18n' => array(
            'lan' => array(
                'zh_CN' => __DIR__ . '/../i18n/zh_CN.php',
            )
        ),
    ),
    // 初始化web的基本配置
    'web' => array(
        // 返回头部的字符类型
        'charset'  => 'UTF-8',
        // 业务所在目录
        'basePath' => __DIR__ . '/../../apps/',
        // 模版相关配置
        'view' => array(
            // 应用全局模版的位置
            'tplPath'       => __DIR__ . '/../../apps/templates/',
            // 模版缓存的位置
            'tplCache'      => '/dev/shm/',
            'realTimeParse' => false,
            // 自定义的模版解析器
            'parser'        => array(
                // 自定义解析模版里面的特殊标签
                '/\{url\s+(.+?)\}/ies'                   => array('\base\TplParse', 'url'),
                '/\{util\s+(.+?)\}/ies'                  => array('\base\TplParse', 'util'),
                '/[\n\r\t]*\{csstemplate\}[\n\r\t]*/ies' => array('\base\TplParse', 'css'),
            	'/\{UCExtra}/ies'                        => array('\base\TplParse', 'ucextra')
            )
        )
    ),
    // service层基础配置
    'services' => array(
        // 配置service的接口实现影射
        'implsMapper' => __DIR__ . '/../../services/SvrImplsMap.php'
    ),
    // 数据层基础配置
    'datalevel' => array(
        'data' => array(
            'type' => ''
        )
    ),
    // 消息队列基础配置
    'tasks'     => array(
        'workerType'  => '',
    )
);
