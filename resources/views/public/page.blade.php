@extends('public.layouts.page')

@section('body:class','page')

@section('page:content')
    @if(!$page->isPublic)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Nicht veröffentlicht!</strong> Diese <em>Page</em> wurde noch nicht veröffentlicht.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="row">
        <div class="col">
            <h1>{{ $page->title }}</h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="page-body">
                {!! $page->body !!}
            </div>
        </div>
    </div>
@stop