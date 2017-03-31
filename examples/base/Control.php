<?php

// 扩展基础的control, 加上 log、user、session相关功能
namespace base;
class Control extends \framework\base\Control
{
    public function __construct()
    {
        parent::__construct();
    }
    private static $flashMsg;
    private static $flashMsgTime   = 86400;
    private static $flashMsgKey    = null;
    /**
     * @deprecated 当前手机浏览器的参数
     * @var  array()
     */
    protected $uc_param    = array();
    /**
     * @deprecated 当前手机的平台
     * @var  String
     */
    protected $platform    = '';
    /**
     * @deprecated 当前用户uid
     * @var  numeric
     */
    protected $uid           = '';
}
