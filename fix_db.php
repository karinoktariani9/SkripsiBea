<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("DROP FUNCTION IF EXISTS hybrid_search(text, vector, integer)");
    DB::statement("
        CREATE OR REPLACE FUNCTION hybrid_search(query_text TEXT, query_embedding VECTOR(1536), match_count INT)
        RETURNS TABLE (
            id BIGINT,
            nama_beasiswa VARCHAR,
            benua VARCHAR,
            negara VARCHAR,
            jenjang VARCHAR,
            deskripsi TEXT,
            benefit TEXT,
            persyaratan TEXT,
            deadline VARCHAR,
            kategori VARCHAR,
            similarity FLOAT8
        ) AS $$
        BEGIN
          RETURN QUERY
          SELECT
            s.id,
            s.nama_beasiswa,
            s.benua,
            s.negara,
            s.jenjang,
            s.deskripsi,
            s.benefit,
            s.persyaratan,
            s.deadline,
            s.kategori,
            (
              (1 - (s.embedding <=> query_embedding)) * 0.5 +
              ts_rank_cd(s.fts_content, websearch_to_tsquery('indonesian', query_text)) * 0.5
            )::FLOAT8 AS similarity
          FROM scholarships s
          ORDER BY similarity DESC
          LIMIT match_count;
        END;
        $$ LANGUAGE plpgsql;
    ");
    echo "Successfully updated hybrid_search function in Supabase!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
