<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departament;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin', description: 'Endpoint-e për menaxhimin e studentëve nga administratori')]
class AdminStudentController extends Controller
{
    #[OA\Get(
        path: '/admin/studentet',
        summary: 'Listo studentët me paginim dhe kërkim',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listë me studentë e paginuar'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
            new OA\Response(response: 403, description: 'Nuk ke rolin admin'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search  = $request->get('search');

        $query = Student::with('departament')
            ->orderBy('STD_MB')
            ->orderBy('STD_EM');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('STD_EM',    'like', "%{$search}%")
                  ->orWhere('STD_MB',  'like', "%{$search}%")
                  ->orWhere('STD_EMAIL','like', "%{$search}%");
            });
        }

        $studentet = $query->paginate($perPage)->through(function ($s) {
            return $this->format($s);
        });

        return response()->json($studentet);
    }

    #[OA\Get(
        path: '/admin/studentet/{id}',
        summary: 'Detajet e një studenti',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detajet e studentit'),
            new OA\Response(response: 404, description: 'Studenti nuk u gjet'),
        ]
    )]
    public function show($id)
    {
        $student = Student::with(['departament', 'regjistrime.seksion.lende'])->find($id);

        if (!$student) {
            return response()->json(['message' => 'Studenti nuk u gjet.'], 404);
        }

        return response()->json(['data' => $this->format($student, true)]);
    }

    #[OA\Post(
        path: '/admin/studentet',
        summary: 'Shto student të ri',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['STD_EM', 'STD_MB', 'STD_EMAIL', 'DEP_ID'],
                properties: [
                    new OA\Property(property: 'STD_EM',    type: 'string', example: 'Anisa'),
                    new OA\Property(property: 'STD_MB',    type: 'string', example: 'Hoxha'),
                    new OA\Property(property: 'STD_EMAIL', type: 'string', example: 'anisa@uamd.edu.al'),
                    new OA\Property(property: 'DEP_ID',    type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Studenti u krijua'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'STD_EM'    => 'required|string|max:100',
            'STD_MB'    => 'required|string|max:100',
            'STD_EMAIL' => 'required|email|max:150|unique:STUDENT,STD_EMAIL',
            'STD_DTL'   => 'nullable|date',
            'STD_GJINI' => 'nullable|in:M,F',
            'DEP_ID'    => 'required|integer|exists:DEPARTAMENT,DEP_ID',
        ]);

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Studenti u shtua me sukses.',
            'data'    => $this->format($student),
        ], 201);
    }

    #[OA\Put(
        path: '/admin/studentet/{id}',
        summary: 'Përditëso të dhënat e studentit',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'STD_EM',    type: 'string'),
                    new OA\Property(property: 'STD_MB',    type: 'string'),
                    new OA\Property(property: 'STD_EMAIL', type: 'string'),
                    new OA\Property(property: 'DEP_ID',    type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Studenti u përditësua'),
            new OA\Response(response: 404, description: 'Studenti nuk u gjet'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
        ]
    )]
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Studenti nuk u gjet.'], 404);
        }

        $validated = $request->validate([
            'STD_EM'    => 'sometimes|string|max:100',
            'STD_MB'    => 'sometimes|string|max:100',
            'STD_EMAIL' => [
                'sometimes', 'email', 'max:150',
                Rule::unique('STUDENT', 'STD_EMAIL')->ignore($student->STD_ID, 'STD_ID'),
            ],
            'STD_DTL'   => 'nullable|date',
            'STD_GJINI' => 'nullable|in:M,F',
            'DEP_ID'    => 'sometimes|integer|exists:DEPARTAMENT,DEP_ID',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Të dhënat u përditësuan.',
            'data'    => $this->format($student->fresh()),
        ]);
    }

    #[OA\Delete(
        path: '/admin/studentet/{id}',
        summary: 'Fshi studentin',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Studenti u fshi'),
            new OA\Response(response: 404, description: 'Studenti nuk u gjet'),
            new OA\Response(response: 409, description: 'Studenti ka regjistrime aktive'),
        ]
    )]
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Studenti nuk u gjet.'], 404);
        }

        // Kontrollo nëse studenti ka regjistrime aktive
        $hasRegjistrime = $student->regjistrime()->exists();
        if ($hasRegjistrime) {
            return response()->json([
                'message' => 'Nuk mund të fshihet — studenti ka regjistrime aktive.',
            ], 422);
        }

        $student->delete();

        return response()->json(['message' => 'Studenti u fshi me sukses.']);
    }

    #[OA\Get(
        path: '/admin/departamente',
        summary: 'Listo departamentet (për dropdown)',
        tags: ['Admin'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listë e departamenteve'),
        ]
    )]
    public function departamente()
    {
        $dep = Departament::orderBy('DEP_EM')
            ->get(['DEP_ID', 'DEP_EM'])
            ->map(fn ($d) => [
                'dep_id' => $d->DEP_ID,
                'dep_em' => $d->DEP_EM,
            ]);

        return response()->json(['data' => $dep]);
    }

    /**
     * Formon të dhënat e studentit për përgjigje JSON.
     */
    private function format(Student $s, bool $withDetails = false): array
    {
        $data = [
            'std_id'    => $s->STD_ID,
            'emri'      => $s->STD_EM . ' ' . $s->STD_MB,
            'email'     => $s->STD_EMAIL,
            'gjinia'    => $s->STD_GJINI,
            'datelindja'=> $s->STD_DTL,
            'departament' => $s->departament?->DEP_EM,
            'dep_id'    => $s->DEP_ID,
        ];

        if ($withDetails && $s->relationLoaded('regjistrime')) {
            $data['lende_count']    = $s->regjistrime->count();
            $data['kredite_totale'] = $s->regjistrime->sum(
                fn($r) => $r->seksion?->lende?->LEN_KRED ?? 0
            );
        }

        return $data;
    }
}
