<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Regjistrim;
use App\Models\Seksion;
use App\Models\Student;
use App\Models\Provim;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
     * Seksionet ku studenti nuk eshte regjistruar ende.
     */
    public function seksione(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'message' => 'Profili i studentit nuk u gjet.',
                'data' => [],
            ], 404);
        }

        $regjistruarIds = $student->regjistrime()->pluck('SEK_ID');

        $seksione = Seksion::with(['lende', 'pedagog', 'salle'])
            ->withCount('regjistrime')
            ->when($regjistruarIds->isNotEmpty(), function ($query) use ($regjistruarIds) {
                $query->whereNotIn('SEK_ID', $regjistruarIds);
            })
            ->orderBy('SEK_DRAFILL')
            ->get()
            ->map(function ($s) {
                $kapaciteti = $s->salle?->SALLE_KAP;
                $teRegjistruar = $s->regjistrime_count ?? 0;

                return [
                    'sek_id' => $s->SEK_ID,
                    'lenda' => $s->lende?->LEN_EM,
                    'lenda_kod' => $s->lende?->LEN_KOD,
                    'kredite' => $s->lende?->LEN_KRED,
                    'pedagog' => $s->pedagog
                        ? trim(($s->pedagog->PED_TIT ?? '') . ' ' . $s->pedagog->PED_EM . ' ' . $s->pedagog->PED_MR)
                        : null,
                    'data' => $s->SEK_DATA,
                    'ora_fill' => $s->SEK_DRAFILL ? Carbon::parse($s->SEK_DRAFILL)->format('H:i') : null,
                    'ora_mba' => $s->SEK_DRAMBIA ? Carbon::parse($s->SEK_DRAMBIA)->format('H:i') : null,
                    'salla' => $s->salle?->SALLE_EM,
                    'kapaciteti' => $kapaciteti,
                    'te_regjistruar' => $teRegjistruar,
                    'vende_te_lira' => $kapaciteti ? max($kapaciteti - $teRegjistruar, 0) : null,
                ];
            })
            ->filter(function ($s) {
                return $s['vende_te_lira'] === null || $s['vende_te_lira'] > 0;
            })
            ->values();

        return response()->json(['data' => $seksione]);
    }

    /**
     * Regjistron studentin ne nje seksion te lire.
     */
    public function regjistrohu(Request $request, int $sekId)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'message' => 'Profili i studentit nuk u gjet.',
            ], 404);
        }

        $seksion = Seksion::with(['lende', 'salle'])->withCount('regjistrime')->find($sekId);

        if (!$seksion) {
            return response()->json([
                'message' => 'Seksioni nuk u gjet.',
            ], 404);
        }

        $ekziston = Regjistrim::where('STD_ID', $student->STD_ID)
            ->where('SEK_ID', $seksion->SEK_ID)
            ->exists();

        if ($ekziston) {
            return response()->json([
                'message' => 'Jeni regjistruar tashme ne kete seksion.',
            ], 422);
        }

        $kapaciteti = $seksion->salle?->SALLE_KAP;
        $teRegjistruar = $seksion->regjistrime_count ?? 0;

        if ($kapaciteti !== null && $teRegjistruar >= $kapaciteti) {
            return response()->json([
                'message' => 'Ky seksion nuk ka me vende te lira.',
            ], 422);
        }

        $regjistrim = Regjistrim::create([
            'REGJL_DT' => now()->toDateString(),
            'REGJL_STATUS' => 'Aktiv',
            'STD_ID' => $student->STD_ID,
            'SEK_ID' => $seksion->SEK_ID,
        ]);

        return response()->json([
            'message' => 'Regjistrimi u krye me sukses.',
            'data' => [
                'regjl_id' => $regjistrim->REGJL_ID,
                'sek_id' => $seksion->SEK_ID,
                'lenda' => $seksion->lende?->LEN_EM,
            ],
        ], 201);
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
