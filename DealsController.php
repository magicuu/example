<?php

namespace App\Http\Controllers;

use App\CRM\CRMDeals;
use App\CRM\CRMCompanys;
use App\CRM\CRMUser;
use App\CRM\CRMInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DealsController extends Controller
{
    protected $arColor = [
        "0" => "#39A8EF",
        "1" => "rgb(47, 198, 246)",
        "2" => "rgb(85, 208, 224)",
        "3" => "rgb(71, 228, 194)",
        "4" => "rgb(255, 169, 0)"
    ];
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $arFilter = [
            "CONTACT_ID" => auth()->user()->crm_contact_id
        ];
        $deals = CRMDeals::GetDealsList($arFilter,["ID","ASSIGNED_BY_ID","STAGE_ID","COMPANY_ID","*"], ["ID" => "desc"]);
        foreach($deals as $k => $deal){
            $deals[$k]["begindate"] = date("d.m.Y", strtotime($deal["BEGINDATE"]));
            $deals[$k]["direction"] = CRMDeals::GetDirection($deal["CATEGORY_ID"]);
            $deals[$k]["stagelist"] = CRMDeals::GetStagesList($deal["CATEGORY_ID"]);
            $deals[$k]["companyinfo"] = CRMCompanys::GetCompany($deal["COMPANY_ID"]);
            $deals[$k]["managerinfo"] = CRMUser::GetUser(["ID" => $deal["ASSIGNED_BY_ID"]])[0];
        }
        return view('deals',[
            "deals" => $deals
        ]);
    }
    public function detail($id){
        $deal = CRMDeals::GetDeal($id);
        $deal["direction"] = CRMDeals::GetDirection($deal["CATEGORY_ID"]);
        $deal["stagelist"] = CRMDeals::GetStagesList($deal["CATEGORY_ID"]);
        $deal["managerinfo"] = CRMUser::GetUser(["ID" => $deal["ASSIGNED_BY_ID"]],["PERSONAL_MOBILE","NAME","ID","LAST_NAME","EMAIL"])[0];
        $deal["companyinfo"] = CRMCompanys::GetCompany($deal["COMPANY_ID"]);
        $deal["invoices"] = CRMInvoices::GetInvoicesList(["UF_DEAL_ID" => $id],[]);

        return view('dealdetail',[
            "deal" => $deal
        ]);
    }
    public function create(){
        return view('deals');
    }
}
