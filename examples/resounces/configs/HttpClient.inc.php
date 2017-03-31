<?php
/**
 * HttpClient配置文件
 *
 * @category   resources
 * @package    configs
 * @author Jiuhong Deng<dengjh@ucweb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
/**
 * @example
 *
 * \utils\Config::get("","HttpClient");
 */
 return array(
     // 默认发起http超时时间
     'timeout'  => '5',
     // 等待多少微秒之后重试
     'sleep'    => 100000,
     // 重试次数
     'retryNum' => 1,
     // 工具使用的ua
     'ua'       => 'Uzone HttpClient/1.0 (' . php_uname('n') . ')',
     // 使用的压缩方式
     'compression' => 'gzip',
     // 关闭http请求后，隔300秒再自动切换回正常状态
     'shutdownLfie'    => '300',
     // 每个域名到达这个失败的阀值，不会再发出http请求
     'shutdownNum'     => '1000',
     // 记录每个域名300秒里面的失败次数
     'shutdownNumLife' => '300'
 );

