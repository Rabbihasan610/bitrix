<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;
use Bitrix\TransformerController\BaseCommand;
use Bitrix\TransformerController\Queue;
use Bitrix\TransformerController\TimeStatistic;

class QueueTest extends \CBitrixTestCase
{
	public $internalQueue = array();

	public static function setUpBeforeClass()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
	}

	public function addToInternalQueue($value)
	{
		array_push($this->internalQueue, $value);
		return true;
	}

	public function getFromInternalQueue()
	{
		$message = array_shift($this->internalQueue);
		if ($message)
		{
			$envelope = $this->getMock('\AMQPEnvelope', array('getBody'));
			$envelope->expects($this->any())->method('getBody')->will($this->returnValue($message));
			return $envelope;
		}
		else
		{
			return false;
		}
	}


	private function getMockConnection($isConnected = true)
	{
		$connection = $this->getMock('\AMQPConnection', array('isConnected'));
		$connection->expects($this->any())->method('isConnected')->will($this->returnValue($isConnected));
		return $connection;
	}

	private function getMockExchange($isConnected = true, $publish = true)
	{
		$connection = $this->getMockConnection($isConnected);
		$exchange = $this->getMock('\AMQPExchange', array('getConnection', 'publish'), array(), '', false);
		$exchange->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
		$exchange->expects($this->any())->method('publish')->will($this->returnValue($publish));
		return $exchange;
	}

	private function getMockQueue()
	{
		$mockQueue = $this->getMock('\AMQPQueue', array(), array(), '', false);
		return $mockQueue;
	}

	private function getMockCommand($result = array())
	{
		$mockCommand = $this->getMockBuilder('Bitrix\TransformerController\BaseCommand')->disableOriginalConstructor()->setMethods(array('execute', 'validate'/*, 'getRequiredParams'*/))->setMockClassName('TestMockCommandClass')->getMock();
		// next does not work as it should.
		// I think it because validate method uses late static binding to getRequiredParams, and it redeclared after validate declaration.
		$mockCommand->expects($this->any())->method('getRequiredParams')->will($this->returnValue($result));
		return $mockCommand;
	}

	/**
	 * @covers Queue::__construct
	 */
	function testConstructSuccess()
	{
		$mockQueue = $this->getMockQueue();
		$exchange = $this->getMockExchange();
		$queue = new Queue($exchange, $mockQueue, 'test');
		return $queue;
	}

	/**
	 * @covers Queue::__construct
	 */
	function testConstructBrokenConnection()
	{
		$this->setExpectedException('Bitrix\Main\InvalidOperationException');
		$mockQueue = $this->getMockQueue();
		$exchange = $this->getMockExchange(false);
		$queue = new Queue($exchange, $mockQueue, 'test');
	}

	/**
	 * @covers Queue::checkCommand()
	 * @param Queue $queue
	 * @depends testConstructSuccess
	 */
	function testCheckCommandSuccess($queue)
	{
		$param = array('test_param' => array('test1', 'test2'), 'back_url' => 'test_url');
		$command = $this->getMockCommand($param);
		$checkResult = $queue->checkCommand($command, $param);
		$this->assertTrue($checkResult->isSuccess());
	}

	/**
	 * @covers Queue::checkCommand()
	 * @param Queue $queue
	 * @depends testConstructSuccess
	 */
	function testCheckCommandNoBackUrl($queue)
	{
		$param = array();
		$command = $this->getMockCommand($param);
		$checkResult = $queue->checkCommand($command, $param);
		$this->assertFalse($checkResult->isSuccess());
		$this->assertEquals(array('required param back_url is not specified'), $checkResult->getErrorMessages());
	}

	/**
	 * @covers Queue::addMessage()
	 */
	function testAddSuccess()
	{
		$exchange = $this->getMockExchange();
		$mockQueue = $this->getMockQueue();
		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = array('param' => array('value'));
		$addResult = $queue->addMessage($class, $params);
		$this->assertEquals(new Result(), $addResult);
	}

	/**
	 * @covers Queue::addMessage()
	 */
	function testAddCantPublish()
	{
		$exchange = $this->getMockExchange(true, false);
		$mockQueue = $this->getMockQueue();
		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = ['param' => ['value']];
		$addResult = $queue->addMessage($class, $params);
		$expectedResult = new Result();
		$expectedResult->addError(new Error('Cant publish to queue', TimeStatistic::ERROR_CODE_QUEUE_ADD_FAIL));
		$this->assertEquals($expectedResult, $addResult);
	}

	public function testAddWithEventSuccess()
	{
		$exchange = $this->getMockExchange();
		$mockQueue = $this->getMockQueue();
		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = array('param' => array('value'));

		EventManager::getInstance()->addEventHandler('transformercontroller', 'onBeforeMessageAdd', function(Event $event)
		{
			return new EventResult(EventResult::SUCCESS);
		});
		$addResult = $queue->addMessage($class, $params);
		EventManager::getInstance()->removeEventHandler('transformercontroller', 'onBeforeMessageAdd', 0);
		$this->assertEquals(new Result(), $addResult);
	}

	public function testAddWithEventFail()
	{
		$exchange = $this->getMockExchange();
		$mockQueue = $this->getMockQueue();
		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = array('param' => array('value'));

		$error = new Error('Some test error message', 'TEST_EVENT_CANCEL');
		EventManager::getInstance()->addEventHandler('transformercontroller', 'onBeforeMessageAdd', function(Event $event) use ($error)
		{
			return new EventResult(EventResult::ERROR, [$error]);
		});
		$expectedResult = new Result();
		$expectedResult->addError(clone ($error));
		$addResult = $queue->addMessage($class, $params);
		EventManager::getInstance()->removeEventHandler('transformercontroller', 'onBeforeMessageAdd', 0);
		$this->assertEquals($expectedResult, $addResult);
	}

	/**
	 * @covers Queue::addMessage()
	 * @covers Queue::getMessage()
	 */
	function testAddGetSequenceSuccess()
	{
		$connection = $this->getMockConnection(true);
		$exchange = $this->getMock('\AMQPExchange', array('getConnection', 'publish'), array(), '', false);
		$exchange->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
		$exchange->expects($this->any())->method('publish')->will($this->returnCallback(array($this, 'addToInternalQueue')));

		$mockQueue = $this->getMock('\AMQPQueue', array(), array(), '', false);
		$mockQueue->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'getFromInternalQueue')));

		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = array('param' => array('value'));
		$addResult = $queue->addMessage($class, $params);
		$this->assertEquals(new Result(), $addResult);

		$get = $queue->getMessage();

		$this->assertEquals(array('command' => $class, 'params' => $params, 'usageInfo' => array()), $get);
	}

	public function testAddWithEventGetSequenceSuccess()
	{
		$connection = $this->getMockConnection(true);
		$exchange = $this->getMock('\AMQPExchange', array('getConnection', 'publish'), array(), '', false);
		$exchange->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
		$exchange->expects($this->any())->method('publish')->will($this->returnCallback(array($this, 'addToInternalQueue')));

		$mockQueue = $this->getMock('\AMQPQueue', array(), array(), '', false);
		$mockQueue->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'getFromInternalQueue')));

		$queue = new Queue($exchange, $mockQueue, 'test');
		$class = 'SomeTestCommandClass';
		$params = array('param' => array('value'));
		$additionalEventParameters = [
			'someEventParam' => 'coolEventValue',
		];
		EventManager::getInstance()->addEventHandler('transformercontroller', 'onBeforeMessageAdd', function(Event $event) use ($additionalEventParameters)
		{
			return new EventResult(EventResult::SUCCESS, $additionalEventParameters);
		});
		$addResult = $queue->addMessage($class, $params);
		EventManager::getInstance()->removeEventHandler('transformercontroller', 'onBeforeMessageAdd', 0);
		$this->assertEquals(new Result(), $addResult);

		$get = $queue->getMessage();

		$this->assertEquals(array('command' => $class, 'params' => array_merge($params, $additionalEventParameters), 'usageInfo' => array()), $get);
	}

	/**
	 * @covers Queue::getMessage()
	 */
	function testGetFromEmptyQueue()
	{
		$connection = $this->getMockConnection(true);
		$exchange = $this->getMock('\AMQPExchange', array('getConnection', 'publish'), array(), '', false);
		$exchange->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
		$exchange->expects($this->any())->method('publish')->will($this->returnCallback(array($this, 'addToInternalQueue')));

		$mockQueue = $this->getMock('\AMQPQueue', array(), array(), '', false);
		$mockQueue->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'getFromInternalQueue')));

		$queue = new Queue($exchange, $mockQueue, 'test');
		$get = $queue->getMessage();
		$this->assertFalse($get);
	}

}