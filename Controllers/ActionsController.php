<?php

namespace App\Http\Controllers\Recipes;

use App\Http\Controllers\Controller;
use App\Models\Recipes\Recipe;
use App\Models\User\User;
use App\Models\User\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActionsController extends Controller
{
    public function toggleYummyAction(Request $request)
    {
        $recipeUUID = $request->recipe_uuid;
        $user = Auth::user();
        $recipe = Recipe::getRecipeByUuid($recipeUUID);
        $toggleYummyResult = $user->toggleYummy($user, $recipe->id);
        return response()->json(['yummy_relation' => $toggleYummyResult], 200);
    }
    public function toggleBookmarkAction(Request $request)
    {
        $recipeUUID = $request->recipe_uuid;
        $user = Auth::user();
        $recipe = Recipe::getRecipeByUuid($recipeUUID);
        $toggleBookmarkResult = $user->toggleBookmark($user, $recipe->id);
        return response()->json(['bookmark_relation' => $toggleBookmarkResult], 200);
    }
}
