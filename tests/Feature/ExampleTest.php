<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Smoke test: aplikacioni boot-on pa gabime dhe endpoint-e te paregjistruar
     * kthejne 404 (jo 500).
     */
    public function test_aplikacioni_boot_on_dhe_kthen_404_per_rruge_te_panjohur(): void
    {
        $response = $this->get('/rruge-qe-nuk-ekziston');
        $response->assertStatus(404);
    }
}
