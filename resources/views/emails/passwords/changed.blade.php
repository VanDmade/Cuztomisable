@extends('cuztomisable::template')

@section('title', 'Password Changed')
@section('header', 'Your Password has Changed!')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>We wanted to let you know that the password for your {{ config('app.name', 'Cuztomisable') }} account was recently changed.</p>
    <p>If you made this change, no further action is needed.</p>
    <p><strong>If you did not change your password, please log in immediately and reset your password to secure your account.</strong></p>
    <p>Need help? Contact your administrator or our support team as soon as possible.</p>

@endsection