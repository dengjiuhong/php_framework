<?php
/**
 * UC乐园 - 基础工具  字符串处理
 *
 * @category   string
 * @package    utils
 * @author liangrn <liangrn@ucewb.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace utils;
class String
{
    /**
     *     字符安全过滤
     * @param  $str  需要安全过滤的字符串
     * @return string
     */
    public static function safeString($str)
    {
        return htmlspecialchars($str);
    }
    /**
     * 取得一个时间过去了多久的描述
     *
     * @param $timeStamp  时间戳
     * @param $format     表现格式
     * @param $showYear   是否显示年份
     */
    public static function getDescTime($timeStamp, $format = 'custom',$showYear = true)
    {
        if ($format !== 'custom'){
            return date($timeStamp, $format);
        }
        $day   = (int) date('ymd', $timeStamp);
        $today = (int) date('ymd');
        $num   = $today - $day;
        if ($num == 0){
            // 今天
            $result = date('H:i', $timeStamp);
        } elseif ($num == 1){
            // 昨天
            $result = '昨天 ' . date('H:i', $timeStamp);
        } elseif ($num == 2){
            $result = '前天 ' . date('H:i', $timeStamp);
        } else {
            if($showYear){
                $result = date('Y-m-d H:i', $timeStamp);
            } else {
                $result = date('m-d H:i', $timeStamp);
            }
        }
        return $result;
    }
    /**
     *   UTF8字符截取
     * @param $str       需要截取的字符串
     * @param $length    保留的字符长度
     * @param $append    是否显示省略号
     */
    public static function substr($str, $length = 0, $append = false) {
        $str       = trim($str);
        $strlength = self::oldStrlen($str);
        if ($length == 0 || $length >= $strlength) {
            return $str;
        } elseif ($length < 0) {
            $length = $strlength + $length;
            if ($length < 0) {
                $length = $strlength;
            }
        }
        $str = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $str);
        if (function_exists('mb_substr')) {
            $newstr = mb_substr($str, 0, $length, 'UTF-8');
        } elseif (function_exists('iconv_substr')) {
            $newstr = iconv_substr($str, 0, $length, 'UTF-8');
        } else {
            $newstr = trim(substr($str, 0, $length));
        }
        if ($append && $str != $newstr) {
            $newstr .= '...';
        }
        $newstr = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $newstr);
        return $newstr;
    }
    
   /**
     *   UTF8字符截取
     * @param $str       需要截取的字符串
     * @param $length    保留的字符长度
     * @param $append    是否显示省略号
     */
    public static function utf8Substr($str, $start=0, $length=0, $append = false) {
        $str       = trim($str);
        $strlength = self::oldStrlen($str);
        if ($length == 0 || $length >= $strlength) {
            return $str;
        } elseif ($length < 0) {
            $length = $strlength + $length;
            if ($length < 0) {
                $length = $strlength;
            }
        }
        $str = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $str);
        if (function_exists('mb_substr')) {
            $newstr = mb_substr($str, $start, $length, 'UTF-8');
        } elseif (function_exists('iconv_substr')) {
            $newstr = iconv_substr($str, $start, $length, 'UTF-8');
        } else {
            $newstr = trim(substr($str,$start, $length));
        }
        if ($append && $str != $newstr) {
            $newstr .= '...';
        }
        $newstr = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $newstr);
        return $newstr;
    }
    
    
    
    
    
    /**
     * 旧版本的字符串长度计算
     * @param $str 需要计算的字符串
     */
    public static function oldStrlen($str) {
        $str = trim($str);
        $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));
        if ($length) {
            return strlen($str) - $length + intval($length / 3) * 2;
        } else {
            return strlen($str);
        }
    }
    /**
     *   计算字符的长度计算字符的长度(根据产品要求，字母和数字都算半个字符，中文算一个字符)
     * 
     * @param $str 需要计算的字符串
     * @return 字符串的长度
     */
    public static function strlen($str) {
        $str = trim($str);
        $str1   = preg_replace('/[\x00-\x7F]/', '', $str);
        $length = strlen($str1);
        if ($length) {
            return ceil((strlen($str) - $length)/2) + intval($length / 3);
        } else {
            return ceil(strlen($str) / 2);
        }
    }
    /**
     * Encodes special characters into HTML entities.
     * The {@link CApplication::charset application charset} will be used for encoding.
     * @param string data to be encoded
     * @return string the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($text, $isNl2Br = true)
    {
    	$text = htmlspecialchars($text, ENT_QUOTES, 'utf-8',false);
        $text = str_replace('&lt;br /&gt;', '', $text);
    	return $isNl2Br ? nl2br($text)  : $text;
    }
    /**
     * 根据 “ “ 和 ”\t" 切割字符串，
     * 注意 $text = ” 0 “; 切割处理是一个空数组
     * @param string $text
     */
    public static function spaceExplode($text) {
    	$text = str_replace("\t", " ", $text);
    	$res  = array_filter(explode(" ", $text));
    	return $res;
    }
    /**讲参数套入php模板
     * 
     * @param array $vars 参数列表
     * @param string $tplName php文件全路经
     * @return string
     */
  	public static function pasteTpl($vars, $tplName) {
  		if (!is_file($tplName)) return null;
  		if (!empty($vars)) {
            extract($vars);  		    
  		}
		ob_start();
		@include $tplName;
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
  	}
}
