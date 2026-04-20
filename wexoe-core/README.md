# Wexoe Core

Unified Airtable data layer for Wexoe's WordPress plugins.

## Status

**Fas 1 (v0.2.0)** — grundinfrastruktur: Airtable-klient, cache, logger, admin-diagnostik.

## Installation

1. Ladda upp `wexoe-core`-mappen (eller zip:en) till `/wp-content/plugins/`
2. Aktivera via **Plugins → Installerade plugins**
3. Gå till **Verktyg → Wexoe Core** för att konfigurera API-nyckel och Base ID

## Struktur

```
wexoe-core/
├── wexoe-core.php              # Huvudfil: WP-header, constants, autoloader, bootstrap
├── src/
│   ├── Plugin.php              # Singleton + config-getters (API key, base ID)
│   ├── Cache.php               # Wrapper runt WP transients + deleteByPrefix
│   ├── Logger.php              # Ring-buffer logger (max 500 entries) i WP options
│   ├── AirtableClient.php      # HTTP-klient mot Airtable REST API
│   └── Admin/
│       └── Page.php            # Admin-sida: settings, test, cache, logs
├── .gitignore
└── README.md
```

## Version

**0.2.0** — Fas 1 komplett

