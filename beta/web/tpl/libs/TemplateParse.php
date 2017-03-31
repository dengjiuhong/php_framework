<?php
declare(encoding='UTF-8');
namespace framework\web\tpl;
use \framework\web\View as View;
use \framework\base\Config as Config;
use \framework\base\Web as Web;
use framework\web\Url as Url;
/**
 * UC乐园模板引擎
 *
 * @category   tpl.TemplateParse
 * @package    web
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link    http://u.uc.cn/
 * @since   File available since Release 2.1.0
 */
final class TemplateParse
{
    /**
     * 模板编译
     *   
     * @param  string $template  - 需要编译的模板
     * @return string            - 编译好的php源代码
     */
    public function parse($template)
    {
        $config = Config::getGlobal();
        // 配置的解析器
        $parser       = isset($config['web']['view']['parser']) ? $config['web']['view']['parser'] : array();
        $var_regexp   = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        // 标签替换
        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        // 使用配置的外部解析器解析模版
        if (!empty($parser)){
            foreach($parser as $key => $v){
                $this->parseFunc = $v;
                $template = preg_replace($key, "\$this->parserProxy('\\1', \$this->parseFunc)\n", $template);
            }
        }
        $template = preg_replace("/\{url\s+(.+?)\}/ies", "\$this->parseUrl('\\1')\n", $template);
        // 支持的几种内置的标签
        // 语言包
        $template = preg_replace("/\{lang\s+(.+?)\}/ies", "\$this->languagevar('\\1')\n", $template);
        // 解析services
        $template = preg_replace("/[\n\r\t]*\{service\s+(.+?)\s*\}[\n\r\t]*/ies", "\$this->parseService('\\1')\n", $template);
        // 基础输出
        $template = str_replace("{LF}", "<?php echo \"\\n\"?>", $template);
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?php echo \\1; ?>\n", $template);
        $template = preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies", "\$this->stripvtags('<?php echo \\1; ?>')", $template);
        // 替换公共模板
        $template = preg_replace("/[\n\r\t]*\{layout\s+([a-z0-9_:\/]+)\}[\n\r\t]*/ies", "\$this->parseLayout('\\1')", $template);
        $template = preg_replace("/[\n\r\t]*\{layout\s+(.+?)\}[\n\r\t]*/ies", "\$this->parseLayout('\\1')", $template);
        // 局部通用模板
        $template = preg_replace("/[\n\r\t]*\{include\s+(.+?)\}[\n\r\t]*/ies", "\$this->parseInclude('\\1')", $template);
        // if else
        $template = preg_replace("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<?php if(\\2) { ?>\\3')", $template);
        $template = preg_replace("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<?php } elseif(\\2) { ?>\\3')", $template);
        $template = preg_replace("/\{else\}/i", "<?php } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/i", "<?php } ?>", $template);
        // 循环
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<?php if(is_array(\\1)) foreach(\\1 as \\2) { ?>')", $template);
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<?php if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>')", $template);
        $template = preg_replace("/\{continue\}/i", "<?php continue; ?>", $template);
        $template = preg_replace("/\{\/loop\}/i", "<?php } ?>", $template);
        // eval
        $template = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/ies", "\$this->evaltags('\\1')\n", $template);
        $template = preg_replace("/\{$const_regexp\}/s", "<?php echo \\1; ?>\n", $template);
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
        // 将url里面的&号转换成标准的输出
        $template = preg_replace("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e", "\$this->transamp('\\0')", $template);
        $template = preg_replace("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ies", "\$this->stripscriptamp('\\1', '\\2')", $template);
        $template = str_replace('<?xml version="1.0" encoding="UTF-8"?>', "<?php echo '<?xml version=\"1.0\" encoding=\"UTF-8\"?>'; ?>", $template);
        return $template;
    }
    /**
     * 模版适配代理
     * @param string $value
     * @param string $func
     */
    private function parserProxy($value, $func)
    {
        return call_user_func($func, $value);
    }
    
    /**
     *   解析模板内调用通用模板块
     * @param string  $var  - 需要调用的模块
     */
    private function parseLayout($var)
    {
        $view = View::getInstance();
        return $view->layout($var);
    }
    /**
     *    局部内部通用模块
     * @param $var
     */
    private function parseInclude($var)
    {
        $view = View::getInstance();
        return $view->_include($var);
    }
    /**
     *   解析常用url
     * @param string  $vars - 需要解析的标记
     */
    private function parseUrl($vars)
    {
        return $this->transamp(Url::tplParse($vars));
    }
    /**
     * 字符替换
     * @param string $url
     */
    private function transamp($url)
    {
        $str = str_replace('&', '&amp;', $url);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }
    /**
     *   解析业务层请求
     * @param string  $vars  - 业务层请求参数
     */
    private function parseService($vars)
    {
        list($var,) = explode(' ', $vars);
        $class      = str_replace($var, '', $vars);
        list($class, $function) = explode('.', $class);
        $str   = '\framework\services\Factory::getInstance()->getService(\''.trim($class).'\')->' . trim($function);
        $str   = '<?php ' . $var . ' = ' . $str . '; ?>';
        return $str;
    }
    /**
     *   解析语言包
     * @param  string  $var  - 需要解析的语言包标签
     */
    private function languagevar($var) {
        return \framework\base\I18n::getInstance()->parse($var);
    }
    /**
     *   过滤特殊的标签
     * @param   string $expr      -  需要过滤的语句
     * @param   string $statement -
     */
    private function stripvtags($expr, $statement = '') {
        $expr      = str_replace("\\\"", "\"", preg_replace("/\<\?php \=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }
    /**
     *   添加quote
     * @Param string $var - 需要添加quote的标签
     */
    private function addquote($var) {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }
    /**
     *   解析eval标签
     */
    private function evaltags($php) {
        $php = str_replace('\"', '"', $php);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<?php $php ?>\n";
        return $search;
    }
    /**
     *   解析block标签
     */
    private function stripblock($var, $s) {
        $s = str_replace('\\"', '"', $s);
        $s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
        preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
        $constadd = '';
        $constary[1] = array_unique($constary[1]);
        foreach($constary[1] as $const) {
            $constadd .= '$__'.$const.' = '.$const.';';
        }
        $s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
        $s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
        $s = str_replace('<?', "\nEOF;\n", $s);
        return "<?php \n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
    }
    private $parseFunc;
}
