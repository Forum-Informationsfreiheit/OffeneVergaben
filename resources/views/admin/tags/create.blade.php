@extends('admin.layouts.default')

@section('page:content')
    <div class="card shadow mb-4 {{ $errors->any() ? "border-left-danger" : "" }}">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Neuen Tag anlegen</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin::store-tag') }}" method="POST">
                @include('admin.tags.form', ['tag' => null, 'mode' => 'create'])
            </form>
        </div>
    </div>
@stop