<?php
/**
 * UC乐园  基础支撑  m8算法
 *
 * @category   HttpClient
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding = 'UTF-8');
namespace utils;
class M8
{
    /**
     *   加密一个字符串
     *
     * @param string src 需要解密的数据
     */
    public function encode($src) {
        $mask = array(
            0 => chr( 238 ),
            1 => chr( 185 ),
            2 => chr( 233 ),
            3 => chr( 179 ),
            4 => chr( 129 ),
            5 => chr( 142 ),
            6 => chr( 151 ),
            7 => chr( 167 ),
        );
        $maskS = chr( 0 );
        $dst   = "";
        $len   = strlen( $src );
        for( $i=0; $i<$len; $i++ ) {
            $a     = substr( $src, $i, 1 );
            $b     = $a ^ $mask[$i % 8];
            $dst  .= $b;
            $maskS = $maskS ^ $a;
        }
        //echo "1:". ord($maskS);
        # 加上校验位 (2位）
        $dst .= $maskS ^ $mask[0];
        $dst .= $maskS ^ $mask[1];
        return $dst;
    }
    /**
     *   解码一个字符串, 成功返回解码后的字符串，校验失败返回false
     *
     * @param string src 需要解密的数据
     */
    public function decode($src)
    {
        $mask = array(
            0 => chr( 238 ),
            1 => chr( 185 ),
            2 => chr( 233 ),
            3 => chr( 179 ),
            4 => chr( 129 ),
            5 => chr( 142 ),
            6 => chr( 151 ),
            7 => chr( 167 ),
        );
        $maskS = chr( 0 );
        $dst = "";
        $len = strlen( $src );
        if( $len < 2 ) {
            return false;
        }
        for( $i=0; $i<$len - 2; $i++ ) {
            $a     = substr( $src, $i, 1 );
            $b     = $a ^ $mask[$i % 8];
            $dst  .= $b;
            $maskS = $maskS ^ $b;
        }
        if(substr( $src, $len - 2, 1 ) === ($maskS ^ $mask[0]) && substr( $src, $len - 1, 1 ) === ($maskS ^ $mask[1])) {
            return $dst;
        } else {
            return false;
        }
    }
}
