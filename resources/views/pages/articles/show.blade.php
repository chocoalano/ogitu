@extends('layouts.app')
@section('content')
    @livewire('ecommerce.article-show', ['slug'=>$slug])
@endsection
