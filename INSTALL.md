# Gyors Telepítési Útmutató

## 🚀 Gyors Start

### 1. Követelmények ellenőrzése

```bash
php -v      # PHP 8.0+ szükséges
mysql -V    # MySQL 5.7+ vagy MariaDB 10.3+
```

### 2. Adatbázis létrehozása

```bash
# Jelentkezz be MySQL-be
mysql -u root -p

# Futtasd a telepítő scriptet
source database.sql

# Vagy importáld phpMyAdmin-ból
```

### 3. Adatbázis konfiguráció

Szerkeszd a `config/database.php` fájlt:

```php
define('DB_HOST', 'localhost');     // ← Állítsd be
define('DB_NAME', 'atr_betegek');   // ← Állítsd be
define('DB_USER', 'root');          // ← Állítsd be
define('DB_PASS', '');              // ← Állítsd be
```

### 4. Jogosultságok beállítása

```bash
chmod -R 755 public/
chmod 644 data/osztaly.csv
```

### 5. Webszerver indítása

**Fejlesztői verzió** (PHP beépített webszerver):

```bash
cd public/
php -S localhost:8000
```

Böngészőben nyisd meg: `http://localhost:8000`

**Éles verzió** (Apache/Nginx):

Állítsd be a DocumentRoot-ot a `public/` könyvtárra.

### 6. Bejelentkezés

Teszt admin hozzáférés:

- **Username**: `admin`
- **Password**: `password`

---

## 🔧 Telepítési Lépések Részletesen

### Apache Konfiguráció

```apache
<VirtualHost *:80>
    ServerName atr-betegek.local
    DocumentRoot /path/to/atr-betegek/public

    <Directory /path/to/atr-betegek/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/atr-betegek-error.log
    CustomLog ${APACHE_LOG_DIR}/atr-betegek-access.log combined
</VirtualHost>
```

### Nginx Konfiguráció

```nginx
server {
    listen 80;
    server_name atr-betegek.local;
    root /path/to/atr-betegek/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## ✅ Ellenőrzési Lista

- [ ] PHP 8.0+ telepítve
- [ ] MySQL/MariaDB telepítve
- [ ] `database.sql` futtatva
- [ ] `config/database.php` beállítva
- [ ] `data/osztaly.csv` létezik
- [ ] Jogosultságok beállítva
- [ ] Webszerver fut
- [ ] Bejelentkezés sikeres

---

## 🐛 Gyakori Problémák

### "Database connection failed"

**Megoldás**: Ellenőrizd a `config/database.php` fájlban az adatbázis kapcsolati adatokat.

### "Cannot find osztaly.csv"

**Megoldás**: Ellenőrizd, hogy a `data/osztaly.csv` fájl létezik és olvasható:

```bash
ls -la data/osztaly.csv
```

### "Session error"

**Megoldás**: Állítsd be a session könyvtár jogosultságát:

```bash
chmod 777 /tmp
```

Vagy konfiguráld a `php.ini`-ben:

```ini
session.save_path = "/custom/path/to/sessions"
```

### Ékezetes karakterek nem jelennek meg

**Megoldás**: Ellenőrizd az adatbázis karakterkódolását:

```sql
ALTER DATABASE atr_betegek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 📞 Támogatás

További segítségért lásd a `README.md` fájlt vagy fordulj a rendszer adminisztrátorához.

---

**Verzió**: 1.0.0
**Utolsó frissítés**: 2025-12-03
