<?php

namespace base\events;
class AppStart
{
    public static function run($param)
    {
          self::__initXHProf();
          self::__initUcweb();
          self::__initSession();
          // 
    }
    private static function __initXHProf()
    {
        // 初始化xhprof
        $ignore= array(
            'intval', 'strval', 'strlen', 'strpos', 'substr', 'count',
            'explode', 'str_replace', 'is_array', 'is_string',
            'json_encode', 'json_decode', 'var_dump', 'strtolower', 
            'define', 'implode', 'ucfirst', 'array_key_exists', 'addslashes',
            'xhprof_disable', 'is_file', 'load::utils/xhprof_lib.php', 'base\events\AppEnd::__gcXHProf',
            'run_init::utils/xhprof_lib.php', 'load::utils/xhprof_runs.php', 'run_init::utils/xhprof_runs.php',
            'dirname', 'in_array'
        );
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY, array('ignored_functions' =>  $ignore));
    }
    private static function __initUcweb()
    {
        //self::$ucweb = \base\Ucweb::getInstance();
        // // \apps\base\Ucweb::getInstance()->getPlatform();
        $platform  = 'v3';
        \framework\base\Web::getInstance()->setPlatfrom($platform);
    }
    private static function __initSession()
    {
        // 初始化session信息
        //\framework\services\Factory::getInstance()->getService('ISession')->bindSid(self::$ucweb);
    }
    private static $ucweb;
}
