<?php

namespace App\Http\Requests;

use App\Http\Traits\CustomValidationErrors;
use App\Model\Properties\PropertyCategory;
use App\Rules\CheckDefaultImageIndex;
use App\Rules\CheckFeatureValue;
use App\Rules\CheckMandatoryFeatures;
use App\Rules\ValidYoutubeUrl;
use Illuminate\Foundation\Http\FormRequest;

class PropertiesAdsRequest extends FormRequest
{
    use CustomValidationErrors;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && !auth()->user()->isModerator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $category   =   PropertyCategory::query()
            ->where('uuid', request()->input('category_id'))
            ->with('features')
            ->first();
        $features_ids   =   optional($category)->features ? implode(',',$category->features->pluck('id')->toArray()) : '';
        return [
            'category_id'       =>  'required|exists:properties_categories,uuid',
            'district_id'       =>  'required|exists:governorate_districts,id',
            'address'           =>  'nullable',
            'ad_purpose'        =>  'required|in:0,1',
            'price'             =>  'required|numeric',
            'title'             =>  'required',
            'description'       =>  'required',
            /*'video_url'         =>  ['nullable',new ValidYoutubeUrl()],*/
            'area'              =>  'required|numeric',
            'payment_method'    =>  'required|in:1,2,3',
            'features'          =>  ['required','json',new CheckMandatoryFeatures($category)],
            'features.*.feature_id'     =>  "required|in:{$features_ids}|exists:properties_features,id",
            'features.*.feature_value'  =>  ['required',new CheckFeatureValue()],
            'images'            =>  'nullable|array',
            'images.*'          =>  'nullable|image',
            'default_image_index'   =>  ['required',new CheckDefaultImageIndex()]
        ];
    }

    public function messages()
    {
        return [
            'features.*.feature_id.in'  =>  'Can not set this feature to this category'
        ];
    }
}
