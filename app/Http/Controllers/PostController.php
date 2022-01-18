<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index() {
        $posts = Post::published()->orderBy('published_at','desc')->paginate(10);

        return view('public.posts.index',compact('posts'));
    }

    public function show($slug) {
        $post = Post::findBySlugOrFail($slug);

        if (!$post->isPublic) {
            $this->authorize('show-news',$post);
        }

        return view('public.posts.show',compact('post'));
    }
}
