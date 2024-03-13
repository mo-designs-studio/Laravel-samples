<?php


namespace App\Http\Filter\Filters;


use App\Http\Filter\AbstractBasicFilter;
use App\Model\User\User;
use Carbon\Carbon;

class CreatedAtLimitDateFilter extends AbstractBasicFilter
{
    public function filter($value)
    {
        switch ($value) {
            case 1:
                return $this->builder->whereBetween('created_at', [Carbon::now()->subDays(7)->endOfDay()->toDateTimeString(), Carbon::now()->toDateTimeString()]);
            case 2:
                return $this->builder->whereBetween('created_at', [Carbon::now()->subDays(15)->endOfDay()->toDateTimeString(), Carbon::now()->toDateTimeString()]);
            case 3:
                return $this->builder->whereBetween('created_at', [Carbon::now()->subDays(30)->endOfDay()->toDateTimeString(), Carbon::now()->toDateTimeString()]);
            default:
                return $this->builder;
        }

    }
}
