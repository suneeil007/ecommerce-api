<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('get_provinces')) {
    function get_provinces()
    {
        return DB::table('provinces')
            ->select('id', 'name', 'code')
            ->orderBy('id')
            ->get();
    }
}

if (! function_exists('get_districts_by_province')) {
    function get_districts_by_province(int $provinceId)
    {
        return DB::table('districts')
            ->select('id', 'province_id', 'name', 'code')
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->get();
    }
}