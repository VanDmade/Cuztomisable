@extends('emails.template')

@section('title', 'Reset Your Password')
@section('header', 'Forgot Your Password?')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>We received a request to reset your password for your {{ $company ?? 'Cuztomisable' }} account.</p>
    <h2 class="code">
        {{ $code }}
    </h2>
    <p>Enter this code in the app to complete your password reset process.</p>
    <p>If you didn’t request a password reset, no action is required. Your account is still secure.</p>

@endsection