<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Provim;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Gjen studentin e lidhur me userin e autentikuar (me email).
     */
    private function getStudent(Request $request)
    {
        return Student::where('STD_EMAIL', $request->user()->email)->first();
    }

    /**
     * Lëndët e regjistruara të studentit (nëpërmjet REGJISTRIM → SEKSION → LENDE).
     */
    public function lende(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'message' => 'Profili i studentit nuk u gjet.',
                'data'    => [],
            ]);
        }

        $lende = $student->regjistrime()
            ->with([
                'seksion.lende',
                'seksion.pedagog',
            ])
            ->get()
            ->map(function ($reg) {
                $seksion = $reg->seksion;
                $lende   = $seksion?->lende;
                $pedagog = $seksion?->pedagog;

                return [
                    'regjl_id'  => $reg->REGJL_ID,
                    'lende_em'  => $lende?->LEN_EM,
                    'lende_kod' => $lende?->LEN_KOD,
                    'kreditë'   => $lende?->LEN_KRED,
                    'pedagog'   => $pedagog
                        ? trim(($pedagog->PED_TIT ?? '') . ' ' . $pedagog->PED_EM . ' ' . $pedagog->PED_MR)
                        : null,
                    'statusi'   => $reg->REGJL_STATUS,
                    'nota'      => $reg->REGJL_NOTA,
                ];
            });

        return response()->json(['data' => $lende]);
    }

    /**
     * Provimet e ardhshme të studentit.
     */
    public function provime(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'message' => 'Profili i studentit nuk u gjet.',
                'data'    => [],
            ]);
        }

        $sekedIds = $student->regjistrime()->pluck('SEK_ID');

        $provime = Provim::whereIn('SEK_ID', $sekedIds)
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
     * Statistikat e studentit (numri i lëndëve, provimeve, krediteve).
     */
    public function statistikat(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'lende_count'  => 0,
                'provim_count' => 0,
                'kredite'      => 0,
                'profili'      => null,
            ]);
        }

        $regjistrime  = $student->regjistrime()->with('seksion.lende')->get();
        $lendeCount   = $regjistrime->count();
        $kredite      = $regjistrime->sum(fn($r) => $r->seksion?->lende?->LEN_KRED ?? 0);
        $sekIds       = $regjistrime->pluck('SEK_ID');
        $provimCount  = Provim::whereIn('SEK_ID', $sekIds)
                            ->where('PRV_DBA', '>=', now()->toDateString())
                            ->count();

        return response()->json([
            'lende_count'  => $lendeCount,
            'provim_count' => $provimCount,
            'kredite'      => $kredite,
            'profili'      => [
                'emri'   => $student->STD_EM . ' ' . $student->STD_MB,
                'email'  => $student->STD_EMAIL,
                'gjinia' => $student->STD_GJINI,
            ],
        ]);
    }
}
