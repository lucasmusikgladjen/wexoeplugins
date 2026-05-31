<?php
/**
 * Section: contact_form (section_type = "contact_form")
 *
 * Tunn wrapper kring Core::renderer('contact-form'). Den delade renderern
 * äger markup + JS + submission-flödet. Vi bara mappar fält och resolvar
 * ev. contact-person via scope.
 */

if (!defined('ABSPATH')) exit;

/**
 * Helpers — declarerade FÖRE `return function` eftersom `require` returnerar
 * vid den statementen och allt nedanför aldrig körs.
 */
if (!function_exists('wxp_resolve_contact_person')) {
    /**
     * Slå upp första aktiva coworker baserat på sektion-scope. Returnerar null
     * om Collections-helper saknas eller ingen match.
     */
    function wxp_resolve_contact_person($section, $ctx) {
        if (!class_exists('\\Wexoe\\Core\\Helpers\\Collections')) return null;
        $scope = wexoe_pages_resolve_scope($section, $ctx, [
            'country'  => 'cf_contact_scope_country',
            'division' => 'cf_contact_scope_division',
        ]) + ['limit' => 1];
        $matches = \Wexoe\Core\Helpers\Collections::coworkers_for_scope($scope);
        if (empty($matches)) return null;
        $c = $matches[0];
        return [
            'name'  => (string) ($c['full_name'] ?? ''),
            'title' => (string) ($c['title'] ?? ''),
            'email' => (string) ($c['email'] ?? ''),
            'phone' => (string) ($c['phone'] ?? ''),
            'image' => (string) ($c['image_url'] ?? ''),
        ];
    }
}

return function ($section, $page, $ctx) {
    $class = \Wexoe\Core\Core::renderer('contact-form');
    if ($class === '') {
        return wexoe_pages_debug_comment('wexoe-pages: Core::renderer(contact-form) saknas');
    }

    $config = [
        'eyebrow'        => (string) ($section['cf_eyebrow'] ?? ''),
        'title'          => (string) ($section['cf_title'] ?? ''),
        'subtitle'       => (string) ($section['cf_subtitle'] ?? ''),
        'layout'         => (string) ($section['cf_layout'] ?? 'split'),
        // Contact-form rendererens egna 'theme'-flagga (light|dark) — separat
        // från sektion-system. Default dark (mörk gradient matchar wexoe-contact-page).
        'theme'          => 'dark',
        'show_company'   => isset($section['cf_show_company']) ? (bool) $section['cf_show_company'] : true,
        'show_phone'     => isset($section['cf_show_phone']) ? (bool) $section['cf_show_phone'] : true,
        'show_dropdown'  => isset($section['cf_show_dropdown']) ? (bool) $section['cf_show_dropdown'] : true,
        'dropdown_label' => (string) ($section['cf_dropdown_label'] ?? ''),
        'options'        => is_array($section['cf_options'] ?? null) ? $section['cf_options'] : null,
        'cta_text'       => (string) ($section['cf_cta_text'] ?? ''),
        'message_label'  => (string) ($section['cf_message_label'] ?? ''),
        'trust_signals'  => is_array($section['cf_trust_signals'] ?? null) ? $section['cf_trust_signals'] : null,
        'source_plugin'  => 'wexoe-pages',
        'page_slug'      => (string) ($page['slug'] ?? ''),
        'contact_person' => !empty($section['cf_show_contact_person'])
            ? wxp_resolve_contact_person($section, $ctx)
            : null,
    ];

    $attrs = wexoe_pages_section_attrs($section, $ctx, 'wxp-cf');
    ob_start();
    ?>
    <section <?= $attrs ?>>
        <?= $class::render($config) ?>
    </section>
    <?php
    return ob_get_clean();
};
