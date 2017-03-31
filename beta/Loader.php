<?php
declare(encoding='UTF-8');
namespace framework;
/**
 * 加载每次都需要使用的类
 */
if (defined('IS_LOAD_FRAMEWORK')) return;
// 定义开始执行的时间
define('FRAMEWORK_TIMESTAMP_APPSTART', microtime(true));
// 加载基础的类
require dirname(__FILE__) . '/base/AutoLoader.php';
require dirname(__FILE__) . '/base/Config.php';
require dirname(__FILE__) . '/base/Event.php';
require dirname(__FILE__) . '/base/I18n.php';
require dirname(__FILE__) . '/base/Logger.php';
require dirname(__FILE__) . '/base/Exception.php';
require dirname(__FILE__) . '/base/ExceptionRegister.php';
require dirname(__FILE__) . '/base/Control.php';

// 加载web服务用到的基础类
require dirname(__FILE__) . '/web/Acl.php';
require dirname(__FILE__) . '/web/Validator.php';
require dirname(__FILE__) . '/web/View.php';
require dirname(__FILE__) . '/web/tpl/Template.php';
require dirname(__FILE__) . '/web/Request.php';
require dirname(__FILE__) . '/web/Url.php';

// 加载service层用到的基础类
require dirname(__FILE__) . '/services/Factory.php';

// 加载数据层用到的基础类
require dirname(__FILE__) . '/datalevel/base/Factory.php';
require dirname(__FILE__) . '/datalevel/dal/Dal.php';
require dirname(__FILE__) . '/datalevel/dao/Factory.php';

// 加载队列拥到的基础类
require dirname(__FILE__) . '/tasks/Manager.php';

// 加载常用的基础类
require dirname(__FILE__) . '/utils/Images.php';
require dirname(__FILE__) . '/utils/Result.php';
require dirname(__FILE__) . '/utils/String.php';
define('IS_LOAD_FRAMEWORK', true);
