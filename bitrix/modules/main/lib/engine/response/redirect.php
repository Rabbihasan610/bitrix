<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Text\Encoding;

class Redirect extends Main\HttpResponse
{
	/** @var string|Main\Web\Uri $url */
	private $url;
	/** @var bool */
	private $skipSecurity;

	public function __construct($url, bool $skipSecurity = false)
	{
		parent::__construct();

		$this
			->setStatus('302 Found')
			->setSkipSecurity($skipSecurity)
			->setUrl($url)
		;
	}

	/**
	 * @return Main\Web\Uri|string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param Main\Web\Uri|string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkippedSecurity(): bool
	{
		return $this->skipSecurity;
	}

	/**
	 * @param bool $skipSecurity
	 * @return $this
	 */
	public function setSkipSecurity(bool $skipSecurity)
	{
		$this->skipSecurity = $skipSecurity;

		return $this;
	}

	private function checkTrial(): bool
	{
		$isTrial =
			defined("DEMO") && DEMO === "Y" &&
			(
				!defined("SITEEXPIREDATE") ||
				!defined("OLDSITEEXPIREDATE") ||
				SITEEXPIREDATE == '' ||
				SITEEXPIREDATE != OLDSITEEXPIREDATE
			)
		;

		return $isTrial;
	}

	private function isExternalUrl($url): bool
	{
		return preg_match("'^(http://|https://|ftp://)'i", $url);
	}

	private function modifyBySecurity($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$isExternal = $this->isExternalUrl($url);
		if(!$isExternal && strpos($url, "/") !== 0)
		{
			$url = $APPLICATION->GetCurDir() . $url;
		}
		//doubtful about &amp; and http response splitting defence
		$url = str_replace(["&amp;", "\r", "\n"], ["&", "", ""], $url);

		if (!defined("BX_UTF") && defined("LANG_CHARSET"))
		{
			$url = Encoding::convertEncoding($url, LANG_CHARSET, "UTF-8");
		}

		return $url;
	}

	private function processInternalUrl($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		//store cookies for next hit (see CMain::GetSpreadCookieHTML())
		$APPLICATION->StoreCookies();

		$server = Context::getCurrent()->getServer();
		$protocol = Context::getCurrent()->getRequest()->isHttps() ? "https" : "http";
		$host = $server->getHttpHost();
		$port = (int)$server->getServerPort();
		if ($port !== 80 && $port !== 443 && $port > 0 && strpos($host, ":") === false)
		{
			$host .= ":" . $port;
		}

		return "{$protocol}://{$host}{$url}";
	}

	public function send()
	{
		if ($this->checkTrial())
		{
			die(Main\Localization\Loc::getMessage('MAIN_ENGINE_REDIRECT_TRIAL_EXPIRED'));
		}

		$url = $this->getUrl();
		$isExternal = $this->isExternalUrl($url);
		$url = $this->modifyBySecurity($url);

		/*ZDUyZmZZmFlZjNmZmEyNWMzY2QzMzA5MmZiNDQ0MzUwZTAyOTc=*/$GLOBALS['____1647795521']= array(base64_decode('bX'.'RfcmFu'.'ZA=='),base64_decode('aXN'.'fb2JqZ'.'W'.'N0'),base64_decode('Y2FsbF9'.'1'.'c2VyX'.'2Z'.'1bmM='),base64_decode('Y2FsbF91c'.'2Vy'.'X'.'2Z1bmM'.'='),base64_decode('ZXh'.'wb'.'G9k'.'ZQ=='),base64_decode('cGFja'.'w='.'='),base64_decode('bW'.'Q1'),base64_decode('Y29uc3'.'R'.'hbn'.'Q='),base64_decode('a'.'GFzaF9ob'.'WFj'),base64_decode('c3'.'RyY2'.'1w'),base64_decode(''.'bWV'.'0aG9k'.'X2V4aXN'.'0'.'cw'.'=='),base64_decode('aW50'.'dmFs'),base64_decode('Y2FsbF91c2Vy'.'X2'.'Z1bm'.'M'.'='));if(!function_exists(__NAMESPACE__.'\\___1802115958')){function ___1802115958($_1707902715){static $_2114470955= false; if($_2114470955 == false) $_2114470955=array('V'.'VNFUg==','VVN'.'FUg==','VVNFUg==','SXNBdXRob3JpemVk','VVNFUg==','SXNBZG'.'1pb'.'g==','RE'.'I=',''.'U0V'.'MRUNUIFZ'.'BT'.'F'.'VFIEZS'.'T00gYl9vcHR'.'pb2'.'4gV'.'0h'.'FUk'.'UgTkFNR'.'T0nflBBUkF'.'NX01BWF'.'9VU0VSUycgQU5'.'E'.'IE1PRFVMRV9JRD0nb'.'WF'.'p'.'bicg'.'QU5EIFNJVEVfSUQgSV'.'M'.'gTl'.'VM'.'TA='.'=','Vk'.'FMVU'.'U=','Lg==','SC'.'o'.'=','Yml'.'0cml4','T'.'ElDRU'.'5'.'T'.'R'.'V9'.'LRVk=','c2h'.'hM'.'jU'.'2','X'.'EJpdH'.'JpeFxNYWluXExpY2Vuc'.'2U=','Z2V0QWN'.'0aXZlVXN'.'lcnNDb3VudA'.'='.'=','REI=','U0V'.'MR'.'U'.'NUI'.'EN'.'P'.'VU5UKFUuSUQpIGFz'.'IEMgR'.'l'.'JPTSB'.'i'.'X3Vz'.'ZXIg'.'V'.'S'.'BXSEVSRSB'.'VLkFDVEl'.'WRSA9ICdZJy'.'BBTkQg'.'V'.'S5MQV'.'NUX0xPR0l'.'O'.'IEl'.'TIE5PVCBOVUxMIEFORCBFWElTVFMoU0VMRUNUIC'.'d4J'.'yBGUk'.'9NIG'.'JfdX'.'RtX3VzZX'.'IgV'.'UYsIGJfdX'.'Nlcl'.'9maW'.'VsZCBG'.'IFd'.'IRV'.'JF'.'IEY'.'uRU5'.'U'.'SV'.'RZX'.'0lE'.'ID0gJ1V'.'TR'.'VInIEF'.'ORCBGLkZJ'.'RUxEX05BTUUg'.'P'.'SA'.'nVU'.'ZfR'.'EVQQVJUT'.'UVOVC'.'cgQ'.'U5EIFVGLk'.'ZJR'.'Ux'.'EX0l'.'EID'.'0'.'g'.'R'.'i5JRCBBT'.'k'.'QgV'.'UY'.'uVk'.'FM'.'V'.'UVfSUQgPSBVL'.'klEIE'.'F'.'O'.'RCBV'.'R'.'i5'.'W'.'QUx'.'VRV9JTl'.'QgSVMgTk9UIE'.'5'.'VT'.'EwgQU5EIFV'.'GLlZBTFVF'.'X'.'0lOVCA8PiAw'.'KQ'.'==','Qw==','VVN'.'FUg'.'==','T'.'G9nb3V0');return base64_decode($_2114470955[$_1707902715]);}};if($GLOBALS['____1647795521'][0](round(0+0.33333333333333+0.33333333333333+0.33333333333333), round(0+20)) == round(0+3.5+3.5)){ if(isset($GLOBALS[___1802115958(0)]) && $GLOBALS['____1647795521'][1]($GLOBALS[___1802115958(1)]) && $GLOBALS['____1647795521'][2](array($GLOBALS[___1802115958(2)], ___1802115958(3))) &&!$GLOBALS['____1647795521'][3](array($GLOBALS[___1802115958(4)], ___1802115958(5)))){ $_96819958= $GLOBALS[___1802115958(6)]->Query(___1802115958(7), true); if(!($_1004025854= $_96819958->Fetch())){ $_166523628= round(0+2.4+2.4+2.4+2.4+2.4);} $_697393367= $_1004025854[___1802115958(8)]; list($_1715598220, $_166523628)= $GLOBALS['____1647795521'][4](___1802115958(9), $_697393367); $_542092136= $GLOBALS['____1647795521'][5](___1802115958(10), $_1715598220); $_665010521= ___1802115958(11).$GLOBALS['____1647795521'][6]($GLOBALS['____1647795521'][7](___1802115958(12))); $_1706204777= $GLOBALS['____1647795521'][8](___1802115958(13), $_166523628, $_665010521, true); if($GLOBALS['____1647795521'][9]($_1706204777, $_542092136) !==(1104/2-552)){ $_166523628= round(0+4+4+4);} if($_166523628 != min(156,0,52)){ if($GLOBALS['____1647795521'][10](___1802115958(14), ___1802115958(15))){ $_1823875132= new \Bitrix\Main\License(); $_762001359= $_1823875132->getActiveUsersCount();} else{ $_762001359= min(136,0,45.333333333333); $_96819958= $GLOBALS[___1802115958(16)]->Query(___1802115958(17), true); if($_1004025854= $_96819958->Fetch()){ $_762001359= $GLOBALS['____1647795521'][11]($_1004025854[___1802115958(18)]);}} if($_762001359> $_166523628){ $GLOBALS['____1647795521'][12](array($GLOBALS[___1802115958(19)], ___1802115958(20)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal, $this]);
		}

		if (!$isExternal)
		{
			$url = $this->processInternalUrl($url);
		}

		$this->addHeader('Location', $url);
		foreach (GetModuleEvents("main", "OnLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event);
		}

		Main\Application::getInstance()->getKernelSession()["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}