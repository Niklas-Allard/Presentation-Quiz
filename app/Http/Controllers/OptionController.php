<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOptionRequest;
use App\Http\Requests\UpdateOptionRequest;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;

class OptionController extends Controller
{
    /**
     * Store a newly created option.
     */
    public function store(StoreOptionRequest $request): RedirectResponse
    {
        $option = Option::create($request->validated());

        return back()->with('success', 'Option created successfully');
    }

    /**
     * Update the specified option.
     */
    public function update(UpdateOptionRequest $request, Option $option): RedirectResponse
    {
        $this->authorize('update', $option->question->presentation);

        $option->update($request->validated());

        return back()->with('success', 'Option updated successfully');
    }

    /**
     * Remove the specified option.
     */
    public function destroy(Option $option): RedirectResponse
    {
        $this->authorize('delete', $option->question->presentation);

        $option->delete();

        return back()->with('success', 'Option deleted successfully');
    }
}
