@extends('layouts.earlybird')

@section('body')
    <h2>Dataset {{ $dataset->id }}</h2>
    <div style="width: 85%;">
        <table>
            <tr>
                <th style="width: 200px;"></th>
                <th></th>
            </tr>
            @if($dataset->otherVersions)
                <style>
                    td.other-versions-container:after {
                        display: block;
                        content: '';
                        clear: left;
                    }
                    ul.other-versions {
                        list-style: none; margin: 0; padding: 0;
                    }
                    ul.other-versions li {
                        float: left;
                    }
                </style>
                <tr>
                    <td>Andere Versionen</td>
                    <td class="other-versions-container">
                        <ul class="other-versions">
                            @foreach($dataset->otherVersions as $other)
                                <li style="padding-right: 4px;"><a href="{{ url('/datasets/'.$other->id) }}">v{{ $other->version }}</a></li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
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
                                        <li>{!! $v !!}</li>
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
        <div style="margin-top: 20px;">
            <a href="" id="xmlToggle">XML anzeigen</a>
            <div id="xmlOutput" style="display: none">
                <?php dump($dataset->xml); ?>
            </div>
        </div>
    </div>
    <script>
        var toggleXml = document.getElementById("xmlToggle");
        var xmlOutput = document.getElementById("xmlOutput");
        toggleXml.addEventListener('click',function(evt) {
            evt.preventDefault();
            if (xmlOutput.style.display == 'none') {
                xmlOutput.style.display = 'block';
            } else {
                xmlOutput.style.display = 'none'
            }
            return false;
        });
    </script>
@stop