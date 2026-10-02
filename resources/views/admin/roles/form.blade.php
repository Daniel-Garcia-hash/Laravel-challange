@extends('layouts.admin')
@section('title', $role->exists ? 'Rol bewerken' : 'Nieuwe rol')
@section('content')
    <form class="edit-form" method="POST"
          action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if($role->exists) @method('PUT') @endif
        <div class="field">
            <label for="name">Naam</label>
            <input id="name" name="name" type="text" required maxlength="255" autofocus
                   value="{{ old('name', $role->name) }}" @if($role->name === 'admin') readonly @endif @error('name') aria-invalid="true" @enderror>
        </div>
        <div class="field">
            <label for="guard">Guard</label><input id="guard" value="web" disabled>
            <p class="hint">Wordt automatisch ingesteld.</p>
        </div>
        <div class="form-actions">
            <button class="button" type="submit">{{ $role->exists ? 'Wijzigingen opslaan' : 'Rol opslaan' }}</button>
            <a class="button secondary" href="{{ route('admin.roles.index') }}">Annuleren</a>
        </div>
    </form>
@endsection
