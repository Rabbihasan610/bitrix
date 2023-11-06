<?php

use Bitrix\Main\Web\Json;
use Bitrix\TransformerController\Controllers\QueueController;

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

$controller = new QueueController();
$controller->setAction($action)->exec();