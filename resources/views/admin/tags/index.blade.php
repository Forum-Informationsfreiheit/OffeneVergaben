@extends('admin.layouts.default')

@section('page:heading','')

@section('page:content')

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Tags</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(17px, 19px, 0px);">
                    <div class="dropdown-header">Aktionen</div>
                    <a class="dropdown-item" href="{{ route('admin::create-tag') }}"><i class="fas fa-plus fa-sm"></i>&nbsp;&nbsp;Neuen Tag anlegen</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Tag</th>
                    <th scope="col">Beschreibung</th>
                    <th scope="col">Erstellt am</th>
                    @can('update-tags')
                        <th>Aktionen</th>
                    @endcan
                </tr>
                </thead>
                <tbody>
                @foreach($tags as $tag)
                    <tr>
                        <td>{{ $tag->name }}</td>
                        <td title="{{ $tag->description }}">{{ ui_shorten($tag->description,90) }}</td>
                        <td>{{ $tag->created_at->format('d.m.Y') }}</td>
                        @can('update-tags')
                            <td>
                                <a class="action-link" href="{{ route('admin::edit-tag',$tag->id) }}" role="button">
                                    <i class="fas fa-w fa-pencil-alt"></i>
                                </a>
                                <a class="action-link delete"
                                   data-confirm-delete
                                   data-delete-string="{{ $tag->name }}"
                                   data-delete-id="{{ $tag->id }}"
                                   data-toggle="modal"
                                   data-target="#confirmDeleteTagModal"
                                   href="#" role="button">
                                    <i class="fas fa-w fa-times"></i>
                                </a>
                            </td>
                        @endcan
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            var $deleteAction = $('[data-confirm-delete]');
            var $deleteConfirmModal = $('#confirmDeleteTagModal');
            $deleteAction.on('click',function() {
                var tagId = $(this).data('deleteId');
                var tagString = $(this).data('deleteString');

                $deleteConfirmModal.find('#confirmDeleteTagId').val(tagId);
                $deleteConfirmModal.find('#confirmDeleteTagName').text(tagString);
            });
        });
    </script>

@stop