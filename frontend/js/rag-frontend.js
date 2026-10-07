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
    const filtersEl = container.querySelector('.rag-filters');
    const toggle = container.querySelector('.rag-filter-toggle');
    const toggleCount = container.querySelector('.rag-filter-toggle-count');
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
        if (toggleCount) {
            // Surface how many filters are active while the panel is collapsed on mobile.
            const active = ['vehicle', 'category', 'price'].filter((key) => state[key] !== 'all').length;
            toggleCount.textContent = String(active);
            toggleCount.hidden = active === 0;
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

    // Mobile-only collapse/expand. The panel is always visible on desktop via CSS.
    if (toggle && filtersEl) {
        toggle.addEventListener('click', () => {
            const open = filtersEl.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Initial pass sets the count and applies the default sort.
    apply();
}

/**
 * One-click copy for affiliate promo codes.
 *
 * Each `.rag-affiliate-code` button carries its code in `data-code`. On click
 * the code is written to the clipboard (with a hidden-textarea fallback for
 * browsers without the async Clipboard API) and the button briefly flips to
 * a "Copied" state.
 */
export function initCopyCodes() {
    const buttons = document.querySelectorAll('.rag-affiliate-code[data-code]');
    if (!buttons.length) {
        return;
    }

    const fallbackCopy = (text) => {
        const el = document.createElement('textarea');
        el.value = text;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(el);
        return ok;
    };

    const copy = (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(() => true, () => fallbackCopy(text));
        }
        return Promise.resolve(fallbackCopy(text));
    };

    buttons.forEach((button) => {
        const feedback = button.querySelector('.rag-affiliate-copied');
        let timer = null;

        button.addEventListener('click', () => {
            const code = button.getAttribute('data-code') || '';
            if (!code) {
                return;
            }
            copy(code).then((ok) => {
                button.classList.add(ok ? 'is-copied' : 'is-failed');
                if (feedback) {
                    feedback.textContent = ok ? 'Copied' : 'Copy failed';
                }
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(() => {
                    button.classList.remove('is-copied', 'is-failed');
                    if (feedback) {
                        feedback.textContent = '';
                    }
                }, 1800);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCardLinks();
    initFilters();
    initCopyCodes();
});
