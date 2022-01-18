@extends('admin.layouts.default')

@section('body:class','posts edit-post')

@section('page:content')
    <div class="card shadow mb-4 {{ $errors->any() ? "border-left-danger" : "" }}">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Post bearbeiten</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin::update-post') }}" method="POST">
                @include('admin.posts.form', ['post' => $post, 'mode' => 'edit'])
            </form>
        </div>
    </div>
@stop