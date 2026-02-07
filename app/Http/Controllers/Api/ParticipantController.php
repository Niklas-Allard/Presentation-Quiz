<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParticipantRequest;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;

class ParticipantController extends Controller
{
    /**
     * Store a newly created participant in storage.
     */
    public function store(StoreParticipantRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $participant = Participant::create([
            'presentation_id' => $validated['presentation_id'],
            'display_name' => $validated['display_name'],
            'score' => 0,
        ]);

        return response()->json([
            'participant_id' => $participant->id,
            'display_name' => $participant->display_name,
            'presentation_id' => $participant->presentation_id,
        ], 201);
    }
}
