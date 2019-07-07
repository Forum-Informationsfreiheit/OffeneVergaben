@extends('layouts.earlybird')

@section('body')
    <h2>Dataset {{ $dataset->id }}</h2>
    <div style="width: 85%;">
        <table>
            <tr>
                <th style="width: 200px;"></th>
                <th></th>
            </tr>
            @foreach($fields as $key => $value)
                @if(!$showAll && !$value)
                    @continue
                @endif
                <tr>
                    <td>{{ $key }}</td>
                    <td>
                        @if(is_array($value))
                            @if(count($value) == 1)
                                {!! $value[0]  !!}
                                @else
                                <ul>
                                    @foreach($value as $v)
                                        <li>{{ $v }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        @else
                            {!! $value !!}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@stop