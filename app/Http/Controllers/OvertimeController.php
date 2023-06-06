<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Overtime;

class OvertimeController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'user_id' => [''],
            'from' => ['date'],
            'to' => ['date']
        ]);
        $query = Overtime::query()->filter($filters);
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }
    public function store()
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['overtime'] > -1 &&
                $tenant->plan_usage['overtime'] == 0,
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan->details['overtime'] . ' overtimes per day.'
        );
        $data = request()->validate([
            'user_id' => ['required', 'exists:tenant.users,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'note' => ['']
        ]);

        $overtime = Overtime::create($data);
        return response()->json([
            'overtime' => $overtime
        ]);
    }

    public function destroy(Overtime $overtime)
    {
        $overtime->delete();
        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
