<?php

namespace Bitrix\TransformerController\Runner;

class SystemRunner extends Runner
{
	/**
	 * Call exec and return the result on success, false on failure.
	 *
	 * @param string $command Command to execute.
	 * @return bool|array
	 */
	public function run($command)
	{
		$result = false;
		exec($command, $result);
		return $result;
	}
}