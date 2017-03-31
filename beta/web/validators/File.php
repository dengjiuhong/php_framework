<?php
/**
 * UC乐园  基础支撑平台  - 通用验证的实现  - 文件校验
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
use \utils\AppsException as Exception;
class File extends  AbstractValidator
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
     *    获取照片的基本信息
     * @param $_FILES $attr  - 照片的基本信息
     * @param String  $msg   - 发生错误时候的描述信息
     * @return Array         - 图片的基本信息
     *
     * @example
     * array(
     *     'format' => '图片的格式 JPEG|GIF|PNG',
     *     'width'  => '图片的宽度',
     *     'height' => '图片的高度',
     *     'size'   => '图片的大小'
     * )
     */
    private function getImgInfo(&$attr, &$msg = '')
    {
        if (self::$imgInfo === null){
            self::$imgInfo = array();
            try {
                $this->imagick->readimageblob(file_get_contents(($attr['tmp_name'])));
                self::$imgInfo = array(
                    'format' => $this->imagick->getImageFormat(),
                    'width'  => $this->imagick->getImageWidth(),
                    'height' => $this->imagick->getImageHeight(),
                    'size'   => $this->imagick->getImageSize()
                );
            } catch (Exception $e) {
                $msg = '您上传的不是图片.';
                $this->imagick->destroy();
            }
        }
        return self::$imgInfo;
    }
    /**
     *    校验长度
     *
     * @param $attr    - 需要校验的内容
     * @param $options - 校验的配置
     * @param $msg     - 描述信息
     * @return boolean - 是否通过校验
     */
    public function maxsize(&$attr, $options = array(), &$msg = '')
    {
        $res = $this->getImgInfo($attr, $msg);
        if (empty($res)) return false;
        if ($res['size'] > (int) $options){
            $msg = '图片太大了, 最大' . $options . '个字节';
            return false;
        }
        return true;
    }
    /**
     *    检测图片的格式是否符合要求
     *
     * @param $attr    - 需要校验的内容
     * @param $options - 校验的配置
     * @param $msg     - 描述信息
     * @return boolean - 是否通过校验
     */
    public function mime(&$attr, $options = array(), &$msg = '')
    {
        $res = $this->getImgInfo($attr, $msg);
        if (empty($res)) return false;
        if (!in_array(strtolower($res['format'], $options))){
            $msg = '当前图片格式不支持，（目前支持 '.implode(', ', $options) .'的图片）';
            return false;
        }
        return true;
    }
    /**
     *   初始化
     */
    public function __construct()
    {
        $this->imagick = new imagick();
    }
    /**
     *   imagick 操作对象
     * @var Object
     */
    private $imagick = null;
    /**
     *   获取到的图片信息
     * @var Array
     */
    public static $imgInfo = null;
}
