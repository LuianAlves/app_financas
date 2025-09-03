@extends('layouts.templates.new_app') {{-- resources/views/layouts/new_app.blade.php --}}

@section('title', 'Início')

@section('content')
    {{-- conteúdo da página (grid/cards/tabelas) --}}
@endsection

{{-- opcional, se quiser um conteúdo diferenciado no mobile --}}
@section('content_mobile')
    {{-- versão mobile específica; se omitir, herda de @section('content') --}}
@endsection

@push('scripts')
    <script>/* scripts específicos da página */</script>
@endpush
