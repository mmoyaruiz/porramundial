@extends('layouts.app')
@section('title','Porras que administro')

@section('content')
<main class="home-layout">
    <section class="hero">
        <h1>Porras que administro</h1>
        <p>Listado de porras creadas/administradas por ti.</p>

        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Porra</th>
                    <th>Estado</th>
                    <th>Competición</th>
                    <th>Tipo</th>
                    <th>Invitar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($porras as $p)
                <tr>
                    <td><strong><a href="{{ route('porras.show', $p->id_porra) }}">{{ $p->nombre }}</a></strong></td>
                    <td>
                        <span class="badge badge-{{ $p->estado }}">
                            {{ ucfirst($p->estado) }}
                        </span>
                    </td>
                    <td>{{ $p->competicion?->nombre }}</td>
                    <td>{{ $p->es_publica ? 'Pública' : 'Privada' }}</td>
                    <td>

                        @if(!$p->es_publica)
                        <a href="{{ route('porras.invitar', $p->id_porra) }}">Invitar</a>
                        @endif

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">Aún no administras ninguna porra.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <aside class="sidebar">
        <h2>Acciones</h2>
        <p><a href="{{ route('porras.create') }}">Crear nueva porra</a></p>
        <p><a href="{{ route('porras.mis') }}">Mis porras</a></p>
    </aside>
    
</main>
@endsection