<?php
/**
 * UC乐园 - 基础工具  生成唯一id
 *
 * @author liangrn
 * @version 1.0
 * @created 09-八月-2010 19:49:50
 */
declare(encoding='UTF-8');
namespace framework\utils;
use framework\datalevel\base\Factory as Factory;
class IdGenerator
{
	private static $_mc = null;
	//private static $_idPrefix = "cache_id_prefix";
	private static $_idSuffix = "cache_id_suffix";
	
	//缓存过期时间
	private static $_expireTime = 7200;
	
	//前缀最小值，因为需要保证至少3位数，需要从100开始
	private static $_minPrefixNum = 100;
	//******************************************************************************************
	//【前缀最大值】
	//取100到9200的随机数(2010-9-19 by liangrn)。History：mt_rand(100, 999);
	//因为bigint 范围是 -2^63 (-9,223,372,036,854,775,808) 到 2^63-1 (9,223,372,036,854,775,807)
	//所以取9200，避免19位数值超出Bigint的范围。id可以使用30年, 30年秒数约为：995000000
	//******************************************************************************************
	private static $_maxPrefixNum = 9200;
	
	//后缀最大值
	private static $_maxSuffixNum = 1000000000;
	
    /**
     *   生成bigint
     */
	public static function getBigIntId()
	{
		//***************************************************************************
		//Id构成：3到4位随机数（或自增数） + 8到9位秒数 + 6位微秒数，现拆分成两部分生成。
		//1、前缀部分：3到4位随机数
		//2、后缀部分：时间数值，精确到微秒
		//***************************************************************************		
	    return self::getRandNum() . self::getSuffixNum();
	}
	
	/**
	 * 获取Id后缀
	 */
	private static function getSuffixNum()
	{
		//当前微秒时间值
		$micTime = self::getMictime();
		//截取了后9位的数值
		$cutCount = strlen(self::$_maxSuffixNum) - 1;
		$midNum = substr($micTime, 0, strlen($micTime) - $cutCount);
		try
		{
			//****************************************************************************
			//通过使用Mc的increment来避免重复Id，在一些边界情况和失败的情况则使用随机数的方式。
			//****************************************************************************
			if(self::$_mc == null)
			{
				$_mc = Factory::getInstance()->getMc();
			}
			$suffixNum = $_mc->increment(self::$_idSuffix);
			if($suffixNum === false)
			{//KEY不存在或失败
				
				//取当前微秒时间后9位
				$suffixNum = substr($micTime, -$cutCount);
				//添加KEY
				$res = $_mc->add(self::$_idSuffix, $suffixNum, self::$_expireTime);
				
				if($res === false)
				{//失败时使用随机数
					return $micTime;
				}
			}
			if($suffixNum >= self::$_maxSuffixNum)
			{
				//取当前微秒时间后9位
				$suffixNum = substr($micTime, -$cutCount);
				
				//重新设置最小值
				$_mc->replace(self::$_idSuffix, $suffixNum, self::$_expireTime);
				
				//边界情况使用当前机器微秒时间
				return $micTime;
			}
			//微秒时间数值的前几位 + 后缀
			return $midNum . $suffixNum;
		}
		catch (Exception $e)
		{//异常情况使用当前机器微秒时间
			return $micTime;
		}
	}
	
	/**
	 * 获取Id前缀
	 */
	/*private static function getPrefixNum()
	{
		try
		{
			//****************************************************************************
			//通过使用Mc的increment来避免重复Id，在一些边界情况和失败的情况则使用随机数的方式。
			//****************************************************************************
			if(self::$_mc == null)
			{
				$_mc = Factory::getInstance()->getMc();
			}
			$prefixNum = $_mc->increment(self::$_idPrefix);
			if($prefixNum === false)
			{//KEY不存在或失败
				//添加KEY
				$res = $_mc->add(self::$_idPrefix, self::$_minPrefixNum, self::$_expireTime);
				$prefixNum = self::$_minPrefixNum;
				
				if($res === false)
				{//失败时使用随机数
					return self::getRandNum();
				}
			}
			if($prefixNum >= self::$_maxPrefixNum)
			{
				//重新设置最小值
				$_mc->replace(self::$_idPrefix, self::$_minPrefixNum, self::$_expireTime);
				
				//边界情况使用随机数
				return self::getRandNum();
			}
			return $prefixNum;
		}
		catch (Exception $e)
		{//异常情况使用随机数据
			return self::getRandNum();
		}
	}*/
	
	//获取随机数
	private static function getRandNum()
	{
		
	    return mt_rand(self::$_minPrefixNum, self::$_maxPrefixNum);
	}
	
	//获取微秒
	private static function getMictime()
	{
		$start_timestamp = 1238119411;
	    $time = explode(' ', microtime());
	    //精确到微秒
	    return ($time[1] - $start_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
	    
	}
	
}

