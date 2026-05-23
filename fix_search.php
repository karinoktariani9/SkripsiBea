<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Dropping and Recreating fts_content column...\n";
    DB::statement("ALTER TABLE scholarships DROP COLUMN IF EXISTS fts_content");
    DB::statement("ALTER TABLE scholarships ADD COLUMN fts_content tsvector");

    echo "Updating FTS Trigger Function...\n";
    DB::statement("
        CREATE OR REPLACE FUNCTION scholarships_update_fts() RETURNS trigger AS $$
        BEGIN
          new.fts_content :=
            setweight(to_tsvector('indonesian', coalesce(new.nama_beasiswa, '')), 'A') ||
            setweight(to_tsvector('indonesian', coalesce(new.benua, '')), 'B') ||
            setweight(to_tsvector('indonesian', coalesce(new.negara, '')), 'B') ||
            setweight(to_tsvector('indonesian', coalesce(new.deskripsi, '')), 'C');
          RETURN new;
        END
        $$ LANGUAGE plpgsql;
    ");

    echo "Creating Trigger...\n";
    DB::statement("DROP TRIGGER IF EXISTS scholarships_fts_update ON scholarships");
    DB::statement("
        CREATE TRIGGER scholarships_fts_update BEFORE INSERT OR UPDATE
        ON scholarships FOR EACH ROW EXECUTE FUNCTION scholarships_update_fts();
    ");

    echo "Updating Existing FTS Content...\n";
    DB::statement("
        UPDATE scholarships SET 
        fts_content = 
            setweight(to_tsvector('indonesian', coalesce(nama_beasiswa, '')), 'A') ||
            setweight(to_tsvector('indonesian', coalesce(benua, '')), 'B') ||
            setweight(to_tsvector('indonesian', coalesce(negara, '')), 'B') ||
            setweight(to_tsvector('indonesian', coalesce(deskripsi, '')), 'C');
    ");

    echo "Updating Hybrid Search Function...\n";
    DB::statement("DROP FUNCTION IF EXISTS hybrid_search(TEXT, VECTOR, INT)");
    DB::statement("
        CREATE OR REPLACE FUNCTION hybrid_search(query_text TEXT, query_embedding VECTOR(1536), match_count INT)
        RETURNS TABLE (
            id BIGINT,
            nama_beasiswa TEXT,
            benua TEXT,
            negara TEXT,
            jenjang TEXT,
            deskripsi TEXT,
            benefit TEXT,
            persyaratan TEXT,
            deadline TEXT,
            kategori TEXT,
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

    echo "DONE!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
