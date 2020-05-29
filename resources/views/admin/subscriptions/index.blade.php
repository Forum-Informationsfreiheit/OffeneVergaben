@extends('admin.layouts.default')

@section('page:heading','')

@section('modals:append')
    @include('admin.modals.delete.subscription')
@stop

@section('page:content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Abonnements</h6>
        </div>
        <div class="card-body">
            <div class="mb-2">
                <span style="font-size: 0.8rem; padding: .3rem;"><em>{{ $total }}</em> Ergebnisse</span>
            </div>
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Email</th>
                    <th scope="col">Titel</th>
                    <th scope="col">Query</th>
                    <th scope="col">Verifiziert am</th>
                    @if(Auth::user()->isAdmin())
                        <th>Aktionen</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach($subscriptions as $subscription)
                    <tr>
                        <th scope="row">{{ $subscription->id }}</th>
                        <td>{{ $subscription->subscriber->email }}</td>
                        <td title="{{ $subscription->title }}">{{ ui_shorten($subscription->title,30) }}</td>
                        <td title="{{ $subscription->query }}">{{ ui_shorten($subscription->query,30) }}</td>
                        <td>{{ $subscription->verified_at ? $subscription->verified_at->format('d.m.Y H:i') : '-' }}</td>
                        @if(Auth::user()->isAdmin())
                            <td>
                                @if(!$subscription->verified_at)
                                    <a class="action-link" href="#" role="button" title="Bestätigungsemail erneut versenden"
                                       onclick="event.preventDefault(); if(confirm('Bestätigungsemail erneut versenden?')) { document.getElementById('resendSubscriptionVerificationNotificationForm_{{ $subscription->id }}').submit(); }">
                                        <i class="far fa-share-square"></i>
                                    </a>
                                    <form style="display: inline;" id="resendSubscriptionVerificationNotificationForm_{{ $subscription->id }}" method="POST" action="{{ route('admin::resend-subscription-verification-notification',$subscription->id) }}">
                                         @method('PATCH')
                                         @csrf
                                        <input type="hidden" name="id" value="{{ $subscription->id }}">
                                    </form>
                                @endif
                                <a class="action-link delete"
                                   data-confirm-delete
                                   data-delete-string="{{ $subscription->subscriber->email }} - {{ $subscription->title }}"
                                   data-delete-id="{{ $subscription->id }}"
                                   data-toggle="modal"
                                   data-target="#confirmDeleteSubscriptionModal"
                                   href="#" role="button">
                                    <i class="fas fa-w fa-times"></i>
                                </a>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $subscriptions->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var $deleteAction = $('[data-confirm-delete]');
            var $confirmDeleteModal = $('#confirmDeleteSubscriptionModal');
            $deleteAction.on('click', function () {
                var id = $(this).data('deleteId');
                var string = $(this).data('deleteString');

                $confirmDeleteModal.find('#confirmDeleteSubscriptionId').val(id);
                $confirmDeleteModal.find('#confirmDeleteSubscriptionTitle').text(string);
            });
        });
    </script>
@stop