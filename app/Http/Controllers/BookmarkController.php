<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Scholarship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    /**
     * Menampilkan daftar beasiswa yang disimpan oleh user
     */
    public function index()
    {
        $user = Auth::user();
        $savedScholarships = $user->savedScholarships()->orderBy('bookmarks.created_at', 'desc')->get();
        
        return view('bookmarks', compact('savedScholarships'));
    }

    /**
     * Menyimpan atau menghapus beasiswa dari daftar favorit (Toggle)
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::user();
        $scholarship = Scholarship::findOrFail($id);

        $exists = $user->savedScholarships()->where('scholarship_id', $id)->exists();

        if ($exists) {
            $user->savedScholarships()->detach($id);
            $message = 'Beasiswa dihapus dari daftar simpan.';
            $status = 'removed';
        } else {
            $user->savedScholarships()->attach($id);
            $message = 'Beasiswa berhasil disimpan!';
            $status = 'added';
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $status
            ]);
        }

        return back()->with('success', $message);
    }
}
