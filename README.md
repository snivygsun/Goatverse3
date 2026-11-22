# GOATVERSE: foundation (Phase 1-2 + core of Phase 3-4)

Creator: Zishan Al Islam. This zip is an **overlay**: it contains only GOATVERSE's own files.
You drop it on top of a fresh Laravel project. It was written without being able to run PHP,
so run the tests first (step 6) and expect to fix a small error or two.

## 1. Requirements
PHP 8.2+ (extensions: mbstring, xml, curl, zip, pdo_mysql), Composer, MySQL or MariaDB, Git.

## 2. Create the Laravel project and add this overlay
```bash
composer create-project laravel/laravel goatverse
cd goatverse
unzip -o ../goatverse-foundation.zip -d .     # overwrites the users migration, seeder, routes/web.php
rm -f database/factories/UserFactory.php       # default factory uses the old name/email columns
rm -f resources/views/welcome.blade.php
```
If your Laravel version has a different users migration name (for example `2014_10_12_000000_create_users_table.php`), delete it. Our `0001_01_01_000000_create_users_table.php` replaces it.

## 3. Configure `.env`
```
APP_NAME=GOATVERSE
APP_ENV=production        # use local while developing
APP_DEBUG=false           # use true while developing
APP_URL=https://your-domain
APP_TIMEZONE=Asia/Dhaka
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goatverse
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
```

## 4. Install
```bash
php artisan key:generate
php artisan migrate --force
php artisan goatverse:install          # asks for the Main Owner password (username: zishan)
php artisan config:cache && php artisan route:cache && php artisan view:cache   # production only
php artisan serve                      # local test: http://127.0.0.1:8000
```
The owner password is typed in the terminal and stored hashed. It is never in code or `.env`.
(Non-interactive deploys can set `GOATVERSE_OWNER_PASSWORD` for the one command, then remove it.)
Password rules: 10+ characters, letters and numbers, must not contain the username.

## 5. Git
```bash
git init && git add . && git commit -m "GOATVERSE foundation"
git branch -M main
git remote add origin https://github.com/<you>/goatverse.git
git push -u origin main
```
Laravel's `.gitignore` already excludes `.env` and `vendor/`. Never commit `.env`.

## 6. Tests
```bash
php artisan test
```

## 7. Deploy
* **Railway:** push to GitHub, New Project, Deploy from GitHub, add a MySQL service, set the variables above
  (DB_* from the MySQL service), set `APP_KEY` (`php artisan key:generate --show`). Run
  `php artisan migrate --force` on deploy, then `php artisan goatverse:install` once from a Railway shell.
* **Own VPS (needs sudo):** PHP-FPM + Nginx pointing at `public/`, MySQL, SSL with certbot, cron for the scheduler.
* The Nyxora server you showed has no sudo and no Docker, so it can't run Nginx/MySQL. Ask its admin first.

## What works now
Register/login (rate limited, age confirmation 12+), Main Owner installer, profiles with gender/country/city/birthdate
(each field private by default, 12+ birthdate check), wallet + bank balances, immutable coin and EXP ledgers,
atomic economy service with idempotency keys, level formula and permanent level roles, Dice game
(server-side RNG, bet limits up to 10,000,000, 20/30 daily plays per game on Dhaka time, completion bonus once per
day, per-game stats and mastery rewards, double-submit protection), dashboard, public profile,
admin panel (players, coin/EXP adjustment with reason, audit log, owner-only revoke of permanent rewards).

## NOT built yet (honest list)
Password reset (users have no email), bank deposit/withdraw, coin transfers, Slots/Spin/Quiz/Picture/Word games,
daily quiz, VIP packages/missions/grants (the 20/30 limit already reads `vip_until`), achievements builder,
chat/stickers, friends, challenges, leaderboards, seasons, events, chests, inventory/cosmetics, notifications,
reports/bans UI, role builder, settings editor in the admin (settings live in `config/goatverse.php`).

## Economy risks to review
* The 1,000,000 GC daily completion bonus per game is by far the biggest inflation source (min bet 100 x 20 plays
  is enough to unlock it). One person with many accounts can farm it. Lower it, scale it with level, or add
  anti-multi-account checks before launch.
* Dice pays 1.9x (5% house edge) plus the +300 GC/+300 EXP win bonus. Both are in `config/goatverse.php`.
* Coins are virtual only. Keep real-money purchases and cash-out out of the platform.
