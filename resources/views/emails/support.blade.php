@extends('emails.template')

@section('title', 'New Support Request')
@section('header', 'New Support Ticket Submitted')

@section('content')

    <p>Hello Support Team,</p>
    <p>A new support request has been submitted on {{ $company ?? 'Cuztomisable' }}.</p>
    <p><strong>Submitted By:</strong></p>
    <ul>
        <li><strong>Name:</strong> {{ $user->name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
    </ul>
    <p><strong>Message:</strong></p>
    <blockquote style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #ccc;">
        {{ $text }}
    </blockquote>

@endsection