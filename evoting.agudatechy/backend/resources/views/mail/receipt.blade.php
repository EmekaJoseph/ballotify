<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fa;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fa;padding:32px 12px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #e5e9f0;">
      <tr>
        <td style="background:#0a1222;padding:28px 32px;">
          <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#5eead4;">{{ $heading }}</div>
          <div style="font-size:24px;font-weight:800;color:#ffffff;margin-top:6px;line-height:1.25;">{{ $title }}</div>
        </td>
      </tr>
      <tr>
        <td style="padding:28px 32px 8px;">
          <p style="margin:0 0 12px;font-size:16px;line-height:1.6;">{{ $greeting }}</p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">{{ $intro }}</p>
          @if (! empty($rows))
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e9f0;border-radius:14px;">
            @foreach ($rows as $label => $value)
              @if ($value !== null && $value !== '')
                <tr>
                  <td style="padding:10px 14px;font-size:13px;color:#64748b;border-bottom:1px solid #eef2f6;">{{ $label }}</td>
                  <td style="padding:10px 14px;font-size:14px;font-weight:700;text-align:right;border-bottom:1px solid #eef2f6;">{{ $value }}</td>
                </tr>
              @endif
            @endforeach
          </table>
          @endif
          @if (! empty($link))
            <div style="text-align:center;margin:24px 0 8px;">
              <a href="{{ $link }}" style="display:inline-block;background:#058580;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:12px 26px;border-radius:12px;">{{ $linkLabel }}</a>
            </div>
          @endif
        </td>
      </tr>
      <tr>
        <td style="padding:12px 32px 28px;">
          <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">{{ $footnote }}</p>
        </td>
      </tr>
    </table>
    <div style="font-size:12px;color:#94a3b8;margin-top:16px;">{{ config('app.name') }}</div>
  </td></tr>
</table>
</body>
</html>
