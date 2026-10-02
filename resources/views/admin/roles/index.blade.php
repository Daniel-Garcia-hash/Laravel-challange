@extends('layouts.admin')
@section('title', 'Rollen')
@section('actions')
    <a class="button" href="{{ route('admin.roles.create') }}">+ Nieuwe rol</a>
@endsection
@section('content')
    <p class="muted">Een rol bundelt permissies en kan aan meerdere gebruikers worden gekoppeld.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Naam</th><th scope="col">Guard</th><th scope="col">Acties</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td><td>{{ $role->guard_name }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.roles.edit', $role) }}">Bewerken</a>
                        @if($role->name !== 'admin')
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                              onsubmit="return confirm('Deze rol verwijderen? Ook de bijbehorende koppelingen worden verwijderd.');">
                            @csrf @method('DELETE')
                            <button class="text-button danger" type="submit">Verwijderen</button>
                        </form>
                        @else <span class="muted">Vaste beheerrol</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Nog geen rollen. Voeg je eerste rol toe.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pagination', ['records' => $roles])
@endsection
