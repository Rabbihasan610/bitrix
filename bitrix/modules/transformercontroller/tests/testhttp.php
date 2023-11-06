<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\Main\Web\HttpClient;

class TestHttp extends HttpClient
{
	public static $url;
	public static $log;

	public function post($url, $post = array(), $entityBody = null)
	{
		self::$url = $url;
		self::$log = $post;
		return array('success' => 'OK');
	}
}