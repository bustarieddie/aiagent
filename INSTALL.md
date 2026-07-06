# Install on Forge server

This repo contains **only the custom Laravel files** (models, controllers, migrations, views, config overrides). You still need a fresh Laravel 11 base to layer these on top of.

## Steps

```bash
# 1. Create a fresh Laravel 11 project
cd /tmp
rm -rf laravel-base
composer create-project laravel/laravel:^11.0 laravel-base
cd laravel-base

# 2. Overlay this repo's custom files
git clone https://github.com/bustarieddie/klinik-bustari-laravel.git /tmp/custom
cp -rn /tmp/custom/. .          # -n = do not overwrite existing
# then explicitly overwrite the files we do want to replace:
cp /tmp/custom/routes/web.php routes/web.php
cp /tmp/custom/bootstrap/app.php bootstrap/app.php
cp /tmp/custom/config/services.php config/services.php
cp /tmp/custom/.env.example .env.example

# 3. Push the merged result to your GitHub repo
rm -rf .git
git init && git branch -M main
git add . && git commit -m "Initial: Laravel 11 base + Klinik Bustari modules"
git remote add origin https://github.com/bustarieddie/klinik-bustari-laravel.git
git push -u origin main --force
```

## Forge site

Create a Laravel-type site. `composer install` runs on every deploy. First time only:

```bash
cd /home/forge/agent.klinikbustari.com/current
cp .env.example .env
php artisan key:generate
# Edit .env — fill in ADMIN_PASSWORD, DB_*, PYTHON_*, TURSO_* (temp)
php artisan migrate
php artisan turso:pull        # one-time data import
```

## Cutover

Once verified end-to-end:
1. Forge → site → Domains → attach `agent.klinikbustari.com` + issue SSL
2. Remove `agent.klinikbustari.com` from the Next.js Forge site
3. DNS was already pointing at the same server IP — traffic just moves to the new nginx vhost.
