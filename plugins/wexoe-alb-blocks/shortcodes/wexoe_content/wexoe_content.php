<?php
/**
 * Wexoe Content — Avia Layout Builder-element.
 *
 * Registrerad via `avia_load_shortcodes`-filtret. Modulen exponerar två
 * dropdowns i builder-modalen: innehållstyp och post. Värdet sparas som
 * `[wexoe_content content_type="..." content_id="{type}:{slug}"]` i post_content.
 */

if (!defined('ABSPATH')) exit;
if (!class_exists('aviaShortcodeTemplate')) return;
if (class_exists('Wexoe_Content_Block', false)) return;

class Wexoe_Content_Block extends aviaShortcodeTemplate {

    /**
     * Konfigurera builder-knappen.
     */
    function shortcode_insert_button() {
        $this->config['name']         = __('Wexoe Content', 'wexoe');
        $this->config['self_closing'] = 'yes';
        $this->config['shortcode']    = 'wexoe_content';
        $this->config['icon']         = WEXOE_ALB_URL . 'assets/builder-icon.svg';
        $this->config['order']        = 50;
        $this->config['target']       = 'avia-target-insert';
        $this->config['tab']          = __('Content Elements', 'avia_framework');
        $this->config['tooltip']      = __('Infoga Wexoe-innehåll från Airtable', 'wexoe');
        $this->config['tinyMCE']      = ['disable' => 'true'];
    }

    /**
     * Modaltens fält. Båda är `select` — den andra fylls med alla optioner
     * från alla typer och filtreras klient-sidan via assets/builder.js.
     */
    function popup_elements() {
        $types = [__('— Välj typ —', 'wexoe') => ''];
        foreach (wexoe_alb_content_types() as $slug => $cfg) {
            $types[$cfg['label']] = $slug;
        }

        $this->elements = [
            [
                'name'    => __('Innehållstyp', 'wexoe'),
                'desc'    => __('Vilken sorts Wexoe-innehåll som ska renderas.', 'wexoe'),
                'id'      => 'content_type',
                'type'    => 'select',
                'std'     => '',
                'subtype' => $types,
            ],
            [
                'name'    => __('Välj post', 'wexoe'),
                'desc'    => __('Listan filtreras utifrån vald innehållstyp.', 'wexoe'),
                'id'      => 'content_id',
                'type'    => 'select',
                'std'     => '',
                'subtype' => wexoe_alb_initial_options(),
            ],
        ];
    }

    /**
     * Builder-preview (vad redaktören ser inuti ALB:n).
     */
    function editor_element($params) {
        $args  = isset($params['args']) ? $params['args'] : [];
        $type  = isset($args['content_type']) ? (string) $args['content_type'] : '';
        $id    = isset($args['content_id']) ? (string) $args['content_id'] : '';

        $types     = wexoe_alb_content_types();
        $type_label = isset($types[$type]) ? $types[$type]['label'] : __('(ingen typ vald)', 'wexoe');
        $id_label   = $id !== '' ? $id : __('(ingen post vald)', 'wexoe');

        $params['innerHtml']  = "<div class='avia_textblock'>";
        $params['innerHtml'] .= '<strong>' . esc_html__('Wexoe Content', 'wexoe') . ':</strong><br>';
        $params['innerHtml'] .= esc_html($type_label) . '<br>';
        $params['innerHtml'] .= '<small>' . esc_html($id_label) . '</small>';
        $params['innerHtml'] .= '</div>';
        return $params;
    }

    /**
     * Frontend-rendering. Delegerar till wexoe_alb_render() som strippar
     * typ-prefix och anropar rätt render-callback.
     */
    function shortcode_handler($atts, $content = '', $shortcodename = '') {
        $atts = shortcode_atts([
            'content_type' => '',
            'content_id'   => '',
        ], $atts, $shortcodename);

        return wexoe_alb_render($atts['content_type'], $atts['content_id']);
    }
}
