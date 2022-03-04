<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

global $USER, $APPLICATION;
IncludeModuleLangFile(__FILE__);
$SALE_RIGHT = $APPLICATION->GetGroupRight("sale");

$ORDER_ID = (isset($_REQUEST['ORDER_ID']) ? (int)$_REQUEST['ORDER_ID'] : 0);

function GetRealPath2Report($rep_name)
{
    $rep_name = str_replace("\0", "", $rep_name);
    $rep_name = preg_replace("#[\\\\\\/]+#", "/", $rep_name);
    $rep_name = preg_replace("#\\.+[\\\\\\/]#", "", $rep_name);

    $rep_file_name = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/reports/" . $rep_name;
    if (!file_exists($rep_file_name)) {
        $rep_file_name = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sale/reports/" . $rep_name;
        if (!file_exists($rep_file_name)) {
            return "";
        }
    }

    return $rep_file_name;
}

if (CModule::IncludeModule("sale")) {
    if ($arOrder = CSaleOrder::GetByID($ORDER_ID)) {
        $order = \Bitrix\Sale\Order::load($ORDER_ID);
        $allowedStatusesView = \Bitrix\Sale\OrderStatus::getStatusesUserCanDoOperations($USER->GetID(), array('view'));

        $rep_file_name = GetRealPath2Report($doc . ".php");
        if (strlen($rep_file_name) <= 0) {
            ShowError("PRINT TEMPLATE NOT FOUND");
            die();
        }

        $arOrderProps = array();
        $dbOrderPropVals = CSaleOrderPropsValue::GetList(
            array(),
            array("ORDER_ID" => $ORDER_ID),
            false,
            false,
            array("ID", "CODE", "VALUE", "ORDER_PROPS_ID", "PROP_TYPE")
        );
        while ($arOrderPropVals = $dbOrderPropVals->Fetch()) {
            $arCurOrderPropsTmp = CSaleOrderProps::GetRealValue(
                $arOrderPropVals["ORDER_PROPS_ID"],
                $arOrderPropVals["CODE"],
                $arOrderPropVals["PROP_TYPE"],
                $arOrderPropVals["VALUE"],
                LANGUAGE_ID
            );
            foreach ($arCurOrderPropsTmp as $key => $value) {
                $arOrderProps[$key] = $value;
            }
        }

        $arSelect = array("ID", "NAME", "PROPERTY_264", "PROPERTY_208");
        $arFilter = array("IBLOCK_ID" => 28, "ACTIVE" => "Y", "=PROPERTY_264" => $arOrderProps['PHARMACY_XML_ID']);
        $res = CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize" => 35000), $arSelect);

        while ($ob = $res->GetNextElement()) {

            $arFields = $ob->GetFields();
            $arFields["PROPERTIES"] = $ob->GetProperties();
            $namberDay = date("N") - 1;
            $arOrderProps['TIME_WORK'] = $arFields['PROPERTY_208_VALUE'][$namberDay];
        }


        if ($arStatus = CSaleStatus::GetByID($arOrder['STATUS_ID'])) {
            $arOrder['STATUS_NAME'] = $arStatus['NAME'];
        }

        if (CSaleLocation::isLocationProMigrated()) {
            if (strlen($arOrderProps['LOCATION_VILLAGE']) && !strlen($arOrderProps['LOCATION_CITY']))
                $arOrderProps['LOCATION_CITY'] = $arOrderProps['LOCATION_VILLAGE'];
            
            if (strlen($arOrderProps['LOCATION_STREET']) && isset($arOrderProps['ADDRESS']))
                $arOrderProps['ADDRESS'] = $arOrderProps['LOCATION_STREET'] . (strlen($arOrderProps['ADDRESS']) ? ', ' . $arOrderProps['ADDRESS'] : '');
        }

        $arBasketIDs = array();
        $arQuantities = array();

        if (!isset($SHOW_ALL) || $SHOW_ALL == "N") {
            $arBasketIDs_tmp = explode(",", $BASKET_IDS);
            $arQuantities_tmp = explode(",", $QUANTITIES);

            if (count($arBasketIDs_tmp) != count($arQuantities_tmp)) die("INVALID PARAMS");
            for ($i = 0, $countBasket = count($arBasketIDs_tmp); $i < $countBasket; $i++) {
                if (IntVal($arBasketIDs_tmp[$i]) > 0 && doubleVal($arQuantities_tmp[$i]) > 0) {
                    $arBasketIDs[] = IntVal($arBasketIDs_tmp[$i]);
                    $arQuantities[] = doubleVal($arQuantities_tmp[$i]);
                }
            }
            unset($countBasket);
        } else {
            $params = array(
                'select' => array("ID", "QUANTITY", "SET_PARENT_ID"),
                'filter' => array(
                    "ORDER_ID" => $ORDER_ID
                ),
                'order' => array("ID" => "ASC")
            );
            $db_basket = \Bitrix\Sale\Internals\BasketTable::getList($params);
            while ($arBasket = $db_basket->Fetch()) {
                if (intval($arBasket['SET_PARENT_ID']) > 0)
                    continue;

                $arBasketIDs[] = $arBasket["ID"];
                $arQuantities[] = $arBasket["QUANTITY"];
            }
        }

        $report = "";
        $serCount = IntVal(COption::GetOptionInt("sale", "reports_count"));
        if ($serCount > 0) {
            for ($i = 1; $i <= $serCount; $i++) {
                $report .= COption::GetOptionString("sale", "reports" . $i);
            }
        } else
            $report = COption::GetOptionString("sale", "reports");

        $arOptions = unserialize($report);


        CCurrencyLang::disableUseHideZero();
        include($rep_file_name);
        CCurrencyLang::enableUseHideZero();
    }
} else
    ShowError("SALE MODULE IS NOT INSTALLED");
?>