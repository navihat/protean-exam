<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchExamRequest;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Http\Requests\Admin\UpdateExamRequest;
use App\Models\Exam;
use App\Services\Exam\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(SearchExamRequest $request): View
    {
        $search = $request->validated('q');

        return view('admin.exams.index', [
            'exams' => $this->exams->paginateForAdmin($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.exams.create', ['exam' => new Exam(['duration_minutes' => 15])]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = $this->exams->create($request->validated(), $request->user());

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đã tạo đề thi.');
    }

    public function show(Exam $exam): View
    {
        return view('admin.exams.show', ['exam' => $this->exams->loadWithQuestions($exam)]);
    }

    public function edit(Exam $exam): View
    {
        return view('admin.exams.edit', ['exam' => $exam]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->exams->update($exam, $request->validated());

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đã cập nhật đề thi.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->exams->delete($exam);

        return redirect()->route('admin.exams.index')->with('status', 'Đã xoá đề thi.');
    }
}
