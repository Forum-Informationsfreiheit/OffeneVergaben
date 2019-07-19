@extends('layouts.earlybird')

@section('body')
    <h2>Organizations</h2>
    <table style="width: 80%">
        <tr>
            <th>FN</th>
            <th>GLN</th>
            <th>GKZ</th>
            <th>Name</th>
        </tr>
        @foreach($organizations as $org)
            <tr>
                <td>{{ $org->fn }}</td>
                <td>{{ $org->gln }}</td>
                <td>{{ $org->gkz }}</td>
                <td>{{ $org->name }}</td>
            </tr>
        @endforeach
    </table>
    <div class="pagination-wrapper">
        {{ $organizations->appends(request()->query())->links() }}
    </div>
@stop