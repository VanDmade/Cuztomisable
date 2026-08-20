@extends('cuztomisable::template')

@section('title', 'Verify Your Email')
@section('header', 'Verify Your Email Address')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>Thanks for signing up with {{ config('app.name', 'Cuztomisable') }}! Please verify your email address by clicking the button below:</p>
    <p style="text-align: center;">
        <a href="{{ $verificationUrl }}" class="button">Verify Email</a>
    </p>
    @if (config('cuztomisable.login.verification.email', false))
        <p>Before you can log in, we just need to verify your email address.</p>
    @endif
    <p>If you did not create an account, no action is needed and you may disregard this email.</p>

@endsection