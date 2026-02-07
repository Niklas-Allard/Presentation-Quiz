<?php

namespace App\Http\Controllers;

use App\Models\Presentation;
use Inertia\Inertia;
use Inertia\Response;

class PresentationViewController extends Controller
{
    /**
     * Show the presentation control panel.
     */
    public function control(Presentation $presentation): Response
    {
        $presentation->load(['questions.options']);

        return Inertia::render('Presentations/Control', [
            'presentation' => $presentation,
        ]);
    }

    /**
     * Show the full-screen presentation mode for screen sharing.
     */
    public function present(Presentation $presentation): Response
    {
        $presentation->load(['questions.options']);

        return Inertia::render('Presentations/Present', [
            'presentation' => $presentation,
        ]);
    }
}
