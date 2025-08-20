<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function public(string $qrCodeUuid)
    {
        $tournament = Tournament::where('qr_code_uuid', $qrCodeUuid)
            ->with(['teams.player1', 'teams.player2', 'rounds'])
            ->firstOrFail();

        $standings = $tournament->calculateStandings();

        $completedRounds = $tournament->rounds()
            ->where('status', 'completed')
            ->orderBy('round_number')
            ->get();

        return Inertia::render('Leaderboard/Public', [
            'tournament' => $tournament,
            'standings' => $standings,
            'completedRounds' => $completedRounds,
            'lastUpdated' => now()->toISOString(),
        ]);
    }

    public function refresh(string $qrCodeUuid)
    {
        $tournament = Tournament::where('qr_code_uuid', $qrCodeUuid)->firstOrFail();
        
        // If this is an AJAX request, return JSON data
        if (request()->wantsJson()) {
            $standings = $tournament->calculateStandings();

            return response()->json([
                'standings' => $standings,
                'lastUpdated' => now()->toISOString(),
            ]);
        }

        return redirect()->route('tournaments.leaderboard.public', $qrCodeUuid);
    }
}
