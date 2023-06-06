<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store()
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['task'] >= 0 &&
                $tenant->plan_usage['task'] <= Task::query()->count(),
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan_usage['task'] . ' tasks.'
        );
        $data = request()->validate([
            'name' => ['required', 'unique:tenant.tasks,name'],
            'stort' => ['numeric']
        ]);

        $task = Task::create($data);

        return response()->json([
            'task' => $task
        ]);
    }

    public function show(Task $task)
    {
        return response()->json([
            'task' => $task
        ]);
    }

    public function index()
    {
        $filters = request()->validate([
            'search' => ['']
        ]);
        $query = Task::query()->orderBy('sort')->filter($filters);

        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }

    public function update(Task $task)
    {
        $data = request()->validate([
            'name' => ['required', Rule::unique('tenant.tasks', 'name')->ignoreModel($task)],
            'sort' => ['required', 'numeric', 'gt:0']
        ]);
        $task->update($data);
        return response()->json(['task' => $task]);
    }

    public function destroy(Task $task)
    {
        $task->roles()->detach();
        $task->duties()->delete();
        $task->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
