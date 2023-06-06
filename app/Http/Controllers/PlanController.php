<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {
        $query = Plan::query();
        return response()->json(['data' => $query->paginate(request()->per_page ?? 30)]);
    }
}
