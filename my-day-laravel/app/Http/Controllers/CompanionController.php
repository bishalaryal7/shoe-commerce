<?php

namespace App\Http\Controllers;

use App\Models\CompanionNote;
use App\Models\CompanionTask;
use Illuminate\Http\Request;

class CompanionController extends Controller
{
    private function owner(Request $request): string
    {
        return $request->user()->companion_owner;
    }

    public function index(Request $request)
    {
        $owner = $this->owner($request);
        return response()->json([
            'tasks' => CompanionTask::where('owner', $owner)->orderBy('done')->latest('id')->get(),
            'note' => CompanionNote::where('owner', $owner)->value('content') ?? '',
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:120'], 'due_date' => ['nullable', 'date_format:Y-m-d']]);
        $data['text'] = trim($data['text']);
        if ($data['text'] === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['text' => 'Please enter a task.']);
        }
        return response()->json(CompanionTask::create($data + ['owner' => $this->owner($request), 'done' => false]), 201);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'tasks' => ['required', 'array', 'max:500'],
            'tasks.*.text' => ['required', 'string', 'max:120'],
            'tasks.*.done' => ['required', 'boolean'],
        ]);
        if (! $request->session()->get('allow_legacy_import')) {
            return response()->json(['imported' => false]);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data) {
            $user = \App\Models\User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($user->legacy_imported) return;
            foreach ($data['tasks'] as $task) {
                if (trim($task['text']) !== '') {
                    CompanionTask::create(['owner' => $user->companion_owner, 'text' => trim($task['text']), 'done' => $task['done']]);
                }
            }
            $user->forceFill(['legacy_imported' => true])->save();
        });
        return response()->json(['imported' => true]);
    }

    public function update(Request $request, int $id)
    {
        $task = CompanionTask::where('owner', $this->owner($request))->findOrFail($id);
        $data = $request->validate(['done' => ['sometimes', 'required', 'boolean'], 'text' => ['sometimes', 'required', 'string', 'max:120'], 'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d']]);
        if (array_key_exists('text', $data) && trim($data['text']) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['text' => 'Please enter a task.']);
        }
        $task->update($data);
        return response()->json($task);
    }

    public function destroy(Request $request, int $id)
    {
        CompanionTask::where('owner', $this->owner($request))->findOrFail($id)->delete();
        return response()->noContent();
    }

    public function note(Request $request)
    {
        $data = $request->validate(['content' => ['present', 'nullable', 'string', 'max:5000']]);
        CompanionNote::updateOrCreate(['owner' => $this->owner($request)], ['content' => $data['content'] ?? '']);
        return response()->json(['saved' => true]);
    }
}
