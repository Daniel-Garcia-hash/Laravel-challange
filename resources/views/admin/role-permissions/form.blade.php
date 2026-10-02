@extends('layouts.admin')
@section('title', $role ? 'Koppeling bewerken' : 'Nieuwe rol-permissie')
@section('content')
    @if($roles->isEmpty() || $permissions->isEmpty())
        <div class="notice">Maak eerst een <a href="{{ route('admin.roles.create') }}">rol</a> en een
            <a href="{{ route('admin.permissions.create') }}">permissie</a> aan.</div>
    @endif
    <form class="edit-form" method="POST"
          action="{{ $role ? route('admin.role-permissions.update', [$role, $permission]) : route('admin.role-permissions.store') }}">
        @csrf
        @if($role) @method('PUT') @endif
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
            <label for="permission_id">Permissie</label>
            <select id="permission_id" name="permission_id" required>
                <option value="">Kies een permissie</option>
                @foreach($permissions as $option)
                    <option value="{{ $option->id }}" @selected(old('permission_id', $permission?->id) == $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-actions">
            <button class="button" type="submit" @disabled($roles->isEmpty() || $permissions->isEmpty())>Koppeling opslaan</button>
            <a class="button secondary" href="{{ route('admin.role-permissions.index') }}">Annuleren</a>
        </div>
    </form>
@endsection
