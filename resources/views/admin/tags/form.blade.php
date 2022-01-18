@csrf
@if ($mode == 'edit')
    @method('PATCH')
    @if ($tag)
        <input type="hidden" name="id" value="{{ $tag->id }}">
    @endif
@endif
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Name</label>
            <input name="name" class="form-control" type="text" value="{{ old('name',$tag ? $tag->name : '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="description">Beschreibung&nbsp;<small>optional</small></label>
            <textarea class="form-control" rows="2" name="description">{{ old('description',$tag ? $tag->description : '') }}</textarea>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <button class="btn btn-primary" type="submit">Speichern</button>
    </div>
</div>