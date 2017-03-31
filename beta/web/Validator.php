<?php
/**
 * UC乐园  基础支撑平台  - 通用验证
 *
 * @category   base
 * @package    apps
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web;
class Validator
{
    /**
     *   每个规则对应的实现方法
     * @var  Array
     */
    public static $buildValidators = array(
        'required' => 'framework\web\validators\Required',    // 非空
        'email'    => 'framework\web\validators\Email',       // 邮件格式
        'length'   => 'framework\web\validators\String',      // 长度控制
        'audit'    => 'framework\web\validators\String',      // 如内容审核系统
        'file'     => 'framework\web\validators\File',        // 文件
    );
    /**
     *    校验数据是否正确
     * @param  array   $attr  - 表单校验的数据
     * @example
     * $attr = array(
     *     'realName' => array($this->request->post('realName'), array('required', 'length' => array(0, 10))),
     *     'email'    => array($htis->request->post('email'), 'email'),
     *     'mobi'     => array($this->request->post('mobi'), 'mobi'),
     *     'content'  => array($this->request->post('content'), array('required', 'length' => array(0, 70), 'audit'))
     * );
     * @param  &array  $error - 返回的错误提示
     * @return boolean        - 是否通过校验
     */
    public function valid(&$attrs = array(), &$errors = array())
    {
        if (empty($attrs)) {
            throw new \framework\base\Exception('\apps\base\Validator empty attrs ...');
        }
        $this->verifyHash();
        if ($this->isDuplicateSubmit()){
            $errors = array('main' => 'duplicate Submit');
        } else {
            foreach($attrs as $keyName => $value){
                $attr   = isset($value[0]) ? $value[0] : '';
                $rule   = isset($value[1]) ? $value[1] : '';
                $msg    = '';
                if (empty($value) || empty($rule)) continue;
                $rules = array();
                if (!is_array($rule)){
                    $rules[] = array('rule' => $rule, 'options' => '');
                } else {
                    $options = '';
                    foreach($rule as $key => $v){
                        if (!is_numeric($key)){
                            $options = $v;
                        } else {
                            $key = $v;
                        }
                        if(is_array($key)){
                            foreach($key as $k => $t){
                                $options = $t;
                                $key     = $k;
                            }
                        }
                        $tmp = array(
                            'rule'    => $key,
                            'options' => $options
                        );
                        $rules[] = $tmp;
                    }
                }
                if(!empty($rules)){
                    $errorNum = 0;
                    foreach($rules as $v){
                        $class = isset(self::$buildValidators[$v['rule']]) ? self::$buildValidators[$v['rule']] : '';
                        if (empty($class)) continue;
                        $class = new $class;
                        if (!$class->validateAttribute($v['rule'], $attr, $msg, $v['options'])){
                            $errors[$keyName] = isset($errors[$keyName]) ? $errors[$keyName] : array();
                            array_push($errors[$keyName],  array('rule' => $v['rule'], 'msg' =>  $msg));
                            if ($this->skipOnError) return false;
                        }
                    }
                }
            }
        }
        $tmp    = $errors;
        $errors = array();
        foreach($tmp as $key => $v){
            if (count($v) == 1){
                $v = $v[0];
            }
            $errors[$key] = $v;
        }
        if (!empty($errors)){
            return false;
        }
        return true;
    }
    /**
     *  判断是否对一个页面，做重复提交
     *
     *@param 无
     *@return true是重复提交，false不是重复提交
     *@limitation: 实现是根据$_POST的内容判断是否是同一个form的提交
     *              建议在form页面中加入一个隐藏的,值为随机数的域来标志不同的form
     *              参见心情状态的form
     **/
    public function isDuplicateSubmit($key_prefix="mood", $expire=self::DUPLICATE_SUBMIT_EXPIRE_TIME)
    {
        if (!isset($_POST)){
            return false;
        }
        $uid = \apps\base\User::getInstance()->getUid();
        if (!$uid) {
            return false;
        } else {
            $key = $key_prefix . "_" . $uid;
        }
        $value = substr(md5(json_encode($_POST)),0,10);
        $mc = \datalevel\base\Factory::getInstance()->getMc();
        if ($mc->get($key) == $value) {
            return true;
        } else {
            $mc->set($key,$value,$expire);
            return false;
        }
        return false;
    }
    /**
     *     加载校验类
     * @param  String $class  - 校验的类的名字
     * @return ValidatorObject
     */
    private function loadValidators($class)
    {
        if (!isset(self::$validClass[$class])){
            self::$validClass[$class] = new $class;
        }
        return self::$validClass[$class];
    }
    /**
     *   创建表单里面用到的安全校验
     */
    public function buildFormHash()
    {
        $hash = md5(self::HASH_VKEY . date('Ymd H'));
        return '<input type="hidden" name="' . self::HASH_NAME . '" value="' . $hash . '" />';
    }
    /**
     *   校验表单的正确性
     */
    public function verifyHash()
    {
        if (self::HASH_VERIFY){
            $hash  = md5(self::HASH_VKEY . date('Ymd H'));
            $_hash = \apps\base\Request::getInstance()->get(self::HASH_NAME);
            if (empty($_hash) || $_hash != $hash){
                throw new \framework\base\Exception('request tainting');
            }
        }
        return true;
    }
    const HASH_NAME   = 'hash';
    const HASH_VKEY   = 'hello';
    const HASH_VERIFY = false;
    /**
     *   取得单实例
     */
    public static function getInstance()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *   初始化
     */
    public function __construct()
    {
        // 初始化
    }
    /**
     * @des 加载进来的表单校验的类
     * @var Validator
     */
    public static $validClass = array();
        /**
     * @var array  需要校验的参数的列表
     */
    public $attributes;
    /**
     * @var 提示信息
     */
    public $message;
    /**
     * @boolean 是否遇到不通过的就不执行接下来的校验
     */
    public $skipOnError = false;
    /**
     *   校验的单实例
     * @var Validator
     */
    public static $obj  = null;
    const DUPLICATE_SUBMIT_EXPIRE_TIME = 30;
}
