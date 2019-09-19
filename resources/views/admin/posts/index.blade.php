@extends('admin.layouts.default')

@section('page:heading','')

@section('page:content')

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Posts</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(17px, 19px, 0px);">
                    <div class="dropdown-header">Aktionen</div>
                    <a class="dropdown-item" href="{{ route('admin::create-post') }}"><i class="fas fa-plus fa-sm"></i>&nbsp;&nbsp;Neuen Post schreiben</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">&nbsp;</th>
                    <th scope="col">Titel</th>
                    <th scope="col">Pfad</th>
                    <th scope="col">Erstellt am</th>
                    <th scope="col">Aktualisiert am</th>
                    @can('update-posts')
                        <th>Aktionen</th>
                    @endcan
                </tr>
                </thead>
                <tbody>
                @foreach($posts as $post)
                    <tr>
                        <td>{!! !$post->published_at ? "<i class='fas fa-lock'></i>" : "" !!}</td>
                        <td>{{ $post->title }}</td>
                        <td>{{ $post->slug }}</td>
                        <td>{{ $post->created_at->format('d.m.Y') }} <span class="btn btn-primary btn-circle btn-xs">{{ strtoupper(Auth::user()->initials) }}</span></td>
                        <td>{{ $post->updated_at->format('d.m.Y') }}</td>
                        @can('update-tags')
                            <td>
                                <a class="action-link" href="{{ route('admin::edit-post',$post->id) }}" role="button">
                                    <i class="fas fa-w fa-pencil-alt"></i>
                                </a>
                                <a class="action-link publish"
                                   data-confirm-publish
                                   data-publish-id="{{ $post->id }}"
                                   data-publish-mode="{{ $post->published_at ? 'unpublish' : 'publish' }}"
                                   data-publish-button-text="{{ $post->published_at ? 'Veröffentlichung zurücknehmen' : 'Veröffentlichen' }}"
                                   data-publish-text="{{ $post->published_at ? 'Veröffentlichung des Posts <em>'.$post->title.'</em> zurücknehmen?' : 'Post <em>'.$post->title.'</em> veröffentlichen?' }}"
                                   data-publish-title="{{ $post->published_at ? 'Veröffentlichung zurücknehmen' : 'Post veröffentlichen' }}"
                                   data-toggle="modal"
                                   data-target="#confirmPublishPostModal"
                                   href="#" role="button">
                                    <i title="{{ !$post->published_at ? 'Publish' : 'Unpublish' }}" class="fas fa-w {{ !$post->published_at ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                </a>
                                <a class="action-link delete"
                                   data-confirm-delete
                                   data-delete-string="{{ $post->title }}"
                                   data-delete-id="{{ $post->id }}"
                                   data-toggle="modal"
                                   data-target="#confirmDeletePostModal"
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
            var $confirmDeleteModal = $('#confirmDeletePostModal');
            $deleteAction.on('click',function() {
                var id = $(this).data('deleteId');
                var string = $(this).data('deleteString');

                $confirmDeleteModal.find('#confirmDeletePostId').val(id);
                $confirmDeleteModal.find('#confirmDeletePostName').text(string);
            });

            var $publishAction = $('[data-confirm-publish]');
            var $confirmPublishPostModal = $('#confirmPublishPostModal');
            $publishAction.on('click',function() {
                var id = $(this).data('publishId');
                var text = $(this).data('publishText');
                var title = $(this).data('publishTitle');
                var mode = $(this).data('publishMode');
                var buttonText = $(this).data('publishButtonText');

                $confirmPublishPostModal.find('#confirmPublishPostModalTitle').text(title);
                $confirmPublishPostModal.find('#confirmPublishPostModalText').html(text);
                $confirmPublishPostModal.find('#confirmPublishPostModalButton').text(buttonText);
                $confirmPublishPostModal.find('#confirmPublishPostModalMode').val(mode);
                $confirmPublishPostModal.find('#confirmPublishPostModalId').val(id);
            });
        });
    </script>

@stop