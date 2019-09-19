@extends('admin.layouts.default')

@section('page:heading','')

@section('page:content')

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Benutzer</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(17px, 19px, 0px);">
                    <div class="dropdown-header">Aktionen</div>
                    <a class="dropdown-item" href="{{ route('admin::create-user') }}"><i class="fas fa-plus fa-sm"></i>&nbsp;&nbsp;Neuer Benutzer</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Rolle</th>
                    <th scope="col">Erstellt am</th>
                    @if(Auth::user()->isAdmin())
                        <th>Aktionen</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <th scope="row">{{ $user->id }}</th>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->name }}</td>
                        <td>{{ $user->created_at->format('d.m.Y') }}</td>
                        @if(Auth::user()->isAdmin())
                            <td>
                                <a class="action-link" href="{{ route('admin::edit-user',$user->id) }}" role="button">
                                    <i class="fas fa-w fa-pencil-alt"></i>
                                </a>
                                @if($user->id != 1)
                                    <a class="action-link delete"
                                       data-confirm-delete
                                       data-delete-string="{{ $user->name . ' - ' . $user->email }}"
                                       data-delete-id="{{ $user->id }}"
                                       data-toggle="modal"
                                       data-target="#confirmDeleteUserModal"
                                       href="#" role="button">
                                        <i class="fas fa-w fa-times"></i>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var $deleteAction = $('[data-confirm-delete]');
            var $deleteConfirmModal = $('#confirmDeleteUserModal');
            $deleteAction.on('click',function() {
                var userId = $(this).data('deleteId');
                var userString = $(this).data('deleteString');

                $deleteConfirmModal.find('#confirmDeleteUserId').val(userId);
                $deleteConfirmModal.find('#confirmDeleteUserName').text(userString);
            });
        });
    </script>
@stop