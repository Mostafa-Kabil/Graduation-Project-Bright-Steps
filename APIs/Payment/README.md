# Bright Steps – Payment API

## Overview
Stripe-integrated payment processing API for subscription management.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Set your Stripe keys in `app.py` (lines 9-10)
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8003`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Health check |
| GET | `/config` | Get Stripe publishable key |
| POST | `/create-checkout` | Create PaymentIntent |
| POST | `/confirm-payment` | Record payment in DB |
| GET | `/payment-history/{parent_id}` | Payment history |
| POST | `/webhook` | Stripe webhook handler |

## Test Card
Use Stripe test card: `4242 4242 4242 4242`, any future expiry, any CVC.

## Swagger Docs
Visit `http://localhost:8003/docs` for interactive API documentation.
