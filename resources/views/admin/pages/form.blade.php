@csrf
@if ($mode == 'edit')
    @method('PATCH')
    @if ($page)
        <input type="hidden" name="id" value="{{ $page->id }}">
    @endif
@endif
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="name">Titel</label>
            <input name="title" class="form-control" type="text" value="{{ old('title',$page ? $page->title : '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="description">Body</label>
            <textarea class="form-control" rows="10" id="body" name="body">{{ old('body',$page ? $page->body : '') }}</textarea>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <label for="slug">URL Alias&nbsp;<small>optional</small></label>
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span style="font-size: 0.9rem" class="input-group-text">{{ url('/page') }}/</span>
            </div>
            <input name="slug" class="form-control" type="text" value="{{ old('slug',$page ? $page->slug : '') }}" aria-describedby="slugHelpBlock">
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
    console.log(route_prefix);
    var editor_config = {
        path_absolute : "",
        selector: "#body",
        plugins: [
            "link image table code anchor"
        ],
        image_caption: true,
        relative_urls: false,
        height: 300,
        file_browser_callback : function(field_name, url, type, win) {
            var x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
            var y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight;

            var cmsURL = editor_config.path_absolute + route_prefix + '?field_name=' + field_name;
            if (type == 'image') {
                cmsURL = cmsURL + "&type=Images";
            } else {
                cmsURL = cmsURL + "&type=Files";
            }

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

    tinymce.init(editor_config);
</script>