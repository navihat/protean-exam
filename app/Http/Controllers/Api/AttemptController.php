<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttemptResource;
use App\Models\Exam;
use App\Services\Attempt\AttemptService;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function __construct(private readonly AttemptService $attempts) {}

    public function store(Request $request, Exam $exam): AttemptResource
    {
        // A resource wrapping a just-created model answers 201.
        return new AttemptResource($this->attempts->start($request->user(), $exam));
    }
}
