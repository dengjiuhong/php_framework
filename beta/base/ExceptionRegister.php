<?php
declare(encoding='UTF-8');
namespace framework\base;
/**
 * 注册异常捕捉事件
 */
set_exception_handler(array('\framework\base\ExceptionHandler', 'init'));
