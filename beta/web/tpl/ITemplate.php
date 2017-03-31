<?php
/**
 * UC乐园模板接口
 *
 * @category   ITemplate
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web\tpl;
interface ITemplate
{
    /**
     *    编译模板
     * @param string  $template  - 需要编辑的模板
     * @return  string           - 编译好的php原代码
     */
    public function parse($template);
}
