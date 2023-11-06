<?php

namespace Bitrix\TransformerController\Controllers;

use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\TransformerController\BanList;
use Bitrix\TransformerController\Queue;

class BanController extends Base
{
	protected function getActionList()
	{
		return [
			'getList' => [
				'params' => ['all'],
				'permissions' => ['admin'],
			],
			'add' => [
				'params' => ['domain' => ['required' => true], 'date_end', 'reason', 'queue'],
				'permissions' => ['admin'],
			],
			'delete' => [
				'params' => ['domain' => ['required' => true], 'queue'],
				'permissions' => ['admin'],
			],
		];
	}

	protected function getList($params)
	{
		$list = BanList::getList($params['all']);
		foreach($list as &$ban)
		{
			/** @var DateTime $date */
			$date = $ban['DATE_ADD'];
			$ban['DATE_ADD'] = $date->getTimestamp();
			$date = $ban['DATE_END'];
			$ban['DATE_END'] = $date->getTimestamp();
		}
		$this->result->setData($list);
	}

	protected function add($params)
	{
		$data = [
			'DOMAIN' => $params['domain'],
			'DATE_END' => DateTime::createFromTimestamp($params['date_end']),
			'REASON' => $params['reason'],
		];
		if($params['queue'])
		{
			$queueId = Queue::getQueueIdByName($params['queue']);
			if(!$queueId)
			{
				$this->result->addError(new Error('queue with name '.$params['queue'].' not found'));
				return false;
			}
			else
			{
				$data['QUEUE_ID'] = $queueId;
			}
		}
		$addResult = BanList::add($data);
		if($addResult->isSuccess())
		{
			$this->result->setData(['ID' => $addResult->getId()]);
		}
		else
		{
			$this->result = $addResult;
		}
	}

	protected function delete($params)
	{
		$this->result = BanList::delete($params['domain'], $params['queue']);
	}
}