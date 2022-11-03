<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Http\Request;
use Redirect;

class TagController extends Controller
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
     * @param  \App\Http\Requests\StoreTagRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTagRequest $request)
    {
        $attributes = $request->validated();
        $item = Item::find($attributes['item_id']);
        if ($item->tags->doesntContain(
            function ($value) use ($attributes) {
                return $value->name == strtolower($attributes['name']);
            }
        ))
            $item->tags()->attach(Tag::firstOrCreate(['name' => strtolower($attributes['name'])])->id);
        return Redirect::route('items.edit', ['item' => $item->id]);
    }

    /**
     * Toggle a tag to an item
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toggle(Request $request, Tag $tag)
    {
        $attributes = $request->validate([
            'item_id' => ['required', 'exists:items,id']
        ]);
        $tag->items()->toggle($attributes['item_id']);
        return Redirect::route('items.edit', ['item' => $attributes['item_id']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTagRequest  $request
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        //
    }
}
