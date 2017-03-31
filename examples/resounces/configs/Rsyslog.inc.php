<?php
/**
 * UC乐园  配置  Rsyslog配置文件
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
    // 是否使用rsyslog作为log集中式管理的工具, 生产环境上面请配置为true
    // rsyslog的详细配置文件见 ucsns/uzone/resources/misc/rsyslog/目录下面的client.conf, server.conf
    // 客户端配置 client.conf
    //     注意 WorkDirectory 配置为本地的 ucsns/logs 文件夹
    // 服务端配置 server.conf
    //     注意配置里面的log存放的文件夹
    // 将 uzone_rsyslog.conf 拷贝到 /etc/rsyslog.d/目录下面即可。
    // windows 开发环境下面可以设为false, false的时侯，日志还是会以原来的方式写到本地
    'isRsyslog'    => false,
    // log option, (default) delay opening the connection until the first message is logged
    'option'       => LOG_ODELAY,
    // 乐园rsyslog使用的facility, 根据实际情况分配，注意要和client.conf, server.conf里面LOCAL4的保持一致
    'facility'     => LOG_LOCAL4,
    // 需要开启记录的日志类型
    // 生产环境需要把 LOG_DEBUG 一行注释掉
    'priority'     => array(
        LOG_EMERG,   //  system is unusable
        LOG_ALERT,   //  ction must be taken immediately
        LOG_CRIT,    //  critical conditions
        LOG_ERR,     //  error conditions
        LOG_WARNING, //  warning conditions
        LOG_NOTICE,  //  normal, but significant, condition
        LOG_INFO,    //  informational message
        LOG_DEBUG,   //  debug-level message
    )
);

