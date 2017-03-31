<?php
/**
 * UC乐园   业务层服务接实现类映射关系配置
 *
 * @category   common
 * @package    services
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace services\common;
//服务接口与实现类的映射关系
return array(
    // 接口名字       实现类的名字(带命名空间)
    "ITest"        => 'services\modules\test\Test',
);
