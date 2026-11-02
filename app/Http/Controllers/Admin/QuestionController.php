<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Services\Exam\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questions) {}

    public function create(Exam $exam): View
    {
        return view('admin.questions.create', ['exam' => $exam, 'question' => new Question]);
    }

    public function store(StoreQuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->questions->create($exam, $request->validated());

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đã thêm câu hỏi.');
    }

    public function edit(Exam $exam, Question $question): View
    {
        return view('admin.questions.edit', ['exam' => $exam, 'question' => $question->load('options')]);
    }

    public function update(UpdateQuestionRequest $request, Exam $exam, Question $question): RedirectResponse
    {
        $this->questions->update($question, $request->validated());

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đã cập nhật câu hỏi.');
    }

    public function destroy(Exam $exam, Question $question): RedirectResponse
    {
        $this->questions->delete($question);

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đã xoá câu hỏi.');
    }
}
