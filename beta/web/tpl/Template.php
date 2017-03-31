<?php
/**
 * UC乐园模板接口的实现
 *
 * @category   Template
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web\tpl;
require dirname(__FILE__) . '/ITemplate.php';
require dirname(__FILE__) . '/libs/TemplateParse.php';
final class Template implements \framework\web\tpl\ITemplate
{
    /**
     *    编辑模板
     * @param String   $tpl  - 待编译的模板
     * @return string        - 编译好的模板
     */
    public function parse($tpl)
    {
        $obj = new TemplateParse();
        return $obj->parse($tpl);
    }
    /**
     *   取得单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    public static $obj = null;
    public function __construct()
    {
    }
}

