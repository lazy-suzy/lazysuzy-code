<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Filters extends Model
{

    /**
     * Filter Factory for applying set of filters
     * replace all ABCFilter::apply() calls wiht this one call
     * and make all filter changes bases in the last param passed
     *
     * @param [type] $cat
     * @param [type] $dept
     * @param [type] $all_filters
     * @param [type] $query
     * @param [type] $filter_id
     * @return object
     */
    public static function apply($cat, $dept, $all_filters, $query, $filter_id = NULL)
    {

        // set filters based on the $filters_id
        switch ($filter_id) {
            case Config::get('FILTER_ESCAPE_CATGEORY'):
                $query = DimensionsFilter::apply($query, $all_filters);
                $query = CollectionFilter::apply($query, $all_filters);
                $query = MaterialFilter::apply($query, $all_filters);
                $query = FabricFilter::apply($query, $all_filters);
                $query = DesignerFilter::apply($query, $all_filters);
                $query = MFDCountry::apply($query, $all_filters);
                break;

            default:
                # code...
                break;
        }

        return $query;
    }
}
