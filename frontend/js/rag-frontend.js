/**
 * Rivian Accessory Guide — Frontend JS
 * Minimal progressive enhancement.
 *
 * @package Rivian_Accessory_Guide
 */

/**
 * Ensure all external card links have proper rel attributes.
 */
export function initCardLinks() {
    const cards = document.querySelectorAll('.rag-card[target="_blank"]');
    cards.forEach((card) => {
        if (!card.getAttribute('rel') || !card.getAttribute('rel').includes('noopener')) {
            card.setAttribute('rel', 'noopener noreferrer');
        }
    });
}

/**
 * Wire up the vehicle filter chips to show/hide accessory cards.
 *
 * Each chip carries a vehicle slug in `data-vehicle` ("all" shows everything).
 * Cards expose their vehicles via a space-separated `data-vehicles` attribute;
 * a card with no vehicles is treated as universal and always shows.
 */
export function initVehicleFilter() {
    const bar = document.querySelector('.rag-filter-bar');
    if (!bar) {
        return;
    }

    const container = bar.closest('.rag-container');
    if (!container) {
        return;
    }

    const chips = Array.from(bar.querySelectorAll('.rag-filter-chip'));
    const cards = Array.from(container.querySelectorAll('.rag-card'));
    const sections = Array.from(container.querySelectorAll('.rag-section'));
    const emptyMsg = container.querySelector('.rag-filter-empty');

    const apply = (vehicle) => {
        let visibleCount = 0;

        cards.forEach((card) => {
            const vehicles = (card.getAttribute('data-vehicles') || '').split(/\s+/).filter(Boolean);
            // Show when filtering is off, the card matches, or the card is universal.
            const show = vehicle === 'all' || vehicles.length === 0 || vehicles.includes(vehicle);
            card.hidden = !show;
            if (show) {
                visibleCount += 1;
            }
        });

        // Hide section headings whose cards are all filtered out.
        sections.forEach((section) => {
            const hasVisible = section.querySelector('.rag-card:not([hidden])');
            section.hidden = !hasVisible;
        });

        if (emptyMsg) {
            emptyMsg.hidden = visibleCount !== 0;
        }
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chips.forEach((c) => {
                c.classList.remove('is-active');
                c.setAttribute('aria-pressed', 'false');
            });
            chip.classList.add('is-active');
            chip.setAttribute('aria-pressed', 'true');
            apply(chip.getAttribute('data-vehicle') || 'all');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCardLinks();
    initVehicleFilter();
});
