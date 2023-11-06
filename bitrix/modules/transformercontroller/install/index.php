<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = mb_substr($PathInstall, 0, mb_strlen($PathInstall) - mb_strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("transformercontroller")) return;

Class transformercontroller extends CModule
{
	var $MODULE_ID = "transformercontroller";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = TRANSFORMERCONTROLLER_VERSION;
			$this->MODULE_VERSION_DATE = TRANSFORMERCONTROLLER_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("TRANSFORMERCONTROLLER_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TRANSFORMERCONTROLLER_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMERCONTROLLER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformercontroller/install/step1.php");
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMERCONTROLLER_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformercontroller/install/step2.php");
		}
		return true;
	}

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;
		$errors = false;

		if (!$DB->Query("SELECT 'x' FROM b_transformercontroller_time_statistic WHERE 1=0", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformercontroller/install/db/".mb_strtolower($DB->type)."/install.sql");
		}
		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		RegisterModule("transformercontroller");

		if(\Bitrix\Main\Loader::includeModule('transformercontroller'))
		{
			\Bitrix\TransformerController\Entity\QueueTable::add([
				'NAME' => 'main_preview',
				'WORKERS' => 5,
				'SORT' => 100,
			]);

			\Bitrix\TransformerController\Entity\QueueTable::add([
				'NAME' => 'documentgenerator_create',
				'WORKERS' => 5,
				'SORT' => 200,
			]);
		}

		$nextDay = time() + 86400;
		CAgent::AddAgent('Bitrix\\TransformerController\\TimeStatistic::deleteOldAgent();',
			'transformercontroller',
			"N",
			86400,
			"",
			"Y",
			ConvertTimeStamp(strtotime(date('Y-m-d 03:00:00', $nextDay)), 'FULL')
		);

		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/transformercontroller/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMERCONTROLLER_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/transformercontroller/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("TRANSFORMERCONTROLLER_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/transformercontroller/install/unstep2.php");
		}

		return true;
	}

	function UnInstallDB()
	{
		global $DB, $APPLICATION;

		$errors = false;

		if (!isset($arParams['savedata']) || $arParams['savedata'] !== "Y")
		{
			$errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/transformercontroller/install/db/".mb_strtolower($DB->type)."/uninstall.sql");
		}

		if($errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $errors));
			return false;
		}

		CAgent::RemoveAgent(
			'Bitrix\\TransformerController\\TimeStatistic::deleteOldAgent();',
			'transformercontroller'
		);

		UnRegisterModule("transformercontroller");
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

}