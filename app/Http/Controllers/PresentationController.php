<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresentationRequest;
use App\Http\Requests\UpdatePresentationRequest;
use App\Models\Presentation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PresentationController extends Controller
{
    /**
     * Display a listing of presentations for the authenticated user.
     */
    public function index(): Response
    {
        $presentations = Auth::user()
            ->presentations()
            ->withCount('questions')
            ->latest()
            ->get();

        return Inertia::render('Presentations/Index', [
            'presentations' => $presentations,
        ]);
    }

    /**
     * Show the form for creating a new presentation.
     */
    public function create(): Response
    {
        return Inertia::render('Presentations/Create');
    }

    /**
     * Store a newly created presentation.
     */
    public function store(StorePresentationRequest $request): RedirectResponse
    {
        $presentation = Auth::user()->presentations()->create([
            'title' => $request->title,
            'description' => $request->description,
            'admin_code' => Str::random(8),
            'status' => 'draft',
        ]);

        return redirect()
            ->route('presentations.edit', $presentation)
            ->with('success', 'Presentation created successfully');
    }

    /**
     * Show the form for editing the specified presentation.
     */
    public function edit(Presentation $presentation): Response
    {
        $this->authorize('update', $presentation);

        $presentation->load(['questions.options']);

        return Inertia::render('Presentations/Edit', [
            'presentation' => $presentation,
        ]);
    }

    /**
     * Update the specified presentation.
     */
    public function update(UpdatePresentationRequest $request, Presentation $presentation): RedirectResponse
    {
        $this->authorize('update', $presentation);

        $presentation->update($request->validated());

        return back()->with('success', 'Presentation updated successfully');
    }

    /**
     * Remove the specified presentation.
     */
    public function destroy(Presentation $presentation): RedirectResponse
    {
        $this->authorize('delete', $presentation);

        $presentation->delete();

        return redirect()
            ->route('presentations.index')
            ->with('success', 'Presentation deleted successfully');
    }
}
