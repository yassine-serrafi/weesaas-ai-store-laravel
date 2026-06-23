@extends('layouts.front')

@section('content')
    {{-- Assemble les sections dans l'ordre configuré (sections_order moins sections_disabled),
         à l'identique de l'ancien template product_page.php. --}}
    @foreach ($sections as $section)
        @includeIf('product.sections.' . $section)
    @endforeach
@endsection
