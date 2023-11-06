<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;
use Bitrix\TransformerController\Controllers\Base;
use Bitrix\Main\Error;
use Bitrix\TransformerController\Controllers\LimitController;
use Bitrix\TransformerController\Controllers\StatisticController;

class ControllerTest extends \CBitrixTestCase
{
	protected $controller;

	function setUp()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
	}

	function paramsProvider()
	{
		return array (
			'successStatisticList' => array (
				'\Bitrix\TransformerController\Controllers\StatisticController',
				'statistic',
				array(),
				array(),
			),
			'errorStatisticWrongAction' => array (
				'\Bitrix\TransformerController\Controllers\StatisticController',
				'add',
				array(),
				array(new Error('action is not found')),
			),
			'successStatisticTop' => array (
				'\Bitrix\TransformerController\Controllers\StatisticController',
				'top',
				array(),
				array(),
			),
			'successBanGetList' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'getList',
				array(),
				array(),
			),
			'failBanWrongAction' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'update',
				array(),
				array(new Error('action is not found')),
			),
			'successBanAdd' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'add',
				array('domain' => 'domain', 'date_end' => '123', 'reason' => 'reason'),
				array(),
			),
			'failBanAdd' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'add',
				array('date_end' => '123', 'reason' => 'reason'),
				array(new Error('Required parameter domain is not specified')),
			),
			'successBanDelete' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'delete',
				array('domain' => 'domain'),
				array(),
			),
			'failBanDelete' => array(
				'\Bitrix\TransformerController\Controllers\BanController',
				'delete',
				array(),
				array(new Error('Required parameter domain is not specified')),
			),
			'successLimitList' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'getList',
				array(),
				array(),
			),
			'failLimitWrongAction' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'update',
				array(),
				array(new Error('action is not found')),
			),
			'successLimitAdd' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'add',
				array('domain' => '123'),
				array(),
			),
			'successLimitDelete' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'delete',
				array(),
				array(),
			),
			'successLimitClear' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'clear',
				array(),
				array(),
			),
			'successLimitUsage' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'usage',
				array('domain' => '123'),
				array(),
			),
			'failLimitUsage' => array(
				'\Bitrix\TransformerController\Controllers\LimitController',
				'usage',
				array(),
				array(new Error('Required parameter domain is not specified')),
			),
		);
	}

	/**
	 * @covers Base::checkParams()
	 * @dataProvider paramsProvider
	 */
	function testCheckParams($controllerName, $action, $get, $errors)
	{
		$request = new HttpRequest(new \Bitrix\Main\Server($_SERVER), $get, array(), array(), array());
		/** @var Base $controller */
		$controller = new $controllerName($request);
		$controller->setAction($action);
		$this->checkParams($controller);
		$this->assertEquals($errors, $controller->getResult()->getErrors());
	}

	/**
	 * @covers Base::setAction()
	 */
	function testEmptyAction()
	{
		$this->setExpectedException('Bitrix\Main\ArgumentNullException');
		$controller = new StatisticController();
		$controller->setAction('');
	}

	/**
	 * @covers Base::setAction()
	 */
	function testWrongTypeAction()
	{
		$this->setExpectedException('Bitrix\Main\ArgumentTypeException');
		$controller = new StatisticController();
		$controller->setAction(array('123'));
	}

	function testEmptyActionExec()
	{
		$controller = new StatisticController();
		$controller->exec(false);
		$this->assertEquals(array(new Error('action is not specified')), $controller->getResult()->getErrors());
	}

	private function checkParams(&$controller)
	{
		$reflection = new \ReflectionClass(get_class($controller));
		$method = $reflection->getMethod('checkParams');
		$method->setAccessible(true);

		return $method->invoke($controller);
	}

	function testSuccessLimitDeleteById()
	{
		$request = new HttpRequest(new \Bitrix\Main\Server($_SERVER), array('id' => 0), array(), array(), array());
		$controller = new LimitController($request);
		$controller->setAction('delete');
		$controller->exec(false);

		$result = new DeleteResult();

		$this->assertEquals($result, $controller->getResult());
	}

	function testSuccessLimitDeleteByDomain()
	{
		$request = new HttpRequest(new \Bitrix\Main\Server($_SERVER), array('domain' => '123'), array(), array(), array());
		$controller = new LimitController($request);
		$controller->setAction('delete');
		$controller->exec(false);

		$result = new Result();
		$result->setData(array('deleted' => 0));

		$this->assertEquals($result, $controller->getResult());
	}

	function testSuccessLimitDeleteByTarif()
	{
		$request = new HttpRequest(new \Bitrix\Main\Server($_SERVER), array('tarif' => '123'), array(), array(), array());
		$controller = new LimitController($request);
		$controller->setAction('delete');
		$controller->exec(false);

		$result = new Result();
		$result->setData(array('deleted' => 0));

		$this->assertEquals($result, $controller->getResult());
	}

	function testFailLimitDelete()
	{
		$request = new HttpRequest(new \Bitrix\Main\Server($_SERVER), array(), array(), array(), array());
		$controller = new LimitController($request);
		$controller->setAction('delete');
		$controller->exec(false);

		$result = new Result();
		$result->addError(new Error('id or domain or tarif should be specified'));

		$this->assertEquals($result, $controller->getResult());
	}
}
