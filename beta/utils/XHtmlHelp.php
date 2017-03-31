<?php
/**
 * UC乐园 页面帮助类
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
class XHtmlHelp
{
	/**
	 * 年份生成
	 * @param unknown_type $begin
	 * @param unknown_type $end
	 * @param unknown_type $default
	 * @param boolean $ase 顺序
	 */
    public static function buildYearOptions($begin, $end, $default = 2008, $ase = true) {
		$html = '';
		if (!$ase) {
			$rev   = $begin;
			$begin = -$end;
			$end   = -$rev;
		}
		for (; $begin <= $end; $begin ++) {
			$tmp = abs($begin);
			$html .= "<option value='{$tmp}' " . (($default == $tmp) ? 'selected="selected"' : '' ) . ">{$tmp}</option>";
		}
		return $html;
    }
}
