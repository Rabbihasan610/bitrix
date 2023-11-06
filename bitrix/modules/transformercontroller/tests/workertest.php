<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\TransformerController\TimeStatistic;
use Bitrix\TransformerController\Worker;

class WorkerTest extends \CBitrixTestCase
{
	private $finishPost = array();

	function setUp()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
		include_once ('testcommand.php');
		include_once ('testhttp.php');
	}

	private function getMockQueue()
	{
		$queue = $this->getMockBuilder('\Bitrix\TransformerController\Queue')->disableOriginalConstructor()->getMock();
		return $queue;
	}

	private function getMockFileUploader()
	{
		$fileUploader = $this->getMockBuilder('\Bitrix\TransformerController\FileUploader')->getMock();
		return $fileUploader;
	}

	public function setFinishPost($url, $post)
	{
		$this->finishPost = array ('back_url' => $url, 'post' => $post);
		return array('success' => 'OK');
	}

	private function getMockHttpClient()
	{
		$httpClient = $this->getMockBuilder('\Bitrix\Main\Web\HttpClient')->getMock();
		$httpClient->expects($this->any())->method('post')->will($this->returnCallback(array($this, 'setFinishPost')));
		return $httpClient;
	}

	function testConstructSuccess()
	{
		$queue = $this->getMockQueue();
		$fileUploader = $this->getMockFileUploader();
		$httpClient = $this->getMockHttpClient();
		$worker = new Worker($queue, $fileUploader, $httpClient);
	}

	function testProcessCommandSuccess()
	{
		$queue = $this->getMockQueue();
		$fileUploader = $this->getMockFileUploader();
		$fileUploader->expects(($this->any()))->method('uploadFiles')->will($this->returnValue(new Result()));

		$httpClass = 'Bitrix\TransformerController\Tests\TestHttp';

		$worker = new Worker($queue, $fileUploader, $httpClass, time() + 100);

		$className = TestCommand::getClassName();

		$mockResult = 'test success';

		$params = array ('back_url' => 'test_url', 'mock_result' => $mockResult);

		$usageInfo = array(
			'DOMAIN' => 'test.domain'
		);

		$worker->processCommand($className, $params, $usageInfo);

		$this->assertEquals(
			array('back_url' => $params['back_url'], 'post' => array ('finish' => 'y', 'result' => array('result' => $mockResult))),
			array('back_url' => $httpClass::$url, 'post' => $httpClass::$log)
		);
	}

	function testProcessCommandFail()
	{
		$queue = $this->getMockQueue();
		$fileUploader = $this->getMockFileUploader();

		$httpClass = 'Bitrix\TransformerController\Tests\TestHttp';

		$worker = new Worker($queue, $fileUploader, $httpClass, time() + 100);

		$className = TestCommand::getClassName();

		$mockError = 'test success';

		$params = array ('back_url' => 'test_url', 'mock_error' => $mockError);

		$usageInfo = array(
			'DOMAIN' => 'test.domain'
		);

		$worker->processCommand($className, $params, $usageInfo);

		$this->assertEquals(
			array('back_url' => $params['back_url'], 'post' => array ('finish' => 'y', 'error' => $mockError, 'errorCode' => 0)),
			array('back_url' => $httpClass::$url, 'post' => $httpClass::$log)
		);
	}

	function testProcessCommandNotFound()
	{
		$queue = $this->getMockQueue();
		$fileUploader = $this->getMockFileUploader();

		$httpClass = 'Bitrix\TransformerController\Tests\TestHttp';

		$worker = new Worker($queue, $fileUploader, $httpClass, time() + 100);

		$className = 'SomeFoulCommand';

		$params = array ('back_url' => 'test_url');

		$usageInfo = array(
			'DOMAIN' => 'test.domain'
		);

		$worker->processCommand($className, $params, $usageInfo);

		$this->assertEquals(
			array('back_url' => $params['back_url'], 'post' => array ('finish' => 'y', 'error' => 'command '.$className.' not found', 'errorCode' => TimeStatistic::ERROR_CODE_COMMAND_NOT_FOUND)),
			array('back_url' => $httpClass::$url, 'post' => $httpClass::$log)
		);
	}

	function testProcessCommandFailUpload()
	{
		$queue = $this->getMockQueue();
		$uploadResult = new Result();
		$errorMessage = 'Some upload error';
		$uploadResult->addError(new Error($errorMessage));
		$fileUploader = $this->getMockFileUploader();
		$fileUploader->expects($this->any())->method('uploadFiles')->will($this->returnValue($uploadResult));

		$httpClass = 'Bitrix\TransformerController\Tests\TestHttp';

		$worker = new Worker($queue, $fileUploader, $httpClass, time() + 100);

		$className = TestCommand::getClassName();

		$params = ['back_url' => 'test_url'];

		$usageInfo = [
			'DOMAIN' => 'test.domain'
		];

		$worker->processCommand($className, $params, $usageInfo);

		$this->assertEquals(
			['back_url' => $params['back_url'], 'post' => ['result' => ['files' => []], 'finish' => 'y', 'error' => $errorMessage, 'errorCode' => TimeStatistic::ERROR_CODE_UPLOAD_FILES]],
			['back_url' => $httpClass::$url, 'post' => $httpClass::$log]
		);
	}

}
