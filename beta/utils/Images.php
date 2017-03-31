<?php
/**
 * UC乐园 - 基础工具  图片上传、获取工具
 *
 * @category   url
 * @package    utils
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace utils;
class Images
{
    /**
     *   获取单实例
     */
    public static function getInstance()
    {
        if (self::$obj === null){
            self::$obj = new self();
        }
        return self::$obj;
    }
    /**
     *   根据图片的url获取图片的二进制数据
     *
     * @param string $url  图片的二进制数据
     * @return string
     */
    public function getImgBlobByUrl($url)
    {
        //TODO 检查url的完整性
        // 根据url获取图片的blob
        $blob = \utils\HttpClient::getInstance()->get($url);
        return $blob;
    }
    /**
     *    获取照片的信息
     *
     * @param $buff  - 图片的二进制数据
     * @param &$msg  - 通过引用返回的错误描述信息
     * @return array(
     *     'format' => '图片的格式',
     *     'width'  => '图片的宽度',
     *     'height' => '图片的高度',
     *     'size'   => '图片的大小
     * );
     * @example
     * UtilsImages::getInstance()->getImgInfo($buffer);
     */
    public function getImgInfo($buff, &$msg = ''){
        $image = new \imagick();
        try {
            $image->readimageblob($buff);
            unset($buff);
            $res = array(
                'format' => $image->getImageFormat() ,
                'width'  => $image->getImageWidth() ,
                'height' => $image->getImageHeight() ,
                'size'   => $image->getImageSize()
            );
        } catch (Exception $e) {
            $msg = '您上传的不是图片.';
            $image->destroy();
            return false;
        }
        $image->destroy();
        return $res;
    }
    /**
     *    上传一个图片
     * @param fileBuffer  - $fileBuffer  - 图片的二进制数据
     * @param String      - $size        - 保存的图片的size
     * @param array       - $callBack    - 上传成功后回调的函数, 使用php的callback特性
     *
     * @return 调用callback的函数, 并且传入返回值
     * array(
     *     'status'  => 'ok',
     *     'msg'     => '',
     *     'files'   => array(
     *         // 原图
     *         'src'   => array(
     *             'fileId'    => '/x/b/xbxx.jpg',
     *             'groupName' => 'group1'
     *         ),
     *         // 根据传入的规格生成
     *         '20x20' => array(
     *             'fileId'    => '/x/b/xbxx.jpg',
     *             'groupName' => 'group1'
     *         ),
     *         '30x30' => array(
     *             'fileId'    => '/x/b/xbxx.jpg',
     *             'groupName' => 'group1'
     *         ),
     *     )
     *
     * @example
     *
     * UtilsImages::getInstance()->upload($fileBuffer, '150, 80, 60', array($this, 'actionUploadRes'));
     * 上传图片的数据到fastdfs，并且剪裁成 150, 80, 60 的规格
     *
     * 然后通过 当前类的actionUploadRest($res) 方法获取到结果
     */
    public function upload($fileBuffer, $size, array $callBack){
        // 需要调用到旧的方法
        self::$callBack = $callBack;
        \tasks\Manager::dispatcher(json_encode(array(
            'size'   => $size,
            'format' => $this->format,
            'blob'   => base64_encode($fileBuffer)
        )), \tasks\WorkerTypes::IMAGE_WORKER, array('\utils\Images', 'uploadCallback'));
    }
    /**
     *    获取图片
     *
     * @param array $file     - 图片索引 array('fileId' => '', 'groupName' => '')
     * @param boolean $outPut - 是否直接输出图片, true 为直接输出图片, false为返回图片的二进制数据
     * @return array(
     *      'ext'  => '', // 图片的格式
     *      'buff' => '图片的二进制数据'
     * )
     * @example
     * UtilsImages::getInstance()->getFile($file)       // 返回图片二进制数据
     * UtilsImages::getInstance()->getFile($file, true) // 直接输出这张图片
     */
    public function getFile($file, $outPut = false, $expire = 86400)
    {
        if ($outPut){
            $this->getBuffer($file);
            $this->showBuffer($expire);
        } else {
            return $this->getBuffer($file);
        }
    }
    private function log($msg, $level = 'debug')
    {
	return \utils\Logger::writeLog($level, 'utils_Images', $msg);
    }
    /**
     * 检测是否为热点图片
     * @param array $file
     */
    private function checkIsHotFile($file)
    {
	$key = "image_counter_" .  md5(http_build_query($file));
        $mc  = \datalevel\base\Factory::getInstance()->getMc('images');
        $res = $mc->get($key);
        $res = $res ? $res : 0;
        // 如果数量大与某个阀值, 就判断为热点图片
        if ($res >= framework\base\Config::get('images.hotPic', 'Misc')){
            // 这个为热点图片
            $log = "hotFile`" . $res . "`" . json_encode($file);
	    $this->log($log, 'info');
            $flag = true;
        } else {
	    $flag = false;
	}
	$res  = $flag ? 'true' : 'false';
	$this->log("checkIsHotFile`McKey`" . $key . "`" . $res);
	return $flag;
    }
    /**
     * 更新图片的读取次数
     * @param array $file
     */
    private function updateFileCounter($file)
    {
        $key = "image_counter_" .  md5(http_build_query($file));
        $mc  = \datalevel\base\Factory::getInstance()->getMc('images');
        $res = $mc->increment($key);
        if (!$res) {
            $mc->set($key, 1, framework\base\Config::get('images.hotCounterExpire', 'Misc'));
	    $res = 1;
	}
	$this->log("updateFileCounter`mcKey`" . $key . "`" . $res);
        return true;
    }
    /**
     *    获取图片的二进制数据
     *
     * @param array $file     - 图片索引 array('fileId' => '', 'groupName' => '')
     */
    private function getBuffer($file)
    {
        if (empty($file)) {
            throw new \utils\UtilsException("empty fastdfs index param");
        }
        // 判断这个图片是否为热点图片
        if ($this->checkIsHotFile($file)){
            // 热点图片，尝试在memcache里面读, 如果缓存读取不到，再到fastdfs里面去拿
	    $mc           = \datalevel\base\Factory::getInstance()->getMc('images');
	    $key          = 'image_buffs_' . md5(http_build_query($file));
            $this->buffer = $mc->get($key);
	    if (!$this->buffer){
		$this->log("getBuffer`McKey`" . $key . "`CacheMiss");
                $this->buffer = $this->getFileBufferFromFastDFS($file);
		$this->log("getBuffer`McKey`" . $key . "`InitCache");
                $mc->set($key, $this->buffer, 86400);
	    } else {
		$this->log("getBuffer`McKey`" . $key . "`CacheHit");
	    }
            unset($mc);
        } else {
            // 直接到fastdfs里面去拿图片
            $this->buffer = $this->getFileBufferFromFastDFS($file);
        }
        $this->updateFileCounter($file);
        return $this->buffer;
    }
    /**
     *   从fastdfs里面获取图片的二进制数据
     * @param array $file
     */
    private function getFileBufferFromFastDFS($file)
    {
	$this->log("getFileBufferFromFastDFS`" . json_encode($file));
        $file_info = array(
            'filename' => $file['fileId'] , 'group_name' => $file['groupName']
        );
        $buffer  = \fastdfs_storage_download_file_to_buff($file_info['group_name'], $file_info['filename']);
        $ext     = strtolower($this->format);
        \fastdfs_tracker_close_all_connections();
        // 更新图片的计数器
        return array(
            'ext' => $ext , 'buff' => $buffer
        );
    }
    /**
     *   显示一张图片, 前提是调用了getBuffer
     *
     * @example  UtilsImages::getInstance()->getBuffer($file, true);
     */
    private function showBuffer($expire)
    {
        $this->buffer['ext'] = isset($this->buffer['ext']) ? $this->buffer['ext'] : 'jpg';
        $gmdate2 = gmdate("D, d M Y H:i:s", time() + $expire)." GMT"; //过期
        switch ($this->buffer['ext']) {
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'jpg':
                header('Content-Type: image/jpeg');
                break;
            case 'gif':
                header('Content-Type: image/gif');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
        }
        header ('Content-Length: ' . strlen($this->buffer['buff']));
        header ('Cache-Control: max-age=' . $expire);
        header ('Expires: ' . $gmdate2);
        echo $this->buffer['buff'];
        unset($this->buffer);
        exit();
    }
    /**
     *    图片上传成功后回调
     *
     * @param String $task - 丢过去给worker返回的基础数据
     */
    public static function uploadCallback($task){
        // 解析返回来的数据
        $func = self::$callBack[1];
        if (is_object(self::$callBack[0])){
            self::$callBack[0]->$func(json_decode($task, true));
        } else {
            $obj = new self::$callBack[0];
            $obj->$func(json_decode($task, true));
        }
    }
    /**
    *   根据手机平台获取要生成的三种图片尺寸
    * @param string $type  - 图片的类型， avatar|mood|photo
    * @param string $pf    - 手机平台
    * @return array        － array('70', '220', '1024')
    */
    public static function getSizesByPf($type='mood', $pf='') {
        
        if (empty($pf)){
            $ucweb = \apps\base\Ucweb::getInstance();
            $pf = $ucweb->getPlatform();
        }
        //guoyong java的屏幕判断等操作有dispatcher做
//        if ($pf == 'java'){
//            $uccPara = snsapiSsoUccPara::getInstance();
//            //加上屏幕分辨率，形成映射表中java平台的特殊键
//            $pf .= $uccPara->ss;
//        }
        $sizes = array ();
        foreach (self :: $imgMapper[$type] as $size => $item) {
            $sizes[] = $item[$pf];
        }
        return $sizes;
    }
    /**
     *   返回业务的所有分辨率尺寸
     * @param string  $type   - 应用类型， avatar|mood|photo
     * 
     * @return array          - 图片的确切尺寸数组，如40x40
     */
    public static function getSizesByType($type){
        $sizes = array ();
        $found_sizes = array ();
        foreach (self :: $imgMapper[$type] as $size => $item) {
            if ($size != 'large'){
                foreach ($item as $pf => $exactSize){
                    if (!array_key_exists($exactSize, $found_sizes)){
                        $found_sizes[$exactSize] = 1;
                        $sizes[] = $exactSize;
                    }
                }
            }
        }
        return $sizes;
    }
    /**
     *   根据不同的业务、图像大小，返回确切的的图片尺寸
     * @param string  $type   - 应用类型， avatar|mood|photo
     * @param string  $size   - 图片的大小， thum|normal|large
     * 
     * @return string         - 图片的确切尺寸，如40x40
     */
    public static function getImgExactSize ($type='mood', $size=''){
        $ucweb = \apps\base\Ucweb::getInstance();
        $pf = $ucweb->getPlatform();
        //如果手机平台属于java，则需要特殊处理
        //guoyong java的屏幕判断等操作有dispatcher做
//        if ($pf == 'java'){
//            $uccPara = snsapiSsoUccPara::getInstance();
//            //加上屏幕分辨率，形成映射表中java平台的特殊键
//            $pf .= $uccPara->ss;
//        }
        if (!isset(self::$imgMapper[$type][$size])){
            return null;
        }
        return self::$imgMapper[$type][$size][$pf];
    }
   /**
    *   根据手机平台获取要生成的图片尺寸
    * @param string $type  - 图片的类型， avatar|mood|photo
    * @param string $pf    - 手机平台
    * @return array        － array(''thum'=>'70', 'normal'=>'220', 'large'=>'1024');
    */
    public static function getSizesByPfWithPfKey($type='mood', $pf='') {
        if (empty($pf)){
            $ucweb = \apps\base\Ucweb::getInstance();
            $pf = $ucweb->getPlatform();
        }
        //guoyong java的屏幕判断等操作有dispatcher做
//        if ($pf == 'java'){
//            $uccPara = snsapiSsoUccPara::getInstance();
//            //加上屏幕分辨率，形成映射表中java平台的特殊键
//            $pf .= $uccPara->ss;
//        }
        $sizes = array ();
        foreach (self :: $imgMapper[$type] as $size => $item) {
            $sizes[$size] = $item[$pf];
        }
        return $sizes;
    }

   /** 剪切图片、压缩图片成20×20、40×40。返回20×20、40×40、原图详细信息
     * 
     */
    public static function fileProcessing(&$imageBin){
        $image  = new \imagick();
        $image->readImageBlob($imageBin);
        $height = $image->getImageHeight();
        $width  = $image->getImageWidth();
        $grap   = 0;
        if($height <= $width){
            $grap = intval($height / 40);
            $image->cropImage($height - $grap, $height - $grap, intval(($width - $height)/2) + $grap, $grap);
        } else {
            $grap = intval($width / 40);
            $image->cropImage($width - $grap, $width - $grap, $grap, intval(($height - $width)/2)+ $grap);
        }
        $color = new \ImagickPixel();
        $color->setColor("rgb(255,255,255)");
        $image->borderImage($color,$grap,$grap);
        $color->setColor("rgb(220,220,220)");
        $image->borderImage($color,$grap,$grap);
        $imageBin = $image->getImageBlob();
        return $imageBin;
    }
    
   /**
    *   获取其他手机平台要生成的图片尺寸
    * @param string $type  - 图片的类型， avatar|mood|photo
    * @return array        - array('70', '220'...)
    */
    public static function getSizesofOtherPf($type, $pf) {
        $sizes = array ();
        $size1 = self :: getSizesbyPf($type, $pf);
        $sizes = array_diff(self :: $imgSize, $size1);
        return $sizes;
    }
    /**
     * 图片的规格
     * @var array
     */
    public static $imgSize = array (
        '50',
        '70',
        '100',
        '160',
        '220',
        '300',
        '440'
    );
    /**
     *   各类业务的图片规格对应表
     * @var  array
     */
    public static $imgMapper = array (
        'photo' => array (
            'thum' => array (
                'v2' => '50',
                'v3' => '70',
                'v5' => '100',
                'java128x160' => '50',
                'java176x220' => '50',
                'java' => '70',
                'wm' => '70',
                'android' => '100',
                'IPhone' => '100'
            ),
            'normal' => array (
                'v2' => '160',
                'v3' => '220',
                'v5' => '300',
                'java128x160' => '100',
                'java176x220' => '160',
                'java' => '220',
                'wm' => '220',
                'android' => '300',
                'IPhone' => '440'
            ),
            'large' => array (
                'v2' => '1024',
                'v3' => '1024',
                'v5' => '1024',
                'java128x160' => '1024',
                'java176x220' => '1024',
                'java' => '1024',
                'wm' => '1024',
                'android' => '1024',
                'IPhone' => '1024'
            ),
            
        ),
        'mood' => array (
            'thum' => array (
                'v2' => '50',
                'v3' => '70',
                'v5' => '100',
                'java128x160' => '50',
                'java176x220' => '50',
                'java' => '70',
                'wm' => '70',
                'android' => '100',
                'IPhone' => '100'
            ),
            'normal' => array (
                'v2' => '160',
                'v3' => '220',
                'v5' => '300',
                'java128x160' => '100',
                'java176x220' => '160',
                'java' => '220',
                'wm' => '220',
                'android' => '300',
                'IPhone' => '440'
            ),
            'large' => array (
                'v2' => '1024',
                'v3' => '1024',
                'v5' => '1024',
                'java128x160' => '1024',
                'java176x220' => '1024',
                'java' => '1024',
                'wm' => '1024',
                'android' => '1024',
                'IPhone' => '1024'
            ),
            
        ),
        'avatar' => array (
            'thum' => array (
                'v2' => '20x20',
                'v3' => '20x20',
                'v5' => '30x30',
                'java128x160' => '20x20',
                'java176x220' => '20x20',
                'java' => '20x20',
                'wm' => '20x20',
                'android' => '30x30',
                'IPhone' => '30x30'
            ),
            'normal' => array (
                'v2'           => '40x40',
                'v3'           => '40x40',
                'v5'           => '60x60',
                'java128x160'  => '40x40',
                'java176x220'  => '40x40',
                'java'         => '40x40',
                'wm'           => '40x40',
                'android'      => '60x60',
                'IPhone'       => '60x60'
            ),
        ),
    );
    /**
     *   程序析构的时候调用
     */
    public function __destruct()
    {
        unset($this->buffer);
    }
    /**
     *   需要回调的函数
     * @var  array
     */
    private static $callBack = array();
    private $buffer           = array();
    private static $obj;
    private $format           = "JPEG";
    private $isCache          = true;
}
