<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListExamsRequest;
use App\Http\Resources\ExamResource;
use App\Services\Exam\ExamService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(ListExamsRequest $request): AnonymousResourceCollection
    {
        return ExamResource::collection(
            $this->exams->paginatePublished($request->validated('q'), (int) ($request->validated('page') ?? 1))
        );
    }
}
