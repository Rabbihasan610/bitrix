<?

namespace Bitrix\TransformerController;

use Bitrix\Main\Web\Json;

class Log
{
	private static $forceDebug;

	public function __construct($forceDebug = false)
	{
		self::$forceDebug = $forceDebug;
	}

	public static function getPath($logName = 'transformer')
	{
		if(is_writable('/var/log/transformer/'))
		{
			return '/var/log/transformer/'.$logName.'.log';
		}
		else
		{
			return $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$logName.'controller.log';
		}
	}

	/**
	 * @return bool
	 */
	private static function getMode()
	{
		if(self::$forceDebug)
		{
			return true;
		}
		if(\Bitrix\Main\Config\Option::get('transformercontroller', 'debug'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param string|array $message Record to write.
	 * @return void
	 */
	public static function write($message)
	{
		if(self::getMode())
		{
			$data = [
				'time' => date('d.m.Y H:i:s'),
				'pid' => getmypid(),
			];
			if(is_array($message))
			{
				$data = array_merge($data, $message);
			}
			else
			{
				$data['message'] = $message;
			}
			@file_put_contents(self::getPath(), Json::encode($data).PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * clears log file.
	 * @return void
	 */
	public static function clear()
	{
		if(self::getMode())
		{
			@file_put_contents(self::getPath(), '');
		}
	}

}