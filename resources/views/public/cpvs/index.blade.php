@extends('public.layouts.default')

@section('body:class','cpvs')

@section('page:content')
    <h1 class="page-title">
        Branchen
    </h1>
    <div class="row">
        <div class="col">
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
                        Wert
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->cpv }}">
                        <td class="code">
                            <a href="{{ route('public::branchen',[ 'node' => $cpvMap[$item->cpv]->code ]) }}">{{ $item->cpv }}</a>
                        </td>
                        <td class="name">
                            {{ $cpvMap[$item->cpv]->name }}
                        </td>
                        <td class="value">
                            {{ ui_format_money($item->sum) }}
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

            var total = sumRecords();

            initTreemap();

            function sumRecords() {
                var sum = 0;
                for (var i = 0; i < app.cpvRecords.length; i++) {
                    sum += parseInt(app.cpvRecords[i].sum);
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
                        .sum(function(d){ return d.sum})
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

                            html += cpv.name + ' <small>('+d.data.cpv+')</small>';
                            html += d.data.isRoot ? '<br><small>nicht genauer definiert</small>' : '';

                            return html;
                        });

                // attach window resize functionality
                d3.select(window).on('resize', resize);
            }

            function clickedNode(d) {
                if (d.data.isRoot || d.data.isLeaf) {
                    return; // no further navigation possible
                }

                var cpv = app.cpvMap[d.data.cpv];

                var baseUrl = "{{ route('public::branchen') }}";

                //var url = util.buildMainViewUrl(pageData.parameters, { ans: ans.slug } );

                document.location = baseUrl + "?node=" + cpv.code;
            }

            function highlightNode(d) {

            }

            function moveTooltip(d) {

            }

            function resize() {

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