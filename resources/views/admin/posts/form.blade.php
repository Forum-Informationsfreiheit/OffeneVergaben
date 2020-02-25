@csrf
@if ($mode == 'edit')
    @method('PATCH')
    @if ($post)
        <input type="hidden" name="id" value="{{ $post->id }}">
    @endif
@endif
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="name">Titel</label>
            <input name="title" class="form-control" type="text" value="{{ old('title',$post ? $post->title : '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Teaser Bild</label>
            <div class="input-group">
                <span class="input-group-btn">
                    <a style="display: {{ $post && $post->thumb ? 'none' : 'block' }};" id="lfm" href="#" data-input="image_filepath" data-preview="lfm_holder" class="btn btn-primary btn-sm">
                        <i class="fas fa-image"></i>&nbsp;auswählen
                    </a>
                </span>
                <input id="image_filepath" class="form-control" type="hidden" name="image_filepath">
                <!-- <input id="reset_image" class="form-control" type="hidden" name="reset_image" value=""> -->
            </div>
            <div class="holder-preview">
                <img id="lfm_holder" style="max-height: 100px;" src="{{ $post && $post->thumb ? url($post->thumb) : '' }}">
                <a style="display: {{ $post && $post->thumb ? 'inline' : 'none' }};" class="reset-image" href="" data-trigger="resetImage">&times;&nbsp;Bild entfernen</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="tags">Tags</label>
            <select name="tags[]" id="selectTags">
                <!-- filled via JS -->
            </select>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="summary">Zusammenfassung / Teaser</label>
            <textarea class="form-control" rows="3" id="summary" name="summary">{{ old('summary',$post ? $post->summary : '') }}</textarea>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="description">Body</label>
            <textarea class="form-control" rows="10" id="body" name="body">{{ old('body',$post ? $post->body : '') }}</textarea>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <label for="slug">URL Alias&nbsp;<small>optional</small></label>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span style="font-size: 0.9rem" class="input-group-text">{{ url('/blog') }}/</span>
            </div>
            <input name="slug" class="form-control" type="text" value="{{ old('slug',$post ? $post->slug : '') }}" aria-describedby="slugHelpBlock">
            <small id="slugHelpBlock" class="form-text text-muted">
                Wird das Feld leer gelassen, wird automatisch ein Alias anhand des Titels generiert. <strong>Achtung:</strong> Das nachträgliche Ändern des URL Alias' von bereits veröffentlichten Pages kann zum 'breaken' externer Links führen.
            </small>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <button class="btn btn-primary" type="submit">Speichern</button>
    </div>
</div>
<!-- TinyMCE init -->
<script src="{{ link_to_script('../vendor/tinymce/tinymce.min') }}"></script>
<script>
    var route_prefix = "{{ url(config('lfm.url_prefix', config('lfm.prefix'))) }}";

    var body_config = {
        path_absolute : "",
        selector: "#body",
        plugins: [
            "link image table code"
        ],
        image_caption: true,
        relative_urls: false,
        height: 300,
        file_browser_callback : function(field_name, url, type, win) {
            var x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
            var y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight;

            var cmsURL = body_config.path_absolute + route_prefix + '?field_name=' + field_name;
            cmsURL += type == "image" ? "&type=Images" : "&type=Files";

            tinyMCE.activeEditor.windowManager.open({
                file : cmsURL,
                title : 'Filemanager',
                width : x * 0.8,
                height : y * 0.8,
                resizable : "yes",
                close_previous : "no"
            });
        }
    };

    tinymce.init(body_config);

    var summary_config = {
        path_absolute : "",
        selector: "#summary",
        plugins: [ "link" ],
        menubar: false,
        relative_urls: false,
        height: 120,
        file_browser_callback : body_config.file_browser_callback,
    };

    tinymce.init(summary_config);
</script>
<!-- Selectize init -->
<link rel="stylesheet" href="{{ link_to_stylesheet('vendor/selectize/selectize.default') }}">
<script src="{{ link_to_script('vendor/selectize/selectize.min') }}"></script>
<script src="{{ url('vendor/laravel-filemanager/js/lfm.js') }}"></script>
<script>
    // wait for page load (vars are bound to footer)
    $(document).ready(function() {
        var data = __ives ? __ives.data : null;

        var availableTags = data.tags;
        var selectedTags  = data.selectedTags ? data.selectedTags : [];

        $('#selectTags').selectize({
            maxItems: 5,
            options: makeOptionsArray(),
            items: makeSelectedArray()
        });

        function makeSelectedArray() {
            var result = [];

            for (var i = 0; i < selectedTags.length; i++) {
                result.push(selectedTags[i].id);
            }

            return result;
        }

        function makeOptionsArray() {
            var result = [];

            for (var i = 0; i < availableTags.length; i++) {
                appendOption(availableTags[i].id, availableTags[i].name);
            }

            return result;

            function appendOption(value,text) {
                result.push({ value: value, text: text });
            }
        }

        var $selectImage = $('#lfm');
        var $selectImageHolder = $('#lfm_holder');
        // handle removal/reset of an image
        var $imgPreview = $('img#lfm_holder');
        // var $resetImageMarker = $('input#reset_image');
        var $resetImage = $('[data-trigger="resetImage"]');

        $resetImage.on('click',function(evt) {
            // reset
            // $resetImageMarker.val('RESET_IMAGE');
            $imgPreview.attr('src','');
            $(this).hide();
            $selectImage.show();

            evt.preventDefault();
            return false;
        });

        var route_prefix = "{{ url(config('lfm.url_prefix', config('lfm.prefix'))) }}";
        $selectImage.filemanager('image',{ prefix: route_prefix });
        $selectImageHolder.on('change',function(){
            $resetImage.toggle($(this).attr('src'));
            $selectImage.toggle($(this).attr('src'));
        });
    });
</script>