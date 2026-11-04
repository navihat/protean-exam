<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Http\Resources\AttemptSummaryResource;
use App\Models\Exam;
use App\Models\Mongo\Attempt;
use App\Services\Attempt\AttemptService;
use Illuminate\Http\Request;

/**
 * Ownership of {attempt} is checked by the `can:` route middleware (AttemptPolicy).
 */
class AttemptController extends Controller
{
    public function __construct(private readonly AttemptService $attempts) {}

    public function store(Request $request, Exam $exam): AttemptResource
    {
        // A resource wrapping a just-created model answers 201; a resumed attempt answers 200.
        return new AttemptResource($this->attempts->startOrResume($request->user(), $exam));
    }

    public function show(Attempt $attempt): AttemptResource
    {
        return new AttemptResource($attempt);
    }

    public function submit(SubmitAttemptRequest $request, Attempt $attempt): AttemptSummaryResource
    {
        return new AttemptSummaryResource($this->attempts->submit($attempt, $request->validated('answers', [])));
    }
}
