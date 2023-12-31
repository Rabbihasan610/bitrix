<?php
namespace Bitrix\ImConnector;
/**
 * Class for converting of a copy of class \Bitrix\ImConnector\Result in an array, for transfer on the server and vice versa.
 * @package Bitrix\ImConnector
 */
class Converter
{
	const ERROR_EMPTY_SERVER_RESPONSE = "EMPTY_SERVER_RESPONSE";

	/**
	 * Converts class \Bitrix\ImConnector\Result copy in an array.
	 *
	 * @param Result $object the data object.
	 * @return mixed
	 */
	public static function convertObjectArray(Result $object)
	{
		$result = [
			'OK' => $object ->isSuccess(),
			'DATA' => $object->getData(),
		];
		if (!$object->isSuccess())
		{
			$result['ERROR'] = [];
			foreach ($object->getErrors() as $error)
			{
				if (
					$error instanceof Error
					|| $error instanceof \Exception
				)
				{
					$result['ERROR'][] = [
						'CODE' => $error->getCode(),
						//'MESSAGE' => $error->getMessage()
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Converts an array in class \Bitrix\ImConnector\Result copy.
	 *
	 * @param array $array the data array.
	 * @return Result.
	 */
	public static function convertArrayObject(array $array)
	{
		$result = new Result();

		if (!empty($array['DATA']) && is_array($array['DATA']))
		{
			$result ->setData($array['DATA']);
		}

		if (empty($array['OK']) || $array['OK'] == '0')
		{
			if (is_array($array['ERROR']))
			{
				foreach ($array['ERROR'] as $error)
				{
					$result->addError(new Error(
						$error['MESSAGE'] ?? '',
						$error['CODE'] ?? 'ERROR_UNDEFINED',
						$error['METHOD'] ?? '',
						$error['PARAMS'] ?? []
					));
				}
			}
			else
			{
				$result->addError(new Error(
					'Empty server response',
					self::ERROR_EMPTY_SERVER_RESPONSE,
					__METHOD__,
					$array['ERROR']
				));
			}
		}

		return $result;
	}

	/**
	 * Recursive replacement of all empty values by a stub.
	 *
	 * @param $data
	 * @return array|string
	 */
	public static function convertStubInEmpty($data)
	{
		if(Library::isEmpty($data))
		{
			if(is_array($data))
			{
				$data = '#EMPTY_ARRAY#';
			}
			else
			{
				$data = '#EMPTY#';
			}
		}
		elseif(is_array($data))
		{
			foreach ($data as $key => $value)
			{
				$data[$key] = self::convertStubInEmpty($value);
			}
		}

		return $data;
	}

	/**
	 * Recursive replacement of a stub by blank line.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function convertEmptyInStub($data)
	{
		if($data === '#EMPTY#')
		{
			$data = '';
		}
		elseif($data === '#EMPTY_ARRAY#')
		{
			$data = [];
		}
		elseif(is_array($data))
		{
			foreach ($data as $key => $value)
			{
				$data[$key] = self::convertEmptyInStub($value);
			}
		}

		return $data;
	}
}
