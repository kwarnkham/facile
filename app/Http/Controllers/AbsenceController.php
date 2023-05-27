<?php

namespace App\Http\Controllers;

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
