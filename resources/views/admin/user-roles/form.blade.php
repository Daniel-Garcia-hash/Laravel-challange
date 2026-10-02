@extends('layouts.admin')
@section('title', $role ? 'Gebruiker-rol bewerken' : 'Nieuwe gebruiker-rol')
@section('content')
    @if($roles->isEmpty() || $users->isEmpty())
        <div class="notice">Maak eerst een <a href="{{ route('admin.roles.create') }}">rol</a> aan.
            Gebruikers kunnen zich via de registratiepagina aanmelden.</div>
    @endif
    <form class="edit-form" method="POST"
          action="{{ $role ? route('admin.user-roles.update', [$role, $user]) : route('admin.user-roles.store') }}">
        @csrf
        @if($role) @method('PUT') @endif
        <div class="field">
            <label for="user_id">Gebruiker</label>
            <select id="user_id" name="user_id" required>
                <option value="">Kies een gebruiker</option>
                @foreach($users as $option)
                    <option value="{{ $option->id }}" @selected(old('user_id', $user?->id) == $option->id)>{{ $option->name }} · {{ $option->email }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="role_id">Rol</label>
            <select id="role_id" name="role_id" required>
                <option value="">Kies een rol</option>
                @foreach($roles as $option)
                    <option value="{{ $option->id }}" @selected(old('role_id', $role?->id) == $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="model_type">Modeltype</label>
            <input id="model_type" value="{{ $modelType }}" disabled>
            <p class="hint">Wordt automatisch ingesteld.</p>
        </div>
        <div class="form-actions">
            <button class="button" type="submit" @disabled($roles->isEmpty() || $users->isEmpty())>Koppeling opslaan</button>
            <a class="button secondary" href="{{ route('admin.user-roles.index') }}">Annuleren</a>
        </div>
    </form>
@endsection
