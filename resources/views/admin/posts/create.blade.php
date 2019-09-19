@extends('admin.layouts.default')

@section('body:class','posts create-post')

@section('page:content')
    <div class="card shadow mb-4 {{ $errors->any() ? "border-left-danger" : "" }}">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Neuen Post schreiben</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin::store-post') }}" method="POST">
                @include('admin.posts.form', ['post' => null, 'mode' => 'create'])
            </form>
        </div>
    </div>
@stop