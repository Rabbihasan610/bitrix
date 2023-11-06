<?php

namespace Bitrix\TransformerController\Tests;

if (!class_exists('Bitrix\TransformerController\BaseCommand'))
	die;

use Bitrix\Main\Error;
use Bitrix\TransformerController\BaseCommand;
use Bitrix\Main\Result;

class TestCommand extends BaseCommand
{
	public function execute()
	{
		$result = new Result();
		if ($this->params['mock_error'])
		{
			$result->addError(new Error($this->params['mock_error']));
		}
		else
		{
			$result->setData(array('result' => $this->params['mock_result']));
		}
		return $result;
	}
}