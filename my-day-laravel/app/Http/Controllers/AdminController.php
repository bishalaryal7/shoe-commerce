<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index() { return view('admin.index', ['articles' => Article::latest()->paginate(20), 'userCount' => User::count()]); }
    public function create() { return view('admin.form', ['article' => new Article]); }
    public function edit(Article $article) { return view('admin.form', compact('article')); }
    private function validated(Request $request): array
    {
        $data = $request->validate(['title' => ['required','string','max:160'], 'summary' => ['required','string','max:300'], 'body' => ['required','string','max:20000'], 'category' => ['required','string','max:50'], 'published' => ['nullable','boolean'], 'source_name' => ['nullable','string','max:120'], 'source_url' => ['nullable','url:http,https','max:2048'], 'source_published_at' => ['nullable','date_format:Y-m-d']]);
        $data['published'] = $request->boolean('published'); return $data;
    }
    public function store(Request $request) { Article::create($this->validated($request)); return redirect()->route('admin.index')->with('status', 'Article created.'); }
    public function update(Request $request, Article $article) { $article->update($this->validated($request)); return redirect()->route('admin.index')->with('status', 'Article updated.'); }
    public function destroy(Article $article) { $article->delete(); return redirect()->route('admin.index')->with('status', 'Article deleted.'); }
}
