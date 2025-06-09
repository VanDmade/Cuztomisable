@extends('emails.template')

@section('title', 'Temporary Password')
@section('header', 'Temporary Password Sent!')

@section('content')

    <p>Hello {{ $user->name ?? 'there' }},</p>
    <p>An administrator has issued a temporary password for your {{ $company ?? 'Cuztomisable' }} account.</p>
    <p><strong>Temporary Password:</strong> {{ $password ?? '******' }}</p>
    <p>Please use this password to log in. For security reasons, you'll be required to set a new password immediately after logging in.</p>
    <p>If you did not request or expect this change, we recommend logging in and updating your password right away. If you have concerns, please contact your administrator or support team.</p>

@endsection