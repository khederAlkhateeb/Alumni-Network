<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Request Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc3545;">Connection Request Rejected</h1>

        <p>Hello,</p>

        <p>
            <strong>{{ $receiverName }}</strong> has rejected your connection request.
            You can still browse other profiles and try connecting again in the future.
        </p>

        <p style="margin-top: 30px;">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/connections"
               style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                Explore Connections
            </a>
        </p>

        <p style="margin-top: 30px; color: #6c757d; font-size: 12px;">
            This notification was sent to {{ $connection->requester->email ?? 'the recipient' }}.
        </p>
    </div>
</body>
</html>
