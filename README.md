# SEATBOOK

SEATBOOK is a Laravel event and seat booking app. Organizers create published events with a fixed seat layout and per-seat price. Attendees browse upcoming events, hold seats for 10 minutes, confirm a booking (server-calculated total), and cancel before the event starts.

## Features

- Role-based registration and login (`organizer` / `attendee`) via Laravel Breeze (Blade)
- Organizers: create, update, and delete their own events; generate a seat grid on create; update price on free seats; draft vs published visibility
- Attendees: browse published upcoming events, interactive seat map, hold / release / confirm / cancel
- Timed seat holds (10 minutes, max 6 seats per event) with countdown UI and scheduled expired-hold cleanup
- Concurrent-safe hold and booking paths (transactions, row locks, unique indexes, friendly conflict responses)
- Short-lived cache for the seats JSON endpoint, invalidated after hold / release / confirm / cancel / expired release
- Per-user-per-event hold rate limiting (in addition to a general request throttle)

## Stack

- PHP 8.3+ / Laravel 13
- MySQL (InnoDB recommended for real locking under contention)
- Laravel Breeze (Blade + Alpine.js)
- Tailwind CSS + Vite
- PHPUnit feature tests (SQLite in-memory by default)

## Requirements

- PHP 8.3+
- Composer
- MySQL
- Node.js and npm

## Setup

```bash
git clone <repository-url> seatbook
cd seatbook

composer install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env` (defaults match a local Laragon-style setup):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seatbook
DB_USERNAME=root
DB_PASSWORD=
```

Create the `seatbook` database, then:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

`public/build` is gitignored, so `npm run build` is required before the UI assets load in production-style (`serve`) mode. For local Vite HMR you can use `npm run dev` instead of `npm run build`.

App URL defaults to `http://localhost:8000` (`APP_URL` in `.env.example`).

## Scheduler

Expired holds are cleaned by the `holds:release-expired` command, scheduled every minute in `routes/console.php`.

In development, run the scheduler worker:

```bash
php artisan schedule:work
```

(Or wire `php artisan schedule:run` into system cron in production.)

Active holds are also ignored at query time when `expires_at` is in the past, so booking logic does not wait on the scheduler alone.

## Tests

Default (SQLite in-memory, from `phpunit.xml`):

```bash
php artisan test
```

Against MySQL (env overrides are not force-locked in `phpunit.xml`):

```bash
# create an empty seatbook_test database first
DB_CONNECTION=mysql DB_DATABASE=seatbook_test php artisan test
```

Concurrency-oriented tests still run sequentially; they assert conflicting second attempts fail. True parallel InnoDB races need MySQL and load outside PHPUnit.

## Seeded credentials

From `DatabaseSeeder` (password is `password` for all):

| Role      | Email                   | Password |
|-----------|-------------------------|----------|
| Organizer | `organizer@example.com` | `password` |
| Attendee  | `attendee1@example.com` | `password` |
| Attendee  | `attendee2@example.com` | `password` |

Seeded content: three published events (`Rock Night`, `Tech Summit`, `Stand-up Comedy`) at venue `Dhaka Hall`, each with seats `A1`–`E10` at price `500`.

## Key technical decisions

- **Separate `seat_holds` table** — holds are first-class rows with `expires_at`, not only a seat status flag.
- **Transactions + `lockForUpdate` + unique indexes** — hold and confirm paths lock rows in a stable order; `seat_holds.seat_id` and `booking_seats.seat_id` uniqueness block double-booking under contention. Unique violations and MySQL lock wait / deadlock codes map to friendly HTTP errors (not raw SQL).
- **Expired holds** — ignored when `expires_at <= now()` at query time, plus `holds:release-expired` scheduled cleanup that frees denormalized seat status.
- **Server-side price calculation** — booking totals are summed from seat prices on the server; client-sent amounts are ignored.
- **Role middleware + policies** — `role:organizer` / `role:attendee` route groups plus `EventPolicy` / `BookingPolicy` for ownership and draft visibility.
- **Service classes** — `SeatHoldService`, `BookingService`, `EventService`, and `SeatGeneratorService` own the write orchestration; controllers authorize and respond.
- **Seat layout not editable after creation** — rows / seats-per-row are create-only; updates may change metadata, status, and free-seat prices only.
- **No hold extension** — re-holding your own seats does not extend `expires_at`.
- **6 seats per event limit** — enforced in validation and again inside the hold transaction.

## Known limitations / future improvements

- Denormalized `seats.status` can briefly disagree with holds/bookings until a write path or the release command corrects it.
- Seat map polling is every ~10 seconds (plus a 2-second shared seats JSON cache); there is no WebSocket push.
- PHPUnit on SQLite does not prove real InnoDB lock behavior; use MySQL for contention confidence.
- No payments, invoices, or email/SMS reminders for expiring holds.
- No soft deletes; deleting an event with confirmed bookings is blocked.
- Organizer price updates skip seats with an active hold or any booking row; already-priced held/booked seats keep their old price.
