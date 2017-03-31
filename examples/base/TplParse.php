<?php
namespace base;
use framework\base\Config as Config;
class TplParse
{
    /**
     * 解析模版里面的url标签
     * @param unknown_type $vars
     */
    public function url ($vars)
    {
        $tmp   = explode(' ', $vars);
        $route = isset($tmp[0]) ? $tmp[0] : '';
        $param = isset($tmp[1]) ? $tmp[1] : '';
        $url   = '';
        switch ($route) {
            default:
                // 直接输入路由
                // 如 friend, /list  friend/list
                $tmp = explode('/', $route);
                if (count($route) == 1 && strpos($route, '/') === false) {
                    $control = Web::$control;
                    $action = $tmp[0];
                } else {
                    $control = isset($tmp[0]) ? $tmp[0] : Web::$control;
                    $action = isset($tmp[1]) ? $tmp[1] : 'index';
                }
                $route = $control . '/' . $action;
                $param = empty($param) ? "" : "&amp;" . $param;
                $baseUrl = Config::get('domain.base', 'Common');
                $prefix = strpos($baseUrl, '?') !== false ? '&' : '?';
                $url = $baseUrl . $prefix . "r=" . $route . $param;
                // 再接上sid
                break;
        }
        return $url;
    }
    public function util ($util)
    {
        if (empty($util))
            return false;
        $tmp = explode(' ', $util);
        $_tmp = isset($tmp[0]) ? $tmp[0] : '';
        if (empty($_tmp))
            return false;
        $str = '';
        switch ($_tmp) {
            case 'form':
                // 表单工具集合
                $attr = isset($tmp[1]) ? $tmp[1] : '';
                if ($attr == 'hash') {
                    $str = '<?php echo $this->getFormVerify(); ?>';
                }
                break;
            case 'page':
                $total = isset($tmp[1]) ? $tmp[1] : '';
                $pageSize = isset($tmp[2]) ? $tmp[2] : 10;
                $url = isset($tmp[3]) ? $tmp[3] : '';
                $total = strpos($total, '$') === false ? '"' . $total . '"' : $total;
                $pageSize = strpos($pageSize, '$') === false ? '"' . $pageSize .
                 '"' : $pageSize;
                $url = strpos($url, '$') === false ? '"' . $url . '"' : $url;
                $baseview = View::getInstance();
                $str = $baseview->page($total, $pageSize, $url);
                break;
            case 'desctime':
                $tmp = isset($tmp[1]) ? $tmp[1] : '';
                $tmp = strpos($tmp, '$') === false ? '"' . $tmp . '"' : $tmp;
                $str = '<?php \apps\base\Util::desctime(' . $tmp . '); ?>' . "\n";
                break;
            case 'encode':
                $tmp = isset($tmp[1]) ? $tmp[1] : '';
                $tmp = strpos($tmp, '$') === false ? '"' . $tmp . '"' : $tmp;
                $str = '<?php \apps\base\Util::encode(' . $tmp . '); ?>' . "\n";
                break;
            default:
                break;
        }
        return $str;
    }
    public function csss ($param)
    {
        return '';
        // 直接输出到页面
        $view = View::getInstance();
        $conf = Config::get('css', 'Static');
        $isDebug = Config::get('debug', 'Common');
        $fileName = $view->getPf() . '_' . $conf['version'] . '.css';
        $path = $conf['staticPath'] . 'css/';
        $file = $path . $fileName;
        if (! is_file($file) && $isDebug) {
            // debug模式下面，自动建立这个文件夹
            if (! is_dir($path)) {
               \mkdir($path, 0777, true);
            }
            $css = $this->loadCss($view->getPf());
            file_put_contents($file, $css);
        }
        $str = '<?php echo \framework\web\Url::cssUrl("' . $fileName . '"); ?>';
        return "\n" . '<link href="' . $str .
         '" rel="stylesheet" type="text/css" />';
    }
    public function ucextra ($param)
    {
        return '<!--UCExtra-->';
    }
}