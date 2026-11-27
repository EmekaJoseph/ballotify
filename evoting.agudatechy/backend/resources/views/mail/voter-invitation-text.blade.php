@if ($reminder)
Reminder: you haven't voted in {{ $event->name }} yet.
@else
You're invited to vote in {{ $event->name }}.
@endif

Hello{{ $voter->name ? ' ' . $voter->name : '' }},

Your personal voting code: {{ $voter->code }}
(It works once, so please don't share it.)

Vote here: {{ $voteUrl }}
@if ($event->ends_at)

Voting closes {{ $event->ends_at->timezone(config('app.timezone'))->format('j M Y, g:i A') }} ({{ config('app.timezone') }}).
@endif

Sent with {{ config('app.name') }}
