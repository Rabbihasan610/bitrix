<?php

use Bitrix\Main\Web\Uri;
use Bitrix\TransformerController\Queue;
use Bitrix\Main\Web\Json;
use Bitrix\TransformerController\Limits;

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

$request = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList()->toArray();
if(!\Bitrix\Main\Application::getInstance()->isUtfMode())
{
	$request = \Bitrix\Main\Text\Encoding::convertEncoding($request, 'UTF-8', SITE_CHARSET);
}

$verification = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('transformercontroller.verification');
$verificationResult = $verification->check($request);

if(!$verificationResult->isSuccess())
{
	$code = null;
	foreach($verificationResult->getErrors() as $error)
	{
		if(!$code)
		{
			$code = $error->getCode();
		}
	}
	if(!$code)
	{
		$code = 'RIGHT_CHECK_FAILED';
	}

	echo Json::encode(array(
		'success' => false,
		'result' => array(
			'code' => $code,
			'msg' => implode(', ', $verificationResult->getErrorMessages()),
		),
	));
	return;
}

$clientInfo = $verificationResult->getData();
$backUri = new Uri($request['params']['back_url']);
$domain = $backUri->getHost();

if(isset($request['QUEUE']))
{
	$queueName = $request['QUEUE'];
}
else
{
	$queueName = Queue::getDefaultQueueName();
}
$queueId = Queue::getQueueIdByName($queueName);
if(!$queueId)
{
	echo Json::encode(array(
		'success' => false,
		'result' => array(
			'code' => 'WRONG_QUEUE_NAME',
			'msg' => 'Queue with name '.$queueName.' not found',
		),
	));
	return;
}
$ban = \Bitrix\TransformerController\BanList::getByDomain($domain, $queueName);
if($ban)
{
	echo Json::encode(array(
		'success' => false,
		'result' => array(
			'code' => 'KEY_BAN',
			'msg' => $ban['REASON'],
		),
	));
	return;
}

if(!Limits::isDomainUnlimited($domain))
{
	$limits = new Limits(array(
		'TARIF' => $clientInfo['TARIF'],
		'COMMAND_NAME' => $request['command'],
		'DOMAIN' => $domain,
		'LICENSE_KEY' => ($clientInfo['LICENSE_KEY'] === 'stub' ? null : $clientInfo['LICENSE_KEY']),
		'QUEUE_ID' => $queueId,
		'TYPE' => $request['BX_TYPE'],
	));
	$resultLimit = $limits->check();
	if(!$resultLimit->isSuccess())
	{
		echo Json::encode(array(
			'success' => false,
			'result' => array(
				'code' => 'LIMIT_EXCEED',
				'msg' => implode(', ', $resultLimit->getErrorMessages()),
			),
		));
		return;
	}
}
$exchange = Queue::createExchange($queueName);
$queue = new Queue($exchange, new \AMQPQueue($exchange->getChannel()), $queueName);
$result = $queue->checkCommand($request['command'], $request['params']);
if($result->isSuccess())
{
	$parsedUrl = parse_url($request['params']['back_url']);
	parse_str($parsedUrl['query'], $backUrlParams);
	$guid = $backUrlParams['id'];
	$usageInfo = array(
		'TIME' => time(),
		'LICENSE_KEY' => $clientInfo['LICENSE_KEY'],
		'TARIF' => $clientInfo['TARIF'],
		'DOMAIN' => $domain,
		'QUEUE_ID' => $queueId,
		'GUID' => $guid,
	);
	$result = $queue->addMessage($request['command'], $request['params'], $usageInfo);
	if($result->isSuccess())
	{
		\Bitrix\TransformerController\Entity\UsageStatisticTable::add(array(
			'COMMAND_NAME' => $request['command'],
			'FILE_SIZE' => $request['params']['fileSize'],
			'DOMAIN' => $domain,
			'LICENSE_KEY' => $clientInfo['LICENSE_KEY'],
			'TARIF' => $clientInfo['TARIF'],
			'QUEUE_ID' => $queueId,
			'GUID' => $guid,
		));
	}
}
if($result->isSuccess())
{
	echo Json::encode([
		'success' => true,
	]);
}
else
{
	echo Json::encode([
		'success' => false,
		'result' => array(
			'code' => $result->getErrors()[0]->getCode(),
			'msg' => implode(', ', $result->getErrorMessages()),
		),
	]);
}