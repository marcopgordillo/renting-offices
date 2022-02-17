<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\UpdateImageRequest;
use App\Http\Resources\V1\ImageResource;
use App\Models\Image;
use App\Models\Office;

class OfficeImageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'verified'])->only(['store']);
    }

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
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreImageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreImageRequest $request, Office $office)
    {
        $path = $request->file('image')->storePublicly('/', ['disk' => 'public']);
        $image = $office->images()->create([
            'path'  => $path,
        ]);

        return ImageResource::make($image);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function show(Image $image)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateImageRequest  $request
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateImageRequest $request, Image $image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function destroy(Image $image)
    {
        //
    }
}
