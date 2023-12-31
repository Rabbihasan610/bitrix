<?php

namespace Bitrix\Dav\Profile;

use Bitrix\Dav\Profile\Response\Base;
use Bitrix\Main\Context;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;

/**
 * Class RequestHandler
 * @package Bitrix\Dav\Profile
 */
class RequestHandler
{
	protected static $validActions = ['payload', 'token'];

	public $user;
	protected $actionName;
	protected $request;

	private $responseHandler;

	/**
	 * RequestHandler constructor.
	 */
	public function __construct()
	{
		global $USER;
		$request = Context::getCurrent()->getRequest();
		$this->user = $USER;
		$action = $request->get('action');
		$this->actionName = $action ?? '';
		if (in_array($this->actionName, static::$validActions, true))
		{
			switch ($this->actionName)
			{
				case 'payload':
					$this->setHandler(new Response\Payload\Base($request));
					break;
				case 'token':
					$this->setHandler(new Response\Token());
					break;
			}
		}
	}

	/**
	 * @return true
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 *
	 * Write response (some error, token, or payload file).
	 */
	public function process()
	{
		$response = new HttpResponse();
		if (!$this->getHandler() instanceof Base)
		{
			$response->setStatus(404);
			$response->addHeader('Content-Type', 'application/json');
			$body = Json::encode(['error_messages' => [$this->getNotAvailableActionErrorMessage()]]);
		}
		else
		{
			foreach ($this->getHandler()->getHeaders() as $name => $value)
			{
				$response->addHeader($name, $value);
			}

			if ($this->getHandler()->getStatus())
			{
				$response->setStatus($this->getHandler()->getStatus());
			}

			$body = $this->getHandler()->getBody();
		}

		$response->flush($body);
		return true;
	}

	/**
	 * @param Response\Base $responseHandler Handler of response for current request.
	 * @return void
	 */
	public function setHandler(Response\Base $responseHandler)
	{
		$this->responseHandler = $responseHandler;
	}

	/**
	 * @return Response\Base
	 */
	public function getHandler()
	{
		return $this->responseHandler;
	}

	/**
	 * @return string
	 */
	private function getNotAvailableActionErrorMessage()
	{
		return $this->actionName ? 'action parameter "' . $this->actionName . '" is not available' : 'action parameter is required';
	}

}