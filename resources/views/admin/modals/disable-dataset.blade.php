<div class="modal fade" id="confirmDisableDatasetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDisableDatasetModalTitle"></h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmDisableDatasetModalText"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">{{ __('global.cancel') }}</button>
                <a class="btn " href="#" id="confirmDisableDatasetModalButton"
                   onclick="event.preventDefault();document.getElementById('confirmDisableDatasetModalForm').submit();">
                </a>
                <form id="confirmDisableDatasetModalForm" action="{{ route('admin::disable-dataset') }}" method="POST" style="display: none;">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="mode" id="confirmDisableDatasetModalMode">
                    <input type="hidden" name="id" id="confirmDisableDatasetModalId">
                </form>
            </div>
        </div>
    </div>
</div>