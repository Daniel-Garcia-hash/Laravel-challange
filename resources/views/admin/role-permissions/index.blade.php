@extends('layouts.admin')
@section('title', 'Permissies per rol')
@section('actions')
    <a class="button" href="{{ route('admin.role-permissions.create') }}">+ Nieuwe koppeling</a>
@endsection
@section('content')
    <p class="muted">Bekijk welke acties iedere rol mag uitvoeren.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Rol</th><th scope="col">Permissie</th><th scope="col">Acties</th></tr></thead>
            <tbody>
            @forelse($links as $link)
                <tr>
                    <td>{{ $link->role_name }}</td><td>{{ $link->permission_name }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.role-permissions.edit', [$link->role_id, $link->permission_id]) }}">Bewerken</a>
                        <form method="POST" action="{{ route('admin.role-permissions.destroy', [$link->role_id, $link->permission_id]) }}"
                              onsubmit="return confirm('Deze permissie van de rol ontkoppelen?');">
                            @csrf @method('DELETE')
                            <button class="text-button danger" type="submit">Ontkoppelen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Nog geen koppelingen. Koppel een permissie aan een rol.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination', ['records' => $links])
@endsection
