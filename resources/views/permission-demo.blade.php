@extends('layouts.admin')

@section('title', 'Product aanpassen')

@section('content')
    <section class="card">
        <h2>Product aanpassen</h2>
        <p>Je kunt deze pagina bekijken omdat jouw account de permissie <strong>product aanpassen</strong> heeft, rechtstreeks of via een rol.</p>
        <p>Deze voorbeeldpagina laat het effect van een toegewezen permissie zien. De route controleert de permissie bij ieder bezoek.</p>
        <p><a href="{{ route('dashboard') }}">Terug naar Mijn toegang</a></p>
    </section>
@endsection
