<?php
if(!$USER->IsAdmin())
	return;

use Bitrix\TransformerController\Settings;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/transformercontroller/options.php');

$moduleName = 'transformercontroller';
CModule::IncludeModule($moduleName);

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "common", "TAB" => GetMessage("TRANSFORMERCONTROLLER_TAB_SETTINGS"), "TITLE" => GetMessage("TRANSFORMERCONTROLLER_TAB_TITLE_SETTINGS_2"),
	),
	array (
		"DIV" => "rabbit", "TAB" => GetMessage("TRANSFORMERCONTROLLER_TAB_RABBIT_SETTINGS"), "TITLE" => GetMessage("TRANSFORMERCONTROLLER_TAB_RABBIT_TITLE_SETTINGS_2"),
	),
	array (
		"DIV" => "queues", "TAB" => GetMessage("TRANSFORMERCONTROLLER_TAB_QUEUES_SETTINGS"), "TITLE" => GetMessage("TRANSFORMERCONTROLLER_TAB_QUEUES_SETTINGS"),
	),
	array (
		"DIV" => "status", "TAB" => GetMessage("TRANSFORMERCONTROLLER_TAB_STATUS_SETTINGS"), "TITLE" => GetMessage("TRANSFORMERCONTROLLER_TAB_STATUS_TITLE_SETTINGS"),
	)
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$verification = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('transformercontroller.verification');

if($_SERVER['REQUEST_METHOD'] === "POST" && check_bitrix_sessid())
{
    if($_POST['Update'] <> '')
    {
        if ($_POST['CRON_TIME'] < 1)
            $_POST['CRON_TIME'] = 1;

        if ($_POST['CRON_TIME'] > 59)
            $_POST['CRON_TIME'] = 59;

        $domains = explode(',', $_POST['DOMAINS'] ?? '');
		$verification->setAllowedDomains($domains);
        \Bitrix\Main\Config\Option::set($moduleName, "debug", isset($_POST['DEBUG_MODE']));
        \Bitrix\Main\Config\Option::set($moduleName, "login", $_POST['QUEUE_LOGIN']);
        \Bitrix\Main\Config\Option::set($moduleName, "password", $_POST['QUEUE_PASSWORD']);
        \Bitrix\Main\Config\Option::set($moduleName, "host", $_POST['QUEUE_HOST']);
        \Bitrix\Main\Config\Option::set($moduleName, "port", $_POST['QUEUE_PORT']);
        \Bitrix\Main\Config\Option::set($moduleName, "vhost", $_POST['QUEUE_VHOST']);
        \Bitrix\Main\Config\Option::set($moduleName, "processes", $_POST['PROCESSES']);
        \Bitrix\Main\Config\Option::set($moduleName, "lifetime_from", $_POST['LIFETIME_FROM']);
        \Bitrix\Main\Config\Option::set($moduleName, "lifetime_to", $_POST['LIFETIME_TO']);
        \Bitrix\Main\Config\Option::set($moduleName, "cron_time", $_POST['CRON_TIME']);
        \Bitrix\Main\Config\Option::set($moduleName, "connection_time", $_POST['CONNECTION_TIME']);
        \Bitrix\Main\Config\Option::set($moduleName, "stream_time", $_POST['STREAM_TIME']);

        $cronSettings = array('processes' => $_POST['PROCESSES'], 'cron_time' => $_POST['CRON_TIME']);
        $queues = \Bitrix\TransformerController\Entity\QueueTable::getList()->fetchAll();
        foreach($queues as $queue)
        {
            $cronSettings[Settings::getKeyForWorkersByQueueName($queue['NAME'])] = $_POST[Settings::getKeyForWorkersByQueueName($queue['NAME'])];
        }
        Settings::saveSettings($cronSettings);

    }
    elseif(mb_strlen($_POST['Generate']))
    {
        \Bitrix\TransformerController\Cron::addToCrontab();
    }
    elseif(mb_strlen($_POST['Clear']))
    {
        \Bitrix\TransformerController\Cron::deleteFromCrontab();
    }
    elseif(mb_strlen($_POST['Kill']))
    {
        Bitrix\TransformerController\Cron::killWorkers();
    }
    if ($_REQUEST["back_url_settings"] <> '')
    {
        LocalRedirect($_REQUEST["back_url_settings"]);
    }
    else
    {
        LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
    }
}
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
	<?php echo bitrix_sessid_post()?>
	<?php
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	if ($errorMessage):?>
		<tr>
			<td colspan="2" align="center"><b style="color:red"><?=htmlspecialcharsbx($errorMessage)?></b></td>
		</tr>
	<?endif;?>
	<tr>
		<td colspan="2"></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_ACCOUNT_DEBUG")?>:</td>
		<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt($moduleName, "debug")? 'checked':'')?> /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_CONNECTION_TIME")?>:</td>
		<td width="60%"><input type="text" name="CONNECTION_TIME" value="<?=COption::GetOptionInt($moduleName, "connection_time", 3);?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_STREAM_TIME")?>:</td>
		<td width="60%"><input type="text" name="STREAM_TIME" value="<?=COption::GetOptionInt($moduleName, "stream_time", 7);?>" /></td>
	</tr>
    <?php if ($verification->isCheckByDomain()): ?>
        <tr>
            <td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_DOMAINS")?>:</td>
            <td width="60%"><textarea style="width: 80%;" name="DOMAINS"><?=htmlspecialcharsbx(implode(',', $verification->getAllowedDomains()));?></textarea></td>
        </tr>
    <?php endif;?>
	<?$tabControl->BeginNextTab();?>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_RABBIT_LOGIN")?>:</td>
		<td width="60%"><input type="text" name="QUEUE_LOGIN" value="<?=htmlspecialcharsbx(COption::GetOptionString($moduleName, "login"));?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_RABBIT_PASSWORD")?>:</td>
		<td width="60%"><input type="text" name="QUEUE_PASSWORD" value="<?=htmlspecialcharsbx(COption::GetOptionString($moduleName, "password"));?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_RABBIT_HOST")?>:</td>
		<td width="60%"><input type="text" name="QUEUE_HOST" value="<?=htmlspecialcharsbx(COption::GetOptionString($moduleName, "host"));?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_RABBIT_PORT")?>:</td>
		<td width="60%"><input type="text" name="QUEUE_PORT" value="<?=htmlspecialcharsbx(COption::GetOptionString($moduleName, "port"));?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_RABBIT_VHOST")?>:</td>
		<td width="60%"><input type="text" name="QUEUE_VHOST" value="<?=htmlspecialcharsbx(COption::GetOptionString($moduleName, "vhost"));?>" /></td>
	</tr>
	<?$tabControl->BeginNextTab();?>
	<tr>
		<td colspan="4"><?=GetMessage('TRANSFORMERCONTROLLER_TAB_QUEUES_SETTINGS_TIP', [
		        '#QUEUE_LINK#' => '/bitrix/tools/transformercontroller/queue.php',
            ]);?></td>
	</tr>
	<tr>
		<td align="right" style="font-weight: bold;"><?=GetMessage('TRANSFORMERCONTROLLER_TAB_QUEUES_NAME');?></td>
		<td align="right" style="font-weight: bold;"><?=GetMessage('TRANSFORMERCONTROLLER_TAB_QUEUES_SORT');?></td>
		<td align="right" style="font-weight: bold;"><?=GetMessage('TRANSFORMERCONTROLLER_TAB_QUEUES_WORKERS_DEFAULT');?></td>
		<td align="right" style="font-weight: bold;"><?=GetMessage('TRANSFORMERCONTROLLER_TAB_QUEUES_WORKERS_LOCAL');?></td>
	</tr>
	<?
	$queues = \Bitrix\TransformerController\Entity\QueueTable::getList()->fetchAll();
	$settings = new Settings();
	foreach($queues as $queue)
	{
	?>
	<tr>
		<td align="right"><?=htmlspecialcharsbx($queue['NAME']);?></td>
		<td align="right"><?=(int)$queue['SORT'];?></td>
		<td align="right"><?=(int)$queue['WORKERS'];?></td>
		<td align="right"><input
			name="<?=htmlspecialcharsbx(Settings::getKeyForWorkersByQueueName($queue['NAME']));?>"
			value="<?=$settings->getLocalWorkersForQueue($queue['NAME']) !== null ? (int)$settings->getLocalWorkersForQueue($queue['NAME']) : null;?>"
		/></td>
	</tr>
	<?}?>
	<?$tabControl->BeginNextTab();?>
	<?
	$runner = new Bitrix\TransformerController\Runner\SystemRunner();
	$statuses = array (
		'AMQP' => false,
		'EXEC' => true,
		'RABBIT' => null,
		'SOFFICE' => null,
		'WORKERS' => 0,
	);
	if (class_exists('AMQPConnection'))
	{
		$statuses['AMQP'] = true;
		$connectionParams = array(
			'login' => \Bitrix\Main\Config\Option::get($moduleName, 'login'),
			'password' => \Bitrix\Main\Config\Option::get($moduleName, 'password'),
			'host' => \Bitrix\Main\Config\Option::get($moduleName, 'host'),
			'port' => \Bitrix\Main\Config\Option::get($moduleName, 'port'),
			'vhost' => \Bitrix\Main\Config\Option::get($moduleName, 'vhost'),
		);
		$connection = new \AMQPConnection($connectionParams);
		try
		{
			$connection->connect();
		}
		catch (\AMQPConnectionException $exception)
		{

		}
		$statuses['RABBIT'] = $connection->isConnected();
		$connection->disconnect();
	}
	$pwdResult = $runner->run('pwd');
	if (!$pwdResult || $pwdResult === null)
	{
		$statuses['EXEC'] = false;
	}
	else
	{
		$statuses['WORKERS'] = \Bitrix\TransformerController\Cron::getProcesses();
		$libreOfficePath = \Bitrix\TransformerController\Document::getLibreOfficePath();
		$officeCheck = $runner->run($libreOfficePath . ' --version');
		if($officeCheck)
		{
			if(!is_array($officeCheck))
			{
				$officeCheck = array($officeCheck);
			}
			foreach($officeCheck as $versionString)
			{
				if(mb_strpos($versionString, 'LibreOffice') !== false)
				{
					$statuses['SOFFICE'] = true;
					break;
				}
			}
		}
	}
	foreach ($statuses as $code => $status)
	{?>
		<tr>
			<td width="40%"><?=GetMessage("TRANSFORMERCONTROLLER_STATUS_".$code)?>:</td>
			<td width="60%"><?
				if (is_int($status))
					echo $status;
				elseif ($status === true)
					echo '<b style="color: green;">'.GetMessage("TRANSFORMERCONTROLLER_STATUS_LABEL_OK").'</b>';
				elseif ($status === false)
					echo '<b style="color: red;">'.GetMessage("TRANSFORMERCONTROLLER_STATUS_LABEL_FAIL").'</b>';
				else
					echo '<b style="color: grey;">'.GetMessage("TRANSFORMERCONTROLLER_STATUS_LABEL_UNKNONW").'</b>';
			?></td>
		</tr>
	<?}
	?>
	<tr>
		<td></td>
	</tr>
	<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
	<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
	<?$tabControl->End();?>
</form>