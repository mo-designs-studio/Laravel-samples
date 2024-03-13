<?php

namespace App\Http\Requests\Admin;

use App\Http\Traits\CustomValidationErrors;
use App\Rules\WithOutSpaces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAdminsUsersRequest extends FormRequest
{
    use CustomValidationErrors;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && auth()->user()->isModerator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'role_id'       => 'required|in:1,2,3',
            'email'         => 'required_without_all:mobile,username|nullable|email|unique:users,email',
            'mobile'        => 'required_without_all:email,username|nullable|unique:users,mobile',
            'country_id'    =>  'required_with:mobile|exists:countries,id',
            'username'      => ['required_without_all:mobile,email','nullable','min:10','unique:users,username',new WithOutSpaces()],
            'password'      => 'required|min:6|confirmed',
            'is_active'     => 'nullable|in:0,1',
            'first_name'    => 'required|max:20',
            'last_name'     => 'required|max:20',
            'public_name'   => 'nullable|max:30',
            'is_public'     => 'nullable|in:0,1',
            'profile_image' => 'nullable|image',
            'can_create_type'=> Rule::requiredIf(auth()->user()->cannotCreateThisType()),
        ];
    }

    public function messages()
    {
        return [
            'can_create_type.required'  =>  trans('global.cannot_create_user_type'),
        ];
    }
}
