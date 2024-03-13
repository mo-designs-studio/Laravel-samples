<?php

namespace App\Models\NutritionFacts;

use App\Models\Recipes\RecipeIngredient;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'fdn_foods';
    protected $guarded = ['id'];

    public function foodCategory()
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }

    public function foodNutrients()
    {
        return $this->hasMany(FoodNutrient::class, 'food_code', 'code');
    }

    public function nutrientConversionFactor()
    {
        return $this->hasMany(NutrientConversionFactor::class, 'food_code', 'code');
    }

    public function calorieConversionFactor()
    {
        return $this->hasOneThrough(CalorieConversionFactor::class, NutrientConversionFactor::class, 'food_code', 'fn_conversion_factor_code', 'code', 'code');
    }

    public function foodPortions ()
    {
        return $this->hasMany(FoodPortion::class,'food_code','code');
    }

    public function checkFoodCalorieFactor()
    {
        $nutrientConversionFactor = $this->calorieConversionFactor();
        return $nutrientConversionFactor->exists();
    }

    public function getCalorieConversionFactor()
    {
        return [
            'protein' => $this->calorieConversionFactor->protein_value,
            'fat' => $this->calorieConversionFactor->fat_value,
            'carbohydrate' => $this->calorieConversionFactor->carbohydrate_value,
        ];
    }

    public function getFoodBasicNutrientsAttribute()
    {
        $foodNutrients = $this->foodNutrients;
        $nutrientsArray = [];
        foreach ($foodNutrients as $nutrient) {
            switch ($nutrient->nutrient_code) {
                case 1003:
                    $nutrientsArray['protein'] = $nutrient->amount;
                    break;
                case 1004:
                    $nutrientsArray['fat'] = $nutrient->amount;
                    break;
                case 1005:
                    $nutrientsArray['carbohydrate'] = $nutrient->amount;
                    break;
                case 1079:
                    $nutrientsArray['fiber'] = $nutrient->amount;
                    break;
                case 2000:
                    $nutrientsArray['sugar'] = $nutrient->amount;
                    break;
            }
        }
        if (!array_key_exists('fiber', $nutrientsArray)) $nutrientsArray['fiber'] = 0;
        if (!array_key_exists('sugar', $nutrientsArray)) $nutrientsArray['sugar'] = 0;
        $nutrientsArray['net_carbohydrate'] = $nutrientsArray['carbohydrate'] - $nutrientsArray['fiber'];
        return $nutrientsArray;
    }

    public function getFoodCaloriesCalculationAttribute()
    {
        $nutrientsArray = $this->getFoodBasicNutrientsAttribute();
        if ($this->checkFoodCalorieFactor()) {
            $foodCalorieConversionFactor = $this->getCalorieConversionFactor();
            $foodCalories = ($nutrientsArray['protein'] * $foodCalorieConversionFactor['protein']) + ($nutrientsArray['fat'] * $foodCalorieConversionFactor['fat']) + ($nutrientsArray['carbohydrate'] * $foodCalorieConversionFactor['carbohydrate']);
        } else {
            $foodCalories = ($nutrientsArray['protein'] * 4) + ($nutrientsArray['fat'] * 9) + ($nutrientsArray['net_carbohydrate'] * 4);
        }
        return (int)round($foodCalories, 0, PHP_ROUND_HALF_UP);
    }

    public function recipeIngredients ()
    {
        return $this->hasMany(RecipeIngredient::class,'food_id');
    }
}
