@csrf
@if ($mode == 'edit')
    @method('PATCH')
    @if ($user)
        <input type="hidden" name="id" value="{{ $user->id }}">
    @endif
@endif
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Name</label>
            <input name="name" class="form-control" type="text" value="{{ old('name',$user ? $user->name : '') }}">
        </div>
    </div>
    <div class="col-md-6">
        @if(Auth::user()->isAdmin())
            <div class="form-group">
                <label for="role">Rolle</label>
                <select name="role" class="form-control">
                    @foreach($roles as $role)
                        <option {{ (old('role') == $role->id) || ($mode === 'edit' && $user && $user->role_id === $role->id) ? "selected" : "" }} value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="email">E-Mail</label>
            <input name="email" class="form-control" type="text" value="{{ old('email',$user ? $user->email : '') }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div style="<?php echo $mode == 'edit' ? 'display:none;' : '' ?>" data-id="passwordBlock">
            <div class="form-group">
                <label>Passwort</label>
                <input name="password" class="form-control" type="password">
            </div>
            <div class="form-group">
                <label>Passwort&nbsp;<small>wiederholen</small></label>
                <input name="password_confirmation" class="form-control" type="password">
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div style="margin: 10px 0 20px 0;">
            <a class="<?php echo $mode == 'create' ? 'hide' : '' ?>" data-id="toggleChangePassword" href="#">Passwort ändern</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <button class="btn btn-primary" type="submit">Speichern</button>
    </div>
</div>

<script>
    var $passwordBlock = $('[data-id="passwordBlock"]');
    $('[data-id="toggleChangePassword"]').on('click',function() {
        $passwordBlock.toggle();
        $(this).hide();
    })
</script>