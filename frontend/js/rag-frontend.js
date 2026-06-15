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
 * Wire up the vehicle and category filter chips to show/hide accessory cards.
 *
 * There are up to two chip rows, each marked with `data-filter` ("vehicle" or
 * "category") on its `.rag-filter-bar`. Chips carry their value in `data-value`
 * ("all" clears that row). Filters combine with AND logic:
 *
 *   - A card matches the vehicle filter when "all" is selected, the card lists
 *     the selected vehicle, or the card has no vehicles (universal).
 *   - A section (category bucket) matches when "all" is selected or its
 *     `data-category` equals the selected category.
 *
 * A card shows only when both its section's category and its own vehicle match.
 */
export function initFilters() {
    const container = document.querySelector('.rag-container');
    if (!container) {
        return;
    }

    const bars = Array.from(container.querySelectorAll('.rag-filter-bar'));
    if (!bars.length) {
        return;
    }

    const sections = Array.from(container.querySelectorAll('.rag-section'));
    const emptyMsg = container.querySelector('.rag-filter-empty');
    const state = { vehicle: 'all', category: 'all' };

    const apply = () => {
        let visibleCount = 0;

        sections.forEach((section) => {
            const sectionCat = section.getAttribute('data-category') || '';
            const catMatch = state.category === 'all' || sectionCat === state.category;
            let hasVisible = false;

            section.querySelectorAll('.rag-card').forEach((card) => {
                const vehicles = (card.getAttribute('data-vehicles') || '').split(/\s+/).filter(Boolean);
                const vehMatch = state.vehicle === 'all' || vehicles.length === 0 || vehicles.includes(state.vehicle);
                const show = catMatch && vehMatch;
                card.hidden = !show;
                if (show) {
                    hasVisible = true;
                    visibleCount += 1;
                }
            });

            // Hide a section when its category is filtered out or it has no matching cards.
            section.hidden = !(catMatch && hasVisible);
        });

        if (emptyMsg) {
            emptyMsg.hidden = visibleCount !== 0;
        }
    };

    bars.forEach((bar) => {
        const type = bar.getAttribute('data-filter') === 'category' ? 'category' : 'vehicle';
        const chips = Array.from(bar.querySelectorAll('.rag-filter-chip'));

        chips.forEach((chip) => {
            chip.addEventListener('click', () => {
                chips.forEach((c) => {
                    c.classList.remove('is-active');
                    c.setAttribute('aria-pressed', 'false');
                });
                chip.classList.add('is-active');
                chip.setAttribute('aria-pressed', 'true');
                state[type] = chip.getAttribute('data-value') || 'all';
                apply();
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCardLinks();
    initFilters();
});
