<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * UC乐园基础框架 - 基础类 - 使用命名空间的特性自动加载类
 *
 * @category   autoloader
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2011 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
class AutoLoader
{
    /**
     *    使用命名空间的类可以自动加载, 没有命名空间的不做自动加载的处理
     *
     * @param String  $class  - 类的名字, 需要带命名空间
     * @return boolean        - 是否加载成功
     */
    public static function getInstance($class)
    {
        if (isset(self::$isLoaded[$class])) return true;
        if (strpos($class, '\\')){
            // framework开头的为框架的基类
            if (strpos($class, 'framework') === 0){
                $file = __DIR__ . '/../../' . str_replace(array('\\', 'framework'), array('/', 'beta'), $class) . '.php';
            } else {
                // 项目本身的类
                $file = PROJECT_BASEPATH . '' . str_replace('\\', '/', $class) . '.php';
            }
            require_once $file;
            self::$isLoaded[$class] = true;
            return true;
        } else {
            return false;
        }
    }
    public static $isLoaded = array();
}
spl_autoload_register(array('\framework\base\AutoLoader', 'getInstance'), false);
