<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store()
    {
        $data = request()->validate([
            'name' => ['required', 'unique:tasks,name'],
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
        $query = Task::query()->filter($filters);

        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
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
