<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Beheeromgeving</title>
    <link rel="stylesheet" href="{{ asset('admin.css') }}">
</head>
<body>
    <a class="skip-link" href="#inhoud">Naar inhoud</a>
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}">Laravel Challenge <span>Beheeromgeving</span></a>
        <div class="account">
            <span>{{ auth()->user()->name }}</span>
            <a href="{{ route('profile.edit') }}">Profiel</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-button" type="submit">Uitloggen</button>
            </form>
        </div>
    </header>
    <div class="shell">
        <aside class="sidebar">
            <p class="nav-label">Mijn account</p>
            <nav aria-label="Hoofdnavigatie">
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Mijn toegang</a>
                @role('admin')
                    <p class="nav-label">Beheer</p>
                    @foreach([
                        'permissions' => 'Permissies', 'roles' => 'Rollen',
                        'role-permissions' => 'Rol-permissies', 'user-roles' => 'Gebruiker-rollen',
                    ] as $resource => $label)
                        @if(Route::has('admin.'.$resource.'.index'))
                            <a href="{{ route('admin.'.$resource.'.index') }}"
                               @if(request()->routeIs('admin.'.$resource.'.*')) aria-current="page" @endif>{{ $label }}</a>
                        @endif
                    @endforeach
                @endrole
            </nav>
        </aside>
        <main id="inhoud">
            <p class="eyebrow">@role('admin') Administratie @else Mijn account @endrole</p>
            <div class="page-heading"><h1>@yield('title')</h1>@yield('actions')</div>
            @if(session('success'))
                <div class="notice success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="notice error" role="alert">
                    <strong>Controleer je invoer.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
