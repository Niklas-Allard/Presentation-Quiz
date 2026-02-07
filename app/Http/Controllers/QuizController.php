<?php

namespace App\Http\Controllers;

use App\Models\Presentation;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    /**
     * Show the join page for a presentation.
     */
    public function join(Presentation $presentation): Response
    {
        return Inertia::render('Quiz/Join', [
            'presentation' => $presentation,
        ]);
    }

    /**
     * Show the quiz play page.
     */
    public function play(Presentation $presentation): Response
    {
        return Inertia::render('Quiz/Play', [
            'presentationId' => $presentation->id,
            'presentationTitle' => $presentation->title,
        ]);
    }
}
