<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePictureRequest;
use App\Http\Requests\UpdatePictureRequest;
use App\Models\Feature;
use App\Models\Picture;
use Illuminate\Support\Facades\Redirect;

class PictureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePictureRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePictureRequest $request)
    {
        $attributes = $request->safe()->only(['pictures', 'type_id', 'type']);
        $model = 'App\\Models\\' . ucfirst(strtolower($attributes['type']));
        foreach ($attributes['pictures'] as $picture) {
            Picture::create([
                'name' => $model::saveFile($picture),
                'pictureable_id' => $attributes['type_id'],
                'pictureable_type' => $model
            ]);
        }
        if ($request->wantsJson())
            return response()->json([
                'feature' => Feature::find($attributes['type_id'])
                    ->load([
                        'item',
                        'pictures',
                        'batches',
                        'latestPurchase'
                    ])
            ]);
        return redirect()->back()->with('message', 'Pictures uploaded');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Picture  $picture
     * @return \Illuminate\Http\Response
     */
    public function show(Picture $picture)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Picture  $picture
     * @return \Illuminate\Http\Response
     */
    public function edit(Picture $picture)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePictureRequest  $request
     * @param  \App\Models\Picture  $picture
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePictureRequest $request, Picture $picture)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Picture  $picture
     * @return \Illuminate\Http\Response
     */
    public function destroy(Picture $picture)
    {
        $picture->delete();
        if (request()->wantsJson()) return response()->json(['message' => 'Finish']);
        return Redirect::back()->with('message', 'Deleted');
    }
}
