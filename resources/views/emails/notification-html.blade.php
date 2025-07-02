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