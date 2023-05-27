<?php

namespace App\Http\Controllers;

use App\Models\Duty;

class DutyController extends Controller
{
    public function store()
    {
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
