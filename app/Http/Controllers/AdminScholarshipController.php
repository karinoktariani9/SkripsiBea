<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminScholarshipController extends Controller
{
    public function index()
    {
        $scholarships = \App\Models\Scholarship::orderBy('id', 'desc')->paginate(20);
        return view('admin.scholarships.index', compact('scholarships'));
    }

    public function create()
    {
        return view('admin.scholarships.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_beasiswa' => 'required',
            'negara' => 'required',
            'jenjang' => 'required',
        ]);

        \App\Models\Scholarship::create($request->all());

        return redirect()->route('admin.scholarships.index')->with('success', 'Data Beasiswa berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $scholarship = \App\Models\Scholarship::findOrFail($id);
        return view('admin.scholarships.edit', compact('scholarship'));
    }

    public function update(Request $request, $id)
    {
        $scholarship = \App\Models\Scholarship::findOrFail($id);
        
        $data = $request->all();
        // Reset status embedding jika deskripsi berubah
        if ($request->has('deskripsi') && $scholarship->deskripsi != $request->deskripsi) {
            $data['embedding'] = null;
        }

        $scholarship->update($data);

        return redirect()->route('dashboard')->with('success', 'Data Beasiswa berhasil diupdate!');
    }

    public function destroy($id)
    {
        $scholarship = \App\Models\Scholarship::findOrFail($id);
        $scholarship->delete();

        return redirect()->back()->with('success', 'Data Beasiswa berhasil dihapus!');
    }

    public function uploadDataset(Request $request)
    {
        $request->validate([
            'dataset_file' => 'required|file|mimes:csv,txt,json',
        ]);

        $file = $request->file('dataset_file');
        $extension = $file->getClientOriginalExtension();
        $data = [];

        if ($extension === 'json') {
            $data = json_decode(file_get_contents($file), true);
        } else {
            // Simple CSV Parser
            if (($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
                $header = fgetcsv($handle, 1000, ",");
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $data[] = array_combine($header, $row);
                }
                fclose($handle);
            }
        }

        $updatedCount = 0;
        $insertedCount = 0;

        foreach ($data as $item) {
            // Mapping keys if necessary (handle both Indonesian and English keys)
            $name = $item['nama_beasiswa'] ?? $item['name'] ?? null;
            if (!$name) continue;

            $scholarship = \App\Models\Scholarship::where('nama_beasiswa', $name)->first();

            $attributes = [
                'nama_beasiswa' => $name,
                'benua' => $item['benua'] ?? $item['continent'] ?? null,
                'negara' => $item['negara'] ?? $item['country'] ?? null,
                'jenjang' => $item['jenjang'] ?? $item['level'] ?? null,
                'deskripsi' => $item['deskripsi'] ?? $item['description'] ?? null,
                'deadline' => $item['deadline'] ?? null,
                'kategori' => $item['kategori'] ?? $item['category'] ?? null,
                'jurusan' => $item['jurusan'] ?? $item['major'] ?? null,
                'benefit' => $item['benefit'] ?? null,
                'persyaratan' => $item['persyaratan'] ?? $item['requirements'] ?? null,
                'sumber' => $item['sumber'] ?? $item['source'] ?? null,
                'url' => $item['url'] ?? null,
                'url_asli' => $item['url_asli'] ?? $item['original_url'] ?? null,
            ];

            if ($scholarship) {
                // Check if content changed to reset embedding
                $changed = false;
                foreach (['deskripsi', 'benefit', 'persyaratan', 'nama_beasiswa'] as $field) {
                    if ($scholarship->$field != $attributes[$field]) {
                        $changed = true;
                        break;
                    }
                }

                if ($changed) {
                    $attributes['embedding'] = null; // Mark for re-sync
                }

                $scholarship->update($attributes);
                $updatedCount++;
            } else {
                $attributes['embedding'] = null;
                \App\Models\Scholarship::create($attributes);
                $insertedCount++;
            }
        }

        return back()->with('success', "✅ Dataset berhasil diproses! ($insertedCount data baru, $updatedCount data diupdate). Silakan klik 'Sync Embeddings' untuk memperbarui vector embedding.");
    }

    public function syncEmbeddings(Request $request)
    {
        $pending = \App\Models\Scholarship::whereNull('embedding')->get();
        
        if ($pending->isEmpty()) {
            return back()->with('info', 'Semua data sudah memiliki vector embedding.');
        }

        $apiKey = trim(env('OPENAI_API_KEY'));
        $baseUrl = str_contains($apiKey, 'sk-or') ? 'https://openrouter.ai/api/v1' : 'https://api.openai.com/v1';
        $model = str_contains($apiKey, 'sk-or') ? 'openai/text-embedding-3-small' : 'text-embedding-3-small';

        $successCount = 0;
        foreach ($pending as $s) {
            $context = "Beasiswa: {$s->nama_beasiswa}\n" .
                       "Negara: {$s->negara}\n" .
                       "Jenjang: {$s->jenjang}\n" .
                       "Deskripsi: {$s->deskripsi}\n" .
                       "Benefit: {$s->benefit}\n" .
                       "Persyaratan: {$s->persyaratan}";

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                    ->post("$baseUrl/embeddings", [
                        'model' => $model,
                        'input' => $context,
                    ]);

                if ($response->successful()) {
                    $embedding = $response->json()['data'][0]['embedding'];
                    $s->update(['embedding' => '[' . implode(',', $embedding) . ']']);
                    $successCount++;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Sync Error for ID {$s->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "🤖 Berhasil melakukan sinkronisasi vector untuk $successCount data beasiswa!");
    }
}
