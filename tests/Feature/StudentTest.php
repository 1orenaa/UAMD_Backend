<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Krijon strukturen minimale te dhenash akademike
     * dhe e kthen me ID-te perkatese te gateshme per teste.
     *
     * @return array{fak_id:int, dep_id:int, ped_id:int, salle_id:int, len_id:int, vit_id:int, sem_id:int}
     */
    private function seedStrukturen(): array
    {
        $fakId = DB::table('FAKULTET')->insertGetId([
            'FAK_EM'     => 'Teknologjia e Informacionit',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'FAK_ID');

        $depId = DB::table('DEPARTAMENT')->insertGetId([
            'DEP_EM'     => 'Informatike',
            'FAK_ID'     => $fakId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'DEP_ID');

        $pedId = DB::table('PEDAGOG')->insertGetId([
            'PED_EM'     => 'Arben',
            'PED_MR'     => 'Berisha',
            'PED_EMAIL'  => 'arben@uamd.edu.al',
            'PED_TIT'    => 'Prof. Dr.',
            'DEP_ID'     => $depId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'PED_ID');

        $salleId = DB::table('SALLE')->insertGetId([
            'SALLE_EM'   => 'A-201',
            'SALLE_KAP'  => 40,
            'FAK_ID'     => $fakId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'SALLE_ID');

        $lenId = DB::table('LENDE')->insertGetId([
            'LEN_EM'     => 'Programim ne Web',
            'LEN_KOD'    => 'PAW301',
            'LEN_KRED'   => 6,
            'DEP_ID'     => $depId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'LEN_ID');

        // Nga default semestri fillon 14 ditë në të ardhmen, që çregjistrimi
        // të lejohet. Testet specifike e override-ojnë këtë fushë.
        $fillSem = now()->addDays(14)->toDateString();
        $mbrSem  = now()->addDays(120)->toDateString();

        $vitId = DB::table('VIT_AKADEMIK')->insertGetId([
            'VIT_EM'      => '2026/2027',
            'VIT_DT_FILL' => $fillSem,
            'VIT_DT_MBR'  => now()->addDays(300)->toDateString(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ], 'VIT_ID');

        $semId = DB::table('SEMESTER')->insertGetId([
            'SEM_NR'      => 1,
            'SEM_DT_FILL' => $fillSem,
            'SEM_DT_MBR'  => $mbrSem,
            'VIT_ID'      => $vitId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ], 'SEM_ID');

        return compact('fakId', 'depId', 'pedId', 'salleId', 'lenId', 'vitId', 'semId');
    }

    /**
     * Ndryshon datën e fillimit të semestrit për testet që duan ta simulojnë
     * semestrin si të filluar (past date) ose jo (future date).
     */
    private function setFillimiSemestrit(int $semId, string $date): void
    {
        DB::table('SEMESTER')
            ->where('SEM_ID', $semId)
            ->update(['SEM_DT_FILL' => $date, 'updated_at' => now()]);
    }

    private function krijoSeksion(array $s, string $fill = '2026-03-01 09:00:00', string $mbari = '2026-03-01 11:00:00'): int
    {
        return DB::table('SEKSION')->insertGetId([
            'SEK_DATA'    => substr($fill, 0, 10),
            'SEK_DRAFILL' => $fill,
            'SEK_DRAMBIA' => $mbari,
            'PED_ID'      => $s['pedId'],
            'SALLE_ID'    => $s['salleId'],
            'LEN_ID'      => $s['lenId'],
            'SEM_ID'      => $s['semId'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ], 'SEK_ID');
    }

    /**
     * Krijon user me rol student (dhe opsionalisht rreshtin perkates ne STUDENT)
     * dhe kthen [user, token, student_id].
     */
    private function krijoStudentMeUser(int $depId, string $email = 'erisa@uamd.edu.al'): array
    {
        $user = User::create([
            'name'     => 'Erisa Krasniqi',
            'email'    => $email,
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $stdId = DB::table('STUDENT')->insertGetId([
            'STD_EM'     => 'Erisa',
            'STD_MB'     => 'Krasniqi',
            'STD_EMAIL'  => $email,
            'DEP_ID'     => $depId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'STD_ID');

        $token = $user->createToken('test')->plainTextToken;

        return [$user, $token, $stdId];
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ROLE GUARD
    // ─────────────────────────────────────────────────────────────────────────

    public function test_student_endpoint_pa_autentikim_kthen_401(): void
    {
        $this->getJson('/api/student/statistikat')->assertStatus(401);
        $this->getJson('/api/student/seksione')->assertStatus(401);
    }

    public function test_student_endpoint_me_rol_pedagog_kthen_403(): void
    {
        $user = User::create([
            'name'     => 'Pedagog',
            'email'    => 'ped@uamd.edu.al',
            'password' => Hash::make('secret123'),
            'role'     => 'pedagog',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/student/statistikat')
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  STATISTIKAT
    // ─────────────────────────────────────────────────────────────────────────

    public function test_statistikat_kthen_zero_per_user_pa_profil_studenti(): void
    {
        // Useri ka rol student por nuk ka rresht ne tabelen STUDENT me kete email
        $user = User::create([
            'name'     => 'Orphan',
            'email'    => 'orphan@uamd.edu.al',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/student/statistikat');

        $response->assertStatus(200)
            ->assertJson([
                'lende_count'  => 0,
                'provim_count' => 0,
                'kredite'      => 0,
                'profili'      => null,
            ]);
    }

    public function test_statistikat_numeron_lendet_dhe_kredite(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        $sekId = $this->krijoSeksion($s);

        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/student/statistikat');

        $response->assertStatus(200)
            ->assertJsonPath('lende_count', 1)
            ->assertJsonPath('kredite', 6);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SEKSIONET
    // ─────────────────────────────────────────────────────────────────────────

    public function test_seksione_liston_me_i_regjistruar_flag(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        $sekIdA = $this->krijoSeksion($s, '2026-03-01 09:00:00', '2026-03-01 11:00:00');
        $sekIdB = $this->krijoSeksion($s, '2026-03-02 12:00:00', '2026-03-02 14:00:00');

        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekIdA,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/student/seksione');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $a = collect($data)->firstWhere('sek_id', $sekIdA);
        $b = collect($data)->firstWhere('sek_id', $sekIdB);

        $this->assertTrue($a['i_regjistruar']);
        $this->assertFalse($b['i_regjistruar']);
        $this->assertSame('Programim ne Web', $a['lende_em']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  REGJISTRIM
    // ─────────────────────────────────────────────────────────────────────────

    public function test_regjistro_krijon_regjistrim_te_ri(): void
    {
        $s = $this->seedStrukturen();
        [, $token] = $this->krijoStudentMeUser($s['depId']);
        $sekId = $this->krijoSeksion($s);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekId]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'regjl_id']);

        $this->assertDatabaseHas('REGJISTRIM', [
            'SEK_ID' => $sekId,
        ]);
    }

    public function test_regjistro_refuzon_regjistrim_duplikat(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);
        $sekId = $this->krijoSeksion($s);

        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekId]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Jeni regjistruar tashmë në këtë seksion.');
    }

    public function test_regjistro_detekton_konflikt_orari(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Seksion A: 09:00 - 11:00
        $sekIdA = $this->krijoSeksion($s, '2026-03-01 09:00:00', '2026-03-01 11:00:00');

        // Seksion B: 10:00 - 12:00 (mbivendoset me A)
        $sekIdB = $this->krijoSeksion($s, '2026-03-01 10:00:00', '2026-03-01 12:00:00');

        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekIdA,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdB]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'konflikt']);
    }

    /**
     * Dy LËNDË të ndryshme në të njëjtën DITË dhe ORË
     * duhet të detektohen si konflikt.
     */
    public function test_regjistro_detekton_konflikt_edhe_per_lende_te_ndryshme(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Lëndë e dytë, e ndryshme nga e para
        $lenIdB = DB::table('LENDE')->insertGetId([
            'LEN_EM'     => 'Bazat e te Dhenave',
            'LEN_KOD'    => 'BDD201',
            'LEN_KRED'   => 5,
            'DEP_ID'     => $s['depId'],
            'created_at' => now(),
            'updated_at' => now(),
        ], 'LEN_ID');

        // Seksion A: Programim në Web, 09:00 - 11:00 më 1 Mars
        $sekIdA = $this->krijoSeksion($s, '2026-03-01 09:00:00', '2026-03-01 11:00:00');

        // Seksion B: Bazat e të Dhënave, E NJËJTA DITË + E NJËJTA ORË
        $sekIdB = DB::table('SEKSION')->insertGetId([
            'SEK_DATA'    => '2026-03-01',
            'SEK_DRAFILL' => '2026-03-01 09:00:00',
            'SEK_DRAMBIA' => '2026-03-01 11:00:00',
            'PED_ID'      => $s['pedId'],
            'SALLE_ID'    => $s['salleId'],
            'LEN_ID'      => $lenIdB,
            'SEM_ID'      => $s['semId'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ], 'SEK_ID');

        // Regjistro studentin në A
        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekIdA,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Përpjekja për regjistrim në B (lëndë ndryshe por të njëjtat orë+ditë)
        // duhet të refuzohet me 422 + detaje konflikti
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdB]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'konflikt' => ['sek_id', 'lenda', 'ora_fillimit', 'ora_mbarimit']])
            ->assertJsonPath('konflikt.sek_id', $sekIdA)
            ->assertJsonPath('konflikt.lenda', 'Programim ne Web');

        // Studenti nuk duhet të jetë regjistruar në B
        $this->assertDatabaseMissing('REGJISTRIM', [
            'STD_ID' => $stdId,
            'SEK_ID' => $sekIdB,
        ]);
    }

    /**
     * Dy seksione me të njëjtën ORË por në DITË të ndryshme
     * NUK duhet të jenë konflikt — studenti mund të regjistrohet në të dyja.
     */
    public function test_regjistro_nuk_ka_konflikt_kur_datat_jane_te_ndryshme(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // E Hënë 09:00-11:00
        $sekIdA = $this->krijoSeksion($s, '2026-03-02 09:00:00', '2026-03-02 11:00:00');

        // E Martë 09:00-11:00 — e njëjta orë, ditë tjetër
        $sekIdB = $this->krijoSeksion($s, '2026-03-03 09:00:00', '2026-03-03 11:00:00');

        DB::table('REGJISTRIM')->insert([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekIdA,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdB]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('REGJISTRIM', [
            'STD_ID' => $stdId,
            'SEK_ID' => $sekIdB,
        ]);
    }

    /**
     * Skenar real: studenti regjistrohet → e kupton se e zgjodhi gabim →
     * çregjistrohet → mund të regjistrohet përsëri në një seksion tjetër
     * që përpara ishte në konflikt.
     */
    public function test_student_mund_te_rregjistrohet_pas_cregjistrimit(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Dy seksione që mbivendosen
        $sekIdA = $this->krijoSeksion($s, '2026-03-01 09:00:00', '2026-03-01 11:00:00');
        $sekIdB = $this->krijoSeksion($s, '2026-03-01 10:00:00', '2026-03-01 12:00:00');

        // 1) Regjistro në A
        $r1 = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdA]);
        $r1->assertStatus(201);
        $regjlId = $r1->json('regjl_id');

        // 2) Përpiqu të regjistrohesh në B → konflikt
        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdB])
            ->assertStatus(422);

        // 3) Çregjistrohu nga A (e kishe zgjedhur gabim)
        $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/student/regjistrim/{$regjlId}")
            ->assertStatus(200);

        // 4) Tani regjistrimi në B duhet të punojë
        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => $sekIdB])
            ->assertStatus(201);

        // Në DB duhet të ketë vetëm një regjistrim aktiv — për B
        $this->assertDatabaseMissing('REGJISTRIM', ['STD_ID' => $stdId, 'SEK_ID' => $sekIdA]);
        $this->assertDatabaseHas('REGJISTRIM', ['STD_ID' => $stdId, 'SEK_ID' => $sekIdB]);
    }

    public function test_regjistro_valideshte_sek_id(): void
    {
        $s = $this->seedStrukturen();
        [, $token] = $this->krijoStudentMeUser($s['depId']);

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/student/regjistrim', ['sek_id' => 9999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sek_id']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ÇREGJISTRIM
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cregjistro_fshin_regjistrimin(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);
        $sekId = $this->krijoSeksion($s);

        $regjlId = DB::table('REGJISTRIM')->insertGetId([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], 'REGJL_ID');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/student/regjistrim/{$regjlId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('REGJISTRIM', ['REGJL_ID' => $regjlId]);
    }

    public function test_cregjistro_refuzon_regjistrim_qe_nuk_eshte_i_studentit(): void
    {
        $s = $this->seedStrukturen();

        // Student A (qe benen kerkesen)
        [, $tokenA] = $this->krijoStudentMeUser($s['depId'], 'a@uamd.edu.al');

        // Student B (zotesore e regjistrimit)
        $stdIdB = DB::table('STUDENT')->insertGetId([
            'STD_EM'     => 'Bledi',
            'STD_MB'     => 'Marku',
            'STD_EMAIL'  => 'b@uamd.edu.al',
            'DEP_ID'     => $s['depId'],
            'created_at' => now(),
            'updated_at' => now(),
        ], 'STD_ID');

        $sekId = $this->krijoSeksion($s);
        $regjlIdB = DB::table('REGJISTRIM')->insertGetId([
            'STD_ID'       => $stdIdB,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], 'REGJL_ID');

        $response = $this->withHeaders($this->authHeaders($tokenA))
            ->deleteJson("/api/student/regjistrim/{$regjlIdB}");

        // Student A nuk duhet te kete akses te regjistrimi i student B
        $this->assertContains($response->status(), [403, 404]);

        // Rreshti ende duhet te ekzistoje
        $this->assertDatabaseHas('REGJISTRIM', ['REGJL_ID' => $regjlIdB]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  RREGULL BIZNESI: ÇREGJISTRIMI VETËM PËRPARA FILLIMIT TË SEMESTRIT
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cregjistro_lejohet_kur_semestri_nuk_ka_filluar(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Semestri fillon 10 ditë pas sot — çregjistrimi lejohet
        $this->setFillimiSemestrit($s['semId'], now()->addDays(10)->toDateString());

        $sekId = $this->krijoSeksion($s);
        $regjlId = DB::table('REGJISTRIM')->insertGetId([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], 'REGJL_ID');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/student/regjistrim/{$regjlId}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('REGJISTRIM', ['REGJL_ID' => $regjlId]);
    }

    public function test_cregjistro_refuzohet_pasi_semestri_ka_filluar(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Semestri ka filluar 5 ditë më parë — çregjistrimi refuzohet
        $this->setFillimiSemestrit($s['semId'], now()->subDays(5)->toDateString());

        $sekId = $this->krijoSeksion($s);
        $regjlId = DB::table('REGJISTRIM')->insertGetId([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->subDays(10)->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], 'REGJL_ID');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/student/regjistrim/{$regjlId}");

        $response->assertStatus(403)
            ->assertJsonStructure(['message', 'fillimi_semestrit']);

        // Regjistrimi ende duhet të ekzistojë — nuk u fshi
        $this->assertDatabaseHas('REGJISTRIM', ['REGJL_ID' => $regjlId]);
    }

    public function test_cregjistro_refuzohet_edhe_diten_e_pare_te_semestrit(): void
    {
        $s = $this->seedStrukturen();
        [, $token, $stdId] = $this->krijoStudentMeUser($s['depId']);

        // Semestri fillon pikërisht sot — nuk lejohet (boundary case)
        $this->setFillimiSemestrit($s['semId'], now()->toDateString());

        $sekId = $this->krijoSeksion($s);
        $regjlId = DB::table('REGJISTRIM')->insertGetId([
            'STD_ID'       => $stdId,
            'SEK_ID'       => $sekId,
            'REGJL_DT'     => now()->subDays(1)->toDateString(),
            'REGJL_STATUS' => 'aktiv',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], 'REGJL_ID');

        $response = $this->withHeaders($this->authHeaders($token))
            ->deleteJson("/api/student/regjistrim/{$regjlId}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('REGJISTRIM', ['REGJL_ID' => $regjlId]);
    }
}
