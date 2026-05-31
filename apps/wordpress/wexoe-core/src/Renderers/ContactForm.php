<?php
namespace Wexoe\Core\Renderers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ContactForm-renderer. CSS-prefix `wxcf-*`.
 *
 * Anropas av wexoe-pages (Tier 2), wexoe-landing-page, wexoe-product-area,
 * wexoe-audience-hero. Returnerar HTML inkl. inline CSS + JS som POST:ar till
 * admin-ajax.php?action=wxcf_submit. Submission landar i `user_submissions`
 * via Core::submission().
 *
 * Config (alla optional):
 *   eyebrow           — string
 *   title             — string (default: "Prata med någon som kan automation")
 *   subtitle          — string
 *   layout            — 'split' | 'centered'  (default: split)
 *   theme             — 'dark' | 'light'      (default: dark)
 *   show_company      — bool (default: true)
 *   show_phone        — bool (default: true)
 *   show_dropdown     — bool (default: true)
 *   dropdown_label    — string (default: "Vad kan vi hjälpa dig med?")
 *   options           — string[] | string (multiline) | null
 *   cta_text          — string (default: "Skicka")
 *   message_label     — string (default: "Berätta mer (valfritt)")
 *   trust_signals     — string[] | string | null  (format "**Bold** | Resten")
 *   colors            — array {main?, accent?}  (override-färger)
 *   source_plugin     — string (för User data.Source Plugin)
 *   page_slug         — string (för User data.Page Slug)
 *   contact_person    — array | null {name, title, email, phone, image, quote}
 */
class ContactForm {

    public static function render(array $raw): string {
        $cfg = self::normalize($raw);
        $uniqid = 'wxcf-' . wp_generate_password(8, false, false);
        $nonce = wp_create_nonce('wxcf_submit');
        ob_start();
        echo self::render_html($uniqid, $cfg, $nonce);
        echo self::render_style($uniqid, $cfg);
        echo self::render_script($uniqid);
        return ob_get_clean();
    }

    /**
     * Bygg render-config från en records `contact_form_json`-fält (delat block).
     *
     * JSON lagras i snake_case med oprefixade nycklar (eyebrow, title,
     * show_company, cta_text, …) — exakt samma form som `render()` förväntar
     * sig, så det här är i princip bara en `json_decode` + per-sida-tillägg
     * (colors/source_plugin/page_slug/contact_person).
     *
     * `$extra` mergas ovanpå det avkodade blocket (per-sida-fält som inte bor
     * i det delade blocket). Saknas/ogiltig JSON → tomt block (render() faller
     * tillbaka på sina defaults).
     */
    public static function from_record(array $data, array $extra = []): array {
        $decoded = [];
        $raw = $data['contact_form_json'] ?? null;
        if (is_string($raw) && trim($raw) !== '') {
            $parsed = json_decode($raw, true);
            if (is_array($parsed)) {
                $decoded = $parsed;
            }
        }
        return array_merge($decoded, $extra);
    }

    /* --------------------------------------------------------
       NORMALIZATION
       -------------------------------------------------------- */

    private static function normalize(array $raw): array {
        return [
            'eyebrow'        => (string) ($raw['eyebrow'] ?? ''),
            'title'          => trim((string) ($raw['title'] ?? '')) !== '' ? (string) $raw['title'] : 'Prata med någon som kan automation',
            'subtitle'       => (string) ($raw['subtitle'] ?? ''),
            'layout'         => in_array($raw['layout'] ?? 'split', ['split', 'centered'], true) ? $raw['layout'] : 'split',
            'theme'          => in_array($raw['theme'] ?? 'dark', ['dark', 'light'], true) ? $raw['theme'] : 'dark',
            'show_company'   => isset($raw['show_company']) ? (bool) $raw['show_company'] : true,
            'show_phone'     => isset($raw['show_phone']) ? (bool) $raw['show_phone'] : true,
            'show_dropdown'  => isset($raw['show_dropdown']) ? (bool) $raw['show_dropdown'] : true,
            'dropdown_label' => trim((string) ($raw['dropdown_label'] ?? '')) !== '' ? (string) $raw['dropdown_label'] : 'Vad kan vi hjälpa dig med?',
            'options'        => self::parse_lines($raw['options'] ?? null, [
                'Generell fråga',
                'Diskutera ett projekt',
                'Lägga en order',
                'Minska stillestånd',
                'Förbättra OEE',
                'Info om produkt',
            ]),
            'cta_text'       => trim((string) ($raw['cta_text'] ?? '')) !== '' ? (string) $raw['cta_text'] : 'Skicka',
            'message_label'  => trim((string) ($raw['message_label'] ?? '')) !== '' ? (string) $raw['message_label'] : 'Berätta mer (valfritt)',
            'trust_signals'  => self::parse_trust_signals($raw['trust_signals'] ?? null),
            'colors'         => is_array($raw['colors'] ?? null) ? $raw['colors'] : [],
            'source_plugin'  => (string) ($raw['source_plugin'] ?? 'wexoe-core'),
            'page_slug'      => (string) ($raw['page_slug'] ?? ''),
            'contact_person' => is_array($raw['contact_person'] ?? null) ? $raw['contact_person'] : null,
        ];
    }

    private static function parse_lines($value, array $default): array {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $v) {
                if (is_string($v) && trim($v) !== '') $out[] = trim($v);
            }
            return !empty($out) ? $out : $default;
        }
        if (is_string($value) && $value !== '') {
            $parts = array_values(array_filter(array_map('trim', explode("\n", $value)), function ($l) {
                return $l !== '';
            }));
            return !empty($parts) ? $parts : $default;
        }
        return $default;
    }

    /**
     * Trust signals: en per rad, format "**Bold** | Resten" eller bara fri-text.
     * Returnerar array av {bold, text}.
     */
    private static function parse_trust_signals($value): array {
        $lines = self::parse_lines($value, []);
        $out = [];
        foreach ($lines as $line) {
            if (preg_match('/^\*\*(.+?)\*\*\s*\|?\s*(.*)$/u', $line, $m)) {
                $out[] = ['bold' => trim($m[1]), 'text' => trim($m[2])];
            } else {
                $out[] = ['bold' => '', 'text' => $line];
            }
            if (count($out) >= 3) break;
        }
        return $out;
    }

    /* --------------------------------------------------------
       HTML
       -------------------------------------------------------- */

    private static function render_html($uniqid, array $cfg, $nonce): string {
        $layout_class = 'wxcf--' . $cfg['layout'];
        $theme_class = 'wxcf--' . $cfg['theme'];

        ob_start();
        ?>
        <div id="<?= esc_attr($uniqid) ?>" class="wxcf <?= esc_attr($layout_class) ?> <?= esc_attr($theme_class) ?>">
            <div class="wxcf__container">

                <?php if ($cfg['layout'] === 'split'): ?>
                <aside class="wxcf__info">
                <?php endif; ?>

                <?php if ($cfg['eyebrow'] !== ''): ?>
                    <p class="wxcf__eyebrow"><?= esc_html($cfg['eyebrow']) ?></p>
                <?php endif; ?>
                <h2 class="wxcf__title"><?= esc_html($cfg['title']) ?></h2>
                <?php if ($cfg['subtitle'] !== ''): ?>
                    <p class="wxcf__subtitle"><?= nl2br(esc_html($cfg['subtitle'])) ?></p>
                <?php endif; ?>

                <?php if (!empty($cfg['trust_signals'])): ?>
                    <ul class="wxcf__trust">
                        <?php foreach ($cfg['trust_signals'] as $sig): ?>
                            <li class="wxcf__trust-item">
                                <svg class="wxcf__trust-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>
                                    <?php if ($sig['bold'] !== ''): ?><strong><?= esc_html($sig['bold']) ?></strong> <?php endif; ?>
                                    <?= esc_html($sig['text']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($cfg['contact_person'] !== null): ?>
                    <div class="wxcf__person">
                        <?php if (!empty($cfg['contact_person']['image'])): ?>
                            <img class="wxcf__person-img" src="<?= esc_url($cfg['contact_person']['image']) ?>" alt="" />
                        <?php endif; ?>
                        <div class="wxcf__person-info">
                            <?php if (!empty($cfg['contact_person']['name'])): ?>
                                <strong><?= esc_html($cfg['contact_person']['name']) ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($cfg['contact_person']['title'])): ?>
                                <span><?= esc_html($cfg['contact_person']['title']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cfg['contact_person']['email'])): ?>
                                <a href="mailto:<?= esc_attr($cfg['contact_person']['email']) ?>"><?= esc_html($cfg['contact_person']['email']) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($cfg['contact_person']['phone'])): ?>
                                <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $cfg['contact_person']['phone'])) ?>"><?= esc_html($cfg['contact_person']['phone']) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($cfg['layout'] === 'split'): ?>
                </aside>
                <div class="wxcf__form-wrap">
                <?php endif; ?>

                <form class="wxcf__form" novalidate>
                    <input type="hidden" name="_wxcf_nonce" value="<?= esc_attr($nonce) ?>" />
                    <input type="hidden" name="_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" />
                    <input type="hidden" name="source_plugin" value="<?= esc_attr($cfg['source_plugin']) ?>" />
                    <input type="hidden" name="page_slug" value="<?= esc_attr($cfg['page_slug']) ?>" />
                    <input type="hidden" name="page_url" value="" data-current-url="1" />

                    <div class="wxcf__error" role="alert" hidden></div>

                    <div class="wxcf__row wxcf__row--<?= $cfg['show_company'] ? 'two' : 'one' ?>">
                        <label class="wxcf__field">
                            <span class="wxcf__label">Namn <em>*</em></span>
                            <input type="text" name="name" required class="wxcf__input" />
                        </label>
                        <?php if ($cfg['show_company']): ?>
                            <label class="wxcf__field">
                                <span class="wxcf__label">Företag <em>*</em></span>
                                <input type="text" name="company" required class="wxcf__input" />
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="wxcf__row wxcf__row--<?= $cfg['show_phone'] ? 'two' : 'one' ?>">
                        <label class="wxcf__field">
                            <span class="wxcf__label">E-post <em>*</em></span>
                            <input type="email" name="email" required class="wxcf__input" />
                        </label>
                        <?php if ($cfg['show_phone']): ?>
                            <label class="wxcf__field">
                                <span class="wxcf__label">Telefon</span>
                                <input type="tel" name="phone" class="wxcf__input" />
                            </label>
                        <?php endif; ?>
                    </div>

                    <?php if ($cfg['show_dropdown'] && !empty($cfg['options'])): ?>
                        <label class="wxcf__field">
                            <span class="wxcf__label"><?= esc_html($cfg['dropdown_label']) ?></span>
                            <select name="behov" class="wxcf__input">
                                <option value="" disabled selected>Välj ett alternativ</option>
                                <?php foreach ($cfg['options'] as $opt): ?>
                                    <option value="<?= esc_attr($opt) ?>"><?= esc_html($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>

                    <label class="wxcf__field">
                        <span class="wxcf__label"><?= esc_html($cfg['message_label']) ?></span>
                        <textarea name="message" rows="4" class="wxcf__input"></textarea>
                    </label>

                    <label class="wxcf__consent">
                        <input type="checkbox" name="newsletter_consent" value="1" />
                        <span>Ja, jag vill ta emot nyheter, tips och erbjudanden från Wexoe via e-post. Du kan när som helst avregistrera dig.</span>
                    </label>

                    <button type="submit" class="wxcf__submit">
                        <span><?= esc_html($cfg['cta_text']) ?></span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="wxcf__success" hidden>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <h3>Tack för ditt meddelande!</h3>
                    <p>Vi återkommer så snart som möjligt.</p>
                </div>

                <?php if ($cfg['layout'] === 'split'): ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* --------------------------------------------------------
       CSS
       -------------------------------------------------------- */

    private static function render_style($uniqid, array $cfg): string {
        $main = !empty($cfg['colors']['main']) ? esc_attr($cfg['colors']['main']) : '#11325D';
        $accent = !empty($cfg['colors']['accent']) ? esc_attr($cfg['colors']['accent']) : '#F28C28';
        ob_start();
        ?>
        <style>
        #<?= esc_attr($uniqid) ?>.wxcf { --wxcf-main: <?= $main ?>; --wxcf-accent: <?= $accent ?>; padding: 64px 24px; font-family: inherit; }
        #<?= esc_attr($uniqid) ?> strong, #<?= esc_attr($uniqid) ?> b, #<?= esc_attr($uniqid) ?> em, #<?= esc_attr($uniqid) ?> i { color: inherit; }
        #<?= esc_attr($uniqid) ?>.wxcf--dark { background: var(--wxcf-main); color: #fff; }
        #<?= esc_attr($uniqid) ?>.wxcf--light { background: #F5F6F8; color: #1A1A1A; }
        #<?= esc_attr($uniqid) ?> .wxcf__container { max-width: 1100px; margin: 0 auto; display: grid; gap: 48px; }
        #<?= esc_attr($uniqid) ?>.wxcf--split .wxcf__container { grid-template-columns: 1fr 1fr; align-items: start; }
        #<?= esc_attr($uniqid) ?>.wxcf--centered .wxcf__container { max-width: 720px; text-align: center; }
        #<?= esc_attr($uniqid) ?> .wxcf__eyebrow { text-transform: uppercase; letter-spacing: 0.08em; font-size: 12px; opacity: 0.75; margin: 0 0 12px; }
        #<?= esc_attr($uniqid) ?> .wxcf__title { font-size: clamp(1.5rem, 3vw, 2.25rem); line-height: 1.2; margin: 0 0 16px; font-weight: 600; }
        #<?= esc_attr($uniqid) ?> .wxcf__subtitle { font-size: 16px; line-height: 1.6; opacity: 0.85; margin: 0 0 24px; }
        #<?= esc_attr($uniqid) ?> .wxcf__trust { list-style: none; padding: 0; margin: 0 0 24px; display: grid; gap: 10px; }
        #<?= esc_attr($uniqid) ?> .wxcf__trust-item { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; }
        #<?= esc_attr($uniqid) ?> .wxcf__trust-icon { color: var(--wxcf-accent); flex-shrink: 0; margin-top: 1px; }
        #<?= esc_attr($uniqid) ?> .wxcf__person { display: flex; gap: 12px; align-items: center; margin-top: 16px; }
        #<?= esc_attr($uniqid) ?> .wxcf__person-img { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; }
        #<?= esc_attr($uniqid) ?> .wxcf__person-info { display: flex; flex-direction: column; font-size: 13px; }
        #<?= esc_attr($uniqid) ?> .wxcf__person-info strong { font-weight: 500; font-size: 14px; }
        #<?= esc_attr($uniqid) ?> .wxcf__person-info a { color: inherit; text-decoration: none; opacity: 0.8; }
        #<?= esc_attr($uniqid) ?> .wxcf__form-wrap { background: rgba(255, 255, 255, 0.05); padding: 24px; border-radius: 12px; }
        #<?= esc_attr($uniqid) ?>.wxcf--light .wxcf__form-wrap { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        #<?= esc_attr($uniqid) ?> .wxcf__form { display: grid; gap: 16px; }
        #<?= esc_attr($uniqid) ?> .wxcf__row { display: grid; gap: 12px; }
        #<?= esc_attr($uniqid) ?> .wxcf__row--two { grid-template-columns: 1fr 1fr; }
        #<?= esc_attr($uniqid) ?> .wxcf__field { display: block; }
        #<?= esc_attr($uniqid) ?> .wxcf__label { display: block; font-size: 13px; margin-bottom: 4px; opacity: 0.9; }
        #<?= esc_attr($uniqid) ?> .wxcf__label em { color: var(--wxcf-accent); font-style: normal; }
        #<?= esc_attr($uniqid) ?> .wxcf__input { width: 100%; padding: 10px 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.06); border-radius: 8px; color: inherit; font-size: 14px; font-family: inherit; }
        #<?= esc_attr($uniqid) ?>.wxcf--light .wxcf__input { border-color: #E0E0E0; background: #fff; color: #1A1A1A; }
        #<?= esc_attr($uniqid) ?> .wxcf__input:focus { outline: none; border-color: var(--wxcf-accent); }
        #<?= esc_attr($uniqid) ?> .wxcf__consent { display: flex; gap: 10px; align-items: flex-start; font-size: 13px; line-height: 1.5; opacity: 0.85; }
        #<?= esc_attr($uniqid) ?> .wxcf__consent input { margin-top: 3px; }
        #<?= esc_attr($uniqid) ?> .wxcf__submit { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border: none; border-radius: 8px; background: var(--wxcf-accent); color: #fff; font-size: 15px; font-weight: 500; cursor: pointer; transition: opacity 0.2s; }
        #<?= esc_attr($uniqid) ?> .wxcf__submit:hover { opacity: 0.9; }
        #<?= esc_attr($uniqid) ?> .wxcf__submit:disabled { opacity: 0.5; cursor: wait; }
        #<?= esc_attr($uniqid) ?> .wxcf__error { padding: 10px 12px; background: rgba(255,80,80,0.15); border: 1px solid rgba(255,80,80,0.3); border-radius: 6px; font-size: 13px; color: #ffb4b4; }
        #<?= esc_attr($uniqid) ?>.wxcf--light .wxcf__error { color: #c0392b; background: #fee; border-color: #fbb; }
        #<?= esc_attr($uniqid) ?> .wxcf__success { text-align: center; padding: 48px 24px; }
        #<?= esc_attr($uniqid) ?> .wxcf__success svg { color: var(--wxcf-accent); margin-bottom: 12px; }
        #<?= esc_attr($uniqid) ?> .wxcf__success h3 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 600; }
        #<?= esc_attr($uniqid) ?> .wxcf__success p { margin: 0; opacity: 0.85; }
        @media (max-width: 720px) {
            #<?= esc_attr($uniqid) ?>.wxcf--split .wxcf__container { grid-template-columns: 1fr; }
            #<?= esc_attr($uniqid) ?> .wxcf__row--two { grid-template-columns: 1fr; }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /* --------------------------------------------------------
       JS
       -------------------------------------------------------- */

    private static function render_script($uniqid): string {
        $ajax_url = esc_js(admin_url('admin-ajax.php'));
        ob_start();
        ?>
        <script>
        (function() {
            var root = document.getElementById('<?= esc_js($uniqid) ?>');
            if (!root) return;
            var form = root.querySelector('.wxcf__form');
            var submit = root.querySelector('.wxcf__submit');
            var errorEl = root.querySelector('.wxcf__error');
            var successEl = root.querySelector('.wxcf__success');
            var urlInput = root.querySelector('[data-current-url]');
            if (urlInput) urlInput.value = window.location.href;
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                errorEl.hidden = true;
                errorEl.textContent = '';
                submit.disabled = true;
                var oldText = submit.querySelector('span').textContent;
                submit.querySelector('span').textContent = 'Skickar…';

                var fd = new FormData(form);
                fd.append('action', 'wxcf_submit');

                fetch('<?= $ajax_url ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data && data.success) {
                            form.hidden = true;
                            successEl.hidden = false;
                            successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            throw new Error((data && data.error) || 'submit_failed');
                        }
                    })
                    .catch(function(err) {
                        errorEl.hidden = false;
                        errorEl.textContent = 'Tyvärr, något gick fel. Försök igen eller mejla oss direkt.';
                        console.error('wxcf submit error:', err);
                    })
                    .finally(function() {
                        submit.disabled = false;
                        submit.querySelector('span').textContent = oldText;
                    });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
