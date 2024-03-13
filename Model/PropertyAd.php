<?php

namespace App\Model\Properties;

use App\Http\Resources\ContactsResource;
use App\Http\Resources\PropertyAdResource;
use App\Http\Traits\HasUUID;
use App\Http\Traits\ImagesUploader;
use App\Model\MasterData\Governorate;
use App\Model\MasterData\GovernorateDistrict;
use App\Model\MasterData\Image;
use App\Model\User\ActionReason;
use App\Model\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Self_;
use PhpParser\Builder;

class PropertyAd extends Model
{
    use HasUUID, SoftDeletes, ImagesUploader;

    protected $table = 'properties_ads';
    protected $guarded = ['id'];
    protected $appends = ['ad_purpose_readable', 'payment_method_readable', 'readable_status', 'video'];
    protected $dates = ['expiry_date'];

    ## Ad Purpose Types ##
    const AdPurposeSale = 0;
    const AdPurposeRent = 1;

    ## Payment Method Types ##
    const PaymentTypeCash = 1;
    const PaymentTypeInstallment = 2;
    const PaymentTypeBoth = 3;

    ## Ad Status ##
    const AdStatusPending   = 0;
    const AdStatusAccepted  = 1;
    const AdStatusRejected  = 2;
    const AdStatusCancelled    = 3;

    ## Price Segments ##
    const PricesSegments = [
        0 => [
            1 => [
                "min" => 0,
                "max" => 100000,
            ],
            2 => [
                "min" => 100001,
                "max" => 200000,
            ],
            3 => [
                "min" => 200001,
                "max" => 300000,
            ],
            4 => [
                "min" => 300001,
                "max" => 500000,
            ],
            5 => [
                "min" => 500001,
                "max" => 1000000,
            ],
            6 => [
                "min" => 1000001,
                "max" => 2000000,
            ],
            7 => [
                "min" => 2000001,
                "max" => 3000000,
            ],
            8 => [
                "min" => 3000001,
                "max" => 5000000,
            ],
            9 => [
                "min" => 5000001,
                "max" => 10000000,
            ],
            10 => [
                "min" => 10000001,
                "max" => 0,
            ],
        ],
        1 => [
            1 => [
                "min" => 0,
                "max" => 1000,
            ],
            2 => [
                "min" => 1001,
                "max" => 2000,
            ],
            3 => [
                "min" => 2001,
                "max" => 3000,
            ],
            4 => [
                "min" => 3001,
                "max" => 5000,
            ],
            5 => [
                "min" => 5001,
                "max" => 10000,
            ],
            6 => [
                "min" => 10001,
                "max" => 20000,
            ],
            7 => [
                "min" => 20001,
                "max" => 50000,
            ],
            8 => [
                "min" => 50001,
                "max" => 0,
            ],
        ],
    ];

    const AreasSegments = [
        1 => [
            "min" => 0,
            "max" => 100,
        ],
        2 => [
            "min" => 101,
            "max" => 200,
        ],
        3 => [
            "min" => 201,
            "max" => 300,
        ],
        4 => [
            "min" => 301,
            "max" => 500,
        ],
        5 => [
            "min" => 501,
            "max" => 1000,
        ],
        6 => [
            "min" => 1001,
            "max" => 0,
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id', 'id');
    }

    public function features()
    {
        return $this->belongsToMany(
            PropertyFeature::class,
            'properties_ads_features_pivot',
            'property_id',
            'feature_id'
        )->withPivot('value');
    }

    public function getPresentableFeaturesAttribute()
    {
        $adFeatures = $this->features();
        $stringAdFeatures= $adFeatures->where('value_type', 2)->get();
        return PropertyFeatureCategory::query()
            ->where(['is_presentable' => 1, 'category_id' => $this->category_id])
            ->whereIn('feature_id',Arr::pluck($stringAdFeatures,'id'))->get();
    }

    public function district()
    {
        return $this->belongsTo(GovernorateDistrict::class, 'district_id', 'id');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by', 'id');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function getDefaultImageAttribute()
    {
        $images = $this->images();
        return $images->where('use', 'default')->first();
    }

    public function views()
    {
        return $this->hasMany(PropertyView::class, 'property_id', 'id');
    }

    public function getAdPurposeReadableAttribute()
    {
        $types = [
            self::AdPurposeSale => trans('global.sale'),
            self::AdPurposeRent => trans('global.rent'),
        ];
        return $types[$this->ad_purpose];
    }

    public function getPaymentMethodReadableAttribute()
    {
        $types = [
            self::PaymentTypeCash => trans('global.cash'),
            self::PaymentTypeInstallment => trans('global.installment'),
            self::PaymentTypeBoth => trans('global.both'),
        ];

        return $types[$this->payment_method];
    }

    public function getReadableStatusAttribute()
    {
        $types = [
            self::AdStatusPending => trans('global.pending'),
            self::AdStatusAccepted => trans('global.accepted'),
            self::AdStatusRejected => trans('global.rejected'),
            self::AdStatusCancelled => trans('global.cancelled'),
        ];

        return $types[$this->ad_status];
    }

    public function createNewAdView()
    {
        if (auth('sanctum')->check()) {
            if(auth('sanctum')->id() === $this->user_id) return;
            $view = $this->views()->updateOrCreate([
                'user_id' => auth('sanctum')->id(),
                'property_id' => $this->id,
            ], [
                'user_id' => auth('sanctum')->id(),
                'property_id' => $this->id,
            ]);

            if ($view->wasRecentlyCreated) {
                $this->increment('views');
            }
        }

    }

    public function createNewAdViewContact()
    {
        if (auth('sanctum')->check()) {
            $this->views()->updateOrCreate([
                'user_id' => auth('sanctum')->id(),
                'property_id' => $this->id,
            ], [
                'user_id' => auth('sanctum')->id(),
                'property_id' => $this->id,
                'is_interested' =>  1
            ]);
        }

    }

    public function getVideoAttribute()
    {
        return "https://www.youtube.com/embed/$this->video_url";
    }

    public function scopeStatus($query)
    {
        return $query->where('ad_status', 1);
    }

    public function scopeExpiry($query)
    {
        return $query->where('expiry_date', '>', Carbon::now());
    }

    public static function prepareSearchRequestParams($request)
    {
        $adPurpose = null;
        $categoryId = null;
        $priceSegment =null;
        $minPrice = null;
        $maxPrice = null;
        $areaSegment = null;
        $minArea = null;
        $maxArea = null;
        $districtContainer = null;
        $categoriesContainer = null;

        if($request->adPurpose !== null) $adPurpose = $request->adPurpose;
        if($request->categoryId !== null){
            $categoryId = $request->categoryId;
            $category = PropertyCategory::query()->where('uuid', $categoryId)->first();
            $categoryHasParent = $category->parent_id;
            $categoriesContainer = [];
            if (!is_null($categoryHasParent)) {
                $categoriesContainer = [$category->id];
            } else {
                $categoryChildren = PropertyCategory::query()->where("parent_id", $category->id)->get();
                foreach ($categoryChildren as $categoryChild) {
                    array_push($categoriesContainer, $categoryChild->id);
                }
            }
        }

        if($request->priceSegment !== null){
            $priceSegment = $request->priceSegment;
            $minPrice = self::PricesSegments[$adPurpose][$priceSegment]["min"];
            $maxPrice = self::PricesSegments[$adPurpose][$priceSegment]["max"];
            if ($maxPrice == 0) {
                $currentMaxPrice = PropertyAd::query()->status()->expiry()->orderBy('price', 'desc')->first();
                $maxPrice = $currentMaxPrice ? $currentMaxPrice->price : PHP_INT_MAX;
            }
        }

        if($request->areaSegment !== null){
            $areaSegment = $request->areaSegment;
            if ($areaSegment == 0) {
                $minArea = 0;
                $currentMaxArea = PropertyAd::query()->status()->expiry()->orderBy('area', 'desc')->first();
                $maxArea = $currentMaxArea ? $currentMaxArea->area : PHP_INT_MAX;
            } else {
                $minArea = self::AreasSegments[$areaSegment]["min"];
                $maxArea = self::AreasSegments[$areaSegment]["max"];
            }
        }

        $governorateId = $request->governorateId;
        $districtId = $request->districtId;
         $districtContainer = [];
        if ($districtId > 0) {
            $districtContainer = [$districtId];
        } else {
            $districts = GovernorateDistrict::query()->where('governorate_id', $governorateId)->get();
            foreach ($districts as $district) {
                array_push($districtContainer, $district->id);
            }
        }
        return compact('adPurpose', 'categoriesContainer', 'districtContainer', 'minPrice', 'maxPrice', 'minArea', 'maxArea');
    }

    public function getPresentableFeatures() {
        $presentableFeatures = $this->presentableFeatures;
        $features = [];
        foreach ($presentableFeatures as $feature) {
            $propertyAdPivot = $this->features()->where('feature_id', $feature->feature->id)->first();
            if ($propertyAdPivot)
                array_push($features, [
                    'id' => $feature->feature->id,
                    'name' => $feature->feature->name,
                    'icon' => $feature->feature->icon,
                    'value' => $propertyAdPivot->pivot->value,
                ]);
        }
        return $features;
    }

    static function prepareResponse ($propertiesAds,$governorateId = null) {
        $response = [];
        foreach ($propertiesAds as $propertyAd) {
            $adResponse = new PropertyAdResource($propertyAd);
            array_push($response, $adResponse);
        }
        $governorate = Governorate::find($governorateId);
        $fullResponse = [
            'governorate' => $governorateId != null ? $governorate->name : '',
            'ads' => $response,
            'current_page' => $propertiesAds->currentPage(),
            'first_item' => $propertiesAds->firstItem(),
            'last_item' => $propertiesAds->lastItem(),
            'total' => $propertiesAds->total(),
            'next_page_url' => $propertiesAds->nextPageUrl(),
            'prev_page_url' => $propertiesAds->previousPageUrl(),
            'last_page' => $propertiesAds->lastPage(),
            'if_first_page' => $propertiesAds->onFirstPage(),
            'more_page' => $propertiesAds->hasMorePages(),
            'url_range' => $propertiesAds->getUrlRange(1, $propertiesAds->lastPage()),
        ];
        return $fullResponse;
    }

    public function contactRequests() {
        return $this->belongsToMany(PropertyAd::class,'contact_requests', 'property_ad_id');
    }

    public function blockedReason() {
        return $this->morphMany(ActionReason::class, 'actionable')->with('refuseBlockReason');
    }

    public function lastBlockedReason() {
        return optional($this->blockedReason()->latest()->first())->refuseBlockReason;
    }

    public function calcCommercialExpiration() {
        return Carbon::now()->diffInDays(Carbon::parse($this->is_commercial_expiration), false) > 0
            ? Carbon::parse($this->is_commercial_expiration)->addDays(request()->input('number_of_days'))
            : Carbon::now()->addDays(request()->input('number_of_days'));
    }

    public static function getAdUUID($uuid){
        return self::query()->where('uuid',$uuid)->firstOrFail();
    }

    public function propertyViews(){
        return $this->belongsToMany(User::class, 'properties_views', 'property_id','user_id')->withPivot('is_interested');
    }

    public function propertyViewers(){
        $viewers = $this->propertyViews;
        $viewersDetails = [];
        foreach ($viewers as $viewer){
            $user = User::find($viewer->id);
            array_push($viewersDetails, [
                'id'    =>  $viewer->uuid,
                'name'  =>  $viewer->name,
                'is_interested'   =>  $viewer->pivot->is_interested,
                'profileImage'       =>  $user->userAttributes->image_url,
                'contacts'      =>  ContactsResource::collection($user->contacts),
            ]);
        }
        return $viewersDetails;
    }
}
