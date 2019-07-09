<?php
Class Response{//Интеграция сайта с CRM Axapta. Отложенно отправляет заявки и проверяет статус получения. Если заявка не получена со стороны CRM, то продует снова.
    function GetUnDeliver(){
        $undelivers = CIBlockElement::GetList([],["IBLOCK_ID" => 11, "PROPERTY_STATUS" => 36],false,false,["ID","IBLOCK_ID","PROPERTY_STATUS","PROPERTY_USER_NAME","PROPERTY_USER_EMAIL","PROPERTY_USER_PHONE","PROPERTY_COMP_NAME","PROPERTY_COMP_TYPE","PROPERTY_LEASING_OBJ","PROPERTY_ADVANCE_SIZE","PROPERTY_LEASING_TIME","PROPERTY_USER_CITY"]);
        while($undeliver = $undelivers->GetNext()){
            $params = [
                "opfCode" => $undeliver["PROPERTY_COMP_TYPE_VALUE"],
                "companyName" => $undeliver["PROPERTY_COMP_NAME_VALUE"],
                "contactName" => $undeliver["PROPERTY_USER_NAME_VALUE"],
                "contactPhone" => $undeliver["PROPERTY_USER_PHONE_VALUE"],
                "contactEmail" => $undeliver["PROPERTY_USER_EMAIL_VALUE"],
                "item" => $undeliver["PROPERTY_LEASING_OBJ_VALUE"],
                "advance" => $undeliver["PROPERTY_ADVANCE_SIZE_VALUE"],
                "period" => $undeliver["PROPERTY_LEASING_TIME_VALUE"],
                "city" => $undeliver["PROPERTY_USER_CITY_VALUE"]
            ];
            $request = $this->Send($params);
            if($request != "")
                $this->SetStatus($undeliver["ID"],36);
            else
                $this->SetStatus($undeliver["ID"],35);
        }
    }
    function SetStatus($RID,$status){
        CIBlockElement::SetPropertyValuesEx($RID, 11, array("STATUS" => $status));
        return;
    }
    function Send($params){
        $url = 'xxxx';//адрес шлюза
        $call_result = cURL($url,$params);
        return $call_result;
    }
    function GetUnDeliverList(){
        $undelivers = CIBlockElement::GetList([],["IBLOCK_ID" => 11, "PROPERTY_STATUS" => 36],false,false,["ID","IBLOCK_ID","PROPERTY_STATUS","PROPERTY_USER_NAME","PROPERTY_USER_EMAIL","PROPERTY_USER_PHONE","PROPERTY_COMP_NAME","PROPERTY_COMP_TYPE","PROPERTY_LEASING_OBJ","PROPERTY_ADVANCE_SIZE","PROPERTY_LEASING_TIME","PROPERTY_USER_CITY"]);
        echo $undelivers->SelectedRowsCount();
        while($undeliver = $undelivers->GetNext()){
            $params = [
                "opfCode" => $undeliver["PROPERTY_COMP_TYPE_VALUE"],
                "companyName" => $undeliver["PROPERTY_COMP_NAME_VALUE"],
                "contactName" => $undeliver["PROPERTY_USER_NAME_VALUE"],
                "contactPhone" => $undeliver["PROPERTY_USER_PHONE_VALUE"],
                "contactEmail" => $undeliver["PROPERTY_USER_EMAIL_VALUE"],
                "item" => $undeliver["PROPERTY_LEASING_OBJ_VALUE"],
                "advance" => $undeliver["PROPERTY_ADVANCE_SIZE_VALUE"],
                "period" => $undeliver["PROPERTY_LEASING_TIME_VALUE"],
                "city" => $undeliver["PROPERTY_USER_CITY_VALUE"]
            ];
            print_r($params);
        }
        return true;
    }
};


Class Catalog{//парсер каталогов техники с различных сайтов.
    var $MonthPayment = "";
    var $FullPrice = "";
    var $Advance = "";
    function CallSync(){
        $elements = Catalog::GetSyncList(50);
        if(!$elements->SelectedRowsCount())
            return "Catalog::CallSync();";
        $ChangeArray = [];
        while($element = $elements->GetNext()){
            $Site = GetSite($element["PROPERTY_SITE_VALUE"]);
            $result = cURL($Site["PROPERTY_EXEC_VALUE"],["URL" => $element["PROPERTY_LINK_VALUE"]]);//вызывает парсер для сайта и получает в ответ масси в элементов.
            $result = json_decode($result,true);
            if($result["PRICE"]){
                if($element["PROPERTY_PRICE_VALUE"] != $result["PRICE"]){
                    $ChangeArray[] = [
                        "ITEM_NAME" => $element["NAME"],
                        "ITEM_LINK" => "https://elementleasing.ru".$element["DETAIL_PAGE_URL"],
                        "ITEM_OLD_PRICE" => $element["PROPERTY_PRICE_VALUE"],
                        "ITEM_NEW_PRICE" => $result["PRICE"]
                    ];
                }
                Catalog::UpdatePrice($element["ID"],intval(str_replace(" ","",$result["PRICE"])));
                Catalog::SetUpdateDate($element["ID"]);
            }
        }
        if(sizeof($ChangeArray)){
            $ChangeTable = "";
            $ChangeTable .= <<<EOT
<table>
    <thead>
        <tr>
            <td>Элемент</td>
            <td>Старая цена</td>
            <td>Новая цена</td>
        </tr>
    </thead>
    <tbody>
EOT;
            foreach($ChangeArray as $ChangeItem){
                $ChangeTable .= <<<EOT
<tr>
<td><a href="{$ChangeItem["ITEM_LINK"]}">{$ChangeItem["ITEM_NAME"]}</a></td>
<td>{$ChangeItem["ITEM_OLD_PRICE"]}</td>
<td>{$ChangeItem["ITEM_NEW_PRICE"]}</td>
</tr>
EOT;
            }
            $ChangeTable .= <<<EOT
    </tbody>
</table>
EOT;
            Catalog::SendPriceEmail([
                "ITEMS_TABLE" => $ChangeTable
            ]);
        }
        return "Catalog::CallSync();";
    }
    function CheckLinkRequest($link){
        $handle = curl_init($link);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }
    function CheckLinks(){
        $return = [];
        $links = CIBlockElement::GetList([],["IBLOCK_ID" => 13, "ACTIVE" => "Y", "!PROPERTY_LINK" => false],false,false,["ID","IBLOCK_ID","PROPERTY_LINK", "DETAIL_PAGE_URL"]);
        while($link = $links->GetNext()){
            $return[] = [
                "element" => $link["DETAIL_PAGE_URL"],
                "link" => $link["PROPERTY_LINK_VALUE"],
                "status" => Catalog::CheckLinkRequest($link["PROPERTY_LINK_VALUE"])
            ];

        }
        return $return;
    }
    function GetSyncList($COUNT = 10){
        $elements = CIBlockElement::GetList(
            ["rand" => "asc"],
            [
                "IBLOCK_ID" => 13,
                [
                    "LOGIC" => "OR",
                    "<=PROPERTY_SYNC_DATE" => date('Y-m-d H:i:s', time()- 86400 * 30),
                    "PROPERTY_SYNC_DATE" => false
                ],
                "!PROPERTY_SITE" => false,
                "!PROPERTY_LINK" => false,
                "ACTIVE" => "Y"
            ],false,["nTopCount" => $COUNT],["ID","IBLOCK_ID","NAME","PROPERTY_SITE","PROPERTY_LINK","PROPERTY_PRICE","DETAIL_PAGE_URL"]
        );
        return $elements;
    }
    function SendPriceEmail($params){
        CEvent::Send(
            "CATALOG_SYSTEM",
            "s1",
            $params,
            "Y",
            34
        );
    }
    function UpdatePrice($ID,$PRICE){
        CIBlockElement::SetPropertyValuesEx($ID, 13, array("PRICE" => $PRICE));
    }
    function SetUpdateDate($ID){
        CIBlockElement::SetPropertyValuesEx($ID, 13, array("SYNC_DATE" => ConvertTimeStamp(false,"FULL")));
    }
    function GetTypesList(){
        $return = [];
        $types = CIBlockPropertyEnum::GetList(["value" => "asc"],["PROPERTY_ID" => 112]);
        while($type = $types->GetNext()){
            $return[] = $type;
        }
        return $return;
    }
    function GetBrandsList(){
        $return = [];
        $brands = CIBlockElement::GetList(["name" => "asc"],["IBLOCK_ID" => 17],false,false,["ID","IBLOCK_ID","NAME"]);
        while($brand = $brands->GetNext()){
            $return[] = $brand;
        }
        return $return;
    }
    function GetFilterAvailableList($filter = []){
        $return = [
            "brand" => [],
            "type" => []
        ];
        $arFilter = ["IBLOCK_ID" => 13, "ACTIVE" => "Y"];
        $arFilter = array_merge($arFilter,$filter);
        $elements = CIBlockElement::GetList(["PROPERTY_BRAND.NAME" => "ASC"],$arFilter,false,false,["ID","IBLOCK_ID","PROPERTY_TYPE_AUTO","PROPERTY_TYPE_AUTO.NAME","PROPERTY_BRAND","PROPERTY_BRAND.NAME"]);

        while($element = $elements->GetNext()){
            if($element["PROPERTY_BRAND_VALUE"])
                $return["brand"][$element["PROPERTY_BRAND_VALUE"]] = $element["PROPERTY_BRAND_NAME"];
            if($element["PROPERTY_TYPE_AUTO_VALUE"])
                $return["type"][$element["PROPERTY_TYPE_AUTO_VALUE"]] = $element["PROPERTY_TYPE_AUTO_NAME"];
        }
        return $return;

    }
    function GetMaxPrice($filter = []){
        $arFilter = ["IBLOCK_ID" => 13, "ACTIVE" => "Y", "!PROPERTY_PRICE" => false];
        $arFilter = array_merge($arFilter,$filter);
        $element = CIBlockElement::GetList(["PROPERTY_PRICE" => "desc"], $arFilter, false, ["nTopCount" => 1], ["ID", "IBLOCK_ID", "PROPERTY_PRICE"]);
        $element = $element->GetNext();
        return $element["PROPERTY_PRICE_VALUE"];
    }
    function GetAdvance(){
        return self::Advance;
    }
    function GetFullPrice(){
        return self::FullPrice;
    }
    function GetMonthPayment($price){
        if(!$price)
            return 0;
        $start = 50;
        $month = 36;
        $percent = 25;
        $beta = $percent/(100*12);
        $st = 1 - 1/(pow(1+$beta,$month));
        $c = $price - $price*($start/100);
        $r = $c*$beta/$st;
        return round($r,0);
    }
    function GetElementType($EID){
        $element = CIBlockElement::GetList(["rand" => "asc"],["IBLOCK_ID" => 13, "ID" => $EID],false, false, ["ID","IBLOCK_ID","PROPERTY_TYPE_AUTO"])->GetNext();
        return $element["PROPERTY_TYPE_AUTO_VALUE"];
    }
    function GetRandByType($count = 2, $type = false, $eid = false){
        $return = [];
        $elements = CIBlockElement::GetList(["rand" => "asc"],["IBLOCK_ID" => 13, "PROPERTY_TYPE_AUTO" => $type, "ACTIVE" => "Y", "!ID" => $eid],false,["nTopCount" => $count],["ID","IBLOCK_ID"]);
        while($element = $elements->GetNext())
            $return[] = $element["ID"];
        return $return;
    }
    function GetListByFilter($filter = [],$sort = [],$count = false){
        $return = [];
        $filter = array_merge(["IBLOCK_ID" => 13], $filter);
        $List = CIBlockElement::GetList($sort,$filter,false,$count,["ID","IBLOCK_ID","NAME"]);
        while($Element = $List->GetNext()){
            $return[$Element["ID"]] = $Element;
        }
        return $return;
    }
    function GetTypeIDByCode($code){
        $type = CIBlockElement::GetList([],["IBLOCK_ID" => 21, "CODE" => $code],false,false,["ID","IBLOCK_ID","NAME","DETAIL_TEXT"])->GetNext();
        return $type["ID"];
    }
    function GetTypeInfo($PID){
        $prop = CIBlockElement::GetByID($PID)->GetNext();
        return $prop;
    }
    function GetLeasingType($TID){
        $type = CIBlockElement::GetByID($TID)->GetNext();
        return $type["NAME"];
    }
};
?>
