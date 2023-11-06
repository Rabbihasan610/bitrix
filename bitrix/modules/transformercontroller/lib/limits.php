<?

namespace Bitrix\TransformerController;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\TransformerController\Entity\LimitsTable;
use Bitrix\TransformerController\Entity\UsageStatisticTable;

/**
 * Limits for transformercontroller usage.
 */

class Limits
{
    public const ERROR_CODE_COMMANDS_LIMIT = 'BX_TC_LIMIT_COMMANDS';
    public const ERROR_CODE_FILE_SIZE_LIMIT = 'BX_TC_LIMIT_FILE_SIZE';

	protected $tarif;
	protected $commandName;
	protected $domain;
	protected $licenseKey;
	protected $queueId;
	protected $type;

	/** @var DataManager */
	protected $tableClassName;

	public function __construct($data = array(), $tableClassName = '')
	{
		$map = self::getMap();
		foreach($map as $name => $attribute)
		{
			if(property_exists($this, $attribute))
			{
				$value = null;
				if(isset($data[$name]))
				{
					$value = $data[$name];
				}
				elseif(isset($data[$attribute]))
				{
					$value = $data[$attribute];
				}
				if($value)
				{
					$this->$attribute = $value;
				}
			}
		}
		if(is_a($tableClassName, DataManager::class, true))
        {
            $this->tableClassName = $tableClassName;
        }
		else
        {
            $this->tableClassName = LimitsTable::class;
        }
	}

	/**
	 * @return Result
	 */
	public function check()
	{
		$result = new Result();
		$limits = $this->getList();
		foreach($limits as $limit)
		{
			// skip limits without limits
			if($limit['COMMANDS_COUNT'] !== "0" && $limit['FILE_SIZE'] !== "0" && empty($limit['COMMANDS_COUNT']) && empty($limit['FILE_SIZE']))
			{
				continue;
			}
			$usage = $this->getUsage($limit['PERIOD']);
			if(isset($limit['COMMANDS_COUNT']) && $usage['count'] > $limit['COMMANDS_COUNT'])
			{
				$result->addError(new Error('Limit on command quantity is exceeded', static::ERROR_CODE_COMMANDS_LIMIT));
				break;
			}
			if(isset($limit['FILE_SIZE']) && $usage['fileSize'] > $limit['FILE_SIZE'])
			{
				$result->addError(new Error('Limit on file size is exceeded', static::ERROR_CODE_FILE_SIZE_LIMIT));
				break;
			}
		}

		return $result;
	}

	/**
	 * If $strict true - strict filter with fields. If false - get all limits where fields are empty
	 *
	 * @param bool $strict.
	 * @return array
	 */
	public function getList($strict = false)
	{
	    $result = [];

	    // get all limits and filter them manually
	    $limits = $this->tableClassName::getList()->fetchAll();

        $map = self::getMap();
        foreach($limits as $limit)
        {
            foreach($map as $name => $attribute)
            {
                if(!property_exists($this, $attribute))
                {
                    continue;
                }
                if(!(
                    ($strict &&
                        (!empty($this->$attribute) && !empty($limit[$name]) && $this->$attribute === $limit[$name]) ||
                        (empty($this->$attribute) && empty($limit[$name]))
                    ) ||
                    (!$strict &&
                        (empty($limit[$name])) ||
                        (!empty($limit[$name]) && $this->$attribute == $limit[$name])
                    )
                ))
                {
                    continue 2;
                }
            }

            $result[] = $limit;
        }

        return $result;
	}

	/**
	 * @param int $period
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getUsage($period = 0)
	{
		$filter = array();
		if($period > 0)
		{
			$filter['>DATE'] = DateTime::createFromTimestamp(time() - $period);
		}
		if($this->licenseKey && $this->domain)
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'=LICENSE_KEY' => $this->licenseKey,
				'=DOMAIN' => $this->domain,
			);
		}
		elseif($this->licenseKey)
		{
			$filter['=LICENSE_KEY'] = $this->licenseKey;
		}
		elseif($this->domain)
		{
			$filter['=DOMAIN'] = $this->domain;
		}
		if($this->commandName)
		{
			$filter['=COMMAND_NAME'] = $this->commandName;
		}
		if($this->queueId)
		{
			$filter['=QUEUE_ID'] = $this->queueId;
		}
		$usage = UsageStatisticTable::getList(array(
			'select' => array(
				new ExpressionField('fileSize', 'SUM(%s)', array('FILE_SIZE')),
				new ExpressionField('count', 'COUNT(*)'),
			),
			'filter' => $filter,
		))->fetch();
		return $usage;
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'TARIF' => 'tarif',
			'COMMAND_NAME' => 'commandName',
			'DOMAIN' => 'domain',
			'LICENSE_KEY' => 'licenseKey',
			'COMMANDS_COUNT' => 'count',
			'FILE_SIZE' => 'fileSize',
			'PERIOD' => 'period',
			'QUEUE_ID' => 'queueId',
			'TYPE' => 'type',
		];
	}

	/**
	 * Returns true if $domain has no limits on this server.
	 *
	 * @param string $domain
	 * @param string $constantName
	 * @return bool
	 */
	public static function isDomainUnlimited($domain, $constantName = 'BX_TC_UNLIMITED_DOMAINS')
	{
		if(is_string($domain) && !empty($domain) && defined($constantName))
		{
			$unlimitedDomains = constant($constantName);

			if(!is_array($unlimitedDomains))
			{
				$unlimitedDomains = [$unlimitedDomains];
			}

			foreach($unlimitedDomains as $unlimitedDomain)
			{
				if($domain == $unlimitedDomain)
				{
					return true;
				}
			}
		}

		return false;
	}
}