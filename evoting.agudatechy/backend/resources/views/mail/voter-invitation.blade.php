<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your voting code</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fa;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fa;padding:32px 12px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #e5e9f0;">
      <tr>
        <td style="background:#0a1222;padding:28px 32px;">
          <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#5eead4;">{{ $reminder ? 'Reminder: you haven\'t voted yet' : 'You\'re invited to vote' }}</div>
          <div style="font-size:24px;font-weight:800;color:#ffffff;margin-top:6px;line-height:1.25;">{{ $event->name }}</div>
        </td>
      </tr>
      <tr>
        <td style="padding:28px 32px 8px;">
          <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello{{ $voter->name ? ' ' . $voter->name : '' }},</p>
          @if ($event->description)
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#475569;">{{ $event->description }}</p>
          @endif
          <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">
            @if ($reminder)
              Voting is still open and your ballot hasn't been cast yet. Your personal code is below. It works <strong>once</strong>, so please don't share it.
            @else
              Use the personal code below to cast your vote. It works <strong>once</strong>, so please don't share it.
            @endif
          </p>
          <div style="text-align:center;background:#ecf8f7;border:1px solid #d2efed;border-radius:16px;padding:18px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#64748b;">Your voting code</div>
            <div style="font-family:Consolas,'Courier New',monospace;font-size:30px;font-weight:700;letter-spacing:.18em;color:#058580;margin-top:6px;">{{ $voter->code }}</div>
          </div>
          <div style="text-align:center;margin:26px 0 8px;">
            <a href="{{ $voteUrl }}" style="display:inline-block;background:#058580;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;padding:14px 30px;border-radius:12px;">Vote now</a>
          </div>
          @if ($event->ends_at)
            <p style="margin:14px 0 0;text-align:center;font-size:13px;color:#64748b;">
              Voting closes {{ $event->ends_at->timezone(config('app.timezone'))->format('j M Y, g:i A') }} ({{ config('app.timezone') }})
            </p>
          @endif
        </td>
      </tr>
      <tr>
        <td style="padding:20px 32px 28px;">
          <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">
            If the button doesn't work, open this link: <br>
            <a href="{{ $voteUrl }}" style="color:#058580;word-break:break-all;">{{ $voteUrl }}</a>
          </p>
        </td>
      </tr>
    </table>
    <div style="font-size:12px;color:#94a3b8;margin-top:16px;">Sent with {{ config('app.name') }}</div>
  </td></tr>
</table>
</body>
</html>
