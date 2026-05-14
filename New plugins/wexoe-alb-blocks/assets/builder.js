/**
 * Wexoe ALB Blocks — builder-modal: filtrera content_id på vald content_type.
 *
 * Modalen renderas dynamiskt av Avia när redaktören öppnar elementet, så
 * vi kan inte binda en gång vid load. Strategi: delegerad change-lyssnare
 * på document som matar #content_id-selecten utifrån #content_type och
 * körs även initialt när en modal öppnas (MutationObserver känner av
 * att avia-modal-container fylls).
 *
 * Option-värden har formatet `{type}:{raw_id}` så vi splittar på första
 * kolon för att avgöra om optionen hör hemma i vald typ. Tom value
 * (placeholder) är alltid synlig.
 */
(function ($) {
    'use strict';

    function getOptionType($option) {
        var val = $option.attr('value') || '';
        if (val === '') return null;
        var idx = val.indexOf(':');
        return idx === -1 ? null : val.substring(0, idx);
    }

    /**
     * Visa endast options som hör till vald typ. Sätt placeholder
     * vald om nuvarande val inte längre är giltigt.
     */
    function filterContentIdSelect($form) {
        var $type = $form.find('select[name="content_type"]');
        var $id   = $form.find('select[name="content_id"]');
        if (!$type.length || !$id.length) return;

        var chosen = $type.val() || '';
        var currentValue = $id.val() || '';
        var currentStillValid = false;

        $id.find('option').each(function () {
            var $opt = $(this);
            var optType = getOptionType($opt);
            var keep = (optType === null) || (optType === chosen);
            $opt.prop('hidden', !keep);
            $opt.prop('disabled', !keep);
            if (keep && $opt.attr('value') === currentValue && currentValue !== '') {
                currentStillValid = true;
            }
        });

        if (!currentStillValid) {
            $id.val('');
        }

        // Vissa custom select-widgets cachar option-listan. Trigger change så
        // att de re-renderar.
        $id.trigger('change');
    }

    function initFormsIn($scope) {
        $scope.find('form.avia-builder-shortcode-options, .avia_modal_content form').each(function () {
            var $form = $(this);
            if ($form.data('wexoeAlbInit')) return;
            if (!$form.find('select[name="content_type"]').length) return;
            if (!$form.find('select[name="content_id"]').length) return;
            $form.data('wexoeAlbInit', true);
            filterContentIdSelect($form);
        });
    }

    $(function () {
        // Delegerad change-lyssnare — fungerar oavsett när modalen renderas.
        $(document).on('change', 'select[name="content_type"]', function () {
            var $form = $(this).closest('form');
            if (!$form.length) $form = $(this).closest('.avia_modal_content');
            if ($form.length) filterContentIdSelect($form);
        });

        // Observera när Avia injicerar nya modaler i DOM.
        if (typeof MutationObserver === 'function') {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (!m.addedNodes) return;
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        initFormsIn($(node));
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        initFormsIn($(document));
    });
}(jQuery));
