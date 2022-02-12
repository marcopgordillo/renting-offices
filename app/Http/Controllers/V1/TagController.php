<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use App\Http\Resources\V1\TagResource;

class TagController extends Controller
{
    public function __invoke()
    {
        return TagResource::collection(
            Tag::all()
        );
    }
}
