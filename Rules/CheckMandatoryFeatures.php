<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckMandatoryFeatures implements Rule
{
    protected $category;
    protected $message;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($category)
    {
        $this->category = $category;
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
        $features = request()->input('features');
        try {
            $mandatoryIds = array_column(json_decode($features),'feature_id');
        } catch (\Exception $e) {
            $this->message  =   trans('global.not_valid_json');
            return  false;
        }


        $mandatory = $this->category->features
            ->where('pivot.is_mandatory', 1)
            ->whereNotIn('pivot.feature_id',$mandatoryIds)
            ->pluck('name');
        $this->message = trans('global.feature_is_mandatory',['features' => $mandatory]);
        return $mandatory->count() == 0;
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
