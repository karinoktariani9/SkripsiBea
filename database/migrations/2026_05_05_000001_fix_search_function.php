<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Fungsi Trigger FTS agar menyertakan Benua dan Negara
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

        // 2. Update data lama agar FTS-nya terisi dengan kolom baru
        DB::statement("
            UPDATE scholarships SET 
            fts_content = 
                setweight(to_tsvector('indonesian', coalesce(nama_beasiswa, '')), 'A') ||
                setweight(to_tsvector('indonesian', coalesce(benua, '')), 'B') ||
                setweight(to_tsvector('indonesian', coalesce(negara, '')), 'B') ||
                setweight(to_tsvector('indonesian', coalesce(deskripsi, '')), 'C');
        ");

        // 3. Update Fungsi hybrid_search agar mengembalikan kolom benua
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback spesifik karena hanya update function
    }
};
