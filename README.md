# AscendEX Binary Options Platform (Laravel)

Web-based binary options crypto trading platform with real market price feed, configurable payout ratio, and fair settlement logic.

## Stack

- Backend: Laravel 12 + PHP 8.2+
- Database: MySQL
- Frontend: Blade + Tailwind CSS (mobile-first)
- Real-time: Laravel Broadcasting + Echo + Pusher-compatible websocket server
- Charts: TradingView Lightweight Charts

## Core Modules

- Auth + roles (`user`, `admin`) with hashed passwords and CSRF/session protection
- Internal wallet (`balance`, `bonus`, `locked_balance`) + ledger transactions
- Price feed engine (`BTCUSDT`, `ETHUSDT`) via Binance public API
- Binary trade flow (`UP` / `DOWN`, 1/5/15 min expiry)
- Trade settlement engine (every second scheduler task)
- Manual deposit/withdrawal with admin approval and idempotent processing
- Admin panel: payout config, trades/users monitoring, balance adjustment

## Database Tables

- `users`
- `wallets`
- `transactions`
- `trades`
- `price_ticks`
- `deposits`
- `withdrawals`
- `admin_settings`

## Project Structure

- `app/Services/PriceFeedService.php` - live market integration + tick storage
- `app/Services/TradeService.php` - trade placement + expiry settlement logic
- `app/Services/WalletService.php` - wallet debit/credit + transaction recording
- `app/Console/Commands/FetchPriceTicksCommand.php` - `prices:fetch`
- `app/Console/Commands/SettleTradesCommand.php` - `trades:settle`
- `app/Http/Controllers/*` - auth, trading, wallet, admin, API
- `resources/views/trade/index.blade.php` - live chart + Buy Up/Buy Down UI

## Fair Settlement Logic

When a trade expires:

- `UP` wins if `close_price > entry_price`
- `DOWN` wins if `close_price < entry_price`
- Otherwise loses

Payout:

- `payout_rate = 1 + (payout_percent / 100)`
- Example: `80%` => rate `1.8`
- Win wallet credit = `amount * payout_rate`
- Lose wallet credit = `0`

No price manipulation is used. Prices are fetched from Binance market API.

## Local Setup (Windows / XAMPP)

1. Install dependencies:
   - `composer install`
   - `npm install`

2. Configure environment:
   - `copy .env.example .env`
   - Set MySQL credentials in `.env`
   - Set websocket/pusher values in `.env`

3. Run app setup:
   - `php artisan key:generate`
   - `php artisan storage:link`
   - `php artisan migrate --seed`

4. Start development processes:
   - `php artisan serve`
   - `php artisan queue:work`
   - `php artisan schedule:work`
   - `npm run dev`

## WebSocket Setup Guide

You can use hosted Pusher or local Laravel Reverb-compatible websocket endpoint.

Required `.env` keys:

- `BROADCAST_CONNECTION=pusher`
- `PUSHER_APP_ID`
- `PUSHER_APP_KEY`
- `PUSHER_APP_SECRET`
- `PUSHER_HOST`
- `PUSHER_PORT`
- `PUSHER_SCHEME`
- `PUSHER_APP_CLUSTER`

Frontend keys (already mapped in `.env.example`):

- `VITE_PUSHER_APP_KEY`
- `VITE_PUSHER_HOST`
- `VITE_PUSHER_PORT`
- `VITE_PUSHER_SCHEME`
- `VITE_PUSHER_APP_CLUSTER`

Client subscription channel/event:

- Channel: `prices`
- Event: `tick.updated`

## Scheduler / Cron Guide

The app defines every-second scheduler jobs in `routes/console.php`:

- `prices:fetch`
- `trades:settle`
- `prices:prune --hours=24` (every 5 minutes)

Production cron (Linux server example):

`* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

For true every-second cadence, keep `php artisan schedule:work` running via supervisor/pm2/systemd.

## API Integration Example (Price Feed)

Internal endpoint:

- `GET /api/prices/latest?symbol=BTCUSDT`

Response:

```json
{
  "symbol": "BTCUSDT",
  "price": "62123.45000000",
  "series": [
    { "time": 1713271900, "value": 62100.12 },
    { "time": 1713271901, "value": 62123.45 }
  ]
}
```

External market source:

- `https://api.binance.com/api/v3/ticker/price?symbols=["BTCUSDT","ETHUSDT"]`

## Default Seed Accounts

- Admin: `admin@example.com` / `password`
- Trader: `trader@example.com` / `password`

## Security Notes

- CSRF protection enabled by default in web routes
- Rate limits on auth/trade/deposit/withdrawal endpoints
- FormRequest validation for all critical inputs
- Eloquent/Query Builder guards against SQL injection
- Role-based middleware for admin routes
- Deposit proofs are stored on private disk (`local`) instead of public storage
- Admin deposit/withdrawal actions are transaction-safe and idempotent
