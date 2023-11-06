<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Main\Config;

use Bitrix\Main;

class Option
{
	protected const CACHE_DIR = "b_option";

	protected static $options = [];
	protected static $loading = [];

	/**
	 * Returns a value of an option.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $default The default value to return, if a value doesn't exist.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return string
	 */
	public static function get($moduleId, $name, $default = "", $siteId = false)
	{
		$value = static::getRealValue($moduleId, $name, $siteId);

		if ($value !== null)
		{
			return $value;
		}

		if (isset(self::$options[$moduleId]["-"][$name]))
		{
			return self::$options[$moduleId]["-"][$name];
		}

		if ($default == "")
		{
			$moduleDefaults = static::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
			{
				return $moduleDefaults[$name];
			}
		}

		return $default;
	}

	/**
	 * Returns the real value of an option as it's written in a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param bool|string $siteId The site ID.
	 * @return null|string
	 * @throws Main\ArgumentNullException
	 */
	public static function getRealValue($moduleId, $name, $siteId = false)
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (isset(self::$loading[$moduleId]))
		{
			trigger_error("Options are already in the process of loading for the module {$moduleId}. Default value will be used for the option {$name}.", E_USER_WARNING);
		}

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$siteKey = ($siteId == ""? "-" : $siteId);

		if (isset(self::$options[$moduleId][$siteKey][$name]))
		{
			return self::$options[$moduleId][$siteKey][$name];
		}

		return null;
	}

	/**
	 * Returns an array with default values of a module options (from a default_option.php file).
	 *
	 * @param string $moduleId The module ID.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getDefaults($moduleId)
	{
		static $defaultsCache = [];

		if (isset($defaultsCache[$moduleId]))
		{
			return $defaultsCache[$moduleId];
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$path = Main\Loader::getLocal("modules/".$moduleId."/default_option.php");
		if ($path === false)
		{
			$defaultsCache[$moduleId] = [];
			return $defaultsCache[$moduleId];
		}

		include($path);

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
		{
			$defaultsCache[$moduleId] = ${$varName};
			return $defaultsCache[$moduleId];
		}

		$defaultsCache[$moduleId] = [];
		return $defaultsCache[$moduleId];
	}

	/**
	 * Returns an array of set options array(name => value).
	 *
	 * @param string $moduleId The module ID.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getForModule($moduleId, $siteId = false)
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$result = self::$options[$moduleId]["-"];

		if($siteId <> "" && !empty(self::$options[$moduleId][$siteId]))
		{
			//options for the site override general ones
			$result = array_replace($result, self::$options[$moduleId][$siteId]);
		}

		return $result;
	}

	protected static function load($moduleId)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = static::getCacheTtl();
		$loadFromDb = true;

		if ($cacheTtl !== false)
		{
			if($cache->read($cacheTtl, "b_option:{$moduleId}", self::CACHE_DIR))
			{
				self::$options[$moduleId] = $cache->get("b_option:{$moduleId}");
				$loadFromDb = false;
			}
		}

		if($loadFromDb)
		{
			self::$loading[$moduleId] = true;

			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			// prevents recursion and cache miss
			self::$options[$moduleId] = ["-" => []];

			$query = "
				SELECT NAME, VALUE
				FROM b_option
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
			";

			$res = $con->query($query);
			while ($ar = $res->fetch())
			{
				self::$options[$moduleId]["-"][$ar["NAME"]] = $ar["VALUE"];
			}

			try
			{
				//b_option_site possibly doesn't exist

				$query = "
					SELECT SITE_ID, NAME, VALUE
					FROM b_option_site
					WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
				";

				$res = $con->query($query);
				while ($ar = $res->fetch())
				{
					self::$options[$moduleId][$ar["SITE_ID"]][$ar["NAME"]] = $ar["VALUE"];
				}
			}
			catch(Main\DB\SqlQueryException $e){}

			if($cacheTtl !== false)
			{
				$cache->setImmediate("b_option:{$moduleId}", self::$options[$moduleId]);
			}

			unset(self::$loading[$moduleId]);
		}

		/*ZDUyZmZOGNhODZjMWFmOTNjNDdlZmQ4YzNlODMyYTNjODJiOGM=*/$GLOBALS['____1887384734']= array(base64_decode('ZX'.'hwbG9k'.'ZQ=='),base64_decode('cGFjaw=='),base64_decode('bW'.'Q1'),base64_decode('Y29uc3'.'Rh'.'bnQ='),base64_decode(''.'aGFzaF9'.'obWFj'),base64_decode('c3RyY'.'21w'),base64_decode('aXNfb2JqZWN0'),base64_decode(''.'Y2'.'Fs'.'bF91c'.'2Vy'.'X2Z1bmM'.'='),base64_decode('Y2FsbF9'.'1c2VyX2Z1bmM='),base64_decode('Y2FsbF'.'91c2VyX2Z1bmM'.'='),base64_decode('Y2FsbF91c2Vy'.'X2Z1bmM='),base64_decode('Y2FsbF91c2Vy'.'X2'.'Z1bmM'.'='));if(!function_exists(__NAMESPACE__.'\\___547285790')){function ___547285790($_1315394678){static $_1214905465= false; if($_1214905465 == false) $_1214905465=array('LQ'.'='.'=','bWFpbg==','b'.'WFpbg==','LQ==','b'.'WF'.'pbg==','f'.'lBB'.'UkFNX01BWF9V'.'U0VSUw='.'=','LQ==','bWFpbg'.'==','f'.'lB'.'BUk'.'F'.'NX'.'01BW'.'F9VU0VSUw='.'=','Lg==','SCo'.'=','Ym'.'l0'.'c'.'ml4','TEl'.'DRU5TR'.'V9'.'LRV'.'k=','c2h'.'h'.'MjU2','LQ='.'=','b'.'WF'.'pbg==',''.'fl'.'BBUkF'.'NX01'.'BWF9VU'.'0V'.'SUw==','LQ'.'==','bWF'.'p'.'bg==','UEFSQU1fTUFYX'.'1VTRVJT','VVNFUg'.'==','VVNFUg==','VV'.'N'.'FUg='.'=','SXNB'.'dXRob3Jpe'.'mVk','VV'.'NFUg==',''.'S'.'X'.'NBZG'.'1'.'pbg==','QVB'.'QT'.'ElDQVRJT04=','Um'.'VzdGF'.'ydEJ1ZmZl'.'c'.'g==','TG9jYWxSZ'.'W'.'RpcmVj'.'dA='.'=','L2xpY2Vuc2V'.'fcmV'.'zdHJpY3Rpb2'.'4uc'.'Gh'.'w','L'.'Q==','bWFpbg'.'==','flBBU'.'kFNX01'.'BWF9VU0VSUw==','LQ==','b'.'WFpbg='.'=','UEF'.'S'.'QU'.'1f'.'TUFYX'.'1VTRV'.'JT',''.'XEJ'.'pdHJ'.'p'.'eFxNYWluXENvbmZ'.'pZ1xPc'.'HRp'.'b2'.'4'.'6'.'OnN'.'ldA'.'==','bWFpbg==',''.'UEF'.'S'.'Q'.'U1fT'.'UF'.'YX1V'.'T'.'RV'.'JT');return base64_decode($_1214905465[$_1315394678]);}};if(isset(self::$options[___547285790(0)][___547285790(1)]) && $moduleId === ___547285790(2)){ if(isset(self::$options[___547285790(3)][___547285790(4)][___547285790(5)])){ $_1293171129= self::$options[___547285790(6)][___547285790(7)][___547285790(8)]; list($_405152808, $_761857426)= $GLOBALS['____1887384734'][0](___547285790(9), $_1293171129); $_1612960138= $GLOBALS['____1887384734'][1](___547285790(10), $_405152808); $_106062217= ___547285790(11).$GLOBALS['____1887384734'][2]($GLOBALS['____1887384734'][3](___547285790(12))); $_1827052469= $GLOBALS['____1887384734'][4](___547285790(13), $_761857426, $_106062217, true); self::$options[___547285790(14)][___547285790(15)][___547285790(16)]= $_761857426; self::$options[___547285790(17)][___547285790(18)][___547285790(19)]= $_761857426; if($GLOBALS['____1887384734'][5]($_1827052469, $_1612960138) !==(958-2*479)){ if(isset($GLOBALS[___547285790(20)]) && $GLOBALS['____1887384734'][6]($GLOBALS[___547285790(21)]) && $GLOBALS['____1887384734'][7](array($GLOBALS[___547285790(22)], ___547285790(23))) &&!$GLOBALS['____1887384734'][8](array($GLOBALS[___547285790(24)], ___547285790(25)))){ $GLOBALS['____1887384734'][9](array($GLOBALS[___547285790(26)], ___547285790(27))); $GLOBALS['____1887384734'][10](___547285790(28), ___547285790(29), true);} return;}} else{ self::$options[___547285790(30)][___547285790(31)][___547285790(32)]= round(0+6+6); self::$options[___547285790(33)][___547285790(34)][___547285790(35)]= round(0+3+3+3+3); $GLOBALS['____1887384734'][11](___547285790(36), ___547285790(37), ___547285790(38), round(0+2.4+2.4+2.4+2.4+2.4)); return;}}/**/
	}

	/**
	 * Sets an option value and saves it into a DB. After saving the OnAfterSetOption event is triggered.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $value The option value.
	 * @param string $siteId The site ID, if the option depends on a site.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function set($moduleId, $name, $value = "", $siteId = "")
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (mb_strlen($name) > 100)
		{
			trigger_error("Option name {$name} will be truncated on saving.", E_USER_WARNING);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$updateFields = [
			"VALUE" => $value,
		];

		if($siteId == "")
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME"];

			$sql = $sqlHelper->prepareMerge("b_option", $keyFields, $insertFields, $updateFields);
		}
		else
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"SITE_ID" => $siteId,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME", "SITE_ID"];

			$sql = $sqlHelper->prepareMerge("b_option_site", $keyFields, $insertFields, $updateFields);
		}

		$con->queryExecute(current($sql));

		static::clearCache($moduleId);

		static::loadTriggers($moduleId);

		$event = new Main\Event(
			"main",
			"OnAfterSetOption_".$name,
			array("value" => $value)
		);
		$event->send();

		$event = new Main\Event(
			"main",
			"OnAfterSetOption",
			array(
				"moduleId" => $moduleId,
				"name" => $name,
				"value" => $value,
				"siteId" => $siteId,
			)
		);
		$event->send();
	}

	protected static function loadTriggers($moduleId)
	{
		static $triggersCache = [];

		if (isset($triggersCache[$moduleId]))
		{
			return;
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$triggersCache[$moduleId] = true;

		$path = Main\Loader::getLocal("modules/".$moduleId."/option_triggers.php");
		if ($path === false)
		{
			return;
		}

		include($path);
	}

	protected static function getCacheTtl()
	{
		static $cacheTtl = null;

		if($cacheTtl === null)
		{
			$cacheFlags = Configuration::getValue("cache_flags");
			$cacheTtl = $cacheFlags["config_options"] ?? 3600;
		}
		return $cacheTtl;
	}

	/**
	 * Deletes options from a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param array $filter {name: string, site_id: string} The array with filter keys:
	 * 		name - the name of the option;
	 * 		site_id - the site ID (can be empty).
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($moduleId, array $filter = array())
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$deleteForSites = true;
		$sqlWhere = $sqlWhereSite = "";

		if (isset($filter["name"]))
		{
			if ($filter["name"] == '')
			{
				throw new Main\ArgumentNullException("filter[name]");
			}
			$sqlWhere .= " AND NAME = '{$sqlHelper->forSql($filter["name"])}'";
		}
		if (isset($filter["site_id"]))
		{
			if($filter["site_id"] <> "")
			{
				$sqlWhereSite = " AND SITE_ID = '{$sqlHelper->forSql($filter["site_id"], 2)}'";
			}
			else
			{
				$deleteForSites = false;
			}
		}
		if($moduleId == 'main')
		{
			$sqlWhere .= "
				AND NAME NOT LIKE '~%'
				AND NAME NOT IN ('crc_code', 'admin_passwordh', 'server_uniq_id','PARAM_MAX_SITES', 'PARAM_MAX_USERS')
			";
		}
		else
		{
			$sqlWhere .= " AND NAME <> '~bsm_stop_date'";
		}

		if($sqlWhereSite == '')
		{
			$con->queryExecute("
				DELETE FROM b_option
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
					{$sqlWhere}
			");
		}

		if($deleteForSites)
		{
			$con->queryExecute("
				DELETE FROM b_option_site
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
					{$sqlWhere}
					{$sqlWhereSite}
			");
		}

		static::clearCache($moduleId);
	}

	protected static function clearCache($moduleId)
	{
		unset(self::$options[$moduleId]);

		if (static::getCacheTtl() !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option:{$moduleId}", self::CACHE_DIR);
		}
	}

	protected static function getDefaultSite()
	{
		static $defaultSite;

		if ($defaultSite === null)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
			{
				$defaultSite = $context->getSite();
			}
		}
		return $defaultSite;
	}
}
