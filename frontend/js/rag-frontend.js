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
 * Wire up the filter chips (vehicle / category / price), the sort control, and
 * the live result count.
 *
 * Each chip row is a `.rag-filter-bar` marked with `data-filter`
 * ("vehicle" | "category" | "price"); chips carry their value in `data-value`
 * ("all" clears that row). Filters combine with AND logic:
 *
 *   - Vehicle: matches when "all", the card lists the vehicle, or the card has
 *     no vehicles (universal).
 *   - Category: a section matches when "all" or its `data-category` equals the
 *     selection.
 *   - Price: matches when "all" or the card's `data-price` tier equals the
 *     selection. Cards with no tier are hidden while a specific price is active.
 *
 * Sorting happens within each category section (the groups stay intact).
 */
export function initFilters() {
    const container = document.querySelector('.rag-container');
    if (!container) {
        return;
    }

    const sections = Array.from(container.querySelectorAll('.rag-section'));
    const bars = Array.from(container.querySelectorAll('.rag-filter-bar'));
    const sortSelect = container.querySelector('[data-sort]');
    const emptyMsg = container.querySelector('.rag-filter-empty');
    const countNum = container.querySelector('.rag-count-num');
    const state = { vehicle: 'all', category: 'all', price: 'all', sort: 'default' };

    // Remember each section's original card order so "Featured" can be restored.
    const originalOrder = new Map();
    sections.forEach((section) => {
        const grid = section.querySelector('.rag-cards');
        if (grid) {
            originalOrder.set(grid, Array.from(grid.querySelectorAll('.rag-card')));
        }
    });

    const sortCards = (cards) => {
        const tier = (card) => parseInt(card.getAttribute('data-price'), 10) || 0;
        const name = (card) => (card.getAttribute('data-name') || '').toLowerCase();
        const sorted = cards.slice();
        switch (state.sort) {
            case 'price-asc':
                // Untiered cards (0) sink to the bottom.
                sorted.sort((a, b) => (tier(a) || Infinity) - (tier(b) || Infinity));
                break;
            case 'price-desc':
                sorted.sort((a, b) => tier(b) - tier(a));
                break;
            case 'name-asc':
                sorted.sort((a, b) => name(a).localeCompare(name(b)));
                break;
            default:
                return cards; // Original order.
        }
        return sorted;
    };

    const apply = () => {
        let visibleCount = 0;

        sections.forEach((section) => {
            const sectionCat = section.getAttribute('data-category') || '';
            const catMatch = state.category === 'all' || sectionCat === state.category;
            const grid = section.querySelector('.rag-cards');
            const cards = grid ? sortCards(originalOrder.get(grid) || []) : [];
            let hasVisible = false;

            cards.forEach((card) => {
                const vehicles = (card.getAttribute('data-vehicles') || '').split(/\s+/).filter(Boolean);
                const vehMatch = state.vehicle === 'all' || vehicles.length === 0 || vehicles.includes(state.vehicle);
                const price = card.getAttribute('data-price') || '';
                const priceMatch = state.price === 'all' || price === state.price;
                const show = catMatch && vehMatch && priceMatch;
                card.hidden = !show;
                if (show) {
                    hasVisible = true;
                    visibleCount += 1;
                }
                // Re-append in sorted order (no-ops when already in place).
                if (grid) {
                    grid.appendChild(card);
                }
            });

            // Hide a section when its category is filtered out or it has no matching cards.
            section.hidden = !(catMatch && hasVisible);
        });

        if (emptyMsg) {
            emptyMsg.hidden = visibleCount !== 0;
        }
        if (countNum) {
            countNum.textContent = String(visibleCount);
        }
    };

    bars.forEach((bar) => {
        const type = bar.getAttribute('data-filter') || 'vehicle';
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

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            state.sort = sortSelect.value || 'default';
            apply();
        });
    }

    // Initial pass sets the count and applies the default sort.
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    initCardLinks();
    initFilters();
});
