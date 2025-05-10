@extends('emails.template')

@section('title', 'New User Registered')
@section('header', 'A New User Has Signed Up')

@section('content')

    <p>Hello,</p>
    <p>A new user has just created an account on {{ $company ?? 'Cuztomisable' }}.</p>
    <p><strong>User Details:</strong></p>
    <ul>
        <li><strong>Name:</strong> {{ $user->name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
    </ul>
    <p>This message was sent automatically. No action is required unless review is needed.</p>

@endsection