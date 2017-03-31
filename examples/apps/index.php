<?php
/**
 * UC乐园web入口
 *
 * @category   index
 * @package    intry
 * @author Jiuhong Deng <dengjh@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace apps;

// 项目的根路径
define('PROJECT_BASEPATH', __DIR__ . '/../');
// 获取框架的配置文件
$config = require __DIR__ . '/../resounces/configs/Framework.inc.php';
// 加载框架的基础Loader
require $config['basePath'] . '/Loader.php';
\framework\base\Web::getInstance($config)->run();

