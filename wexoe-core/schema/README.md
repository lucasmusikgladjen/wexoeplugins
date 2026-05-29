# `schema/` — single-source entitetsscheman (ARKITEKTURPLAN FAS 0–1)

Det här är den **kanoniska källan** för en entitets fältlista. Ett fält
definieras *en gång* här och konsumeras av båda repona:

- **wexoe-core (PHP):** `entities/<table>.php` är en enrads-shim
  `return \Wexoe\Core\Schema::from_json('<table>');`. `Schema::from_json()`
  översätter JSON → den array-form `SchemaRegistry`/`Normalizer` förväntar sig.
- **wexoebuilder (TS):** läser en spegelkopia av samma JSON (se
  `wexoebuilder/schema/`) via `lib/schema/to-state.ts` för record → state.

Mål: ta bort den tidigare 6–10-faldiga upprepningen av samma fältlista
(`entities/*.php`, `write-entities/*.php`, builderns `*-types.ts`,
`*-mapper.ts`, `airtable-schema-*.md`).

> **Repo-spegling:** JSON-filerna är kanoniska *här*. Buildern har en kopia
> under `wexoebuilder/schema/` tills ett delat npm-paket / git-submodule
> införs. Ändrar du ett schema — uppdatera båda kopiorna i samma change-set.
> Detta är samma sanktionerade duplicering som `ARKITEKTURPLAN.md` självt.

## Status (pilot)

| Tabell | JSON | PHP-read härledd | Builder-read härledd |
|---|---|---|---|
| `cms_customer_type_pages` | ✅ | ✅ | ✅ (skalärer + länkar; `contact_form`-blocket + `stat_number`-som-sträng är escape-hatchar, se nedan) |

Övriga entiteter migreras efter att piloten verifierats (en i taget).

## Format

```jsonc
{
  "table": "cms_customer_type_pages",   // dokumentation
  "table_id": "tbl...",                 // krävs
  "base": "ssot",                       // "ssot" → Plugin::SSOT_BASE_ID; "legacy"/utelämnat → plugin-default; "app..." → explicit
  "primary_key": "slug",
  "cache_ttl": 86400,                   // sekunder, default 86400
  "required": ["slug"],
  "fields": {
    "<airtable_field>": {
      "type": "text",                   // se typtabell nedan
      "source": "<airtable_field>",     // valfritt, default = nyckeln
      "entity": "<entity>",             // för type=link (dokumentation)

      // Builder-only hints — IGNORERAS helt av PHP:
      "php_only": true,                 // läses bara av PHP (ej builder-state)
      "block": "contact_form",          // hör till ett delat block (builder hanterar separat; FAS 3)
      "builder_as": "string"            // builderns representation avviker från read-typen
    }
  }
}
```

### Fälttyper

| JSON-typ | PHP (Normalizer) | Builder-state (`to-state.ts`) |
|---|---|---|
| `text`, `richtext`, `image`, `url` | string passthrough | `string` |
| `int`, `float` | `int` / `float` | `number` (om inte `builder_as: "string"`) |
| `bool` | `bool` (aldrig null) | `boolean` |
| `lines` | `string[]` | `string` (rå multiline; konsumenten splittar) |
| `link` | `string[]` av record-IDs | `string[]` |
| `attachment` / `attachments` | attachment-objekt | (ej i builder-state än) |

Typuppsättningen är ett superset av `Normalizer`-typerna — håll dem i synk om
nya typer läggs till.

### Builder-konventioner

- **Statenyckel** härleds från fältnamnet via snake_case → camelCase
  (`stat_number` → `statNumber`, `value_text_1` → `valueText1`).
- `php_only`-fält och `block`-fält hoppas över av `to-state.ts`; resten blir
  top-level state-fält.
- Beräknade UI-flaggor (t.ex. `showValue`) och nästlade block (t.ex.
  `contactForm`) sätts av sidtypens mapper ovanpå det härledda resultatet —
  block blir egna scheman i FAS 3.
