<?php
/**
 * UC乐园   统一返回的数据格式
 *
 * @category   result
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\utils;
use framework\base\Logger as Logger;
use framework\base\Exception as Exception;
class Result
{
    /**
     * 返回错误结果
     *
     * @param String errorMsg  - 错误的描述信息, 开发人员用户
     * @param String errorPos  - 错误发生的位置，格式如“类:方法”
     * @param String  $level   - 错误等级 error warn info debug
     *
     * 注意 ，当level为error的时候，程序会直接退出
     * @return array
     * array(
     *     "status"   => 'error',
     *     "errorMsg" => 'something wrong!',
     *     "errorNo"  => '10001',
     * )
     */
    public static function error($errorMsg, $errorPos, $level = "error")
    {
        $levels = array(
            'error', 'warn', 'info', 'debug'
        );
        if (!in_array($level, $levels)){
            throw new Exception("invalid error level, " . implode(', ', $levels) . ' is available.');
        }
        self::logger($errorMsg, $errorPos, $level);
        // 如果是错误 error 的类型，直接抛出异常
        if ($level == 'error'){
            throw new Exception("Result Error \n ErrorMsg: \n" . $errorMsg . "\n ErrorPos: \n" . $errorPos);
        }
        return array(
            'status'   => 'error',
            'errorMsg' => $errorMsg,
            'errorNo'  => $errorPos,
        );
    }
    /**
     *   返回成果数据
     * @param  Array  $data - 格式类型由调用者定
     * @return
     * array(
     *     "status" => 'ok'
	 *     ......
     * )
     *
     */
    public static function ok($data)
    {
    	$res = array();
    	$res['status'] = "ok";
    	$res['data'] = $data;
    	return $res;
		/*if(!isset($arr['status'])) {
			$arr['status'] = "ok";
		}
		return $arr;*/
    }
    /**
     *   判断返回结果是否为ok
     *
     * @param array   $ret  - 待检查的返回的结果
     * @return boolean
     */
    public static function check($ret)
    {
        if(isset($ret['status']) && "error" == $ret['status'] ) {
            return false;
        } else {
        	if (!isset($ret))
        	{
        		// 如果参数不符合check的格式，则返回false
        		return false;
        	}
            return true;
        }
    }
    /**
     *   解析结果数据，并且通过引用返回数据的data数据
     * @param array $result 待解析的数据
     * @return boolean 该数据合法与否，true/false
     */
    public static function parseResult(&$result){
    	if(isset($result['status']) && "error" == $result['status']) {
            return false;
        } else if (isset($result['data'])){
        	$result = $result['data'];
            return true;
        }else{
        	return true;
        }
    }
    /**
     *   记录Result::error的log
     *
     * @param  String $msg   - 错误信息
     * @param  String $code  - 错误代号
     * @param  String $level - 错误等级
     */
    private static function logger($msg, $code, $level)
    {
        // result 不再记录log
        $category = 'system';
        switch($level){
            case 'error':
            Logger::e($category, $msg . "`" . $code);
            break;
            //case 'info':
            //\utils\Logger::i($category, $msg . "`" . $code);
            break;
            // case 'warn';
            // \utils\Logger::w($category, $msg . "`" . $code);
            // break;
            case 'debug':
            Logger::d($category, $msg . "`" . $code);
            break;
            default:
            break;
        }
        return true;
    }
}

