<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:50'], 'saved' => ['nullable', 'in:1']]);
        $query = Article::query()->where('published', true);
        if (! empty($data['q'])) {
            $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $data['q']);
            $query->where(function ($q) use ($term) { $q->where('title', 'like', "%$term%")->orWhere('summary', 'like', "%$term%"); });
        }
        if (! empty($data['category'])) $query->where('category', $data['category']);
        if (! empty($data['saved'])) $query->whereIn('id', DB::table('saved_articles')->where('user_id', $request->user()->id)->select('article_id'));
        $categories = Article::where('published', true)->distinct()->orderBy('category')->pluck('category');
        return view('news.index', ['articles' => $query->orderByRaw('COALESCE(source_published_at, DATE(created_at)) DESC')->orderByDesc('id')->paginate(9)->withQueryString(), 'categories' => $categories]);
    }
    public function show(Request $request, Article $article)
    {
        abort_unless($article->published, 404);
        $saved = DB::table('saved_articles')->where('user_id', $request->user()->id)->where('article_id', $article->id)->exists();
        $related = Article::where('published', true)->where('category', $article->category)->whereKeyNot($article->id)->latest()->limit(3)->get();
        return view('news.show', compact('article', 'saved', 'related'));
    }
    public function save(Request $request, Article $article)
    {
        abort_unless($article->published, 404);
        DB::table('saved_articles')->insertOrIgnore(['user_id' => $request->user()->id, 'article_id' => $article->id, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('status', 'Story saved.');
    }
    public function unsave(Request $request, Article $article)
    {
        DB::table('saved_articles')->where('user_id', $request->user()->id)->where('article_id', $article->id)->delete();
        return back()->with('status', 'Story removed from your saved list.');
    }
}
