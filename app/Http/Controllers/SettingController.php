<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(Setting $setting)
    {
        return response()->json([
            'setting' => $setting
        ]);
    }

    public function update(Setting $setting)
    {
        $data = request()->validate([
            'active_order_status' => ['']
        ]);

        $setting->update(['active_order_status' => $data['active_order_status']]);

        return response()->json(['setting' => $setting]);
    }
}
