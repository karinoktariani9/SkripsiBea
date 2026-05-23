<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatbotTest extends TestCase
{
    private function ask($message, $ragEnabled = true)
    {
        return $this->postJson('/chatbot/ask', [
            'message' => $message,
            'rag_enabled' => $ragEnabled,
        ]);
    }

    public function test_greeting_response()
    {
        $response = $this->ask('halo');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_thank_you_response()
    {
        $response = $this->ask('makasih ya');
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_out_of_topic_blocked_in_rag_mode()
    {
        $response = $this->ask('resep nasi goreng', true);
        $response->assertStatus(200);
        $this->assertStringContainsStringIgnoringCase('beasiswa', $response->json('answer'));
    }

    public function test_scholarship_search_australia()
    {
        $response = $this->ask('beasiswa australia');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $answer = $response->json('answer');
        $this->assertTrue(
            str_contains(strtolower($answer), 'australia') || str_contains(strtolower($answer), 'beasiswa'),
            'Response harus mengandung kata australia atau beasiswa'
        );
    }

    public function test_scholarship_search_with_level_filter()
    {
        $response = $this->ask('beasiswa s2 jepang');
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_pagination_next_page()
    {
        $this->ask('beasiswa australia');
        $response = $this->ask('yang lain');
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_typo_handling()
    {
        $response = $this->ask('beasisw australi');
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_pure_ai_mode()
    {
        $response = $this->ask('beasiswa australia', false);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringContainsStringIgnoringCase('australia', strtolower($response->json('answer')));
    }

    public function test_response_time_is_logged()
    {
        $response = $this->ask('beasiswa jepang');
        $response->assertStatus(200);
        $this->assertNotNull($response->json('response_time_ms'));
        $this->assertIsFloat($response->json('response_time_ms'));
    }

    public function test_invalid_message_rejected()
    {
        $response = $this->postJson('/chatbot/ask', ['message' => '']);
        $response->assertStatus(422);
    }
    public function test_filter_tanpa_toefl()
    {
        $response = $this->ask('beasiswa tanpa toefl');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_filter_khusus_perempuan()
    {
        $response = $this->ask('beasiswa khusus perempuan');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_filter_fresh_graduate()
    {
        $response = $this->ask('beasiswa fresh graduate');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_empty_data_handling()
    {
        // Kata benar-benar tidak ada di dataset
        $response = $this->ask('beasiswa di kutub utara untuk alien');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_context_memory_followup()
    {
        // Search pertama
        $this->ask('beasiswa s1 di jepang');
        // Follow-up tanpa menyebut negara lagi
        $response = $this->ask('yang fully funded');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('answer'));
    }

    public function test_link_returned_on_apply_intent()
    {
        // Test apply intent langsung dengan context scholarship di session
        $response = $this->withSession([
            'selected_scholarship' => [
                'id' => 1,
                'nama_beasiswa' => 'Test Scholarship',
                'benefit' => 'Full tuition',
                'persyaratan' => 'GPA 3.0',
                'deadline' => '2025-12-31',
                'kategori' => 'Fully Funded',
                'negara' => 'Australia',
                'jenjang' => 'S2',
                'url' => 'https://www.schoters.com/id/beasiswa/test',
                'url_asli' => 'https://example.com/scholarship',
            ]
        ])->postJson('/chatbot/ask', [
            'message' => 'cara daftar',
            'rag_enabled' => true,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $answer = $response->json('answer');
        $this->assertTrue(
            str_contains($answer, 'http') || str_contains($answer, 'daftar') || str_contains($answer, 'link'),
            'Response should contain a link or registration info'
        );
    }
}
