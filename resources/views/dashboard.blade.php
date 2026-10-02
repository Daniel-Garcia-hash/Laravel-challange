@extends('layouts.admin')

@section('title', 'Mijn toegang')

@section('content')
    <section class="card">
        <h2>Welkom, {{ auth()->user()->name }}</h2>
        <p>Hier zie je welke rollen en permissies aan jouw account zijn gekoppeld. Een admin kan deze via de beheeromgeving wijzigen.</p>
    </section>

    <section class="card">
        <h2>Mijn rollen</h2>
        <ul>
            @forelse(auth()->user()->getRoleNames() as $role)
                <li>{{ $role }}</li>
            @empty
                <li>Je hebt nog geen rol.</li>
            @endforelse
        </ul>
        <h2>Mijn permissies</h2>
        <ul>
            @forelse(auth()->user()->getAllPermissions()->sortBy('name') as $permission)
                <li>{{ $permission->name }}</li>
            @empty
                <li>Je hebt nog geen permissies.</li>
            @endforelse
        </ul>
    </section>

    <section class="card">
        <h2>Beschikbare pagina's</h2>
        @can('product aanpassen')
            <p><a href="{{ route('permission-demo') }}">Product aanpassen</a></p>
        @else
            <p>Voor de pagina Product aanpassen heb je de permissie <strong>product aanpassen</strong> nodig.</p>
        @endcan
        @role('admin')
            <p><a href="{{ route('admin.permissions.index') }}">Open de beheeromgeving</a></p>
        @endrole
    </section>
@endsection
