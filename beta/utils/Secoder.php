<?php
/**
 * UC乐园 - 基础工具  安全验证码
 *
 * 安全的验证码要：验证码文字扭曲、旋转，使用不同字体，添加干扰码
 * 
 * @category   secoder
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding = 'UTF-8');
namespace utils;
class Secoder
{
    /**
     * 获取验证码的图片地址
     * @return 图片的地址
     */
    public function getVerifyImgSrc()
    {
        $prefix = \apps\base\Request::getInstance()->getPrefix($this->intry);
        return $this->intry . $prefix . "v=" . time();
    }
    /**
     *   生成一个验证码, 并且输出图片
     */
    public function genVerifyImg()
    {
        $authnum = '';
        // 初始化imagick对象
        $Imagick = new \Imagick();
        // 初始化背景对象
        $bg      = new \ImagickPixel();
        // 设置画笔的颜色
        $bg->setColor('rgb(235,235,235)');
        // 画刷
        $ImagickDraw = new \ImagickDraw();
        // 设定特定的字体
        //$ImagickDraw->setFont('path/to/ttf');
        $ImagickDraw->setFontSize(24);
        $ImagickDraw->setFillColor('black');
        // 生成数字和字母混合的验证码方法
        $ychar  = "0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
        $list   = explode(",", $ychar);
        srand((double) microtime() * 1000000);
        for ($i = 0; $i < 4; $i ++) {
            $randnum  = rand(0, 60);
            $authnum .= $list[$randnum];
        }
        $code = $authnum;
        $this->log("genVerifyImg`code`" . $code);
        $this->saveVerifyCode($code);
        // 新建一个画布
        $Imagick->newImage(75, 24, $bg);
        // 把文字写上去
        $Imagick->annotateImage($ImagickDraw, 4, 20, 0, $authnum);
        // 变形
        $Imagick->swirlImage(10);
        // 随即线条
        $ImagickDraw->line(rand(0, 30), rand(20, 60), rand(0, 70), rand(0, 30));
        $ImagickDraw->line(rand(0, 30), rand(0, 30), rand(1, 70), rand(2, 30));
        $ImagickDraw->line(rand(0, 20), rand(0, 30), rand(30, 70), rand(0, 30));
        $ImagickDraw->line(rand(10, 60), rand(0, 30), rand(8, 70), rand(0, 30));
        $ImagickDraw->line(rand(0, 10), rand(0, 30), rand(0, 70), rand(0, 30));
        // 画图
        $Imagick->drawImage($ImagickDraw);
        // 设置输出格式
        $Imagick->setImageFormat('png');
        // 输出图片
        header("Content-Type: image/{$Imagick->getImageFormat()}");
        echo $Imagick->getImageBlob();
        // 释放资源
        $Imagick->destroy();
        $ImagickDraw->destroy();
        $bg->destroy();
    }
    /**
     * 检查验证码是否正确
     * @param $code 待检查的验证码
     */
    public function checkIsVerify ($code)
    {
        $code = strtolower($code);
        $this->log("checkIsVerify`" . $code);
        $data = $this->getVerifyCode();
        $this->delVerifyCode();
        $this->log("checkIsVerify`mc`" . json_encode($data));
        $data['code'] = isset($data['code']) ? strtolower($data['code']) : '';
        if (!empty($data['code']) && $code == $data['code']){
            $this->log("checkIsVerify`ok");
            return true;
        }
        $this->log("checkIsVerify`error");
        return false;
    }
    /**
     * 删除验证码
     */
    private function delVerifyCode()
    {
        $mc  = \datalevel\base\Factory::getInstance()->getMc();
        $uc  = \apps\base\Ucweb::getInstance();
        $key = 'utils.secoder.' . $uc->imei . $uc->sn;
        $this->log("delVerifyCode`" . $key);
        return $mc->delete($key);
    }
    /**
     * 保存验证码到mc里面去
     * @param $code 待保存的验证码
     */
    private function saveVerifyCode($code)
    {
        $mc  = \datalevel\base\Factory::getInstance()->getMc();
        $uc  = \apps\base\Ucweb::getInstance();
        $key = 'utils.secoder.' . $uc->imei . $uc->sn;
        $res = array(
            'code' => $code,
            'time' => time()
        );
        $this->log("saveVerifyCode`" . $key . "`" . json_encode($res));
        return $mc->set($key, $res, $this->expire);
    }
    /**
     * 获取验证码
     */
    private function getVerifyCode()
    {
        $mc  = \datalevel\base\Factory::getInstance()->getMc();
        $uc  = \apps\base\Ucweb::getInstance();
        $key = 'utils.secoder.' . $uc->imei . $uc->sn;
        $this->log("getVerifyCode`" . $key);
        return $mc->get($key);
    }

    /**
     * 写log
     * @param  $msg
     * @param  $level
     */
    private function log($msg, $level = 'debug')
    {
        return \utils\Logger::writeLog($level, 'utils_secoder', $msg);
    }
    /**
     * 获取单实例
     */
    public static function getInstance ()
    {
        if (self::$obj == null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    public function __construct ()
    {
       $config         = framework\base\Config::get('', 'Secoder');
       $this->expire   = $config['expire'];
       $this->intry    = $config['intry'];
    }
    public static $obj = null;
    private $intry  = '';
    private $expire = 1800;
}
