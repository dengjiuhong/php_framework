<?php
/**
 * UC乐园  基础支撑平台  - 通用验证的实现  - 字符串校验
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
class String extends AbstractValidator
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
        return $this->$rule($attr, $options, $msg);
    }
    /**
     *    校验长度
     * @param $attr    - 需要校验的内容
     * @param $options - 长度校验
     */
    public function length(&$attr, $options = array(), &$msg = '')
    {
        $start = isset($options[0]) ? $options[0] : 0;
        $end   = isset($options[1]) ? $options[1] : 0;
        $len   = \apps\base\Util::strlen($attr);
        if ($len > $start && $len <= $end){
            return true;
        }
        $msg = '长度不符合要求, 要求大于 ' . $start . ' 个汉字, 小于 ' . $end . '个汉字';
        return false;
    }
    /**
     *    接入内容审核系统
     * @param $attr     - 需要接入审核系统的内容
     * @param $options  - 配置
     * @param $msg      - 描述信息
     */
    public function audit(&$attr, $options = array(), &$msg = '')
    {
        $msg = '内容没有通过审核';
        return false;
    }
}
