# Expiry and Notifications

Initial reminder schedule: 90, 30, and 7 days before expiry, plus the expiry date. The first implementation sends database notifications to the document owner.

The document_reminders table has a unique key across document, recipient, reminder type, and due date. Failures are logged and must be retryable.

The scheduler is defined in routes/console.php. Production must run the Laravel scheduler every minute; the reminder command is scheduled daily at 08:00 in the configured application timezone.

Before production, improve retry handling, avoid holding database transactions while sending notifications, and add race-condition tests.
