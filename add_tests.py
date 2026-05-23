content = open('tests/Feature/ChatbotTest.php').read()

new_tests = """
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
        $response = $this->ask('beasiswa di hogwarts');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringContainsStringIgnoringCase('tidak', strtolower($response->json('answer')));
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
        // Search dulu, lalu minta link
        $this->ask('beasiswa australia');
        $response = $this->ask('nomor 1');
        $response->assertStatus(200)->assertJson(['success' => true]);
        // Pilih detail apply
        $response2 = $this->ask('cara daftar');
        $response2->assertStatus(200)->assertJson(['success' => true]);
        $answer = $response2->json('answer');
        $this->assertTrue(
            str_contains($answer, 'http') || str_contains($answer, 'link') || str_contains($answer, 'daftar'),
            'Response should contain a link or registration info'
        );
    }
}
"""

# Hapus closing brace terakhir dan tambah test baru
old_end = "    public function test_invalid_message_rejected()\n    {\n        $response = $this->postJson('/chatbot/ask', ['message' => '']);\n        $response->assertStatus(422);\n    }\n}"
new_end = "    public function test_invalid_message_rejected()\n    {\n        $response = $this->postJson('/chatbot/ask', ['message' => '']);\n        $response->assertStatus(422);\n    }" + new_tests

if old_end in content:
    content = content.replace(old_end, new_end)
    open('tests/Feature/ChatbotTest.php', 'w').write(content)
    print('DONE - 6 test cases added')
else:
    print('NOT FOUND - cek manual')
