<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(FirebaseService $firebase): View
    {
        $user = auth()->user();
        $feedbacks = collect($firebase->all('feedbacks'));

        if ($user->isStudent()) {
            $feedbacks = $feedbacks->where('group_id', $user->group_id);
        }

        return view('feedback.index', [
            'feedbacks' => $feedbacks->sortByDesc('created_at')->values(),
            'journals' => collect($firebase->all('journals'))->keyBy('id'),
            'groups' => collect($firebase->all('groups'))->keyBy('id'),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase): RedirectResponse
    {
        abort_unless($request->user()->isTeacher(), 403);

        $validated = $request->validate([
            'journal_id' => ['required', 'string'],
            'comment' => ['required', 'string', 'max:1600'],
            'feedback_type' => ['required', 'string', 'max:60'],
            'priority' => ['required', 'in:rendah,sedang,tinggi'],
            'revision_status' => ['required', 'in:baru,revisi,selesai'],
        ]);

        $journal = $firebase->find('journals', $validated['journal_id']);
        abort_if(! $journal, 404);

        $firebase->push('feedbacks', [
            ...$validated,
            'group_id' => $journal['group_id'],
            'created_by' => (string) $request->user()->id,
        ]);

        return back()->with('status', 'Feedback guru berhasil ditambahkan.');
    }
}
