<?php
/**
 * UC乐园  基础支撑平台  - 通用验证实现的抽象类
 *
 * @see 所有的校验都要继承这个抽象类
 *
 * @category   Validator
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web\validators;
abstract class AbstractValidator
{
    /**
     *    验证的操作
     *
     * @param $stirng  $rule     - 校验的名称
     * @param &String  $attr     - 需要验证的数据
     * @param &String  $msg      - 返回了的描述信息
     * @param Array    $options  - 校验的配置
     * @return boolean           - 是否通过验证
     */
    public function validateAttribute($rule = '', &$attr = '', &$msg = '', $options = array())
    {
        return true;
    }
}
