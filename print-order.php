<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv=Content-Type content="text/html; charset=<?=LANG_CHARSET?>">
    <title langs="ru">Заказ № <?echo $arOrder["ACCOUNT_NUMBER"]?> от <?echo $arOrder["DATE_INSERT_FORMAT"]?></title>
    <style>
        table {
            font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
            font-size: 14px;
            border-collapse: collapse;
            text-align: center;
        }
        th, td:first-child {
          padding: 10px 20px;
        }
        th, td {
            border-style: solid;
            border-width: 0 1px 1px 0;
            border-color: white;
            border: 1px solid black;
            padding: 3px;
        }

        th:nth-child(2), td:nth-child(2) {
            text-align: left;
        }
        table {
            width: 100%;
        }
        table.block-two th:nth-child(2), table.block-two td:nth-child(2) {
            text-align: center;
        }

        thead th:nth-child(1) {
            width: 10%;
        }

        thead th:nth-child(2) {
            width: 30%;
        }

        thead th:nth-child(3) {
            width: 30%;
        }

        thead th:nth-child(4) {
            width: 30%;
        }
    </style>
</head>

<body>

<div class=Section1>

    <!-- REPORT BODY -->
<img class="print-logo" src=".../logo.png.webp">
    <p><b>ЗАКАЗ №:</b> <?echo $arOrder["ACCOUNT_NUMBER"]?> от <?echo $arOrder["DATE_INSERT_FORMAT"]?></p>

    <?
    $priceTotal = 0;
    $bUseVat = false;
    $arBasketOrder = array();
    for ($i = 0, $max = count($arBasketIDs); $i < $max; $i++)
    {
        $arBasketTmp = CSaleBasket::GetByID($arBasketIDs[$i]);

        if (floatval($arBasketTmp["VAT_RATE"]) > 0 )
            $bUseVat = true;

        $priceTotal += $arBasketTmp["PRICE"]*$arBasketTmp["QUANTITY"];

        $arBasketTmp["PROPS"] = array();
        if (isset($_GET["PROPS_ENABLE"]) && $_GET["PROPS_ENABLE"] == "Y")
        {
            $dbBasketProps = CSaleBasket::GetPropsList(
                array("SORT" => "ASC", "NAME" => "ASC"),
                array("BASKET_ID" => $arBasketTmp["ID"]),
                false,
                false,
                array("ID", "BASKET_ID", "NAME", "VALUE", "CODE", "SORT")
            );
            while ($arBasketProps = $dbBasketProps->GetNext())
                $arBasketTmp["PROPS"][$arBasketProps["ID"]] = $arBasketProps;
        }

        $arBasketOrder[] = $arBasketTmp;
    }


    $arCurFormat = CCurrencyLang::GetCurrencyFormat($arOrder["CURRENCY"]);
    $currency = preg_replace('/(^|[^&])#/', '${1}', $arCurFormat['FORMAT_STRING']);
    ?>
    <table border="0" cellspacing="0" cellpadding="2" width="100%">
        <col style="width:10%">
        <col style="width:40%">
        <col style="width:30%">
        <col style="width:30%">
        <tr>
            <td>№</td>
            <td>Товары</td>
            <td>Количество</td>
            <td>Цена</td>
            <td>Стоимость</td>
        </tr>
        <?
        $n = 1;
        $sum = 0.00;
        $arTax = array("VAT_RATE" => 0, "TAX_RATE" => 0);
        $mi = 0;
        $total_sum = 0;

        foreach ($arBasketOrder as $arBasket)
        {
            $nds_val = 0;
            $taxRate = 0;

            if (floatval($arQuantities[$mi]) <= 0)
                $arQuantities[$mi] = DoubleVal($arBasket["QUANTITY"]);

            $b_AMOUNT = DoubleVal($arBasket["PRICE"]);

            //определяем начальную цену
            $item_price = $b_AMOUNT;

            if(DoubleVal($arBasket["VAT_RATE"]) > 0)
            {
                $bVat = true;
                $nds_val = ($b_AMOUNT - DoubleVal($b_AMOUNT/(1+$arBasket["VAT_RATE"])));
                $item_price = $b_AMOUNT - $nds_val;
                $taxRate = $arBasket["VAT_RATE"]*100;
            }
            elseif(!$bUseVat)
            {
                $basket_tax = CSaleOrderTax::CountTaxes($b_AMOUNT, $arTaxList, $arOrder["CURRENCY"]);
                for ($i = 0, $max = count($arTaxList); $i < $max; $i++)
                {
                    if ($arTaxList[$i]["IS_IN_PRICE"] == "Y")
                    {
                        $item_price -= $arTaxList[$i]["TAX_VAL"];
                    }
                    $nds_val += DoubleVal($arTaxList[$i]["TAX_VAL"]);
                    $taxRate += ($arTaxList[$i]["VALUE"]);
                }
            }
            if (empty($arBasket['SET_PARENT_ID']))
            {
                $total_nds += $nds_val*$arQuantities[$mi];
            }

            ?>
            <tr>
                <td>
                    <?echo $n++ ?>
                </td>
                <td>
                    <?echo $arBasket["NAME"]; ?>

                </td>
                <td>
                    <?echo Bitrix\Sale\BasketItem::formatQuantity($arQuantities[$mi]); ?>
                </td>
                <td >
                    <?echo CCurrencyLang::CurrencyFormat($arBasket["PRICE"], $arOrder["CURRENCY"], false) . " ₽"; ?>
                </td>
                <td >
                    <?
                    $sum = $arBasket["PRICE"] * $arQuantities[$mi];
                    echo CCurrencyLang::CurrencyFormat($sum, $arOrder["CURRENCY"], false) . " ₽";
                    ?>
                </td>
            </tr>
            <?
            if (empty($arBasket['SET_PARENT_ID']))
            {
                $total_sum += $arBasket["PRICE"]*$arQuantities[$mi];
            }
            $mi++;
        }//endforeach
        ?>

    </table>
    <br>
    <br>
    <br>
    <table class="block-two">
        <col style="width:50%">
        <col style="width:50%">
        <tr>
            <td>Информация</td>
        </tr>
        <tr><td>Аптека: </td><td><?=$arOrderProps['PHARMACY_NAME']?></td></tr>
        <tr><td>Режим работы (сегодня): </td><td><?=$arOrderProps['TIME_WORK']?></td></tr>
        <tr><td>Статус: </td><td> <?=$arOrder['STATUS_NAME']?></td></tr>
        <tr><td>Сумма: </td><td><?=CCurrencyLang::CurrencyFormat($total_sum, $arOrder["CURRENCY"], false). " ₽";?></td></tr>
    <?//endif?>

</div>
</body>
</html>