@extends('layouts.admin')
@section('title', $permission->exists ? 'Permissie bewerken' : 'Nieuwe permissie')
@section('content')
    <form class="edit-form" method="POST"
          action="{{ $permission->exists ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">
        @csrf
        @if($permission->exists) @method('PUT') @endif
        <div class="field">
            <label for="name">Naam</label>
            <input id="name" name="name" type="text" required maxlength="255" autofocus
                   value="{{ old('name', $permission->name) }}" @error('name') aria-invalid="true" @enderror>
        </div>
        <div class="field">
            <label for="guard">Guard</label><input id="guard" value="web" disabled>
            <p class="hint">Wordt automatisch ingesteld.</p>
        </div>
        <div class="form-actions">
            <button class="button" type="submit">{{ $permission->exists ? 'Wijzigingen opslaan' : 'Permissie opslaan' }}</button>
            <a class="button secondary" href="{{ route('admin.permissions.index') }}">Annuleren</a>
        </div>
    </form>
@endsection
