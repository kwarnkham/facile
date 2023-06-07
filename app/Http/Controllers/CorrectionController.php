<?php

namespace App\Http\Controllers;

use App\Models\AItem;
use App\Models\Correction;
use Illuminate\Support\Facades\DB;

class CorrectionController extends Controller
{
    public function store()
    {
        $data = request()->validate([
            'a_item_id' => ['required', 'exists:tenant.a_items,id'],
            'stock' => ['required', 'numeric', 'not_in:0'],
            'note' => ['max:255']
        ]);

        $correction = DB::connection('tenant')->transaction(function () use ($data) {
            $correction = Correction::create($data);
            $a_item = AItem::find($data['a_item_id']);
            $a_item->update(['stock' => $a_item->stock + $data['stock']]);
            return $correction;
        });

        return response()->json(['correction' => $correction]);
    }

    public function index()
    {
        $filters = request()->validate([
            'a_item_id' => ['required', 'exists:tenant.a_items,id']
        ]);

        $query = Correction::query()->where('a_item_id', $filters['a_item_id']);

        return response()->json(['data' => $query->paginate(request()->per_page ?? 30)]);
    }
}
