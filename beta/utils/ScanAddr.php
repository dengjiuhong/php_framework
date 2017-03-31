<?php
/**
 * UC乐园  基础支撑  通讯录上传
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
class ScanAddr{
    /**
     *    解析客户端上传的数据，返回电话号码
     *
     * @param string  $addr  - 客户端$_POST上传的addr原数据
     * @param string  $z     - 客户端$_POST上传的压缩方式的原数据
     * @return array('电话号码1', '电话号码2')
     */
    public function parse($addr, $z)
    {
        $this->log("parse`start`" . $addr . '`' . $z);
        $addr = str_replace(" ", "+", $addr);
        $addr = base64_decode($addr);
        if ($z == 'zlib'){
            $addr = gzuncompress($addr);
        } elseif ($z == 'gzib') {
            $addr = $this->gzdecode($addr);
        }
        $m8    = new \utils\M8();
        $addr  = $m8->decode($addr);
        $this->log("parse`decode`" . $addr);
        // 解出原数据
        // 保存到日志
        // +08602012345678'02012345679
        $addrs = explode("`", $addr);
        $res   = array();
        foreach($addrs as $phone){
            if ($this->checkIsVerifyPhone($phone)){
                $res[] = $phone;
            }
        }
        return \utils\Result::ok($res);
    }
    private function log($msg, $level = 'debug')
    {
        return \utils\Logger::writeLog($level, 'utils_Addr', $msg);
    }
    public static function getInstance()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    public function __construct()
    {

    }
    /**
     *   检测是否为合法的手机号码
     *
     * @param $phone  - 待检查的手机号码
     * @return boolean - 是否合法
     */
    private function checkIsVerifyPhone(&$phone)
    {
        // 截取最后11位是否为手机号码
        if ($phone == '13800138000') return false;
        $phone = substr($phone, -11);
        if (strlen($phone) < 11) return false;
        // 正则匹配
        if (preg_match('/^1[0-9]{10}$/', $phone)){
            return true;
        }
        return false;
    }
    /**
     *   gzib解压函数
     *
     * @param   $data  - 需要解压的数据
     * @return  string - 解压后的数据
     */
    private function gzdecode($data)
    {
        if (function_exists ( 'gzdecode' )){
            return \gzdecode($data);
        }
        $flags = ord ( substr ( $data, 3, 1 ) );
        $headerlen = 10;
        $extralen  = 0;
        if ($flags & 4) {
            $extralen = unpack ( 'v', substr ( $data, 10, 2 ) );
            $extralen = $extralen [1];
            $headerlen += 2 + $extralen;
        }
        if ($flags & 8) // Filename
            $headerlen = strpos ( $data, chr ( 0 ), $headerlen ) + 1;
        if ($flags & 16) // Comment
            $headerlen = strpos ( $data, chr ( 0 ), $headerlen ) + 1;
        if ($flags & 2) // CRC at end of file
            $headerlen += 2;
        $unpacked = @gzinflate ( substr ( $data, $headerlen ) );
        if ($unpacked === FALSE)
            $unpacked = $data;
        return $unpacked;
    }
    public static $obj;
}
