@extends('layouts.admin')
@section('title', 'Permissies')
@section('actions')
    <a class="button" href="{{ route('admin.permissions.create') }}">+ Nieuwe permissie</a>
@endsection
@section('content')
    <p class="muted">Beheer de acties die je later aan rollen kunt koppelen.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Naam</th><th scope="col">Guard</th><th scope="col">Acties</th></tr></thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td><td>{{ $permission->guard_name }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.permissions.edit', $permission) }}">Bewerken</a>
                        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}"
                              onsubmit="return confirm('Deze permissie verwijderen? Ook de bijbehorende koppelingen worden verwijderd.');">
                            @csrf @method('DELETE')
                            <button class="text-button danger" type="submit">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Nog geen permissies. Voeg je eerste permissie toe.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination', ['records' => $permissions])
@endsection
