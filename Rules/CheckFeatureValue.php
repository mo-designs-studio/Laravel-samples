<?php

namespace App\Rules;

use App\Model\Properties\PropertyFeature;
use Illuminate\Contracts\Validation\Rule;

class CheckFeatureValue implements Rule
{
    private $pass;
    private $message;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->pass = false;
        $this->message = trans('global.cannot_find_feature');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $feature_id =   request()->input(str_replace('feature_value','feature_id',$attribute));
        $feature    =   PropertyFeature::query()->find($feature_id);
        if($feature) {
            switch ($feature->value_type) {
                case PropertyFeature::TypeBoolean:
                    $this->pass = in_array($value,[0,1]);
                    $this->message = trans('global.feature_value_must_between');
                    break;
                case PropertyFeature::TypeString:
                    $this->pass = true;
                    break;
                case PropertyFeature::TypeExtra:
                    $this->pass = $feature->extra()->where('id' , $value)->exists();
                    $this->message = trans('global.cannot_find_feature_extra_id');
                    break;
                default:
                    $this->message = trans('global.cannot_find_feature_type');
                    $this->pass = false;
                    break;
            }
        }

        return  $this->pass;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
