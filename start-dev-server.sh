#!/bin/bash

# ÁTR Beragadt Betegek - Fejlesztői Szerver Indító
# Használat: ./start-dev-server.sh

echo "================================================"
echo "ÁTR Beragadt Betegek - Fejlesztői Szerver"
echo "================================================"
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP nincs telepítve. Kérlek telepítsd a PHP 8.0+ verziót."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP verzió: $PHP_VERSION"

# Check if database.sql exists
if [ ! -f "database.sql" ]; then
    echo "❌ database.sql fájl nem található!"
    exit 1
fi

echo "✓ database.sql fájl megtalálva"

# Check if config file exists
if [ ! -f "config/database.php" ]; then
    echo "❌ config/database.php fájl nem található!"
    exit 1
fi

echo "✓ config/database.php fájl megtalálva"

# Check if osztaly.csv exists
if [ ! -f "data/osztaly.csv" ]; then
    echo "❌ data/osztaly.csv fájl nem található!"
    exit 1
fi

echo "✓ data/osztaly.csv fájl megtalálva"

echo ""
echo "================================================"
echo "Szerver indítása..."
echo "================================================"
echo ""
echo "🌐 URL: http://localhost:8000"
echo "👤 Admin felhasználó: admin"
echo "🔑 Jelszó: password"
echo ""
echo "Nyomj CTRL+C-t a szerver leállításához."
echo ""

# Start PHP built-in server
cd public && php -S localhost:8000
