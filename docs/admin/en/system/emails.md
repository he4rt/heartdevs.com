---
title: Email Management
icon: heroicon-o-envelope
order: 2
---

# Email Management

The platform includes a built-in email management system for monitoring and reviewing outgoing emails. Navigate to the **System** section to access it.

## Viewing Sent Emails

The email log shows all emails sent by the platform, including:

- **Recipient** — Who the email was sent to
- **Subject** — The email subject line
- **Status** — Whether the email was sent successfully
- **Timestamp** — When the email was dispatched

## Email Preview

You can click on any email entry to preview its content as it was sent. This is useful for verifying that templates rendered correctly and that the right information was included.

## Troubleshooting

If emails are not being delivered:

1. Verify the mail driver is correctly set (SMTP, SES, Mailgun, etc.)
2. Check that the queue worker is running if your emails are queued
3. Review the email logs for any errors related to email sending
4. Contact your system administrator for mail server configuration issues
