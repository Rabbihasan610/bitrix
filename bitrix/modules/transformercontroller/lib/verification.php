<?php

namespace Bitrix\TransformerController;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\MicroService\LicenseVerification;

class Verification
{
	protected const MODULE_ID = 'transformercontroller';
	protected const OPTION_DOMAINS = 'allowed_domains';

	protected $isCheckByLicenseCode;
	protected $isEnabled = true;

	public function __construct()
	{
		$this->isCheckByLicenseCode = $this->isCheckByLicenseCode();
	}

	public function isCheckByLicenseCode(): bool
	{
		return Loader::includeModule('microservice');
	}

	public function isCheckByDomain(): bool
	{
		return !$this->isCheckByLicenseCode();
	}

	public function setIsEnabled(bool $isEnabled): void
	{
		$this->isEnabled = $isEnabled;
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function check(array $request): Result
	{
		$result = new Result();

		if(!$this->isEnabled())
		{
			return $result;
		}

		if(!$this->isDomainInBackUrlTheSame($request))
		{
			return $result->addError(new Error(
				'Wrong host in back_url',
				'WRONG_BACK_URL'
			));
		}

		if($this->isCheckByLicenseCode)
		{
			$licenseVerification = new LicenseVerification();
			$resultVerify = $licenseVerification->verify($request);
			if(!$resultVerify->isSuccess())
			{
				return $resultVerify;
			}
			$clientInfo = $resultVerify->getData()['client'] ?? [];
			if(empty($clientInfo['LICENSE_KEY']))
			{
				$clientInfo['LICENSE_KEY'] = $clientInfo['URL'];
			}
			if(empty($clientInfo['TARIF']))
			{
				$result->addError(new Error(
					'Missing data about license',
					'RIGHT_CHECK_FAILED'
				));
			}

			$result->setData($clientInfo);

			return $result;
		}

		$backUri = new Uri($request['params']['back_url']);

		if(!$this->isDomainAllowed($backUri))
		{
			return $result->addError(new Error(
				'Domain is not allowed for this request',
				'RIGHT_CHECK_FAILED'
			));
		}

		// we do not check license for standalone editions
		$result->setData([
			'TARIF' => 'stub',
			'LICENSE_KEY' => 'stub',
		]);

		return $result;
	}

	public function getAllowedDomains(): array
	{
		$options = Option::get(static::MODULE_ID, static::OPTION_DOMAINS);
		if(empty($options))
		{
			return [];
		}
		try
		{
			$domains = Json::decode($options);
		}
		catch (ArgumentException $exception)
		{
			return [];
		}

		if(empty($domains) || !is_array($domains))
		{
			return [];
		}

		return $domains;
	}

	public function setAllowedDomains(array $domains): void
	{
		Option::set(static::MODULE_ID, static::OPTION_DOMAINS, Json::encode($domains));
	}

	public function isDomainAllowed(Uri $backUri): bool
	{
		$domain = $backUri->getHost();
		$domains = $this->getAllowedDomains();

		if(empty($domains))
		{
			return false;
		}

		return in_array($domain, $domains, true);
	}

	public function isDomainInBackUrlTheSame(array $request): bool
	{
		$backUri = new Uri($request['params']['back_url']);
		$domain = $backUri->getHost();
		$postUri = new Uri($request['BX_DOMAIN']);
		$postDomain = $postUri->getHost();

		return ($domain === $postDomain);
	}
}