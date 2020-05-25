@extends('public.layouts.page')

@section('body:class','posts show')

@section('page:content')
    @if(!$post->isPublic)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Nicht veröffentlicht!</strong> Dieser <em>Post</em> wurde noch nicht veröffentlicht.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="row">
        <div class="col">
            <div class="post-meta">
                <span class="float-left">{{ $post->published_at ? $post->published_at->format('d.m.Y') : 'Noch nicht veröffentlicht' }}</span>
                <span class="float-right">{{ $post->author ? $post->author->name : '' }}</span>
            </div>
            <img class="post-image mb-3" src="{{ url($post->image) }}">
            <h1>{{ $post->title }}</h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            {!! $post->body !!}
        </div>
    </div>
@stop