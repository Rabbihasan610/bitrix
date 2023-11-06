<?php

namespace Bitrix\TransformerController\Tests;

use Bitrix\TransformerController\Limits;

class LimitsTest extends \CBitrixTestCase
{
	public static function setUpBeforeClass()
	{
		\Bitrix\Main\Loader::includeModule('transformercontroller');
	}

	/**
	 * @return array
	 */
	public function getDomains()
	{
		return [
			'emptyArray' => [
				[],
				'bitrix.ru',
				false,
			],
			'emptyNull' => [
				null,
				'bitrix.ru',
				false,
			],
			'emptyString' => [
				'',
				'bitrix.ru',
				false,
			],
			'single' => [
				'bitrix.ru',
				'bitrix.ru',
				true,
			],
			'array' => [
				['bitrix.ru'],
				'bitrix.ru',
				true,
			],
			'fullArray' => [
				['bitrix.ru', 'asdfasdf.sdf', 'aghrtasdfasdf',],
				'bitrix.ru',
				true,
			],
			'fullArrayFail' => [
				['bitrix.ru', 'asdfasdf.sdf', 'aghrtasdfasdf',],
				'sdf.sdf',
				false,
			],
		];
	}

	/**
	 * @covers       \Bitrix\TransformerController\Limits::isDomainUnlimited()
	 * @dataProvider getDomains
	 * @param $unlimitedDomains
	 * @param $domain
	 * @param $result
	 */
	public function testIsDomainUnlimited($unlimitedDomains, $domain, $result)
	{
		$constName = 'BX_TC_LIMITS_TEXT_'.uniqid();
		define($constName, $unlimitedDomains);
		$this->assertEquals($result, Limits::isDomainUnlimited($domain, $constName));
	}
}