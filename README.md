# ÁTR – Beragadt Betegek Nyilvántartó Rendszer

Teljes körű PHP + MySQL alapú webapplikáció az ÁTR-hez kapcsolódó "beragadt betegek" adatainak rögzítésére, listázására és Excel exportjára.

## 📋 Tartalomjegyzék

- [Funkciók](#funkciók)
- [Technológiai Stack](#technológiai-stack)
- [Telepítés](#telepítés)
- [Konfigurálás](#konfigurálás)
- [Használat](#használat)
- [Jogosultságok](#jogosultságok)
- [Adatbázis Struktúra](#adatbázis-struktúra)
- [Osztály CSV Fájl](#osztály-csv-fájl)
- [Excel Export](#excel-export)
- [Biztonság](#biztonság)
- [Fejlesztői Információk](#fejlesztői-információk)

## ✨ Funkciók

- **Rögzítés**: ÁTR betegadatok felvitele űrlapon keresztül
- **Lista / Áttekintés**: Rögzített rekordok listázása, keresés, lapozás
- **Excel Export**: CSV export a 6 kötelező oszloppal
- **Admin Felület**:
  - Rekordok szerkesztése és törlése
  - Admin felhasználók kezelése
  - Extra információk (IP cím, létrehozás ideje)
- **Kereshető Osztály Dropdown**: Medsol kód és osztály név alapján szűrhető
- **IP Cím Naplózás**: Automatikus kliens IP cím mentés
- **Reszponzív UI**: Modern, Bootstrap 5 alapú felület

## 🛠 Technológiai Stack

- **Backend**: PHP 8.0+
- **Adatbázis**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**:
  - Bootstrap 5.3
  - Select2 (kereshető dropdown)
  - Bootstrap Icons
- **Architektúra**: MVC-szerű objektumorientált struktúra

## 📦 Telepítés

### 1. Előfeltételek

- PHP 8.0 vagy újabb
- MySQL 5.7+ vagy MariaDB 10.3+
- Webszerver (Apache, Nginx)
- Composer (opcionális)

### 2. Fájlok Telepítése

```bash
# Klónozd a projektet
git clone <repository-url>
cd atr-betegek

# Állítsd be a megfelelő jogosultságokat
chmod -R 755 public/
chmod -R 777 data/
```

### 3. Adatbázis Létrehozása

```bash
# Jelentkezz be MySQL-be
mysql -u root -p

# Futtasd a database.sql fájlt
source database.sql

# Vagy phpMyAdmin-on keresztül importáld a database.sql fájlt
```

A `database.sql` fájl:
- Létrehozza az `atr_betegek` adatbázist
- Létrehozza a `admins` és `atr_records` táblákat
- Beszúr 2 teszt admin felhasználót
- Beszúr 2 példa rekordot

## ⚙️ Konfigurálás

### 1. Adatbázis Kapcsolat

Szerkeszd a `config/database.php` fájlt:

```php
define('DB_HOST', 'localhost');     // Adatbázis szerver
define('DB_NAME', 'atr_betegek');   // Adatbázis név
define('DB_USER', 'root');          // Felhasználónév
define('DB_PASS', '');              // Jelszó
```

### 2. Osztály CSV Fájl

Az `data/osztaly.csv` fájl tartalmazza az osztály adatokat. Formátum:

```csv
medsol_kod,osztaly_nev,nngyk_kod
MS001,Belgyógyászati Osztály,001000536
MS002,Sebészeti Osztály,001000537
```

**Fontos**: Az export során az `nngyk_kod` (9 karakteres kód) kerül az OSZTALY mezőbe.

### 3. Webszerver Konfiguráció

#### Apache

`.htaccess` fájl (opcionális, ha nem a `public/` könyvtárat állítod be DocumentRoot-nak):

```apache
RewriteEngine On
RewriteBase /public/
```

#### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
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
}
```

## 🚀 Használat

### Első Lépések

1. **Böngészőben nyisd meg**: `http://localhost/public/index.php`
2. **Bejelentkezés Admin-ként**:
   - Username: `admin`
   - Password: `password`

### Teszt Admin Felhasználók

A `database.sql` 2 teszt admint hoz létre:

| Username | Password | Display Name |
|----------|----------|--------------|
| `admin` | `password` | Dr. Nagy Péter |
| `teszt` | `password` | Kovács Anna |

## 👥 Jogosultságok

### Be Nem Jelentkezett Felhasználók (Ápoló/Orvos)

✅ **Mit tehetnek:**
- Új rekordok felvitele
- Lista nézet megtekintése (csak olvasás)
- Excel export letöltése (opcionális)

❌ **Mit nem tehetnek:**
- Rekordok szerkesztése
- Rekordok törlése
- Admin beállítások megtekintése
- IP címek és létrehozási idők megtekintése

### Admin Felhasználók

✅ **Minden jogosultság:**
- Új rekordok felvitele
- Meglévő rekordok szerkesztése
- Rekordok törlése
- IP címek és létrehozási idők megtekintése
- Új admin felhasználók létrehozása
- Admin beállítások kezelése

## 🗄 Adatbázis Struktúra

### `atr_records` Tábla

| Mező | Típus | Leírás |
|------|-------|--------|
| `id` | INT AUTO_INCREMENT | Elsődleges kulcs |
| `intezmeny` | VARCHAR(10) | Fix: 140100 |
| `osztaly` | VARCHAR(20) | 9 karakteres NNGYK kód |
| `tavido` | DATETIME | Távozási időpont |
| `atr_dismissing_type` | VARCHAR(50) | Elbocsátás módja |
| `atr_nursing_cycle_id` | VARCHAR(100) | ÁTR ápolási ciklus ID |
| `atr_nursing_cycle_data_id` | VARCHAR(100) | ÁTR ápolási ciklus adat ID |
| `created_ip` | VARCHAR(45) | IP cím (IPv4/IPv6) |
| `created_at` | DATETIME | Létrehozás időpontja |
| `created_by_admin_id` | INT NULL | Admin ID (ha admin hozta létre) |

### `admins` Tábla

| Mező | Típus | Leírás |
|------|-------|--------|
| `id` | INT AUTO_INCREMENT | Elsődleges kulcs |
| `username` | VARCHAR(50) | Egyedi felhasználónév |
| `password_hash` | VARCHAR(255) | Hashed jelszó |
| `display_name` | VARCHAR(100) | Megjelenített név |
| `created_at` | TIMESTAMP | Létrehozás időpontja |

## 📊 Osztály CSV Fájl

A `data/osztaly.csv` fájl 3 oszlopot tartalmaz:

1. **medsol_kod**: Medsol azonosító (pl. MS001)
2. **osztaly_nev**: Osztály teljes neve (pl. Belgyógyászati Osztály)
3. **nngyk_kod**: 9 karakteres NNGYK/NNK9 kód (pl. 001000536)

**Fontos tudnivalók:**

- A kereshető dropdown mind a 3 mezőben keres
- A kiválasztott osztályból az `nngyk_kod` kerül mentésre
- Az `nngyk_kod` jelenik meg az Excel exportban az OSZTALY oszlopban

## 📤 Excel Export

### Export Oszlopok (Sorrendben)

1. `INTEZMENY` – Intézmény kód (140100)
2. `OSZTALY` – 9 karakteres NNGYK kód
3. `TAVIDO` – Távozási idő (ÉÉÉÉ.MM.NN ÓÓ:PP formátum)
4. `ATR_DISMISSING_TYPE` – Elbocsátás módja
5. `ATR_NURSING_CYCLE_ID` – ÁTR ápolási ciklus ID
6. `ATR_NURSING_CYCLE_DATA_ID` – ÁTR ápolási ciklus adat ID

### ⚠️ Fontos

- Az `created_ip` és `created_at` **NEM** kerül bele az exportba
- Az export fájl UTF-8 BOM kódolású (Excel kompatibilis)
- Pontosvesszővel (`;`) elválasztott CSV formátum
- Fájlnév: `atr_export_ÉÉÉÉ-MM-DD_ÓÓPPMP.csv`

## 🔒 Biztonság

### Implementált Biztonsági Intézkedések

- ✅ **Prepared Statements**: SQL injection védelem
- ✅ **Password Hashing**: `password_hash()` + `password_verify()`
- ✅ **Session Management**: Biztonságos session kezelés
- ✅ **HTML Escaping**: XSS védelem (`htmlspecialchars()`)
- ✅ **IP Logging**: Automatikus IP cím naplózás
- ✅ **Admin Only Actions**: Jogosultság ellenőrzés minden műveletnél

### Jelszó Változtatás

Új jelszó hash generálás PHP-ban:

```php
echo password_hash('új_jelszó', PASSWORD_DEFAULT);
```

## 👨‍💻 Fejlesztői Információk

### Fájl Struktúra

```
atr-betegek/
├── config/
│   └── database.php          # Adatbázis konfiguráció
├── data/
│   └── osztaly.csv           # Osztály adatok CSV
├── includes/
│   ├── header.php            # Fejléc template
│   ├── footer.php            # Lábléc template
│   └── functions.php         # Segédfüggvények
├── models/
│   ├── AtrRecord.php         # ÁTR rekord model
│   └── Admin.php             # Admin model
├── public/
│   ├── css/
│   │   └── style.css         # Egyedi stílusok
│   ├── index.php             # Rögzítés oldal
│   ├── list.php              # Lista oldal
│   ├── edit.php              # Szerkesztés oldal (admin)
│   ├── export.php            # Export oldal
│   ├── login.php             # Bejelentkezés
│   ├── logout.php            # Kijelentkezés
│   └── admin.php             # Admin beállítások
├── database.sql              # Adatbázis séma + kezdő adatok
└── README.md                 # Dokumentáció
```

### Model Osztályok

**AtrRecord.php** – Fő rekordok kezelése
- `create($data)` – Új rekord létrehozása
- `getAll($page, $perPage, $search)` – Lista lekérés
- `getById($id)` – Rekord lekérés ID alapján
- `update($id, $data)` – Rekord módosítás
- `delete($id)` – Rekord törlés
- `getAllForExport()` – Export adatok lekérés

**Admin.php** – Admin felhasználók kezelése
- `authenticate($username, $password)` – Bejelentkezés
- `login($username, $password)` – Session létrehozás
- `logout()` – Kijelentkezés
- `create($data)` – Új admin létrehozása
- `getAll()` – Összes admin listázása

### Segédfüggvények (functions.php)

- `loadOsztalyData()` – CSV fájl betöltése
- `formatDateTime($datetime)` – Dátum formázás megjelenítéshez
- `formatDateTimeLocal($datetime)` – Dátum formázás input mezőhöz
- `validateAtrRecord($data)` – Rekord validálás
- `exportToCSV($data, $filename)` – CSV export
- `breadcrumb($items)` – Breadcrumb generálás

## 🐛 Hibaelhárítás

### Adatbázis Kapcsolódási Hiba

```
Database connection failed: SQLSTATE[HY000] [1045] Access denied...
```

**Megoldás**: Ellenőrizd a `config/database.php` fájlban az adatbázis hozzáférési adatokat.

### Osztály CSV Nem Töltődik Be

**Megoldás**:
1. Ellenőrizd, hogy létezik-e a `data/osztaly.csv` fájl
2. Állítsd be a megfelelő jogosultságokat: `chmod 644 data/osztaly.csv`

### Session Hibák

**Megoldás**: Állítsd be a megfelelő jogosultságokat a session könyvtárra vagy konfiguráld a `php.ini`-ben:

```ini
session.save_path = "/path/to/sessions"
```

## 📝 Licenc

Ez a projekt belső használatra készült.

## 👨‍💻 Kapcsolat

Kérdések esetén fordulj a rendszer adminisztrátorához.

---

**Verzió**: 1.0.0
**Utolsó frissítés**: 2025-12-03
