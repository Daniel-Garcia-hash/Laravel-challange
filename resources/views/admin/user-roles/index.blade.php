@extends('layouts.admin')
@section('title', 'Rollen per gebruiker')
@section('actions')
    <a class="button" href="{{ route('admin.user-roles.create') }}">+ Nieuwe koppeling</a>
@endsection
@section('content')
    <p class="muted">Geef bestaande gebruikers een rol of wijzig hun toegang.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Gebruiker</th><th scope="col">E-mail</th><th scope="col">Rol</th><th scope="col">Acties</th></tr></thead>
            <tbody>
            @forelse($links as $link)
                <tr>
                    <td>{{ $link->user_name }}</td><td>{{ $link->email }}</td><td>{{ $link->role_name }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.user-roles.edit', [$link->role_id, $link->user_id]) }}">Bewerken</a>
                        <form method="POST" action="{{ route('admin.user-roles.destroy', [$link->role_id, $link->user_id]) }}"
                              onsubmit="return confirm('Deze rol van de gebruiker ontkoppelen?');">
                            @csrf @method('DELETE')
                            <button class="text-button danger" type="submit">Ontkoppelen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Nog geen koppelingen. Geef een bestaande gebruiker een rol.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination', ['records' => $links])
@endsection
