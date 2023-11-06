<?php

use Bitrix\TransformerController\Worker;
use Bitrix\TransformerController\Queue;
use Bitrix\Main\Config\Option;

declare(ticks = 1);

if(!isset($argv[0]) || empty($argv[0]))
{
	die('worker.php should start from console only');
}

if(!$queueName || empty($queueName))
{
	die('No queue name');
}

if(!isset($docRoot) || !is_dir($docRoot))
{
    $docRoot = realpath(__DIR__.'/../../../../');
}

$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);
define('NO_AGENT_CHECK', true);
define('BX_CRONTAB', true);

/** @noinspection PhpIncludeInspection */
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!\Bitrix\Main\Loader::includeModule('transformercontroller'))
{
	die ('module transformercontroller is not installed');
}

\Bitrix\TransformerController\Cron::changeDirectory(\Bitrix\TransformerController\FileUploader::getLocalUploadPath());

$exchange = Queue::createExchange($queueName);
$queue = new Queue($exchange, new \AMQPQueue($exchange->getChannel()), $queueName);

$startTime = time();
$workTimeFrom = (int)Option::get('transformercontroller', 'lifetime_from', 5);
$workTimeTo = (int)Option::get('transformercontroller', 'lifetime_to', 10);
$workTime = random_int($workTimeFrom, $workTimeTo) * 60;
$endTime = $startTime + $workTime;

$fileUploader = new \Bitrix\TransformerController\FileUploader();

$worker = new Worker($queue, $fileUploader, '\Bitrix\Main\Web\HttpClient', $endTime);
pcntl_signal(SIGUSR1, array($worker, 'setEndTime'), false);
$worker->work();
