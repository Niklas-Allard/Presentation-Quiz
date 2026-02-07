<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $participantId = $request->header('X-Participant-ID');

        if (! $participantId) {
            return response()->json([
                'message' => 'Participant ID is required.',
            ], 401);
        }

        $participant = Participant::find($participantId);

        if (! $participant) {
            return response()->json([
                'message' => 'Invalid participant ID.',
            ], 401);
        }

        // Attach participant to request for easy access in controllers
        $request->merge(['participant' => $participant]);

        return $next($request);
    }
}
