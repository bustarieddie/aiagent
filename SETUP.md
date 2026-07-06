# Klinik Bustari — Laravel Portal

Laravel 11 rewrite of the WhatsApp AI Agent admin portal (formerly Next.js).

## Deploy sequence

### 1. Fresh Laravel scaffold on Forge server

```bash
cd /tmp
rm -rf laravel-fresh
composer create-project laravel/laravel laravel-fresh
```

### 2. Copy this repo INTO the Laravel scaffold

```bash
cd /tmp/laravel-fresh
git clone https://github.com/bustarieddie/klinik-bustari-laravel.git /tmp/custom-laravel
cp -r /tmp/custom-laravel/. .
rm -rf .git
git init && git branch -M main
git add . && git commit -m "Initial Laravel + custom modules"
git remote add origin https://github.com/bustarieddie/klinik-bustari-laravel.git
git push -u origin main --force
```

### 3. Set up Forge Laravel site

Forge → Sites → New Site:
- Domain: `laravel.klinikbustari.com` (or `agent.klinikbustari.com` if switching)
- Type: Laravel
- Web dir: `/public`
- PHP 8.3
- Git repo: `bustarieddie/klinik-bustari-laravel`, branch `main`
- Install Composer Dependencies: ✅

### 4. Environment

Forge → site → Environment. Base `.env` from `.env.example`; fill in:

```
APP_NAME="Klinik Bustari WhatsApp Agent"
APP_URL=https://laravel.klinikbustari.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=klinik_agent
DB_USERNAME=klinik_agent
DB_PASSWORD=<from-forge>

ADMIN_PASSWORD=<same-as-current-portal>
PYTHON_BOT_URL=https://klinik-bustari-bot.on-forge.com
PYTHON_ADMIN_KEY=<same-as-bot-ADMIN_KEY>
APPOINTMENT_BOOKING_URL=https://klinikbustari.com/appointment.html
```

### 5. Run migrations

Via Forge Console or SSH:

```bash
cd /home/forge/laravel.klinikbustari.com/current
php artisan key:generate
php artisan migrate
```

### 6. Data migration Turso → MySQL

Run once, from server:

```bash
php artisan turso:pull
```

(Command defined in `app/Console/Commands/PullFromTurso.php` — expects `TURSO_URL` + `TURSO_AUTH_TOKEN` in `.env`.)

### 7. Verify

Visit `https://laravel.klinikbustari.com/admin/whatsapp-agent` → login screen.
Log in with `ADMIN_PASSWORD` → Dashboard.
