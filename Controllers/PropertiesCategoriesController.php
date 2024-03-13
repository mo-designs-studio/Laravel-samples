<?php

namespace App\Http\Controllers\Api\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\AddFeaturesToCategoriesRequest;
use App\Http\Requests\Admin\MasterData\PropertiesCategoriesRequest;
use App\Http\Resources\Admin\MasterData\PropertiesCategoriesCollection;
use App\Http\Resources\Admin\MasterData\PropertiesCategoriesResource;
use App\Http\Resources\Web\Categories\PropertiesCategoriesResource as PropertiesCategoriesWebResource;
use App\Model\Properties\PropertyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertiesCategoriesController extends Controller
{
    public function index() {
        $cats   =   PropertyCategory::query()->whereNull('parent_id')->with('children')->get();
        return response()->json([
            'categories'    =>  new PropertiesCategoriesCollection($cats)
        ]);
    }

    public function store(PropertiesCategoriesRequest $request) {
        $cat = PropertyCategory::query()->create([
            'ar_name'   =>  $request->input('ar_name'),
            'en_name'   =>  $request->input('en_name'),
            'parent_id' =>  $request->input('parent_id'),
            'is_active' =>  $request->input('is_active')?:1,
        ]);

        return response()->json([
            'category'   =>  new PropertiesCategoriesResource($cat)
        ]);
    }

    public function update() {}

    public function show($id) {
        $cat   =   PropertyCategory::query()->with('children')->where('uuid', $id)->first();
        if ($cat) {
            return response()->json([
                'category'    =>  new PropertiesCategoriesResource($cat)
            ]);
        } else {
            return response()->json([
                'error' =>  trans('global.cannot_find_category')
            ],422);
        }
    }

    public function AddFeaturesToCategories(AddFeaturesToCategoriesRequest $request) {
        $category   =   PropertyCategory::query()->where('uuid', $request->input('category_id'))->first();
        if (empty($request->features)) {
            $category->features()->delete();
        } else {
            $features = [];
            array_map(function($feature) use (&$features){$features[$feature['feature_id']] = ['is_mandatory'=> $feature['is_mandatory']];},$request->input('features'));
            $category->features()->sync($features);
        }
        return response()->json([
            'category'    =>  new PropertiesCategoriesResource($category)
        ]);
    }

    public function getSearchableCategories(){
        $searchableCategories = PropertyCategory::query()->where('is_searchable', true)->get();
        return response()->json(['searchableCategories' => new PropertiesCategoriesCollection($searchableCategories) ],200);
    }

    public function getAdvancedSearchableCategories(){
        $categories = PropertyCategory::query()->whereHas('features',function ($query){
            $query->where('properties_features_categories.is_searchable',1);
        })->get();
        return response()->json(['advancedSearchableCategories' => PropertiesCategoriesWebResource::collection($categories)],200);
    }
}
