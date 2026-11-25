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
 * Wire up the filter pills (vehicle / category), the sort menu, and the live
 * result count.
 *
 * Each pill row is a `.rag-filter-bar` marked with `data-filter`
 * ("vehicle" | "category"); pills carry their value in `data-value`
 * ("all" clears that row). Filters combine with AND logic:
 *
 *   - Vehicle: matches when "all", the card lists the vehicle, or the card has
 *     no vehicles (universal).
 *   - Category: matches when "all" or the card's `data-category` list holds
 *     the selection ("uncategorized" for cards with no category).
 *
 * All cards sit in one `.rag-cards` grid; sorting reorders it in place.
 */
export function initFilters() {
    const container = document.querySelector('.rag-container');
    if (!container) {
        return;
    }

    const grid = container.querySelector('.rag-cards');
    if (!grid) {
        return;
    }

    const bars = Array.from(container.querySelectorAll('.rag-filter-bar'));
    const sortSelect = container.querySelector('[data-sort]');
    const emptyMsg = container.querySelector('.rag-filter-empty');
    const countNum = container.querySelector('.rag-count-num');
    const state = { vehicle: 'all', category: 'all', sort: 'default' };

    // Remember the original card order so "Featured" can be restored.
    const originalOrder = Array.from(grid.querySelectorAll('.rag-card'));

    const list = (card, attr) => (card.getAttribute(attr) || '').split(/\s+/).filter(Boolean);

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

        sortCards(originalOrder).forEach((card) => {
            const vehicles = list(card, 'data-vehicles');
            const vehMatch = state.vehicle === 'all' || vehicles.length === 0 || vehicles.includes(state.vehicle);
            const catMatch = state.category === 'all' || list(card, 'data-category').includes(state.category);
            const show = vehMatch && catMatch;
            card.hidden = !show;
            if (show) {
                visibleCount += 1;
            }
            // Re-append in sorted order (no-ops when already in place).
            grid.appendChild(card);
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
                button.setAttribute('aria-label', (ok ? 'Copied code ' : 'Could not copy code ') + code);
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(() => {
                    button.classList.remove('is-copied', 'is-failed');
                    if (feedback) {
                        feedback.textContent = '';
                    }
                    button.setAttribute('aria-label', 'Copy code ' + code);
                }, 1800);
            });
        });
    });
}

/**
 * Phone-only "Show all N partners" toggle for the affiliate panel.
 *
 * The list starts with `.is-collapsed`, which CSS turns into "first four
 * rows only" below 720px and ignores on wider screens.
 */
export function initAffiliateToggle() {
    document.querySelectorAll('.rag-affiliates-more').forEach((button) => {
        const list = button.parentElement ? button.parentElement.querySelector('.rag-affiliate-list') : null;
        if (!list) {
            return;
        }
        button.addEventListener('click', () => {
            const collapsed = list.classList.toggle('is-collapsed');
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            button.textContent = collapsed
                ? (button.getAttribute('data-show-all') || 'Show all')
                : (button.getAttribute('data-show-fewer') || 'Show fewer');
        });
    });
}

/**
 * "Show codes" button on the one-line affiliate panel above the grid. Opens
 * the full partner list in place on every screen size.
 */
export function initCompactAffiliates() {
    document.querySelectorAll('.rag-affiliates-toggle').forEach((button) => {
        const list = document.getElementById(button.getAttribute('aria-controls') || '');
        if (!list) {
            return;
        }
        const labels = button.querySelectorAll('[data-closed]');
        button.addEventListener('click', () => {
            const open = list.hidden;
            list.hidden = !open;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            labels.forEach((el) => {
                el.textContent = el.getAttribute(open ? 'data-open' : 'data-closed') || '';
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCardLinks();
    initFilters();
    initCopyCodes();
    initAffiliateToggle();
    initCompactAffiliates();
});
