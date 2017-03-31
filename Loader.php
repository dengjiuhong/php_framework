<?php
// +----------------------------------------------------------------------+
// | UC乐园基础框架                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009-2012 UC                                           |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Jiuhong Deng <dengjiuhong@gmail.com>                         |
// +----------------------------------------------------------------------+
//
// $Id: Loader.php 00001 2010-12-24 05:11:09Z dengjh $
declare(encoding='UTF-8');
namespace framework;
/**
 * Loader
 * 这是乐园基础框架的基础Loader，程序只要加载这个Loader，即可使用基础框架的所有内容
 *
 * @author   Jiuhong Deng <dengjiuhng@gmail.com>
 */
if (!isset($config['version'])){
    exit('please define framework\'s version.');
}
require $config['version'] . '/Loader.php';
