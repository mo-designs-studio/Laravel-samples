<?php


namespace App\Http\Traits;


use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadHandler
{
    public function uploadUserProfile($user) {
        if(request()->hasFile('profile_image') && $user->userAttributes) {
            $oldImage   =   $user->userAttributes->profile_image;
            $image      =   request()->file('profile_image');
            $imageExt   =   $image->getClientOriginalExtension();
            $path       =   $image->storeAs("users/{$user->uuid}/profile",Str::uuid().'.'.$imageExt, 'public');
            $user->userAttributes->update(['profile_image'=>$path]);
            $this->deleteFile($oldImage);
        }
    }

    public function uploadCompanyImage($company) {
        $oldImage   =   optional($company->images()->where('use','company_image')->first())->path;
        $image      =   request()->file('image');
        $imageExt   =   $image->getClientOriginalExtension();
        $path       =   $image->storeAs("companies/{$company->uuid}/images",Str::uuid().'.'.$imageExt, 'public');
        $company->images()->create([
            'name'  =>  'company_image',
            'use'   =>  'company_image',
            'path'  =>  $path
        ]);
        $this->deleteFile($oldImage);
    }

    public function uploadArticleImage($article) {
        $oldImage   =   optional($article->images()->where('use','article_image')->first())->path;
        $image      =   request()->file('article_image');
        $imageExt   =   $image->getClientOriginalExtension();
        $path       =   $image->storeAs("articles/{$article->id}/",Str::uuid().'.'.$imageExt, 'public');
        $article->images()->create([
            'name'  =>  'article_image',
            'use'   =>  'article_image',
            'path'  =>  $path
        ]);
        $this->deleteFile($oldImage);
    }

    private function deleteFile($file) {
        Storage::delete($file);
    }
}
