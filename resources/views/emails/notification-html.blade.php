<?php
// resources/views/emails/notification-html.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailData['subject'] ?? 'Notification' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        {!! $htmlContent ?? $emailData['body_html'] ?? '' !!}
    </div>
</body>
</html>

<?php
// resources/views/emails/notification-text.blade.php
?>
{{ $textContent ?? $emailData['body_text'] ?? '' }}

<?php
// resources/views/emails/notification.blade.php (ถ้ามีไฟล์นี้อยู่แล้ว)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $emailData['subject'] ?? 'Notification' }}</title>
</head>
<body>
    @if(isset($htmlContent))
        {!! $htmlContent !!}
    @elseif(isset($emailData['body_html']))
        {!! $emailData['body_html'] !!}
    @elseif(isset($textContent))
        <pre>{{ $textContent }}</pre>
    @elseif(isset($emailData['body_text']))
        <pre>{{ $emailData['body_text'] }}</pre>
    @else
        <p>No content available</p>
    @endif
</body>
</html>