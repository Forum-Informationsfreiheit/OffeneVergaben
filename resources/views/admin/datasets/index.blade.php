@extends('admin.layouts.default')

@section('body:class','datasets')

@section('page:heading','')

@section('modals:append')
    @include('admin.modals.disable-dataset')
@stop

@section('page:content')
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex flex-row align-items-center justify-content-between mb-3">
                <h6 class="m-0 font-weight-bold text-primary">Kerndaten (Aufträge)</h6>
            </div>
            <div class="filter-block">
                <form method="GET" action="{{ route('admin::datasets') }}" style="display: block;">
                    <div class="form-inline">
                        <div class="form-group">
                            <input name="id" type="text" class="form-control form-control-sm mr-2" placeholder="id" style="width: 80px;" value="{{ request('id','') }}">
                        </div>
                        <div class="form-check form-check-inline mr-5">
                            <input class="form-check-input" type="checkbox" id="inactive" name="inactive" value="1" {!! request()->has('inactive') ? 'checked="checked"' : ''  !!} autocomplete="off">
                            <label class="form-check-label" for="inactive">nur deaktivierte Aufträge anzeigen</label>
                        </div>
                        <button title="Filtern" class="btn btn-outline-secondary btn-sm" type="submit"><i class="fas fa-filter" style="font-size: 0.7rem;"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-2">
                <span style="font-size: 0.8rem; padding: .3rem;"><em>{{ $total }}</em> Ergebnisse</span>
            </div>
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">&nbsp;</th>
                    <th scope="col">Titel</th>
                    <th scope="col">Auftraggeber</th>
                    <th scope="col">Lieferant</th>
                    <th scope="col">Aktualisiert</th>
                    @can('update-datasets')
                        <th>Aktionen</th>
                    @endcan
                </tr>
                </thead>
                <tbody>
                @foreach($datasets as $dataset)
                    <tr>
                        <td title="{{ $dataset->disabled_at ? 'Deaktiviert am ' . $dataset->disabled_at->format('d.m.Y H:i') : '' }}">{!! $dataset->disabled_at ? '<i class="fa fa-eye-slash"></i>' : ''  !!}</td>
                        <td title="{{ $dataset->title }}">{{ ui_shorten($dataset->title) }}</td>
                        <td title="{{ $dataset->offeror->name }}">{{ ui_shorten($dataset->offeror->name,35) }}</td>
                        <td title="{{ $dataset->contractor ? $dataset->contractor->name : '' }}">{{ $dataset->contractor ? ui_shorten($dataset->contractor->name,35) : '' }}</td>
                        <td>{{ $dataset->item_lastmod->format('d.m.Y') }}&nbsp;<span title="Version {{ $dataset->version }}">v{{ $dataset->version }}</span></td>
                        @can('update-datasets')
                            <td>
                                <a class="action-link show" href="{{ route('public::auftrag',[ 'id' => $dataset->id ]) }}" title="Anzeigen">
                                    <i class="fas fa-w fa-external-link-alt"></i>
                                </a>
                                <a class="action-link {{ !$dataset->disabled_at ? 'disable-dataset' : 'enable-dataset' }}"
                                   data-confirm-disable
                                   data-disable-id="{{ $dataset->id }}"
                                   data-disable-mode="{{ !$dataset->disabled_at ? 'disable' : 'enable' }}"
                                   data-disable-button-text="{{ !$dataset->disabled_at ? 'Kerndatensatz deaktivieren' : 'Deaktivierung zurücknehmen' }}"
                                   data-disable-button-class="{{ !$dataset->disabled_at ? 'btn-danger' : 'btn-primary' }}"
                                   data-disable-text="{{ !$dataset->disabled_at ? 'Kerndatensatz <em>'.$dataset->title.' (Auftraggeber: '. $dataset->offeror->name . ')</em> deaktivieren? <br><br>Falls weitere Versionen des gleichen Datensatzes existieren werden diese ebenfalls deaktiviert.' : 'Deaktivierung des Kerndatensatz <em>'.$dataset->title.' (Auftraggeber: '. $dataset->offeror->name . ')</em> zurücknehmen?' }}"
                                   data-disable-title="{{ !$dataset->disabled_at ? 'Kerndatensatz deaktivieren' : 'Kerndatensatz Deaktivierung zurücknehmen' }}"
                                   data-toggle="modal"
                                   data-target="#confirmDisableDatasetModal"
                                   href="#" role="button"
                                   title="{{ !$dataset->disabled_at ? 'Deaktivieren' : 'Deaktivierung zurücknehmen' }}">
                                    <i class="fas fa-w {{ !$dataset->disabled_at ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </a>
                            </td>
                        @endcan
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $datasets->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
    <script>

        $(document).ready(function() {
            var $disableAction = $('[data-confirm-disable]');
            var $confirmDisableDatasetModal = $('#confirmDisableDatasetModal');
            $disableAction.on('click',function() {
                var id = $(this).data('disableId');
                var text = $(this).data('disableText');
                var title = $(this).data('disableTitle');
                var mode = $(this).data('disableMode');
                var buttonText = $(this).data('disableButtonText');
                var buttonClass = $(this).data('disableButtonClass');

                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalTitle').text(title);
                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalText').html(text);
                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalButton').text(buttonText);

                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalButton').removeClass('btn-danger');
                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalButton').removeClass('btn-primary');
                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalButton').addClass(buttonClass);

                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalMode').val(mode);
                $confirmDisableDatasetModal.find('#confirmDisableDatasetModalId').val(id);
            });
        });

    </script>

@stop