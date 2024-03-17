# Deployment Notification

- Site : {{ $site->domain }}
- Status : {{ $success ? '✅ Success' : '🚨 Failed' }}
- Date : {{ now() }}