<?php


namespace App\Http\Filter\ModelFilters;


use App\Http\Filter\AbstractFilter;
use App\Http\Filter\Filters\CreatedAtFromFilter;
use App\Http\Filter\Filters\CreatedAtLimitDateFilter;
use App\Http\Filter\Filters\CreatedAtToFilter;
use App\Http\Filter\Filters\EmailFilter;
use App\Http\Filter\Filters\FirstNameFilter;
use App\Http\Filter\Filters\HasPropertiesAdsFilter;
use App\Http\Filter\Filters\IsActiveFilter;
use App\Http\Filter\Filters\LastNameFilter;
use App\Http\Filter\Filters\MobileFilter;

class UsersFilter extends AbstractFilter
{
    protected $filters  =   [
        'created_at_from'   =>  CreatedAtFromFilter::class,
        'created_at_to'     =>  CreatedAtToFilter::class,
        'created_at_limit_date'  =>  CreatedAtLimitDateFilter::class,
        'has_properties_ads'    =>  HasPropertiesAdsFilter::class,
        'email' =>  EmailFilter::class,
        'mobile' =>  MobileFilter::class,
        'is_active' =>  IsActiveFilter::class,
        'first_name'    =>  FirstNameFilter::class,
        'last_name' =>  LastNameFilter::class,
    ];
}
