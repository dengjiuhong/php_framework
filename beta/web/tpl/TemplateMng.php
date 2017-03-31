<?php
/**
 * UC乐园模板后台接口的实现
 *
 * @category   TemplateMng
 * @package    view
 * @author Jiuhong Deng <dengjiuhong@gmail.com>
 * @version $Id:$
 * @copyright 优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 */
declare(encoding='UTF-8');
namespace framework\web\tpl;
require dirname(__FILE__) . '/ITemplateMng.php';
final class TemplateMng implements \framework\web\tpl\ITemplateMng
{
    /**
     *    根据各种条件获取模板列表
     *
     * @param string $control  - 模板所在的control
     * @param string $action   - 模板所在的action
     * @param string $pf       - 模板对应的平台
     * @param string $ver      - 模板的版本
     */
    public function getTemplateList($control = '', $action = '', $pf = '', $ver = '')
    {
    }
    /**
     *    获取模板的详细信息
     *
     * @param numeric  $templateId  - 模板的唯一id
     * @return XHtml
     */
    public function getTemplateSource($templateId)
    {
    }
    /**
     *    获取指定模板的版本
     * @param numeric $templateId  - 查询模板的唯一id
     */
    public function getTemplateVers($templateId)
    {
    }
    /**
     *    编辑一个模板
     *
     * @param numeric $tmplateId  - 模板的唯一id
     * @param string  $tpl        - 模板的内容
     */
    public function edit($tmplateId, $tpl)
    {
    }
    /**
     *    预览模板的效果
     *
     * @param numeric   $tmplateId - 模板的唯一id
     * @param string    $tpl       - 模板的内容
     * @return XHtml               - 可以直接输出的XHtml标签
     */
    public function preview($tmplateId, $tpl)
    {
    }
    /**
     *    保存模板
     *
     * @param numbric $templateId  - 模板的唯一id
     * @param string  $tpl         - 模板的内容
     * @return bool                - 是否保存成功
     */
    public function save($templateId, $tpl)
    {
    }
    /**
     *    新建一个模板
     *
     * @param string $control   - 模板所在的control
     * @param string $action    - 模板所在的action
     * @param string $pf        - 模板所在的平台
     * @param string $ver       - 版本
     * @param string $tpl       - 模板的详细信息
     * @return numeric          - 模板的唯一id
     */
    public function create($control, $action, $pf, $ver, $tpl)
    {
    }
    /**
     *    删除一个模板
     *
     * @param numeric $templateId  - 唯一的模板id
     * @return bool                - 是否删除成功
     */
    public function delete($templateId)
    {
    }
    /**
     *    同步模板到生产环境
     * @param numeric  $templateId  - 唯一的模板id, 当为空时，同步所有的模板
     */
    public function sync($templateId = '')
    {
    }
    /**
     *    发布到生产环境后回滚
     * @param numeric $templateId  - 要回滚的模板id, 如果为空，回滚所有的模板
     * @param string  $ver         - 回滚到指定的版本
     */
    public function rollback($templateId = '', $ver = '')
    {
    }
    /**
     *    获取指定action可以用到的变量
     *
     * @param String  $control    - 查询的control
     * @param string  $action     - 查询的action
     * @param string  $pf         - 查询的平台
     * @return vars
     */
    public function getVars($control, $action, $pf)
    {
    }
}

