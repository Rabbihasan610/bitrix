<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_transformercontroller_serviceLocator_codes',
		'transformercontroller.verification',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('transformercontroller.verification'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'transformercontroller.verification' => \Bitrix\TransformerController\Verification::class,
	]));
}