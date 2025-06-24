<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $emailData['subject'] ?? 'Notification' }}</title>
</head>
<body>
    {!! $htmlContent !!}
</body>
</html>