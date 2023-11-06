<?php

namespace Bitrix\TransformerController\Controllers;

use Bitrix\TransformerController\Queue;
use Bitrix\TransformerController\TimeStatistic;
use Bitrix\TransformerController\Entity\UsageStatisticTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Error;

class StatisticController extends Base
{
	protected function getActionList()
	{
		return [
			'statistic' => [
				'params' => ['period' => ['default' => 60], 'queue', 'date', 'dateFrom', 'dateTo'],
			],
			'top' => [
				'params' => ['period' => ['default' => 60], 'limit' => ['default' => 5], 'queue', 'date', 'dateFrom', 'dateTo'],
			],
		];
	}

	protected function getPeriodsFromRequest(array $params)
	{
		$period = intval($params['period']);
		$periodStart = $periodEnd = null;
		if(isset($params['date']))
		{
			$date = DateTime::tryParse($params['date'], 'Y-m-d');
			if($date)
			{
				$periodStart = $date;
				$periodEnd = clone $date;
				$periodEnd->add('1D');
			}
		}
		elseif(isset($params['dateFrom']) && isset($params['dateTo']))
		{
			$periodStart = DateTime::tryParse($params['dateFrom'], 'Y-m-d-H-i');
			$periodEnd = DateTime::tryParse($params['dateTo'], 'Y-m-d-H-i');
		}

		if(!$periodStart || !$periodEnd)
		{
			$periodEnd = DateTime::createFromTimestamp(time());
			$periodStart = DateTime::createFromTimestamp(time() - $period);
		}

		return [$periodStart, $periodEnd];
	}

	protected function statistic($params)
	{
		$filter = [];
		if(isset($params['queue']))
		{
			$queueId = Queue::getQueueIdByName($params['queue']);
			if(!$queueId)
			{
				$this->result->addError(new Error('queue with name '.$params['queue'].' not found'));
				return false;
			}
			else
			{
				$filter['=QUEUE_ID'] = $queueId;
			}
		}

		list($periodStart, $periodEnd) = $this->getPeriodsFromRequest($params);

		$timeStatistic = TimeStatistic::get($periodStart, $periodEnd, $filter);

		$errorsCount = TimeStatistic::getErrorsCount($periodStart, $periodEnd, $filter);

		$timeStatistic = TimeStatistic::formatJson($timeStatistic, $errorsCount);

		$usageStatistic = UsageStatisticTable::getList([
			'select' => [
				new ExpressionField('count', 'COUNT(*)'),
				'COMMAND_NAME',
			],
			'filter' => array_merge([
				'>DATE' => $periodStart,
				'<DATE' => $periodEnd,
			], $filter),
			'group' => ['COMMAND_NAME']
		])->fetchAll();

		foreach($usageStatistic as $command)
		{
			$timeStatistic[TimeStatistic::getJsonField($command['COMMAND_NAME'])]['added_count'] = $command['count'];
		}

		return $timeStatistic;
	}

	protected function top($params)
	{
		list($periodStart, $periodEnd) = $this->getPeriodsFromRequest($params);
		$limit = intval($params['limit']);
		$filter = [
			'>DATE' => $periodStart,
			'<DATE' => $periodEnd,
		];
		if(isset($params['queue']))
		{
			$queueId = Queue::getQueueIdByName($params['queue']);
			if(!$queueId)
			{
				$this->result->addError(new Error('queue with name '.$params['queue'].' not found'));
				return false;
			}
			else
			{
				$filter['=QUEUE_ID'] = $queueId;
			}
		}

		$result = [];
		$topList = UsageStatisticTable::getList([
			'select' => [
				new ExpressionField('commands', 'COUNT(*)'),
				new ExpressionField('size', 'SUM(%s)', ['FILE_SIZE']),
				'DOMAIN',
			],
			'order' => [
				'commands' => 'DESC'
			],
			'filter' => $filter,
			'group' => ['DOMAIN'],
			'limit' => $limit,
		]);
		while($top = $topList->fetch())
		{
			$top['file_size'] = $top['size'];
			$top['domain'] = $top['DOMAIN'];
			unset($top['DOMAIN']);
			unset($top['size']);
			$result[] = $top;
		}

		return $result;
	}
}