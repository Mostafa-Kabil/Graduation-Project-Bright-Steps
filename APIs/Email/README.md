# Bright Steps – Email API

## Overview
SMTP email-sending API with branded HTML templates for welcome, payment, appointment, and password reset emails.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Run migration: `../migration.sql` (creates `email_logs` table)
3. Set environment variables:
   ```
   set SMTP_EMAIL=yourname@gmail.com
   set SMTP_PASSWORD=your-app-password
   ```
4. Run: `run_server.bat` or `uvicorn app:app --reload --port 8006`

## Gmail App Password
For Gmail, enable 2FA, then generate an App Password at:
`https://myaccount.google.com/apppasswords`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Health check |
| POST | `/send` | Generic email |
| POST | `/send/welcome` | Welcome email |
| POST | `/send/payment-confirmation` | Payment receipt |
| POST | `/send/appointment-reminder` | Appointment reminder |
| POST | `/send/password-reset` | Password reset link |

## Swagger Docs
Visit `http://localhost:8006/docs`
