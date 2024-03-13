<?php

namespace App\Http\Controllers\Api;

use App\Events\AdStatusChangeEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefuseOrComplaintOrCloseAdRequest;
use App\Http\Requests\PropertiesAdsRequest;
use App\Http\Requests\Web\AdReportRequest;
use App\Http\Resources\PropertyAdResource;
use App\Http\Traits\ImagesUploader;
use App\Model\MasterData\Governorate;
use App\Model\MasterData\GovernorateDistrict;
use App\Model\MasterData\Image;
use App\Model\MasterData\Setting;
use App\Model\Properties\PropertyAd;
use App\Model\Properties\PropertyCategory;
use App\Model\Properties\PropertyFeature;
use App\Model\User\User;
use App\Model\User\UserAttribute;
use App\Notifications\AdStatusChangeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertiesAdsController extends Controller
{

    public function store(PropertiesAdsRequest $request)
    {
        DB::beginTransaction();
        try {
            $category = PropertyCategory::query()->where('uuid', $request->input('category_id'))->first();
            $ad = PropertyAd::query()->create([
                'category_id' => $category->id,
                'user_id' => $request->user()->id,
                'district_id' => $request->input('district_id'),
                'address' => $request->input('address'),
                'ad_purpose' => $request->input('ad_purpose'),
                'price' => $request->input('price'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'video_url' => getYoutubeVideoId($request->input('video_url')),
                'is_commercial' => $request->input('is_commercial') ?: 0,
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
                'area' => $request->input('area'),
                'payment_method' => $request->input('payment_method'),
                'is_negotiable' => $request->input('is_negotiable') ?: 0,
                'ad_status' => PropertyAd::AdStatusPending
            ]);

            $features = [];

            array_map(function ($feature) use (&$features) {
                $features[$feature['feature_id']] = ['value' => $feature['feature_value']];
            }, json_decode($request->input('features'), true));

            $ad->features()->sync($features);

            $ad->handleImages($request->file('images'), null, true);

            UserAttribute::query()->where(['user_id' => Auth::user()->id])->increment('ads_count',);

            DB::commit();

            return response()->json([
                'property_ad_id' => $ad->uuid
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function update($id, Request $request)
    {

        DB::beginTransaction();
        try {
            $category = PropertyCategory::query()->where('uuid', $request->input('category_id'))->first();
            $ad = PropertyAd::getAdUUID($id);
            PropertyAd::query()->where(['uuid' => $id])->update(
                [
                    'category_id' => $category->id,
                    'user_id' => $request->user()->id,
                    'district_id' => $request->input('district_id'),
                    'address' => $request->input('address'),
                    'ad_purpose' => $request->input('ad_purpose'),
                    'price' => $request->input('price'),
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'video_url' => $request->input('video_url') == $ad->video_url ? $request->input('video_url') : getYoutubeVideoId($request->input('video_url')),
                    'is_commercial' => $request->input('is_commercial') ?: 0,
                    'lat' => $request->input('lat'),
                    'lng' => $request->input('lng'),
                    'area' => $request->input('area'),
                    'payment_method' => $request->input('payment_method'),
                    'is_negotiable' => $request->input('is_negotiable') ?: 0,
                    'ad_status' => PropertyAd::AdStatusPending
                ]);

            $ad->features()->detach();

            $features = [];

            array_map(function ($feature) use (&$features) {
                $features[$feature['feature_id']] = ['value' => $feature['feature_value']];
            }, json_decode($request->input('features'), true));

            $ad->features()->sync($features);

            array_map(function ($image) use ($ad, $id) {
                $ad->images()->where('name', $image['imageName'])->delete();
                Storage::delete("public/property_ads/{$id}/images/" . $image['imageName']);
            }, json_decode($request->input('cancelled_images'), true));

            $ad->images()->update(['use' => '']);

            $ad->images()->where('name', $request->input('updated_cover'))->update(['use' => 'default']);

            if ($request->file('images')) $ad->handleImages($request->file('images'), null, true);

            DB::commit();

            return response()->json([
                'property_ad_id' => $ad->uuid
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

    }

    public function show($id)
    {
        $ad = PropertyAd::query()
            ->where('uuid', $id)
            ->when(auth()->check(), function ($query) {
                $query->where(function ($sub) {
                    $sub->where('ad_status', PropertyAd::AdStatusAccepted)
                        ->orWhere('user_id', auth()->user()->id);
                });
            })
            ->when(!auth()->check(), function ($query) {
                $query
                    ->where('ad_status', PropertyAd::AdStatusAccepted);
            })
            ->first();
        if ($ad) {
            $ad->createNewAdView();
            return response()->json([
                'propertyAd' => new PropertyAdResource($ad)
            ], 200);
        }
        return response()->json([
            'error' => trans('global.cannot_find_ad')
        ], 422);

    }

    public function searchPropertiesAds(Request $request)
    {
        $params = PropertyAd::prepareSearchRequestParams($request);
        $propertiesAds = PropertyAd::query()->status()->expiry()
            ->when($request->adPurpose !== null, function ($query) use ($params) {
                $query->where(['ad_purpose' => $params['adPurpose']]);
            })
            ->when($request->categoryId, function ($query) use ($params) {
                $query->where(['category_id' => $params['categoriesContainer']]);
            })
            ->when($request->priceSegment, function ($query) use ($params) {
                $query->whereBetween('price', [$params['minPrice'], $params['maxPrice']]);
            })
            ->when($request->areaSegment !== null, function ($query) use ($params) {
                $query->whereBetween('area', [$params['minArea'], $params['maxArea']]);
            })
            ->whereIn('district_id', $params['districtContainer'])
            ->when($request->features != null, function ($query) use ($request) {
                array_map(function ($feature) use ($query) {
                    $propertyFeature = PropertyFeature::find($feature['feature_id']);
                    if ($propertyFeature) {
                        if ($propertyFeature->value_type == 1)
                            $query->whereHas('features', function ($relationQuery) use ($feature) {
                                $relationQuery->where('feature_id', $feature['feature_id'])->where('value', '1');
                            });
                        if ($propertyFeature->value_type == 2)
                            $query->whereHas('features', function ($relationQuery) use ($feature) {
                                $relationQuery->where('feature_id', $feature['feature_id'])->whereIn('value', $feature['feature_value']);
                            });
                        if ($propertyFeature->value_type == 3)
                            $query->whereHas('features', function ($relationQuery) use ($feature) {
                                $relationQuery->where(['feature_id' => $feature['feature_id'], 'value' => $feature['feature_value']]);
                            });
                    }
                }, json_decode($request->input('features'), true));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        if (count($propertiesAds) === 0) return response()->json(['propertiesAds' => ['ads' => [], 'governorate' => Governorate::find($request->governorateId)->name,]], 200);
        $response = PropertyAd::prepareResponse($propertiesAds, $request->governorateId);
        return response()->json(['propertiesAds' => $response], 200);
    }

    //todo check if same blow function { MyPropertiesAds }
    public function getMyProperties()
    {
        $propertiesAds = PropertyAd::query()
            ->where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(3);
        $response = PropertyAd::prepareResponse($propertiesAds);
        return response()->json(['propertiesAds' => $response], 200);
    }

    public function getMyPropertiesAds()
    {
        $propertiesAds = PropertyAd::query()
            ->where('user_id', auth('sanctum')->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'PropertiesAds' => PropertyAdResource::collection($propertiesAds),
            'current_page' => $propertiesAds->currentPage(),
            'first_item' => $propertiesAds->firstItem(),
            'last_item' => $propertiesAds->lastItem(),
            'total' => $propertiesAds->total(),
            'next_page_url' => $propertiesAds->nextPageUrl(),
            'prev_page_url' => $propertiesAds->previousPageUrl(),
            'last_page' => $propertiesAds->lastPage(),
            'more_page' => $propertiesAds->hasMorePages(),
            'url_range' => $propertiesAds->getUrlRange(1, $propertiesAds->lastPage()),
        ]);
    }

    public function getLastAddedAds()
    {
        $propertiesAds = cache()->remember('last_ads_' . request()->getQueryString(), now()->addHour(1), function () {
            return PropertyAd::query()->status()->expiry()->latest()->limit(100)->paginate(10);
        });
        $response = PropertyAd::prepareResponse($propertiesAds);
        return response()->json(['propertiesAds' => $response], 200);
    }

    public function reportAd(AdReportRequest $request)
    {
        $ad = PropertyAd::query()->where(['uuid' => $request->input('ad_id')])->first();
        $reason = $request->input('reason_id');

        if (!$ad) {
            return response()->json(['message' => trans('global.cannot_find_ad')], 422);
        }

        $checkPastReportExist = $ad->blockedReason()->where(['actionable_id' => $ad->id, 'actioned_by' => auth()->user()->id])->exists();

        if ($checkPastReportExist) return response()->json(['message' => trans('global.already_reported')], 422);
        $ad->blockedReason()->create(['refuse_block_reason_id' => $reason, 'actioned_by' => auth()->user()->id]);

        return response()->json([
            'message' => trans('global.ad_reported')
        ], 200);
    }

    public function updateImagesNames()
    {

        $images = Image::all();

        foreach ($images as $image) {
            $path = $image->path;

            if (str_contains($path, '/')) {
                $pathParts = explode('/', $path);
                $imageName = $pathParts[sizeof($pathParts) - 1];
                $image->update(['name' => $imageName]);
            }
        }
    }
}
