@extends('cuztomisable::template')

@section('title', 'You\'re Invited!')
@section('header')
    Welcome to {{ config('app.name', 'Cuztomisable') }}!
@endsection

@section('content')

    <p>Hello {{ $name ?? 'there' }},</p>
    <p>You've officially been summoned to join {{ config('app.name', 'Cuztomisable') }} — congrats! That means someone thinks you're awesome enough to be part of the fun. Click the link below to claim your spot, shake things up, and get started on your journey. Cocktails optional, but good vibes required.
    <p class="button-container">
        <a href="{{ $url ?? '#' }}" class="button">Accept Invitation</a>
    </p>
    <p>But don't wait too long — this magical link will vanish in {{ $expires ?? 60 }} minutes, just like a good cocktail at a party. So click it before it goes flat!</p>
    <p>If you weren't expecting this invitation, you can safely ignore this email.</p>

@endsection