<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatbotController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featuredScholarships = \App\Models\Scholarship::orderBy('id', 'desc')->take(4)->get();
    return view('welcome', compact('featuredScholarships'));
});

Route::get('/scholarship', function (Illuminate\Http\Request $request) {
    $query = \App\Models\Scholarship::query();
    
    if ($request->has('destination')) {
        if ($request->destination == 'domestic') {
            $query->where('negara', 'like', '%Indonesia%');
        } elseif ($request->destination == 'international') {
            $query->where('negara', 'not like', '%Indonesia%');
        }
    }

    if ($request->has('search') && $request->search != '') {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('nama_beasiswa', 'like', "%{$searchTerm}%")
              ->orWhere('negara', 'like', "%{$searchTerm}%")
              ->orWhere('benua', 'like', "%{$searchTerm}%");
        });
    }

    if ($request->has('degree') && $request->degree != '') {
        $query->where('jenjang', 'like', "%{$request->degree}%");
    }

    $scholarships = $query->orderBy('id', 'desc')->paginate(12);
    $bookmarkedIds = auth()->check() ? auth()->user()->savedScholarships()->pluck('scholarship_id')->toArray() : [];
    
    return view('scholarship', compact('scholarships', 'bookmarkedIds'));
})->name('scholarship');

Route::get('/scholarship/{id}', function ($id) {
    $scholarship = \App\Models\Scholarship::findOrFail($id);
    $isBookmarked = auth()->check() ? auth()->user()->savedScholarships()->where('scholarship_id', $id)->exists() : false;
    
    return view('scholarship-detail', compact('scholarship', 'isBookmarked'));
})->name('scholarship.detail');

Route::get('/chatbot', function () {
    return view('scholarbot');
})->name('chatbot');

Route::post('/chatbot/ask', [ChatbotController::class, 'ask']);
Route::post('/chatbot/clear', [ChatbotController::class, 'clearSession'])->name('chatbot.clear');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'index'])->name('bookmarks');
    Route::post('/scholarship/{id}/toggle-bookmark', [\App\Http\Controllers\BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
});
Route::redirect('/saved', '/bookmarks');

Route::get('/dashboard', function (Illuminate\Http\Request $request) {
    $query = \App\Models\Scholarship::query();

    // Filter Search (Title or Country)
    if ($request->has('search') && $request->search != '') {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('nama_beasiswa', 'like', "%{$searchTerm}%")
              ->orWhere('negara', 'like', "%{$searchTerm}%");
        });
    }

    // Filter Destination
    if ($request->has('destination') && $request->destination != '') {
        if ($request->destination == 'domestic') {
            $query->where('negara', 'like', '%Indonesia%');
        } elseif ($request->destination == 'international') {
            $query->where('negara', 'not like', '%Indonesia%');
        }
    }

    // Filter Degree
    if ($request->has('degree') && $request->degree != '') {
        $query->where('jenjang', 'like', "%{$request->degree}%");
    }

    // Filter Status
    if ($request->has('status') && $request->status != '') {
        if ($request->status == 'embedded') {
            $query->whereNotNull('embedding');
        } elseif ($request->status == 'pending') {
            $query->whereNull('embedding');
        }
    }

    $scholarships = $query->orderBy('id', 'desc')->paginate(20);
    
    try {
        $lastUpdated = \App\Models\Scholarship::max('updated_at');
    } catch (\Exception $e) {
        $lastUpdated = null;
    }
    
    return view('dashboard', compact('scholarships', 'lastUpdated'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    
    // Admin Scholarship Routes
    Route::resource('admin/scholarships', \App\Http\Controllers\AdminScholarshipController::class)->names('admin.scholarships');
    Route::post('admin/scholarships/sync-embeddings', [\App\Http\Controllers\AdminScholarshipController::class, 'syncEmbeddings'])->name('admin.scholarships.sync_embeddings');
    Route::post('admin/scholarships/upload-dataset', [\App\Http\Controllers\AdminScholarshipController::class, 'uploadDataset'])->name('admin.scholarships.upload');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
