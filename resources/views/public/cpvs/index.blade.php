@extends('public.layouts.default')

@section('body:class','cpvs')

@section('page:content')
    <h1 class="page-title">
        Branchen
    </h1>
    <div class="row">
        <div class="col">
            <div class="treemap-top-controls">
                <ul class="float-left">
                    <li>
                        @svg('/img/icons/auftragsvolumen.svg','sum')&nbsp;&nbsp;
                        @if($params->type === 'volume')
                            nach Auftragsvolumen
                            @else
                            <a href="{{ \App\Http\Controllers\CpvController::buildViewUrl($params, [ 'type' => 'volume' ]) }}">nach Auftragsvolumen</a>
                        @endif
                    </li>
                    <li>
                        @svg('/img/icons/auftragsanzahl.svg','sum')&nbsp;&nbsp;
                        @if($params->type === 'anzahl')
                            nach Anzahl Aufträgen
                            @else
                            <a href="{{ \App\Http\Controllers\CpvController::buildViewUrl($params, [ 'type' => 'anzahl' ]) }}">nach Anzahl Aufträgen</a>
                        @endif
                    </li>
                </ul>
            </div>
            <div class="treemap-wrapper" id="treemap">
                <div class="treemap">
                    <!-- visual representation -->
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>
                        Code
                    </th>
                    <th>
                        Bezeichnung
                    </th>
                    <th>
                        Anzahl Aufträge
                    </th>
                    <th>
                        Auftragsvolumen
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->cpv }}">
                        <td class="code">
                            @if($item->isRoot)
                                {{ $item->cpv }}
                                @else
                                <a href="{{ route('public::branchen',[ 'node' => $cpvMap[$item->cpv]->code ]) }}">{{ $item->cpv }}</a>
                            @endif
                        </td>
                        <td class="name">
                            {{ $cpvMap[$item->cpv]->name }}
                        </td>
                        <td class="count">
                            @if($item->isRoot)
                                <a title="Aufträge mit CPV Code {{ $item->cpv }} anzeigen" href="{{ route('public::auftraege',[ 'cpv' => $item->cpv, 'cpv_like' => 1 ]) }}">{{ $rootNodeTotals->count }}</a>
                                @else
                                <a title="Aufträge mit CPV Code {{ $item->cpv }} anzeigen" href="{{ route('public::auftraege',[ 'cpv' => $item->cpv, 'cpv_like' => 1 ]) }}">{{ $item->count }}</a>
                            @endif
                        </td>
                        <td class="value">
                            @if($item->isRoot)
                                {{ ui_format_money($rootNodeTotals->sum) }}
                            @else
                                {{ ui_format_money($item->sum) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                @if(isset($data))
                {{ $data->appends(request()->query())->links('public.partials.pagination', [ 'ulClass' => [ "mx-auto", "justify-content-center" ] ]) }}
                @endif
            </div>
        </div>
    </div>
@stop

@section('body:append')
    <div class="ov-tooltip tooltip-treemap" id="tooltip">
        <div class="inner"></div>
    </div>
    <script src="{{ url('/vendor/d3/d3_v5.12.js') }}"></script>
    <script>
        (function(app){

            console.log(app);

            // get width of our container
            var container_width = $('.treemap').parent().width();
            var container_height = 420; // fixed height
            var margin = {top: 0, right: 0, bottom: 0, left: 0};
            var width = container_width - margin.left - margin.right;
            var height = container_height - margin.top - margin.bottom;

            var params = app.parameters;

            var total = sumRecords();

            var $tooltip = $('#tooltip');
            var $tooltipInner = $tooltip.find('.inner');

            initTreemap();

            function sumRecords() {
                var sum = 0;
                for (var i = 0; i < app.cpvRecords.length; i++) {
                    sum += params.type == 'volume' ? parseInt(app.cpvRecords[i].sum) : parseInt(app.cpvRecords[i].count);
                }
                return sum;
            }

            console.log(total);

            function initTreemap() {

                var div = d3.select(".treemap")
                        .append("div")
                        .attr("class","inner")
                        .style("position", "relative")
                        .style("width", (width + margin.left + margin.right) + "px")
                        .style("height", (height + margin.top + margin.bottom) + "px")
                        .style("left", margin.left + "px")
                        .style("top", margin.top + "px");

                // Give the data to this cluster layout:
                var root = d3.hierarchy({ children: app.cpvRecords, name: 'root-node' })
                        .sum(function(d){
                            if (params.type == 'volume') {
                                return d.sum;
                            } else {
                                return d.count;
                            }
                        })
                        .sort(function(a, b) {
                            return b.value - a.value
                        });

                d3.treemap()
                        .size([width, height])
                        .padding(2)
                        (root);

                // use this information to add rectangles:
                div
                        .selectAll("div")
                        .data(root.leaves())
                        .enter()
                        .append("div")
                        .attr("class",function(d) {
                            var classes = "node";

                            if (d.value * 100 > total) {
                                classes += " with-label";
                            }

                            if (d.data.isRoot === 1) {
                                classes += " is-root";
                            } else if(d.data.isLeaf === 1) {
                                classes += " is-leaf";
                            } else {
                                classes += " navable";
                            }

                            return classes;
                        })
                        .call(nodePosition)
                        .on('click',clickedNode)
                        .on('mouseover',highlightNode)
                        .on('mouseout',function() { highlightNode(null); })
                        .on('mousemove',moveTooltip);

                // append the headings
                d3.selectAll(".node.with-label")
                        .append('span')
                        .attr('class', "node-title")
                        .html(function(d) {
                            var cpv = app.cpvMap[d.data.cpv];

                            var html = '';

                            //html += cpv.name + ' <small>('+d.data.cpv+')</small>';
                            html += cpv.name;
                            html += d.data.isRoot ? '<br><small>nicht genauer definiert</small>' : '';

                            return html;
                        });

                /*
                thats not cutting it. fix 'laggy' tooltip feeling TODO
                d3.select('div.ov-tooltip')
                        .on('mouseover',moveTooltip)
                        .on('mousemove',moveTooltip);
                        */

                // attach window resize functionality
                d3.select(window).on('resize', resize);
            }

            function clickedNode(d) {
                if (d.data.isRoot || d.data.isLeaf) {
                    return; // no further navigation possible
                }

                var cpv = app.cpvMap[d.data.cpv];
                var url = app.util.buildViewUrl('cpv', app.parameters, { root: cpv } );

                document.location = url;
            }

            function highlightNode(d) {
                var node = d3.select(this);

                if (!d) {
                    d3.selectAll('.treemap .node').classed('highlight',false);
                    $tooltip.hide();
                    return;
                }

                node.classed('highlight',true);

                showTooltip(d);
                moveTooltip(d);
            }

            function showTooltip(d) {
                // reset
                $tooltipInner.html('');

                // build html
                var cpv = app.cpvMap[d.data.cpv];

                $tooltipInner.append('<span class="title"><small>'+cpv.code+'</small><br>'+cpv.name+'</span>');

                if (params.type == 'volume') {
                    $tooltipInner.append('<span class="value">'+app.util.uiNumberToMoney(d.value, 0)+'</span>');
                } else {
                    $tooltipInner.append('<span class="count">Anzahl: '+ d.value + '</span>');
                }

                $tooltip.show();
            }

            function moveTooltip(d) {
                var evt = d3.event;
                // position
                var offsetX = 15;
                var offsetY = 15;
                $tooltip.css('left', (evt.pageX + offsetX) + 'px');
                $tooltip.css('top', (evt.pageY + offsetY) + 'px');
            }

            function resize() {
                // update things when the window size changes
                container_width = $('.treemap').parent().width();

                width = container_width - margin.left - margin.right;
                height = container_height - margin.top - margin.bottom;

                // resize the treemap container
                div = d3.select("div.treemap")
                        .style("width", (width + margin.left + margin.right) + "px")
                        .style("height", (height + margin.top + margin.bottom) + "px");

                d3.select('.treemap div').remove();
                initTreemap();
            }

            function nodePosition(selection) {
                selection.style("left", function(d) {
                    return d.x0 + "px";
                })
                        .style("top", function(d) {
                            return d.y0 + "px";
                        })
                        .style("width", function(d) {
                            return Math.max(0, (d.x1 - d.x0) - 1) + "px";
                        })
                        .style("height", function(d) {
                            return Math.max(0, (d.y1 - d.y0) - 1) + "px";
                        });
            }
        })(__ives);
    </script>
@stop