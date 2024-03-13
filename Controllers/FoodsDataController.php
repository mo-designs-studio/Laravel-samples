<?php

namespace App\Http\Controllers\NutritionFacts;

use App\Http\Controllers\Controller;
use App\Models\NutritionFacts\Food;
use App\Models\NutritionFacts\Nutrient;

class FoodsDataController extends Controller
{
    public function updateFoodsTableNutritionAndCaloriesData()
    {
        try {
            Food::chunk(100, function ($foods) {
                foreach ($foods as $food) {
                    $foodNutrients = $food->foodBasicNutrients;
                    $foodCalories = $food->foodCaloriesCalculation;
                    $food->update([
                        'calories' => $foodCalories,
                        'protein' => $foodNutrients['protein'],
                        'fat' => $foodNutrients['fat'],
                        'carbohydrate' => $foodNutrients['carbohydrate'],
                        'fiber' => $foodNutrients['fiber'],
                        'net_carb' => $foodNutrients['net_carbohydrate'],
                    ]);
                }
            });
        } catch (\Exception $exception) {
            return $exception;
        }
        return 'Food nutrients and calories updated successfully';
    }

    public function updateFoodsTableSugarData()
    {
        try {
            Food::chunk(100, function ($foods) {
                foreach ($foods as $food) {
                    $foodNutrients = $food->foodBasicNutrients;
                    $food->update([
                        'sugar' => $foodNutrients['sugar'],
                    ]);
                }
            });
        } catch (\Exception $exception) {
            return $exception;
        }
        return 'Food nutrients and calories updated successfully';
    }

    public function updateIds()
    {
        $nutrients = Nutrient::all();
        $index = 1;
        foreach ($nutrients as $nutrient){
            $nutrient->update(['id' => $index]);
            $nutrient->update(['created_at' => $nutrient->updated_at]);
            $index++;
        }
        return 'IDs updated successfully';
    }

}
