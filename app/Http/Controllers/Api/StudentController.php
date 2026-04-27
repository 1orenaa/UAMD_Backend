<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Regjistrim;
use App\Models\Seksion;
use App\Models\Student;
use App\Models\Provim;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Student', description: 'Endpoint-e të aksesueshme vetëm nga studentët e autentikuar')]
class StudentController extends Controller
{
    /**
     * Gjen studentin e lidhur me userin e autentikuar (me email).
     */
    private function getStudent(Request $request): ?Student
    {
        return Student::where('STD_EMAIL', $request->user()->email)->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  STATISTIKAT
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/student/statistikat',
        summary: 'Statistikat e studentit të autentikuar',
        tags: ['Student'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Statistikat: numri i lëndëve, provimeve, krediteve dhe profili'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin student'),
        ]
    )]
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

        $regjistrime = $student->regjistrime()->with('seksion.lende')->get();
        $lendeCount  = $regjistrime->count();
        $kredite     = $regjistrime->sum(fn($r) => $r->seksion?->lende?->LEN_KRED ?? 0);
        $sekIds      = $regjistrime->pluck('SEK_ID');
        $provimCount = Provim::whereIn('SEK_ID', $sekIds)
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

    // ─────────────────────────────────────────────────────────────────────────
    //  LËNDËT E REGJISTRUARA
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/student/lende',
        summary: 'Lista e lëndëve në të cilat studenti është regjistruar',
        tags: ['Student'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë me lëndët dhe seksionet përkatëse'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin student'),
        ]
    )]
    public function lende(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json(['message' => 'Profili i studentit nuk u gjet.', 'data' => []]);
        }

        $sotDate = now()->startOfDay();

        $lende = $student->regjistrime()
            ->with(['seksion.lende', 'seksion.pedagog'])
            ->get()
            ->map(function ($reg) use ($sotDate) {
                $seksion = $reg->seksion;
                $lende   = $seksion?->lende;
                $pedagog = $seksion?->pedagog;

                // Gjen fillimin e semestrit për këtë seksion
                $semester = $seksion
                    ? DB::table('SEMESTER')->where('SEM_ID', $seksion->SEM_ID)->first()
                    : null;

                $mundCregjistrohet = true;
                $fillSem           = null;

                if ($semester && $semester->SEM_DT_FILL) {
                    $fillSem = Carbon::parse($semester->SEM_DT_FILL)->startOfDay();
                    $mundCregjistrohet = $sotDate->lessThan($fillSem);
                }

                return [
                    'regjl_id'            => $reg->REGJL_ID,
                    'lende_em'            => $lende?->LEN_EM,
                    'lende_kod'           => $lende?->LEN_KOD,
                    'kredite'             => $lende?->LEN_KRED,
                    'pedagog'             => $pedagog
                        ? trim(($pedagog->PED_TIT ?? '') . ' ' . $pedagog->PED_EM)
                        : null,
                    'statusi'             => $reg->REGJL_STATUS,
                    'nota'                => $reg->REGJL_NOTA,
                    'mund_cregjistrohet'  => $mundCregjistrohet,
                    'fillimi_semestrit'   => $fillSem?->toDateString(),
                ];
            });

        return response()->json(['data' => $lende]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PROVIMET E ARDHSHME
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/student/provime',
        summary: 'Provimet e ardhshme për studentin',
        tags: ['Student'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë me provimet e ardhshme'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin student'),
        ]
    )]
    public function provime(Request $request)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json(['message' => 'Profili i studentit nuk u gjet.', 'data' => []]);
        }

        $sekIds  = $student->regjistrime()->pluck('SEK_ID');
        $provime = Provim::whereIn('SEK_ID', $sekIds)
            ->where('PRV_DBA', '>=', now()->toDateString())
            ->with('seksion.lende')
            ->orderBy('PRV_DBA')
            ->get()
            ->map(fn($p) => [
                'prv_id'   => $p->PRV_ID,
                'lenda'    => $p->seksion?->lende?->LEN_EM,
                'data'     => $p->PRV_DBA,
                'ora_fill' => $p->PRV_DTFILL,
                'ora_mba'  => $p->PRV_DTMBA,
                'lloji'    => $p->PRV_TIP,
            ]);

        return response()->json(['data' => $provime]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SEKSIONET E LIRA (për regjistrim)
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/student/seksione',
        summary: 'Seksionet e disponueshme (me status të regjistrimit)',
        tags: ['Student'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë seksionesh me `i_regjistruar` për secilin'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin student'),
        ]
    )]
    public function seksione(Request $request)
    {
        $student = $this->getStudent($request);

        // ID-të e seksioneve ku studenti është regjistruar tashmë
        $regjistruarNe = $student
            ? $student->regjistrime()->pluck('SEK_ID')->toArray()
            : [];

        // Të gjitha seksionet, me info të plotë
        $seksione = Seksion::with(['lende', 'pedagog'])
            ->orderBy('SEK_DATA')
            ->get()
            ->map(function ($s) use ($regjistruarNe) {
                return [
                    'sek_id'       => $s->SEK_ID,
                    'lende_em'     => $s->lende?->LEN_EM,
                    'lende_kod'    => $s->lende?->LEN_KOD,
                    'kredite'      => $s->lende?->LEN_KRED,
                    'pedagog'      => $s->pedagog
                        ? trim(($s->pedagog->PED_TIT ?? '') . ' ' . $s->pedagog->PED_EM)
                        : null,
                    'data'         => $s->SEK_DATA,
                    'ora_fillimit' => $s->SEK_DRAFILL,
                    'ora_mbarimit' => $s->SEK_DRAMBIA,
                    'i_regjistruar'=> in_array($s->SEK_ID, $regjistruarNe),
                ];
            });

        return response()->json(['data' => $seksione]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  REGJISTRIM NË SEKSION
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/student/regjistrim',
        summary: 'Regjistro studentin në një seksion',
        tags: ['Student'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sek_id'],
                properties: [
                    new OA\Property(property: 'sek_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Regjistrimi u krye'),
            new OA\Response(response: 409, description: 'Tashmë i regjistruar ose seksioni është plot'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
        ]
    )]
    public function regjistro(Request $request)
    {
        $request->validate([
            'sek_id' => 'required|integer|exists:SEKSION,SEK_ID',
        ]);

        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json([
                'message' => 'Profili i studentit nuk u gjet në sistem. Kontaktoni administratorin.',
            ], 404);
        }

        $seksionRi = Seksion::find($request->sek_id);

        // ── Kontrolli 1: A është regjistruar tashmë në këtë seksion? ──────────
        $ekziston = Regjistrim::where('STD_ID', $student->STD_ID)
            ->where('SEK_ID', $seksionRi->SEK_ID)
            ->exists();

        if ($ekziston) {
            return response()->json([
                'message' => 'Jeni regjistruar tashmë në këtë seksion.',
            ], 422);
        }

        // ── Kontrolli 2: Konflikt orari ───────────────────────────────────────
        // Gjej seksionet ekzistuese të studentit
        $seksionetEStudentit = Seksion::whereIn(
            'SEK_ID',
            $student->regjistrime()->pluck('SEK_ID')
        )->get();

        foreach ($seksionetEStudentit as $sek) {
            // Dy seksione mbivendosen nëse:
            // fillimi_ri < mbarimi_ekzistues AND fillimi_ekzistues < mbarimi_ri
            $mbivendoset =
                $seksionRi->SEK_DRAFILL < $sek->SEK_DRAMBIA &&
                $sek->SEK_DRAFILL       < $seksionRi->SEK_DRAMBIA;

            if ($mbivendoset) {
                return response()->json([
                    'message'   => 'Konflikte orari — keni tashmë një seksion në të njëjtën kohë.',
                    'konflikt'  => [
                        'sek_id'        => $sek->SEK_ID,
                        'lenda'         => $sek->lende?->LEN_EM,
                        'ora_fillimit'  => $sek->SEK_DRAFILL,
                        'ora_mbarimit'  => $sek->SEK_DRAMBIA,
                    ],
                ], 422);
            }
        }

        // ── Regjistro ─────────────────────────────────────────────────────────
        $regjistrim = Regjistrim::create([
            'STD_ID'       => $student->STD_ID,
            'SEK_ID'       => $seksionRi->SEK_ID,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
        ]);

        return response()->json([
            'message'    => 'Regjistrimi u krye me sukses.',
            'regjl_id'   => $regjistrim->REGJL_ID,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ÇREGJISTRIM NGA SEKSIONI
    // ─────────────────────────────────────────────────────────────────────────

    #[OA\Delete(
        path: '/student/regjistrim/{id}',
        summary: 'Çregjistro studentin nga një seksion (vetëm përpara fillimit të semestrit)',
        tags: ['Student'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID e regjistrimit (REGJL_ID)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Çregjistrimi u krye'),
            new OA\Response(response: 403, description: 'Semestri ka filluar — nuk lejohet çregjistrimi'),
            new OA\Response(response: 404, description: 'Regjistrimi nuk u gjet ose nuk i përket studentit aktual'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
        ]
    )]
    public function cregjistro(Request $request, $regjlId)
    {
        $student = $this->getStudent($request);

        if (!$student) {
            return response()->json(['message' => 'Profili i studentit nuk u gjet.'], 404);
        }

        $regjistrim = Regjistrim::where('REGJL_ID', $regjlId)
            ->where('STD_ID', $student->STD_ID)
            ->first();

        if (!$regjistrim) {
            return response()->json([
                'message' => 'Regjistrimi nuk u gjet ose nuk ju përket juve.',
            ], 404);
        }

        // ── Kontroll: semestri nuk duhet të ketë filluar ────────────────────
        // Gjen semestrin e seksionit përkatës dhe krahason datën e fillimit me sot.
        $seksioni = Seksion::find($regjistrim->SEK_ID);
        $semester = $seksioni
            ? DB::table('SEMESTER')->where('SEM_ID', $seksioni->SEM_ID)->first()
            : null;

        if ($semester && $semester->SEM_DT_FILL) {
            $sotDate       = now()->startOfDay();
            $fillimiSemest = Carbon::parse($semester->SEM_DT_FILL)->startOfDay();

            if ($sotDate->greaterThanOrEqualTo($fillimiSemest)) {
                return response()->json([
                    'message' => 'Semestri ka filluar — çregjistrimi nuk lejohet më. '
                        . 'Çregjistrimi duhet të bëhet përpara datës ' . $fillimiSemest->format('d/m/Y') . '.',
                    'fillimi_semestrit' => $fillimiSemest->toDateString(),
                ], 403);
            }
        }

        $regjistrim->delete();

        return response()->json(['message' => 'Çregjistrimi u krye me sukses.']);
    }
}
