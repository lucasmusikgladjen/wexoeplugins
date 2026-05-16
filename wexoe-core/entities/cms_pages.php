<?php
/**
 * Entity schema: cms_pages
 *
 * One-off informational pages (start, about, pillar-sidor, etc.).
 * Airtable-tabell: cms_pages (tblglNKHehRWy3lEM) i Wexoe NY.
 * Renderas av wexoe-pages-pluginet via shortcoden [wexoe_page slug="..."].
 *
 * En sida består av:
 *   - Metadata (slug, h1, SEO, scope-länkar, page-theme)
 *   - En länkad lista section_ids → cms_page_sections (polymorfa sektioner)
 *
 * Sektionsordningen styrs av link-fältets ordning. Använd `order`-fältet på
 * sektionen för builder-sortering, men rendering följer link-ordningen som
 * find_by_ids() bevarar.
 *
 * Konvention: snake_case överallt — passthrough.
 */

if (!defined('ABSPATH')) exit;

return [
    'base_id' => \Wexoe\Core\Plugin::SSOT_BASE_ID,
    'table_id' => 'tblglNKHehRWy3lEM',
    'primary_key' => 'slug',
    'cache_ttl' => 86400,
    'required' => ['slug'],
    'fields' => [
        'slug' => 'slug',
        'internal_label' => 'internal_label',
        'internal_notes' => 'internal_notes',
        'h1' => 'h1',
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'og_image_url' => 'og_image_url',
        'is_published' => ['source' => 'is_published', 'type' => 'bool'],
        'country_ids' => ['source' => 'country_ids', 'type' => 'link', 'entity' => 'core_countries'],
        'division_ids' => ['source' => 'division_ids', 'type' => 'link', 'entity' => 'core_divisions'],
        'page_theme' => 'page_theme',
        'max_width' => 'max_width',
        'section_ids' => ['source' => 'section_ids', 'type' => 'link', 'entity' => 'cms_page_sections'],
    ],
];
