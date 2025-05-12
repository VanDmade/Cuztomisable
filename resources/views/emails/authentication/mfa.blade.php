@extends('emails.template')

@section('title', 'Your MFA Code')
@section('header', 'Multi-Factor Authentication')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>To complete your login to {{ $company ?? 'Cuztomisable' }}, please enter the following authentication code:</p>
        {{ $code }}
    </h2>
    <p>This code is valid for a limited time and can only be used once.</p>
    <p>If you didn't try to log in, please secure your account immediately or contact support.</p>

@endsection