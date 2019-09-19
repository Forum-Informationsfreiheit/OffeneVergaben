@extends('admin.layouts.default')

@section('page:heading','')

@section('page:content')

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Pages</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(17px, 19px, 0px);">
                    <div class="dropdown-header">Aktionen</div>
                    <a class="dropdown-item" href="{{ route('admin::create-page') }}"><i class="fas fa-plus fa-sm"></i>&nbsp;&nbsp;Neue Page anlegen</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th scope="col">Titel</th>
                    <th scope="col">Pfad</th>
                    <th scope="col">Erstellt am</th>
                    <th scope="col">Aktualisiert am</th>
                    @can('update-pages')
                        <th>Aktionen</th>
                    @endcan
                </tr>
                </thead>
                <tbody>
                @foreach($pages as $page)
                    <tr>
                        <td>{!! !$page->published_at ? "<i class='fas fa-lock'></i>" : "" !!}</td>
                        <td>{{ $page->title }}</td>
                        <td>{{ $page->slug }}</td>
                        <td>{{ $page->created_at->format('d.m.Y') }} <span class="btn btn-primary btn-circle btn-xs">{{ strtoupper(Auth::user()->initials) }}</span></td>
                        <td>{{ $page->updated_at->format('d.m.Y') }}</td>
                        @can('update-tags')
                            <td>
                                <a class="action-link" href="{{ route('admin::edit-page',$page->id) }}" role="button">
                                    <i class="fas fa-w fa-pencil-alt"></i>
                                </a>
                                <a class="action-link publish"
                                   data-confirm-publish
                                   data-publish-id="{{ $page->id }}"
                                   data-publish-mode="{{ $page->published_at ? 'unpublish' : 'publish' }}"
                                   data-publish-button-text="{{ $page->published_at ? 'Veröffentlichung zurücknehmen' : 'Veröffentlichen' }}"
                                   data-publish-text="{{ $page->published_at ? 'Veröffentlichung der Page <em>'.$page->title.'</em> zurücknehmen?' : 'Page <em>'.$page->title.'</em> veröffentlichen?' }}"
                                   data-publish-title="{{ $page->published_at ? 'Veröffentlichung zurücknehmen' : 'Page veröffentlichen' }}"
                                   data-toggle="modal"
                                   data-target="#confirmPublishPageModal"
                                   href="#" role="button">
                                    <i title="{{ !$page->published_at ? 'Publish' : 'Unpublish' }}" class="fas fa-w {{ !$page->published_at ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                </a>
                                <a class="action-link delete"
                                   data-confirm-delete
                                   data-delete-id="{{ $page->id }}"
                                   data-delete-string="{{ $page->titel }}"
                                   data-toggle="modal"
                                   data-target="#confirmDeletePageModal"
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
            var $confirmDeleteModal = $('#confirmDeletePageModal');
            $deleteAction.on('click',function() {
                var id = $(this).data('deleteId');
                var string = $(this).data('deleteString');

                $confirmDeleteModal.find('#confirmDeletePageId').val(id);
                $confirmDeleteModal.find('#confirmDeletePageName').text(string);
            });


            var $publishAction = $('[data-confirm-publish]');
            var $confirmPublishPageModal = $('#confirmPublishPageModal');
            $publishAction.on('click',function() {
                var id = $(this).data('publishId');
                var text = $(this).data('publishText');
                var title = $(this).data('publishTitle');
                var mode = $(this).data('publishMode');
                var buttonText = $(this).data('publishButtonText');

                $confirmPublishPageModal.find('#confirmPublishPageModalTitle').text(title);
                $confirmPublishPageModal.find('#confirmPublishPageModalText').html(text);
                $confirmPublishPageModal.find('#confirmPublishPageModalButton').text(buttonText);
                $confirmPublishPageModal.find('#confirmPublishPageModalMode').val(mode);
                $confirmPublishPageModal.find('#confirmPublishPageModalId').val(id);
            });
        });
    </script>

@stop