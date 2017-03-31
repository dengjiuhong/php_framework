<?php

// 程序执行完成后触发
// 调用这个事件之前，系统已经将内容返回了给用户 
namespace base\events;
class AppEnd
{
    public static function run($param)
    {
        self::__gcXHProf();
    }
    private static function __writeAccessLog()
    {
               
        // // 记录access
        // \utils\Logger::writeAccessLog(self::$control . '/' . self::$action);
    }
    private static function __gcXHProf()
    {
        include_once __DIR__ . '/../../../../xhprof/lib/utils/xhprof_lib.php';
        include_once __DIR__ . '/../../../../xhprof/lib/utils/xhprof_runs.php';
        $profiler_namespace = 'framework';  // namespace for your application
        $xhprof_data = xhprof_disable();
        $xhprof_runs = new \XHProfRuns_Default();
        $run_id      = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
        // url to the XHProf UI libraries (change the host name and path)
        echo sprintf('http://localhost/xhprof/html/index.php?run=%s&source=%s', $run_id, $profiler_namespace) . "\n";
    }
}
