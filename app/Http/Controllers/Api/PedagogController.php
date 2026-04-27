<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedagog;
use App\Models\Provim;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pedagog', description: 'Endpoint-e të aksesueshme vetëm nga pedagogët e autentikuar')]
class PedagogController extends Controller
{
    /**
     * Gjen pedagogun e lidhur me userin e autentikuar (me email).
     */
    private function getPedag(Request $request)
    {
        return Pedagog::where('PED_EMAIL', $request->user()->email)->first();
    }

    #[OA\Get(
        path: '/pedagog/seksione',
        summary: 'Seksionet që ligjëron pedagogu',
        tags: ['Pedagog'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë seksionesh të ligjëruara'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin pedagog'),
        ]
    )]
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

    #[OA\Get(
        path: '/pedagog/provime',
        summary: 'Provimet e ardhshme për seksionet e pedagogut',
        tags: ['Pedagog'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë provimesh të ardhshme'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin pedagog'),
        ]
    )]
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

    #[OA\Get(
        path: '/pedagog/statistikat',
        summary: 'Statistikat e pedagogut (numri i seksioneve, lëndëve, etj.)',
        tags: ['Pedagog'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Statistikat e pedagogut'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin pedagog'),
        ]
    )]
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
