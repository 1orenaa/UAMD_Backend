<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedagog;
use App\Models\Provim;
use Illuminate\Http\Request;

class PedagogController extends Controller
{
    /**
     * Gjen pedagogun e lidhur me userin e autentikuar (me email).
     */
    private function getPedag(Request $request)
    {
        return Pedagog::where('PED_EMAIL', $request->user()->email)->first();
    }

    /**
     * Seksionet që ligjëron pedagogu.
     */
    public function seksione(Request $request)
    {
        $pedagog = $this->getPedag($request);

        if (!$pedagog) {
            return response()->json([
                'message' => 'Profili i pedagogut nuk u gjet.',
                'data'    => [],
            ]);
        }

        $seksione = $pedagog->seksione()
            ->with('lende')
            ->orderBy('SEK_DATA')
            ->get()
            ->map(function ($s) {
                return [
                    'sek_id'   => $s->SEK_ID,
                    'lenda'    => $s->lende?->LEN_EM,
                    'data'     => $s->SEK_DATA,
                    'ora_fill' => $s->SEK_DRAFILL,
                    'ora_mba'  => $s->SEK_DRAMBIA,
                ];
            });

        return response()->json(['data' => $seksione]);
    }

    /**
     * Provimet e ardhshme për seksionet e pedagogut.
     */
    public function provime(Request $request)
    {
        $pedagog = $this->getPedag($request);

        if (!$pedagog) {
            return response()->json([
                'message' => 'Profili i pedagogut nuk u gjet.',
                'data'    => [],
            ]);
        }

        $sekIds  = $pedagog->seksione()->pluck('SEK_ID');

        $provime = Provim::whereIn('SEK_ID', $sekIds)
            ->where('PRV_DBA', '>=', now()->toDateString())
            ->with('seksion.lende')
            ->orderBy('PRV_DBA')
            ->get()
            ->map(function ($p) {
                return [
                    'prv_id'   => $p->PRV_ID,
                    'lenda'    => $p->seksion?->lende?->LEN_EM,
                    'data'     => $p->PRV_DBA,
                    'ora_fill' => $p->PRV_DTFILL,
                    'ora_mba'  => $p->PRV_DTMBA,
                    'lloji'    => $p->PRV_TIP,
                ];
            });

        return response()->json(['data' => $provime]);
    }

    /**
     * Statistikat e pedagogut.
     */
    public function statistikat(Request $request)
    {
        $pedagog = $this->getPedag($request);

        if (!$pedagog) {
            return response()->json([
                'seksion_count' => 0,
                'provim_count'  => 0,
                'student_count' => 0,
                'profili'       => null,
            ]);
        }

        $sekIds       = $pedagog->seksione()->pluck('SEK_ID');
        $seksionCount = $sekIds->count();
        $provimCount  = Provim::whereIn('SEK_ID', $sekIds)
                            ->where('PRV_DBA', '>=', now()->toDateString())
                            ->count();

        // Numri i studentëve unikë nëpër seksionet e pedagogut
        $studentCount = \App\Models\Regjistrim::whereIn('SEK_ID', $sekIds)
                            ->distinct('STD_ID')
                            ->count('STD_ID');

        return response()->json([
            'seksion_count' => $seksionCount,
            'provim_count'  => $provimCount,
            'student_count' => $studentCount,
            'profili'       => [
                'emri'   => trim(($pedagog->PED_TIT ?? '') . ' ' . $pedagog->PED_EM . ' ' . $pedagog->PED_MR),
                'email'  => $pedagog->PED_EMAIL,
                'titulli'=> $pedagog->PED_TIT,
            ],
        ]);
    }
}
