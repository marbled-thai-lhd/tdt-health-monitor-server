# Alert Email Notifications

This system automatically sends email notifications to administrators when alerts are created.

## Configuration

### 1. Mail Server Setup

Configure your mail server in `.env`:

#### Using AWS SES (Recommended for Production):
```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Health Monitor"

# AWS SES Configuration
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
```

#### Using SMTP (Gmail):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Health Monitor"
```
Note: Use [App Passwords](https://support.google.com/accounts/answer/185833) for Gmail

#### Using SendGrid:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

#### For Local Testing:
```env
MAIL_MAILER=log
```
This will write emails to `storage/logs/laravel.log`

### 2. Admin Email Configuration

Add admin email addresses in `.env` (comma-separated):

```env
ALERT_ADMIN_EMAILS="admin@example.com,ops@example.com,devops@example.com"
```

**Important:**
- Multiple emails are separated by commas
- Spaces around emails are automatically trimmed
- Invalid email addresses are automatically filtered out
- Email validation is performed before sending

## Alert Types and Severity

Emails are automatically sent for all alert types:

### Alert Types:
- **Server Offline** - When a server stops reporting
- **Supervisor Issue** - When supervisor processes are not running
- **Queue Issue** - When queue health checks fail
- **Cron Issue** - When cron monitoring detects problems
- **Backup Failed** - When database backup fails

### Severity Levels:
- 🔴 **Critical** - Requires immediate attention (e.g., server offline)
- 🟠 **High** - Important issues (e.g., supervisor/queue problems)
- 🟡 **Medium** - Moderate concerns (e.g., cron issues)
- 🔵 **Low** - Minor notifications

## Email Content

Each alert email includes:
- Alert severity badge with color coding
- Alert title and type
- Server name and IP address
- Timestamp (UTC)
- Alert status (resolved/unresolved)
- Detailed message
- Additional data (JSON format)
- Direct link to view alert in dashboard

## Email Delivery

**Important:** Emails are sent **synchronously** (immediately) when alerts are created. This means:
- ✅ Emails are sent instantly without delay
- ✅ No need to run queue workers
- ⚠️ Alert creation may take slightly longer due to email sending
- ⚠️ If mail server is slow/unavailable, it may delay the request

The system uses Laravel's Mail facade to send emails directly through the configured mail driver.

## AWS SES Configuration

### Initial Setup

1. **Create AWS IAM User for SES:**
   - Go to AWS IAM Console
   - Create user with `AmazonSESFullAccess` policy
   - Save Access Key ID and Secret Access Key

2. **Configure AWS Region:**
   - Choose your preferred region (e.g., `us-east-1`, `eu-west-1`)
   - Update `AWS_DEFAULT_REGION` in `.env`

### Sandbox Mode vs Production

#### Sandbox Mode (Default):
- Can only send to **verified email addresses**
- Maximum **200 emails per 24 hours**
- **1 email per second** sending rate
- Both sender and recipient emails must be verified

#### Production Mode:
- Can send to **any email address**
- Higher sending limits (depends on your request)
- Faster sending rate

### Verifying Email Addresses

**Required when in Sandbox Mode:**

1. Go to AWS SES Console: https://console.aws.amazon.com/ses/
2. Navigate to **"Verified identities"**
3. Click **"Create identity"**
4. Select **"Email address"**
5. Enter the email address (e.g., `admin@example.com`)
6. Click **"Create identity"**
7. Check inbox and click verification link

**You must verify:**
- ✅ Sender email (`MAIL_FROM_ADDRESS`)
- ✅ All admin emails (`ALERT_ADMIN_EMAILS`) if in Sandbox mode

### Requesting Production Access

To send emails to any address:

1. Go to AWS SES Console
2. Click **"Account dashboard"**
3. Click **"Request production access"**
4. Fill out the form with:
   - **Mail type:** Transactional
   - **Website URL:** Your monitoring dashboard URL
   - **Use case description:** "System health monitoring alerts for production infrastructure"
   - **Compliance:** Confirm you have permission to send
5. Submit and wait for approval (usually 24 hours)

### Checking SES Status

Use the built-in command to check your SES configuration:

```bash
php artisan ses:check-status
```

This will show:
- Account sending quota and limits
- Verified email identities
- Sandbox vs Production mode
- Current configuration status
- Recommendations for setup

**Example output:**
```
📊 Account Information:
+--------------------+-----------------+
| Metric             | Value           |
+--------------------+-----------------+
| Max 24 Hour Send   | 200             |
| Max Send Rate      | 1 emails/second |
| Sent Last 24 Hours | 15              |
+--------------------+-----------------+

✉️  Verified Email Identities:
✓ admin@example.com - Success
✓ alerts@example.com - Success
✗ test@example.com - Failed

⚠️  Account is in SANDBOX MODE
   → You can only send to verified email addresses
   → Maximum 200 emails per 24 hours
   → Request production access in AWS SES Console
```

## Testing Email Configuration

### Test Email Command

Send a test email to verify your configuration:

```bash
# Send test email to configured admin emails
php artisan alert:test-email

# Send test email to specific address
php artisan alert:test-email your-email@example.com
```

The command will:
1. Create a test alert (not saved to database)
2. Send email to specified address
3. Display mail configuration
4. Show success/error messages

### Example Output:

```
Preparing test alert email...
Sending test email to: admin@example.com
✓ Test email sent successfully to SES!
SES Response: Message ID - 0000018cfb2e2d3e-1a2b3c4d-5e6f-7g8h-9i0j-1k2l3m4n5o6p

Important checks:
1. Check your email inbox (and SPAM folder)
2. Verify email is verified in AWS SES Console
3. Check SES sending statistics in AWS Console
4. If in sandbox mode, both sender and recipient emails must be verified

Recipient: admin@example.com
Sender: noreply@example.com

Current Mail Configuration:
+-------------+--------------------+
| Setting     | Value              |
+-------------+--------------------+
| MAIL_MAILER | ses                |
| MAIL_HOST   | smtp.gmail.com     |
| MAIL_PORT   | 587                |
| MAIL_FROM   | noreply@example.com|
+-------------+--------------------+
```

## Troubleshooting

### No emails received?

1. **Check SES status:**
   ```bash
   php artisan ses:check-status
   ```

2. **Verify emails in AWS Console:**
   - Sender email must be verified
   - Recipient emails must be verified (if in Sandbox mode)

3. **Check spam folder:**
   - SES emails may be marked as spam initially
   - Configure SPF/DKIM records for your domain

4. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Verify admin emails:**
   ```bash
   php artisan tinker
   >>> env('ALERT_ADMIN_EMAILS')
   ```

6. **Test configuration:**
   ```bash
   php artisan config:clear
   php artisan alert:test-email
   ```

### Common Issues:

**Error: "Email address is not verified"**
- Verify the sender email in AWS SES Console
- Verify recipient emails if in Sandbox mode
- Check verification status with `php artisan ses:check-status`

**Error: "Connection refused"**
- Check MAIL_HOST and MAIL_PORT
- Verify firewall allows outbound connections
- Check if mail server requires authentication

**Error: "Authentication failed"**
- Verify AWS credentials (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY)
- Check IAM user has SES permissions
- Verify AWS_DEFAULT_REGION matches your SES region

**Error: "Throttling: Maximum sending rate exceeded"**
- You've hit the sending rate limit (1/second in Sandbox)
- Wait a moment and try again
- Request production access for higher limits

**Emails go to spam:**
- Configure SPF/DKIM records for your domain
- Use a verified sender email address
- Request production access and provide valid use case
- Consider using a custom domain with proper DNS records

## Customizing Email Templates

The email template is located at:
```
resources/views/emails/alert-notification.blade.php
```

You can customize:
- Email styling (CSS in `<style>` tag)
- Email layout and structure
- Content formatting
- Button colors and styles
- Logo and branding

## Security Considerations

1. **Never commit .env to version control**
2. **Use IAM user with minimum required permissions (SES only)**
3. **Rotate AWS credentials regularly**
4. **Use TLS/SSL encryption for mail connections**
5. **Limit admin email list to trusted recipients**
6. **Monitor SES sending statistics for suspicious activity**
7. **Enable AWS CloudTrail for SES API logging**

## Performance Considerations

- Emails are sent **synchronously** (blocking)
- Each alert creation will wait for email to be sent
- Typical SES email sending time: 100-500ms
- Consider impact on alert creation performance
- Multiple recipients are sent sequentially
- No queue workers needed (simplifies deployment)

**Trade-offs:**
- ✅ **Pros:** Simple, no queue infrastructure needed, immediate delivery
- ⚠️ **Cons:** Alert creation slightly slower, blocks if mail server unavailable

## Monitoring

Check email sending in logs:

```bash
# Search for alert email logs
grep "Alert email notifications sent" storage/logs/laravel.log

# Check for failures
grep "Failed to send alert email" storage/logs/laravel.log
```

Log entries include:
- Alert ID
- Alert type and severity
- Number of recipients
- Success/failure status
- Error messages (if any)

### Example log entry:
```
[2025-10-15 10:30:45] local.INFO: Alert email notifications sent 
{
    "alert_id": "550e8400-e29b-41d4-a716-446655440000",
    "alert_type": "server_offline",
    "severity": "critical",
    "recipients_count": 2
}
```

## Best Practices

1. **Verify sender domain:** Use a custom domain with SPF/DKIM records
2. **Request production access early:** Takes 24 hours for approval
3. **Monitor sending statistics:** Track bounces and complaints in SES Console
4. **Use meaningful from address:** e.g., `alerts@yourdomain.com` instead of `noreply@`
5. **Test in staging first:** Verify emails before deploying to production
6. **Set up bounce handling:** Configure SNS notifications for bounces
7. **Review alert thresholds:** Don't spam admins with too many alerts
8. **Implement alert grouping:** Consider batching similar alerts (future enhancement)

## Example .env Configuration

```env
# Application
APP_NAME="Health Monitor"
APP_URL=https://monitor.example.com

# Mail Driver: ses
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="alerts@example.com"
MAIL_FROM_NAME="Health Monitor Alerts"

# Alert Recipients (comma-separated)
ALERT_ADMIN_EMAILS="admin@example.com,ops@example.com,devops@example.com"

# AWS SES Configuration
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
```

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Run diagnostics: `php artisan ses:check-status`
- Test email: `php artisan alert:test-email`
- AWS SES documentation: https://docs.aws.amazon.com/ses/
