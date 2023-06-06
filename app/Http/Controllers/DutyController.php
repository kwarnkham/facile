<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Duty;

class DutyController extends Controller
{
    public function store()
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['duty'] >= 0 &&
                $tenant->plan_usage['duty'] <= Duty::query()->count(),
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan_usage['duty'] . ' duties.'
        );
        $data = request()->validate([
            'name' => ['required', 'unique:tenant.tasks,name'],
            'note' => [''],
            'task_id' => ['required', 'exists:tenant.tasks,id']
        ]);

        $duty = Duty::create($data);

        return response()->json([
            'duty' => $duty
        ]);
    }

    public function index()
    {
        $filters = request()->validate([
            'search' => [''],
            'task_id' => ['numeric']
        ]);
        $query = Duty::query()->filter($filters);

        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }

    public function update(Duty $duty)
    {
        $data = request()->validate([
            'name' => ['required'],
            'note' => ['']
        ]);

        $duty->update($data);

        return response()->json([
            'duty' => $duty
        ]);
    }

    public function destroy(Duty $duty)
    {
        $duty->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
