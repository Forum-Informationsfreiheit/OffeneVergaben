<div class="modal fade" id="confirmPublishPostModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmPublishPostModalTitle"></h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmPublishPostModalText"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">{{ __('global.cancel') }}</button>
                <a class="btn btn-primary" href="#" id="confirmPublishPostModalButton"
                   onclick="event.preventDefault();document.getElementById('confirmPublishPostForm').submit();">
                </a>
                <form id="confirmPublishPostForm" action="{{ route('admin::publish-post') }}" method="POST" style="display: none;">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="mode" id="confirmPublishPostModalMode">
                    <input type="hidden" name="id" id="confirmPublishPostModalId">
                </form>
            </div>
        </div>
    </div>
</div>