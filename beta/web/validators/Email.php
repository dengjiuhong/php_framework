<?php
/**
 * UC乐园  基础支撑平台  - 通用验证的实现  - email校验
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
class Email extends AbstractValidator
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
        if(! preg_match("/^[a-z0-9-_.]+@[\da-z][\.\w-]+\.[a-z]{2,4}$/i", $attr) ) {
            $msg = '非法的email地址';
            return false;
        }
        return true;
    }
}
