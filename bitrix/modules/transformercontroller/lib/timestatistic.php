<?php

namespace Bitrix\TransformerController;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\TransformerController\Entity\TimeStatisticTable;
use Bitrix\Main\Type\DateTime;

/**
 * Statistic of time usage.
 *
 * This statistic is actual for each worker-machine separately.
 * Each machine knows only it is own statistic.
 */

class TimeStatistic
{
	const MODULE_ID = 'transformercontroller';

	const ERROR_CODE_WRONG_STATUS_BEFORE_DOWNLOAD = 100;
	const ERROR_CODE_WRONG_CONTENT_TYPE_BEFORE_DOWNLOAD = 101;
	const ERROR_CODE_FILE_IS_TOO_BIG_ON_DOWNLOAD = 102;
	const ERROR_CODE_DOMAIN_IS_BANNED = 103;
	const ERROR_CODE_QUEUE_ADD_EVENT = 150;
	const ERROR_CODE_QUEUE_ADD_FAIL = 151;
	const ERROR_CODE_WRONG_STATUS_AFTER_DOWNLOAD = 200;
	const ERROR_CODE_CANT_DOWNLOAD_FILE = 201;
	const ERROR_CODE_FILE_IS_TOO_BIG_AFTER_DOWNLOAD = 202;
	const ERROR_CODE_UPLOAD_FILES = 203;
	const ERROR_CODE_TRANSFORMATION_FAILED = 300;
	const ERROR_CODE_COMMAND_FAILED = 301;
	const ERROR_CODE_COMMAND_NOT_FOUND = 302;
	const ERROR_CODE_COMMAND_ERROR = 303;

	/**
	 * Add new record to statistic.
	 * @see DataManager::add()
	 *
	 * @param $data
	 * @return \Bitrix\Main\Entity\AddResult
	 * @throws \Exception
	 */
	public static function add($data)
	{
		return TimeStatisticTable::add($data);
	}

	/**
	 * Returns array with time statistic.
	 *
	 * @param DateTime $periodStart
	 * @param DateTime $periodEnd
	 * @param array $filter Filter for getList().
	 * @param array $group Group for getList().
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function get(DateTime $periodStart, DateTime $periodEnd, $filter = array(), $group = array('COMMAND_NAME'))
	{
		$query = new Query(Entity\TimeStatisticTable::getEntity());
		return $query
			->setSelect(array_merge($group, array(
				new ExpressionField('minWaitTime', 'MIN(%s)', array('TIME_START')),
				new ExpressionField('avgWaitTime', 'AVG(%s)', array('TIME_START')),
				new ExpressionField('maxWaitTime', 'MAX(%s)', array('TIME_START')),
				new ExpressionField('minExecTime', 'MIN(%s)', array('TIME_EXEC')),
				new ExpressionField('avgExecTime', 'AVG(%s)', array('TIME_EXEC')),
				new ExpressionField('maxExecTime', 'MAX(%s)', array('TIME_EXEC')),
				new ExpressionField('minUploadTime', 'MIN(%s)', array('TIME_UPLOAD')),
				new ExpressionField('avgUploadTime', 'AVG(%s)', array('TIME_UPLOAD')),
				new ExpressionField('maxUploadTime', 'MAX(%s)', array('TIME_UPLOAD')),
				new ExpressionField('minFullTime', 'MIN(%s)', array('TIME_END')),
				new ExpressionField('avgFullTime', 'AVG(%s)', array('TIME_END')),
				new ExpressionField('maxFullTime', 'MAX(%s)', array('TIME_END')),
				new ExpressionField('fileSize', 'SUM(%s)', array('FILE_SIZE')),
				new ExpressionField('count', 'COUNT(*)'),
			)))
			->setGroup($group)
			->setFilter(array_merge($filter, array(
				'>TIME_END_ABSOLUTE' => $periodStart,
				'<TIME_END_ABSOLUTE' => $periodEnd,
			)))
			->exec()
			->fetchAll()
		;
	}

	public static function getErrorsCount(DateTime $periodStart, DateTime $periodEnd, $filter = array(), $group = array('COMMAND_NAME', 'ERROR'))
	{
		$errorData = $commandMaxQueueWait = Entity\TimeStatisticTable::getList(array(
			'select' => array_merge($group, array(
				new ExpressionField('errors', 'COUNT(*)'),
			)),
			'filter' => array_merge($filter, array(
				'>TIME_END_ABSOLUTE' => $periodStart,
				'<TIME_END_ABSOLUTE' => $periodEnd,
				'>ERROR' => 0,
			)),
			'group' => $group,
		))->fetchAll();
		$result = array();
		foreach($errorData as $row)
		{
			$result[$row['COMMAND_NAME']][$row['ERROR']] = $row['errors'];
		}
		return $result;
	}

	public static function formatJson(array $data, array $errorData = array())
	{
		$result = array();
		foreach($data as $command)
		{
			$result[self::getJsonField($command['COMMAND_NAME'])] = array(
				'wait_time' => array(
					'min' => $command['minWaitTime'],
					'avg' => $command['avgWaitTime'],
					'max' => $command['maxWaitTime'],
				),
				'exec_time' => array(
					'min' => $command['minExecTime'],
					'avg' => $command['avgExecTime'],
					'max' => $command['maxExecTime'],
				),
				'upload_time' => array(
					'min' => $command['minUploadTime'],
					'avg' => $command['avgUploadTime'],
					'max' => $command['maxUploadTime'],
				),
				'full_time' => array(
					'min' => $command['minFullTime'],
					'avg' => $command['avgFullTime'],
					'max' => $command['maxFullTime'],
				),
				'count' => $command['count'],
				'file_size' => $command['fileSize'],
			);
			if(isset($errorData[$command['COMMAND_NAME']]))
			{
				$result[self::getJsonField($command['COMMAND_NAME'])]['errors'] = $errorData[$command['COMMAND_NAME']];
			}
		}

		return $result;
	}

	public static function getMapFields()
	{
		return array(
			'Bitrix\\TransformerController\\Document' => 'document',
			'Bitrix\\TransformerController\\Video' => 'video',
			'Bitrix\TransformerController\Document' => 'document',
			'Bitrix\TransformerController\Video' => 'video',
		);
	}

	public static function getJsonField($field)
	{
		$mapFields = self::getMapFields();
		if(isset($mapFields[$field]))
		{
			return $mapFields[$field];
		}

		return $field;
	}

	public static function deleteOldAgent($days = 22, $portion = 500)
	{
		Entity\TimeStatisticTable::deleteOld($days, $portion);
		Entity\UsageStatisticTable::deleteOld($days, $portion);

		return "\\Bitrix\\TransformerController\\TimeStatistic::deleteOldAgent({$days}, {$portion});";
	}
}