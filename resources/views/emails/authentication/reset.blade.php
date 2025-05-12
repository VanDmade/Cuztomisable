@extends('emails.template')

@section('title', 'Password Successfully Reset')
@section('header', 'Your Password Was Changed')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>This is a confirmation that the password for your {{ $company ?? 'Cuztomisable' }} account was successfully reset.</p>
    <p>If you made this change, no further action is needed.</p>
    <p>If you didn’t initiate this change, please secure your account immediately by clicking the link below. It will temporarily lock your account and sign out all active sessions.</p>
    <p class="button-container">
        <a href="{{ $lockUrl ?? '#' }}" class="button">Lock My Account</a>
    </p>

@endsection