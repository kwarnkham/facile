<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Absence;

class AbsenceController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'user_id' => [''],
            'from' => ['date'],
            'to' => ['date']
        ]);
        $query = Absence::query()->filter($filters);
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }
    public function store()
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['absence'] > -1 &&
                $tenant->plan_usage['absence'] == 0,
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan->details['absence'] . ' absences per day.'
        );
        $data = request()->validate([
            'user_id' => ['required', 'exists:tenant.users,id'],
            'date' => ['required', 'date'],
            'note' => ['']
        ]);

        $absence = Absence::create($data);
        return response()->json([
            'absence' => $absence
        ]);
    }

    public function destroy(Absence $absence)
    {
        $absence->delete();
        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
