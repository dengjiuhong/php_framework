<?php
/**
 * UC乐园  配置  异步记录日志的配置文件
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
    // 全局的logger配置
    'rsyslog'     => require __DIR__ . '/Rsyslog.inc.php',
    // 日志的目录
    'basePath'    => __DIR__ . '/../../logs/',
    // 日志名字的前缀
    'prefix'      => 'uzone',
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
    ),
    // 全局的logger配置结束
    
    // 每存满20条日志, flush到硬盘里面去
    'writeBuff'    => 1,
    // 模拟测试帐号的id
    'monUids'      => '9322720,9322733,9322734,9322752,9322756,9322757,9322793,9322803,9322804,9322817,9322819,9322835,9322836,9322855,9322870,9322871,9322894,9322908,9322907,9322945,9322953,9322955,9322980,9322981,9323007,9323021,9323022,9323033,9323034,9323053,9323059,9323060,9323108,9323130,9323134,9323179,9323190,9323191,9323196,9323197,9323223,9323227,9323231,9323232,9323271,9323285,9323289,9323319,9323330,9323339,9323340,9323366,9323368,9323379,9323380,9323430,9323447,9323466,9323504,9323510,9323513,9323514,9323536,9323539,9323546,9323545,9323597,9323620,9323627,9323661,9323672,9323682,9323684,9323709,9323713,9323723,9323724,9323781,9323792,9323799,9323850,9323858,9323870,9323871,9323890,9323893,9323906,9323907,9323949,9323971,9323975,9324011,9324022,9324030,9324031,9324062,9324064,9324069,9324070,9324119',
    // 已经知道的版本号
    'knownVendors' => 'nokia,sonyericsson,samsung,lenovo,tianyu,dopod,motorola,moto,bird,amoi,huawei,windows ce,sharp,haier,gionee,lg,htc,ty,zte,tcl,mot',
    // 统计log的dir
    'LOG_DIR' => __DIR__ . '/../../../logs/tongji',
    // 即时统计日志配置
    "instant_stat"=>array(
        // 即时统计范围 (计算5分钟内的PV和UV)
        'INSTANT_STAT_TIME_SPAN' => 300,
        // 即时统计结果上传URL 
        'INSTANT_STAT_UPLOAD_URL'=>'http://10.16.67.72:1979/mng/upload_instant.php'
    ),
    // 旧框架写日志配置
    'writeLog'=>array(
        'UZONE_WORKER_LOGGER_CACHE_NUM'=>2,
    ),
);

