<?php
/**
 * UC乐园  基础支撑  基层加密解密算法
 *
 * @category   Crypt
 * @package    base
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace apps\base;
use \utils\AppsException as Exception;
class Crypt
{
    public static $publicKey  = 'public.key';
    public static $privateKey = 'private.key';
    /**
     *   对信息进行签名
     *
     * @param String $str          - 要签名的信息
     * @param String $encode       - 签名后的信息
     * @param String $privateKey   - 私钥
     * @param String $pubKey       - 公钥
     * @return String 签名后的信息
     */
    public static function  sign($str, &$encode = '', $privateKey = '') {
        if (empty($privateKey)){
            $privateKey = file_get_contents(dirname(__FILE__) . '/../../resources/key/' . self::publicKey);
        }
        $res = openssl_public_encrypt($str, $encode, $privateKey);
        if ($res){
            $encode = base64_encode($encode);
            return true;
        }
        return false;
    }
    /**
     *   对加密过的信息签名解密
     *
     * @param String $str       - 待解密的信息
     * @param String $decode    - 解密出来的信息
     * @param String $publicKey - 公钥
     * @param boolean 是否解密成功
     */
    public static function unSign($str, &$decode = '', $publicKey = '')
    {
        if (empty($publicKey)){
            $publicKey = file_get_contents(dirname(__FILE__) . '/../../resources/key/' . self::$privateKey);
        }
        if (empty($str)){
            return false;
        }
        $str    = base64_decode($str);
        $decode = self::ssl_decrypt($str, 'private', $publicKey);
        return true;
    }
    /**
     *   openssl长字符串解密方法
     */
    public static  function ssl_decrypt($source,$type = 'private',$key = ''){
        $maxlength   = 64;
        $output      = $out = '';
        while($source){
            $input   = substr($source, 0, $maxlength);
            $source  = substr($source, $maxlength);
            if($type == 'private'){
                $ok = openssl_private_decrypt($input, $out, $key);
            } else {
                $ok = openssl_public_decrypt($input, $out, $key);
            }
            if(!$ok){
                throw new Exception("decrypt(string) error : " . openssl_error_string());
            }
            $output .= $out;
        }
        return $output;
    }
    /**
     *   对信息进行签名
     *
     * @param String $str          - 要签名的信息
     * @param String $encode       - 签名后的信息
     * @param String $privateKey   - 私钥
     * @param String $pubKey       - 公钥
     * @return String 签名后的信息
     */
    public static  function ssoSign($str, $key = '', $type = 'public') {
        $encode = self::ssl_encrypt($str, $type, $key);
        if ($encode){
            return base64_encode($encode);
        }
        return false;
    }
    /**
     *   openssl长字符串加密方法
     */
    public static function ssl_encrypt($source,$type,$key){
        $maxlength = 53;
        $output    = $encrypted = '';
        while($source){
            $input= substr($source,0,$maxlength);
            $source=substr($source,$maxlength);
            if($type=='private'){
                $ok= openssl_private_encrypt($input,$encrypted,$key);
            }else{
                $ok= openssl_public_encrypt($input,$encrypted,$key);
            }
            if(!$ok){
                throw new Exception("encrypt(string) error : " . openssl_error_string());
            }
            $output.=$encrypted;
        }
        return $output;
    }
    /**
     *   通用可逆加密
     */
    public static function encode($data , $key){
        $res = '';
        self::sign($data, $res);
        return $res;
    }
    /**
     *   通用可逆解密
     */
    public static function decode($data, $key = ''){
        $res = '';
        self::unsign($data, $res, $key);
        return $res;
    }
}

