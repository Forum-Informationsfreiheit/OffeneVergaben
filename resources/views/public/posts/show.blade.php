@extends('public.layouts.page')

@section('page:title',$post->title)
@section('page:description',strip_tags($post->summary))
@section('page:image',url($post->image))

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
            <h1>{{ $post->title }}</h1>
            <img class="post-image" src="{{ url($post->image) }}">
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="post-body">
                {!! $post->body !!}
            </div>
        </div>
    </div>
@stop