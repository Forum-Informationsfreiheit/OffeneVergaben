<div class="modal fade" id="confirmPublishPageModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmPublishPageModalTitle"></h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmPublishPageModalText"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">{{ __('global.cancel') }}</button>
                <a class="btn btn-primary" href="#" id="confirmPublishPageModalButton"
                   onclick="event.preventDefault();document.getElementById('confirmPublishPageForm').submit();">
                </a>
                <form id="confirmPublishPageForm" action="{{ route('admin::publish-page') }}" method="POST" style="display: none;">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="mode" id="confirmPublishPageModalMode">
                    <input type="hidden" name="id" id="confirmPublishPageModalId">
                </form>
            </div>
        </div>
    </div>
</div>