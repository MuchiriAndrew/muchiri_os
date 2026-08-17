<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    public function show(string $key)
    {
        return [
            'key' => $key,
            'value' => Setting::get($key),
        ];
    }
}
