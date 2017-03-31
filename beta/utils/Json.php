<?php
/**
 * UC乐园 - json  字符串处理
 *
 * @category   string
 * @package    utils
 * @author guoyong <guoyong@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace utils;
class Json {

	/**************************************************************
	 *
	 *  使用特定function对数组中所有元素做处理
	 *  @param  string  &$array     要处理的字符串
	 *  @param  string  $function   要执行的函数
	 *  @return boolean $apply_to_keys_also     是否也应用到key上
	 *  @access public
	 *
	 *************************************************************/
	private static function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
	{
		static $recursive_counter = 0;
		if (++$recursive_counter > 1000) {
			die('possible deep recursion attack');
		}
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				self::arrayRecursive($array[$key], $function, $apply_to_keys_also);
			} else {
				$array[$key] = $function($value);
			}
			if ($apply_to_keys_also && is_string($key)) {
				$new_key = $function($key);
				if ($new_key != $key) {
					$array[$new_key] = $array[$key];
					unset($array[$key]);
				}
			}
		}
		$recursive_counter--;
	}
	/**************************************************************
	 *
	 *  将数组转换为JSON字符串（兼容中文）
	 *  @param  array   $array      要转换的数组
	 *  @return string      转换得到的json字符串
	 *  @access public
	 *
	 *************************************************************/
	public static function jsonEncode($array, $unicode = true) {
		if ($unicode) {
			return json_encode($array);
		}
		self::arrayRecursive($array, 'urlencode', true);
		$json = json_encode($array);
		return urldecode($json);
	}
	
	public static function jsonDecode($json, $assoc) {
		return json_decode($json, $assoc);
	}
}

