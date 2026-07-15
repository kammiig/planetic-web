<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('public.blog.index', [
            'posts' => Post::published()->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return view('public.blog.show', [
            'post' => $post,
            'related' => Post::published()->whereKeyNot($post->getKey())->take(3)->get(),
        ]);
    }
}
