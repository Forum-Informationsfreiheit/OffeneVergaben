@extends('public.layouts.page')

@section('body:class','posts index')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1>Neuigkeiten</h1>
        </div>
    </div>
    @foreach($posts as $post)
        <div class="row">
            <div class="col">
                <div class="teaser-meta">
                    <span class="float-left">{{ $post->published_at->format('d.m.Y') }}</span>
                </div>
                <h2><a href="{{ route('public::show-post',$post->slug) }}">{{ $post->title }}</a></h2>
                <div class="teaser-text">
                    {!! $post->summary !!}
                </div>
            </div>
        </div>
        @if(!$loop->last)
            <hr>
        @endif
    @endforeach
    <div class="pagination-wrapper">
        {{ $posts->links() }}
    </div>
@stop