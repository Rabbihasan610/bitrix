<?php

namespace Bitrix\TransformerController;

use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\TransformerController\Runner\Runner;

class Document extends BaseCommand
{
	public const DIRECTORY = 'documents';
	public const DEFAULT_PATH = 'libreoffice';

	protected $file;
	protected $convert = '#LIBREOFFICE_PATH# #ENVDIR# --convert-to #FORMAT# --outdir "#WORK_DIR#" "#FILE#" --headless --display :0';
	protected $transformTypes = array(
		'basic' => array('pdf', 'txt', 'csv'),
		'files' => array('jpg'),
	);
	protected $fileUploader;

	/**
	 * Return array to validate parameters of this class.
	 *
	 * @return array
	 */
	protected static function getRequiredParams()
	{
		$requiredParams = parent::getRequiredParams();
		return array_merge($requiredParams,
			array(
				'file',
				'formats' => array('pdf', 'jpg', 'txt', 'text', 'md5', 'sha1', 'crc32')
			)
		);
	}

	/**
	 * Document constructor.
	 * @param array $params Parameters of this document.
	 * @param Runner $runner Object to execute commands.
	 * @param FileUploader $fileUploader Object to work with files.
	 */
	public function __construct($params, Runner $runner, FileUploader $fileUploader)
	{
		parent::__construct($params, $runner);
		$this->file = $params['file'];
		$this->fileUploader = $fileUploader;
	}

	/**
	 * Parse parameters and perform particular operations on the file.
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function execute()
	{
		$downloadResult = $this->fileUploader->downloadFile(self::DIRECTORY, $this->file);
		if($downloadResult->isSuccess())
		{
			$downloadData = $downloadResult->getData();
			$this->file = $downloadData['file'];
			$this->fileUploader->setFiles($this->file);
			if(!$this->result->isSuccess())
			{
				$this->fileUploader->deleteFiles();
				return $this->result;
			}
			$fileResult = array();
			$formats = $this->params['formats'];
			// first basic transform
			foreach($formats as $key => $format)
			{
				if(!in_array($format, $this->transformTypes['basic']))
				{
					continue;
				}

				$fileResult[$format] = $this->transform($format);
				unset($formats[$key]);
			}
			$this->addResult('files', $fileResult);

			foreach($formats as $key => $format)
			{
				if(!in_array($format, $this->transformTypes['files']))
				{
					continue;
				}

				if(method_exists($this, $format))
				{
					$fileResult[$format] = $this->$format();
				}
				unset($formats[$key]);
			}
			$this->addResult('files', $fileResult);
			foreach($formats as $format)
			{
				if(method_exists($this, $format))
				{
					$methodResult = $this->$format($this->file);
					$this->addResult($format, $methodResult);
				}
			}
		}
		else
		{
			$this->result = $downloadResult;
		}
		if(!$this->checkFinalResults())
		{
			$directory = $this->getDirectoryForSavingFilesOnError();
			if($directory)
			{
				$path = Path::combine($directory, randString());
				Log::write([
					'type' => 'document_transform',
					'path' => $path,
					'message' => 'save document on error to '.$path,
				]);
				@copy($this->file, $path);
			}
		}
		$this->fileUploader->deleteFiles();
		return $this->result;
	}

	/**
	 * Make basic transformation through libreoffice.
	 *
	 * @param string $format Method of this class to call, or extension of the file to call in libreoffice.
	 * @param string $inputFile Full path to the file to transform.
	 * @return bool
	 */
	protected function transform($format, $inputFile = '')
	{
		if (empty($inputFile))
		{
			$inputFile = $this->file;
		}

		$envDir = '-env:UserInstallation=file://' . static::getLibreOfficeConfigUserPath();
		$libreofficePath = static::getLibreOfficePath();

		$workDir = Path::getDirectory($inputFile);

		$command = str_replace(
			['#LIBREOFFICE_PATH#', '#ENVDIR#', '#FORMAT#', '#FILE#', '#WORK_DIR#'],
			[$libreofficePath, $envDir, $format, $inputFile, $workDir],
			$this->convert
		);
		$resultExec = $this->exec($command);
		if($resultExec !== false)
		{
			$fileCandidate = $this->findFilename($resultExec, $format);
			if($fileCandidate)
			{
				$absolutePath = \Bitrix\Main\IO\Path::convertSiteRelativeToAbsolute($fileCandidate);
				if (File::isFileExists($fileCandidate))
				{
					$this->fileUploader->addToDeleteFiles($this->getPossibleLockFileName($fileCandidate));
					return $fileCandidate;
				}
				if (File::isFileExists($absolutePath))
				{
					$this->fileUploader->addToDeleteFiles($this->getPossibleLockFileName($absolutePath));
					return $absolutePath;
				}

				Log::write([
					'type' => 'document_transform',
					'format' => $format,
					'command' => $command,
					'file' => $fileCandidate,
					'absolutePath' => $absolutePath,
					'message' => 'file found but does not exist',
					'resultExec' => $resultExec,
				]);
			}
			else
			{
				// we do not addError here or all the results won't be send
				Log::write([
					'type' => 'document_transform',
					'format' => $format,
					'command' => $command,
					'message' => 'cant find '.$format.' in result '.print_r($resultExec, 1)
				]);
			}
		}
		else
		{
			// we do not addError here or all the results won't be send
			Log::write([
				'type' => 'document_transform',
				'command' => $command,
				'message' => 'cant exec '.$command,
			]);
		}
		return false;
	}

	/**
	 * Check whether document is a spreadsheet.
	 *
	 * @return bool
	 */
	protected function isSpreadsheet()
	{
		$mimeTypes = array(
			'application/octet-stream', // .xlsx
			'application/vnd.oasis.opendocument.spreadsheet', // .ods
			'application/vnd.ms-office', // .xls
			'application/vnd.oasis.opendocument.spreadsheet-template', // .ots
			'application/xml', // .uos and others
		);
		$fileInfo = $this->exec('file -b --mime-type "'.$this->file.'"');
		foreach($fileInfo as $output)
		{
			if(in_array(trim($output), $mimeTypes))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Find result filename in libreoffice output.
	 *
	 * @param array $data Result of exec function.
	 * @param string $format Extension we are looking for.
	 * @return bool
	 */
	protected function findFilename($data, $format)
	{
		foreach($data as $output)
		{
			if(preg_match('#(\/[ _a-zA-Z0-9\.\/]+'.$format.')#U', $output, $matches))
			{
				return $matches[0];
			}
		}

		return false;
	}

	/**
	 * Get preview image from the document.
	 * If we have pdf - get image from it.
	 * If file is a spreadsheet we should get pdf first.
	 * If file is not a spreadsheet we can get image right from it.
	 *
	 * @return bool|string
	 */
	protected function jpg()
	{
		$deleteAfter = false;
		$jpg = null;
		$pdfFile = $this->file;
		if($this->isSpreadsheet())
		{
			$result = $this->result->getData();
			if($result['files']['pdf'])
			{
				$pdfFile = $result['files']['pdf'];
			}
			else
			{
				$pdfFile = $this->transform('pdf');
				$deleteAfter = true;
			}
		}

		if($pdfFile)
		{
			$jpg = $this->transform('jpg', $pdfFile);
			if($deleteAfter)
			{
				File::deleteFile($pdfFile);
			}
		}

		return $jpg;
	}

	/**
	 * Get raw text from the document.
	 *
	 * @return bool|string
	 */
	protected function text()
	{
		$deleteAfter = false;
		$text = null;
		$result = $this->result->getData();
		if($result['files']['txt'])
		{
			$txtFile = $result['files']['txt'];
		}
		else
		{
			$txtFile = $this->transform('txt');
			$deleteAfter = true;
		}
		if($txtFile)
		{
			$text = File::getFileContents($txtFile);
			if($deleteAfter)
			{
				File::deleteFile($txtFile);
			}
		}
		return $text;
	}

	/**
	 * Returns path to libreoffice file
	 *
	 * @return string
	 */
	public static function getLibreOfficePath()
	{
		if(defined('BX_TC_SOFFICE_PATH'))
		{
			return BX_TC_SOFFICE_PATH;
		}

		return Option::get('transformercontroller', 'libreoffice_path', static::DEFAULT_PATH);
	}

	public static function getLibreOfficeConfigUserPath($pid = null)
	{
		if(!$pid)
		{
			$pid = getmypid();
		}

		$path = sys_get_temp_dir();

		if(defined('BX_TC_SOFFICE_CONFIG_USER_PATH'))
		{
			$path = BX_TC_SOFFICE_CONFIG_USER_PATH;
		}

		return Path::combine($path, 'libreoffice-'.$pid);
	}

	protected function getPossibleLockFileName($filePath)
	{
		if(!empty($filePath))
		{
			$file = new File($filePath);
			$fileName = $file->getName();
			return $file->getDirectoryName().DIRECTORY_SEPARATOR.'.~lock.'.$fileName.'#';
		}

		return false;
	}

	/**
	 * @return false|string
	 */
	protected function getDirectoryForSavingFilesOnError()
	{
		if(defined('BX_TC_DIRECTORY_FOR_SAVING_DOCUMENTS_ON_ERROR'))
		{
			$directory = BX_TC_DIRECTORY_FOR_SAVING_DOCUMENTS_ON_ERROR;
			if(is_string($directory) && !empty($directory) && Directory::isDirectoryExists($directory))
			{
				return $directory;
			}
		}

		return false;
	}
}