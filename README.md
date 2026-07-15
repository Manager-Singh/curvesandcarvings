# Curves and Carvings

Mage-OS **3.1.0** (Magento 2.4.9) e-commerce site with custom theme `Curvesandcarvings/luma`.

**Repository:** https://github.com/Manager-Singh/curvesandcarvings

---

## What is in this repo

| Included | Not included (set up on server) |
|----------|----------------------------------|
| `app/code/` custom modules | `vendor/` → `composer install` |
| `app/design/frontend/Curvesandcarvings/luma/` theme | `pub/media/` → rsync from backup/live |
| `app/etc/config.php` (modules enabled) | `app/etc/env.php` → copy from sample |
| `composer.json` + `composer.lock` | Database dump → import separately |
| `pub/.htaccess` (incl. `/our-gallery` → `/gallery`) | `var/`, `generated/`, `pub/static/` |

Customizations live in **`app/design`** and **`app/code`** — never edit `vendor/magento`.

---

## Server requirements

- **PHP** 8.2+ (8.4 tested) with: `bcmath`, `ctype`, `curl`, `dom`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `soap`, `sodium`, `xsl`, `zip`
- **MySQL** 8.0+ / MariaDB 10.6+
- **Composer** 2.x
- **OpenSearch** 2.x on `localhost:9200` (catalog search)
- **Apache** or Nginx with docroot = `pub/`

---

## Staging deployment (step by step)

### 1. Clone repository

```bash
git clone https://github.com/Manager-Singh/curvesandcarvings.git
cd curvesandcarvings
```

### 2. Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure environment

```bash
cp app/etc/env.php.sample app/etc/env.php
nano app/etc/env.php   # DB host, name, user, password, crypt key, domain
```

Use the **same `crypt/key`** as production if you import the existing database (required for encrypted config/passwords).

### 4. Import database

Import your SQL dump (not stored in git):

```bash
mysql -u root -p qgcyetztur < /path/to/qgcyetztur.sql
```

### 5. Copy media files

```bash
rsync -av /path/to/backup/pub/media/ pub/media/
```

Media is ~4 GB and excluded from git.

### 6. Set file permissions

```bash
find var generated pub/static pub/media -type d -exec chmod 775 {} \; 2>/dev/null || true
find var generated pub/static pub/media -type f -exec chmod 664 {} \; 2>/dev/null || true
chown -R www-data:www-data var generated pub/static pub/media  # adjust user for your server
```

### 7. Start OpenSearch

```bash
bash start-opensearch.sh
```

### 8. Run Magento setup

```bash
bash deploy-staging.sh
```

Or manually:

```bash
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f en_US \
  --theme Curvesandcarvings/luma --theme Magento/luma --theme Magento/blank
php bin/magento indexer:reindex
php bin/magento cache:flush
```

The `Curvesandcarvings_Theme` module assigns theme **Curvesandcarvings/luma** automatically on `setup:upgrade`.

### 9. Apache virtual host example

```apache
<VirtualHost *:80>
    ServerName staging.curvesandcarvings.com
    DocumentRoot /var/www/curvesandcarvings/pub

    <Directory /var/www/curvesandcarvings/pub>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 10. Base URL (after first login to admin)

```bash
php bin/magento setup:store-config:set --base-url="https://staging.curvesandcarvings.com/"
php bin/magento cache:flush
```

---

## Custom theme structure

```
app/design/frontend/Curvesandcarvings/luma/
├── Magento_Theme/templates/extra/   # CMS pages: home, our-store, gallery, etc.
├── Magento_Theme/templates/html/  # skip.phtml (top bar), nvdfooter.phtml
├── Magento_Theme/layout/          # default.xml, default_head_blocks.xml
├── Magento_Search/templates/      # form.mini.phtml (header)
└── web/css/                       # styles-m.css, styles-l.css, owl, fixes
```

CMS pages reference templates like `Magento_Theme::extra/home-view.phtml` — Magento resolves them from this theme.

---

## Custom modules (`app/code`)

| Module | Purpose |
|--------|---------|
| `Curvesandcarvings/Theme` | Auto-assign storefront theme |
| `Curvesandcarvings/Homepage` | Homepage helpers |
| `DR/Gallery` | Gallery images (our-store, /gallery page) |
| `Ibnab/MegaMenu` | Mega menu |
| `Yereone/Testimonials` | Customer testimonials |
| `Bss/SocialLogin` | Social login |
| `Magento/Ccavenuepay` | Payment gateway |
| `WeltPixel/*` | Backend / Quickview |
| `Firebear/*` | Import/export |

---

## Important URLs / redirects

| URL | Notes |
|-----|-------|
| `/` | Homepage — `home-view.phtml` |
| `/our-store` | Store locations page |
| `/gallery` | Finished work gallery (DR Gallery) |
| `/our-gallery` | **Redirects to `/gallery`** (see `pub/.htaccess`) |

---

## Helper scripts

| Script | Purpose |
|--------|---------|
| `deploy-staging.sh` | Full post-clone Magento setup |
| `start-opensearch.sh` | Start OpenSearch Docker container |
| `package-for-staging.sh` | Create tar.gz without vendor/media |
| `apply-live-css.sh` | Fallback: copy theme CSS to pub/static if deploy fails |

---

## Local development notes

- **OpenSearch must be running** for category/search pages.
- If `bin/magento` fails with interceptor errors, fix `generated/` permissions and run `php bin/magento setup:di:compile`.
- Do not commit `app/etc/env.php` — it contains database credentials.

---

## License

Magento / Mage-OS components: OSL-3.0 / AFL-3.0. Custom code: proprietary.
