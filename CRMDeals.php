<?
namespace App\CRM;

use App\CRM\CrmHelper;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

Class CRMDeals{
    public static function GetDealsList($filter = [],$select = [],$order = []){
        return CrmHelper::GetList('crm.deal.list',$filter,$select,$order);
    }
    public static function GetDirection($ID){
        if(Cache::has("direction_{$ID}")){
            $directioninfo = Cache::get("direction_{$ID}");
        }
        else{
            $directioninfo = CrmHelper::GetByID('crm.dealcategory.get',$ID);
            Cache::put("direction_{$ID}", $directioninfo, Carbon::now()->addDay());
        }
        return $directioninfo;
    }
    public static function GetStagesList($ID){
        if(Cache::has("stagelist_{$ID}")){
            $stagelist = Cache::get("stagelist_{$ID}");
        }
        else{
            $stagelist = CrmHelper::GetByID('crm.dealcategory.stage.list',$ID);
            Cache::put("stagelist_{$ID}", $stagelist, Carbon::now()->addDay());
        }
        return $stagelist;
    }
    public static function GetDeal($ID){
        if(Cache::has("dealdetail_{$ID}")){
            $dealdetail = Cache::get("dealdetail_{$ID}");
        }
        else{
            $dealdetail = CrmHelper::GetByID('crm.deal.get',$ID);
            Cache::put("dealdetail_{$ID}", $dealdetail, Carbon::now()->addDay());
        }
        return $dealdetail;
    }
    public static function GetProductRows($ID){
        return CrmHelper::GetByID('crm.deal.productrows.get',$ID);
    }
    public static function GetDocumentsList($filter = [],$select = [],$order = []){
        return CrmHelper::GetList('crm.documentgenerator.document.list',$filter,$select,$order);
    }
    public static function GetOwner($filter = [],$select = [],$order = []){
        return CrmHelper::GetList('crm.enum.ownertype',$filter,$select,$order);
    }
    public static function GetEvents($filter = [],$select = [],$order = []){
        return CrmHelper::GetList('crm.timeline.bindings.list',$filter,$select,$order);
    }
    public static function AddDeal($arFields){
        return CrmHelper::Add('crm.deal.add',$arFields);
    }
    public static function UpdateDeal($ID,$arFields){
        return CrmHelper::Update('crm.deal.update',$ID,$arFields);
    }
}
?>
