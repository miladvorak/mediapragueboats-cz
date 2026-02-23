# Prague Boats Media Hub - PHP

PHP verze media hubu Prague Boats s ochranou heslem.

## Struktura projektu

```
prague-boats-media/
├── .htaccess                 # Apache rewrite + security pravidla
├── index.php                 # Hlavní stránka (chráněná heslem)
├── login.php                 # Přihlašovací stránka (veřejná)
├── logout.php                # Odhlášení
├── assets/
│   ├── css/
│   │   └── styles.css        # CSS styly
│   ├── logo/
│   │   └── logo.svg          # Logo Prague Boats
│   ├── thumbs/               # Náhledy obrázků
│   └── media/
│       └── content/          # Video soubory
├── config/
│   └── auth.php              # Konfigurace hesla (JEDINÝ soubor pro změnu hesla)
├── includes/
│   ├── auth_guard.php        # Ochrana stránek - přesměruje nepřihlášené na login
│   ├── header.php            # HTML hlavička + sidebar navigace + logout tlačítko
│   └── footer.php            # Patička + JavaScript
└── README.md
```

## Nasazení na Hostinger (sdílený hosting)

1. Nahrajte celý obsah repozitáře do `public_html/`.
2. Složky `config/` a `includes/` jsou chráněny pravidly v `.htaccess`.
3. Ověřte, že je povolený `mod_rewrite` (na Hostingeru standardně ano).
4. Hotovo — žádné další nastavení serveru není potřeba.

## Změna hesla

1. Otevřete soubor `config/auth.php`.
2. Vygenerujte nový hash hesla:
   ```bash
   php -r "echo password_hash('vase_nove_heslo', PASSWORD_DEFAULT);"
   ```
3. Nahraďte hash v `AUTH_PASSWORD_HASH` vygenerovaným hashem.
4. Hotovo. Žádný jiný soubor měnit nemusíte.

**Výchozí heslo:** `prague2025`

## Přihlášení

- Po vstupu na web se zobrazí přihlašovací formulář.
- Po zadání správného hesla se uživatel dostane na požadovanou stránku.
- Odhlášení je možné přes odkaz "Odhlásit se" v dolní části sidebaru.
- Session je zabezpečena přes `session_regenerate_id()` po přihlášení.

## Chybějící assety

Po nasazení je potřeba doplnit:

- **Náhledy** (`assets/thumbs/`) - zkopírujte z originálu
- **Videa** (`assets/media/content/`) - zkopírujte z originálu
