<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*
$tmp = $GLOBALS["APPLICATION"]->GetPageProperty("BodyClass");
if ($tmp)
	$tmp .= " page-one-column";
else
	$tmp = "page-one-column";
$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", $tmp);
*/

$col = 0;
$arDiff = array_diff($arParams["GADGETS_FIXED"], $arResult["GADGETS_LIST"]);

if (!empty($arDiff))
{
	if (array_key_exists("DEFAULT_ID", $arParams) && trim($arParams["DEFAULT_ID"]) <> '')
		$arUserOptionsDefault = CUserOptions::GetOption("intranet", "~gadgets_".$arParams["DEFAULT_ID"], false, 0);
	else
		$arUserOptionsDefault = false;

	$arUserOptions = CUserOptions::GetOption("intranet", "~gadgets_".$arParams["ID"], $arUserOptionsDefault, 99999999);

	if (is_array($arUserOptions) && array_key_exists("GADGETS", $arUserOptions) && is_array($arUserOptions["GADGETS"]))
	{
		foreach($arUserOptions["GADGETS"] as $key => $arGadgetDefault)
		{
			list($gadget_id, $id) = explode("@", $key, 2);
			
			if (!in_array($gadget_id, $arParams["GADGETS_FIXED"]))
				continue;

			if (in_array($gadget_id, $arDiff))
			{
				$arGadgetDefaultAll = $arResult["ALL_GADGETS"][$gadget_id];
				
				if (!array_key_exists("CAN_BE_FIXED", $arGadgetDefaultAll) ||!$arGadgetDefaultAll["CAN_BE_FIXED"])
					continue;
				
				$arGadgetDefault = array_merge($arGadgetDefault, $arGadgetDefaultAll);
				$arGadgetDefault["TITLE"] = htmlspecialcharsbx($arGadgetDefault["NAME"]);

				if (!array_key_exists("SETTINGS", $arGadgetDefault))
					$arGadgetDefault["SETTINGS"] = array();

				$arGadgetParams = array();

				foreach($arParams as $id=>$p)
				{
					$pref = "G_".$gadget_id."_";
					if(mb_strpos($id, $pref) === 0)
						$arGadgetParams[mb_substr($id, mb_strlen($pref))]=$p;

					$pref = "GU_".$gadget_id."_";
					if(mb_strpos($id, $pref) === 0 && !isset($arGadgetParams[mb_substr($id, mb_strlen($pref))]))
						$arGadgetParams[mb_substr($id, mb_strlen($pref))]=$p;
				}

				$arGadgetDefault["SETTINGS"] = array_merge($arGadgetParams, $arGadgetDefault["SETTINGS"]);
				$arGadgetDefault["GADGET_ID"] = $arGadgetDefault["ID"];
				$arGadgetDefault["CONTENT"] = BXGadget::GetGadgetContent($arGadgetDefault, $arParams);

				if ($col >= $arParams["COLUMNS"])
					$col = 0;
				array_unshift($arResult["GADGETS"][$col], $arGadgetDefault);
				$col++;
			}
		}
	}
}
?>