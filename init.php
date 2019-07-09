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
