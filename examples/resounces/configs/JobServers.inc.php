<?php
/**
 * UC乐园  配置  jobserver 配置文件
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
    // 默认worker分组
    'default' => array(
        'addr'    => '127.0.0.1:1411',
        'timeout' => 3000
    ),
    //新鲜事分组
    'feedJobServer' => array(
        'addr'    => '127.0.0.1:1411',
        'timeout' => 1000
    ),
);
