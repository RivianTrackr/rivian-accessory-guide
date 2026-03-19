/**
 * Rivian Accessory Guide — Frontend JS
 * Minimal progressive enhancement.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Ensure all external card links have proper rel attributes.
        var cards = document.querySelectorAll('.rag-card[target="_blank"]');
        cards.forEach(function (card) {
            if (!card.getAttribute('rel') || card.getAttribute('rel').indexOf('noopener') === -1) {
                card.setAttribute('rel', 'noopener noreferrer');
            }
        });
    });
})();
