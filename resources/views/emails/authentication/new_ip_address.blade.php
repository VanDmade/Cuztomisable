@extends('emails.template')

@section('title', 'New Login Detected')
@section('header', 'New Login to Your Account')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>We noticed a new login to your {{ $company ?? 'Cutomisable' }} account from an unfamiliar IP address.</p>
    <p><strong>Login Details:</strong></p>
    <ul>
        <li><strong>IP Address:</strong> {{ $ip->ip_address ?? 'Unknown' }}</li>
        <li><strong>Date & Time:</strong> {{ now()->format('F j, Y, g:i a') }}</li>
    </ul>
    <p>If this was you, no further action is needed.</p>
    <p>If you don't recognize this activity, we strongly recommend securing your account immediately!</p>
    <p>If you need help or have questions, our support team is here for you.</p>

@endsection