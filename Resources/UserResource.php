<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ContactsResource;
use App\Http\Resources\Web\Notifications\NotificationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->uuid,
            'userType' => optional($this->role)->name,
            'userTypeId' => $this->role_id,
            'isVerified' => optional($this->mobile_verified_at)->format('Y-m-d H:i:s'),
            'is_active' => (int)$this->is_active,
            'is_active_agent' => $this->is_active_agent,
            'mobile' => $this->mobile_with_code,
            'phone' => $this->when($this->mobile != null, $this->mobile),
            'code' => optional($this->country)->call_code,
            'email' => $this->email,
            'lang' => $this->lang,
            'profile' => [
                'name' => optional($this->userAttributes)->name,
                'firstName' => optional($this->userAttributes)->first_name,
                'lastName' => optional($this->userAttributes)->last_name,
                'profileImgUrl' => optional($this->userAttributes)->image_url,
                'ads_count' => $this->userAttributes->ads_count,
                'social_type' => $this->social_type,
            ],

            'contacts' => ContactsResource::collection($this->contacts),
            'notifications' => $this->when(auth()->check() && auth()->user()->uuid == $this->uuid, NotificationResource::collection($this->notifications)) ,
            'force_change_pass' => $this->force_change_password,
            'is_profile_completed' => $this->completed,
            'need_current_password' => $this->need_current_password,
            'blocked_reason' => $this->when(!$this->is_active, optional($this->lastBlockedReason())->only('id', 'name', 'type')),
            'created_at' => $this->created_at->format('Y-m-d')
        ];
    }

    public function isAdminRequest()
    {
        return request()->user('sanctum')->isModerator();
    }
}
