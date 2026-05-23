<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan ekstensi vector aktif di PostgreSQL
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // 2. Buat tabel scholarships sesuai 13 kolom dataset
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('name');             // Nama Beasiswa
            $table->string('continent')->nullable(); // Benua
            $table->string('country')->nullable();   // Negara
            $table->string('level')->nullable();     // Jenjang
            $table->text('description')->nullable(); // Deskripsi
            $table->string('deadline')->nullable();  // Deadline
            $table->string('category')->nullable();  // Kategori
            $table->text('major')->nullable();       // Jurusan
            $table->text('benefit')->nullable();     // Benefit
            $table->text('requirements')->nullable();// Persyaratan
            $table->string('source')->nullable();    // Sumber
            $table->string('url')->nullable();       // URL
            $table->string('original_url')->nullable(); // URL ASLI
            $table->timestamps();
        });

        // 3. Tambahkan kolom embedding (vector 1536) & Full Text Search (tsvector)
        DB::statement('ALTER TABLE scholarships ADD COLUMN embedding vector(1536)');
        DB::statement('ALTER TABLE scholarships ADD COLUMN fts tsvector');

        // 4. Tambahkan index
        DB::statement('CREATE INDEX ON scholarships USING hnsw (embedding vector_cosine_ops)');
        DB::statement('CREATE INDEX scholarships_fts_idx ON scholarships USING gin (fts)');

        // 5. Trigger untuk otomatis update FTS
        DB::statement("
            CREATE OR REPLACE FUNCTION scholarships_update_fts() RETURNS trigger AS $$
            BEGIN
              new.fts :=
                setweight(to_tsvector('indonesian', coalesce(new.name, '')), 'A') ||
                setweight(to_tsvector('indonesian', coalesce(new.description, '')), 'B');
              RETURN new;
            END
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER scholarships_fts_update BEFORE INSERT OR UPDATE
            ON scholarships FOR EACH ROW EXECUTE FUNCTION scholarships_update_fts();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
