<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderQuestionsRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * Store a newly created question.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $question = Question::create($request->validated());

        return back()->with('success', 'Question created successfully');
    }

    /**
     * Update the specified question.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question->presentation);

        $question->update($request->validated());

        return back()->with('success', 'Question updated successfully');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question->presentation);

        $question->delete();

        return back()->with('success', 'Question deleted successfully');
    }

    /**
     * Reorder questions (for drag-and-drop).
     */
    public function reorder(ReorderQuestionsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->questions as $questionData) {
                Question::where('id', $questionData['id'])->update([
                    'order' => $questionData['order'],
                    'group_name' => $questionData['group_name'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Questions reordered successfully');
    }
}
