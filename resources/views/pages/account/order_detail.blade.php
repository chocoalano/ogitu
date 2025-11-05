@extends('layouts.app')

@section('content')
    @livewire('ecommerce.order-detail', ['id' => $id])
@endsection
