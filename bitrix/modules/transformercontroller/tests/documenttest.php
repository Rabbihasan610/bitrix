<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\Result;
use Bitrix\TransformerController\Document;
use Bitrix\TransformerController\FileUploader;
use Bitrix\TransformerController\TimeStatistic;

class DocumentTest extends \CBitrixTestCase
{
	const MOCK_PDF_RESULT = '/bitrix/modules/transformercontroller/test_result_file.pdf';
	const MOCK_DOC_FILE = '/bitrix/modules/transformercontroller/test_doc_file.doc';

	public $runLog = array();

	public function addToRunLog ($command)
	{
		$this->runLog[] = $command;
		return array('convert /path/to/test/file.doc -> '.$_SERVER['DOCUMENT_ROOT'].self::MOCK_PDF_RESULT.' using filter : draw_pdf_Export');
	}

	function setUp()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
	}

	function paramsProvider ()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
		return [
			'pure success' => [
				[
					'file' => 'file',
					'back_url' => 'back_url',
					'formats' => ['pdf', 'jpg', 'txt', 'text']
				],
				false
			],
			'more params success' => [
				[
					'file' => 'file',
					'back_url' => 'back_url',
					'formats' => ['pdf', 'jpg', 'txt', 'text'],
					'param_test' => ['1', '2'],
					'param_more' => 'value',
				],
				false
			],
			'No back url' => [
				[
					'file' => 'file',
					'formats' => ['pdf', 'jpg', 'txt', 'text']
				],
				[
					new Error('required param back_url is not specified', TimeStatistic::ERROR_CODE_COMMAND_ERROR)
				]
			],
			'No back url and file' => [
				[
					'formats' => ['pdf', 'jpg', 'txt', 'text']
				],
				[
					new Error('required param back_url is not specified', TimeStatistic::ERROR_CODE_COMMAND_ERROR),
					new Error('required param file is not specified', TimeStatistic::ERROR_CODE_COMMAND_ERROR)
				]
			],
			'Wrong format test' => [
				[
					'file' => 'file',
					'back_url' => 'back_url',
					'formats' => ['test']
				],
				[
					new Error('value test is not allowed in formats', TimeStatistic::ERROR_CODE_COMMAND_ERROR)
				]
			]
		];
	}

	/**
	 * @covers Document::validate()
	 * @dataProvider paramsProvider
	 */
	function testValidation ($params, $errors)
	{
		$this->assertEquals($errors, Document::validate($params));
	}

	function testExecutePdfSuccess ()
	{
		$runner = $this->getMockBuilder('\Bitrix\TransformerController\Runner\Runner')->setMethods(['run'])->getMockForAbstractClass();
		$runner->expects($this->any())->method('run')->will($this->returnCallback([$this, 'addToRunLog']));

		$mockDocPath = self::MOCK_DOC_FILE;
		$mockDocFullPath = $_SERVER['DOCUMENT_ROOT'].$mockDocPath;
		$downloadResult = new Result();
		$downloadResult->setData(['file' => $mockDocFullPath]);

		$mockDocFile = new File($mockDocFullPath);
		$mockDocFile->putContents('123');

		$mockResultFile = new File($_SERVER['DOCUMENT_ROOT'].self::MOCK_PDF_RESULT);
		$mockResultFile->putContents('234');

		$fileUploader = $this->getMockBuilder('\Bitrix\TransformerController\FileUploader')->setMethods(['downloadFile'])->getMock();
		$fileUploader->expects($this->any())->method('downloadFile')->will($this->returnValue($downloadResult));

		$params = ['back_url' => 'test_url', 'formats' => ['pdf'], 'file' => 'some_url'];

		$expectedResult = new Result();
		$expectedResult->setData(['files' => ['pdf' => $_SERVER['DOCUMENT_ROOT'].self::MOCK_PDF_RESULT]]);

		$document = new Document($params, $runner, $fileUploader);
		$result = $document->execute();
		$this->assertTrue($result->isSuccess());
		$this->assertEquals($expectedResult, $result);

		$mockDocFile->delete();
		$mockResultFile->delete();
	}

	function testExecuteMd5Success ()
	{
		$runner = $this->getMockBuilder('\Bitrix\TransformerController\Runner\Runner')->setMethods(array('run'))->getMockForAbstractClass();
		$runner->expects($this->any())->method('run')->will($this->returnCallback(array($this, 'addToRunLog')));

		$file = new File($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/transformercontrollertestfile');
		if ($file->putContents('some test data to calc md5'))
		{
			$downloadResult = new Result();
			$downloadResult->setData(array('file' => $file->getPath()));

			$fileUploader = $this->getMockBuilder('\Bitrix\TransformerController\FileUploader')->setMethods(array('downloadFile'))->getMock();
			$fileUploader->expects($this->any())->method('downloadFile')->will($this->returnValue($downloadResult));

			$params = array('back_url' => 'test_url', 'formats' => array('md5'), 'file' => 'some_url');

			$expectedResult = new Result();
			$expectedResult->setData(array('md5' => md5_file($file->getPath()), 'files' => array()));

			$document = new Document($params, $runner, $fileUploader);
			$result = $document->execute();
			$this->assertTrue($result->isSuccess());
			$this->assertEquals($expectedResult, $result);

			$file->delete();
		}
	}

	function testExecuteCantDownloadFile ()
	{
		$runner = $this->getMockBuilder('\Bitrix\TransformerController\Runner\Runner')->setMethods(array('run'))->getMock();
		$runner->expects($this->any())->method('run')->will($this->returnCallback(array($this, 'addToRunLog')));

		$fileUploader = new FileUploader();

		$params = array('back_url' => 'test_url', 'formats' => 'pdf', 'file' => 'bad_url');

		$downloadResult = new Result();
		$downloadResult->addError(new Error('Wrong http-status 0 before download from '.$params['file'], TimeStatistic::ERROR_CODE_WRONG_STATUS_BEFORE_DOWNLOAD));

		$document = new Document($params, $runner, $fileUploader);
		$result = $document->execute();
		$this->assertEquals($downloadResult, $result);
	}

}