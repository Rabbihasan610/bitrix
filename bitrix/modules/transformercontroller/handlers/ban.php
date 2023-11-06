<?php

use Bitrix\Main\Web\Json;
use Bitrix\TransformerController\Controllers\BanController;

/** @var CMain $APPLICATION */
global $APPLICATION;

if(is_object($APPLICATION))
	$APPLICATION->RestartBuffer();

if(!\Bitrix\Main\Loader::includeModule('transformercontroller'))
{
	echo Json::encode(array(
		'success' => false,
		'result' => array(
			'code' => 'MODULE_NOT_INSTALLED',
			'msg' => 'Module transformercontroller isn`t installed',
		)
	));
	return;
}

$action = \Bitrix\Main\Context::getCurrent()->getRequest()->getQuery('action');
if(!$action)
{
	$action = 'getList';
}

$controller = new BanController();
$controller->setAction($action)->exec();