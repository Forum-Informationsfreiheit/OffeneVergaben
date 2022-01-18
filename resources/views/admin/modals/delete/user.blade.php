<div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Benutzerkonto löschen</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Den Benutzer <em><span id="confirmDeleteUserName"></span></em> wirklich löschen?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">{{ __('global.cancel') }}</button>
                <a class="btn btn-danger" href="#"
                   onclick="event.preventDefault();document.getElementById('confirmDeleteUserForm').submit();">
                    {{ __('global.delete') }}
                </a>
                <form id="confirmDeleteUserForm" action="{{ route('admin::destroy-user') }}" method="POST" style="display: none;">
                    @csrf
                    @method('delete')
                    <input type="hidden" name="id" id="confirmDeleteUserId">
                </form>
            </div>
        </div>
    </div>
</div>