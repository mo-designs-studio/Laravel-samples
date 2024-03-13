<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CreateContactsRequest;
use App\Http\Resources\ContactsResource;
use App\Model\Companies\ContactMethod;
use App\Model\Properties\PropertyAd;
use App\Model\User\User;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    public function store(CreateContactsRequest $request)
    {
        $user   =   $request->user();

        foreach ($request->input('contacts') as $contact) {
            $user->contacts()->updateOrCreate($contact,$contact);
        }

        return response()->json([
            'contacts'  => ContactsResource::collection($user->contacts)
        ]);
    }

    public function destroy($id) {
        $user = \request()->user('sanctum');
        $count = $user->contacts()->where('id', $id)->delete();
        return $count > 0
            ? response()->json(['message'=> trans('global.deleted_successfully')])
            : response()->json(['message'=> trans('global.not_found')] , 422);
    }

    public function show($id) {
        $propertyAd =   PropertyAd::query()->where('uuid',$id)->first();

        if(!$propertyAd) {
            return response()->json(['message'=>trans('global.cannot_find_user')], 422);
        }

        $propertyAd->createNewAdViewContact();

        return response()->json(['contacts'  => ContactsResource::collection($propertyAd->user->contacts)]);
    }

    public function contactsMethods() {
        return response()->json([
            'contactsMethods'   =>  ContactMethod::query()->get()->map(fn($method) => $method->only('id','name','icon') ),
        ]);
    }
}
