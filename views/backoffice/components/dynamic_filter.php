<?php
if (defined('VALORYS_DYNAMIC_FILTER_INCLUDED')) {
    return;
}
define('VALORYS_DYNAMIC_FILTER_INCLUDED', true);
?>

<style>
    .content-card.is-loading .table-responsive,
    .content-card.is-loading .pagination-bar {
        opacity: 0.45;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
</style>

<script>
(function () {
    const DEBOUNCE_MS = 350;
    const controllers = new WeakMap();
    const timers = new WeakMap();

    function getCard(form) {
        return form.closest('.content-card') || document;
    }

    function buildUrl(form, resetPage) {
        const url = new URL(form.action || window.location.href, window.location.href);
        const data = new FormData(form);

        for (const key of Array.from(url.searchParams.keys())) {
            url.searchParams.delete(key);
        }

        for (const [key, value] of data.entries()) {
            if (value !== '') {
                url.searchParams.set(key, value);
            }
        }

        if (resetPage) {
            url.searchParams.delete('p');
        }

        return url;
    }

    function replaceOrRemove(currentParent, selector, sourceParent, afterSelector) {
        const current = currentParent.querySelector(selector);
        const source = sourceParent.querySelector(selector);

        if (current && source) {
            current.replaceWith(source);
            return;
        }

        if (current && !source) {
            current.remove();
            return;
        }

        if (!current && source) {
            const anchor = currentParent.querySelector(afterSelector);
            if (anchor) {
                anchor.insertAdjacentElement('afterend', source);
            } else {
                currentParent.appendChild(source);
            }
        }
    }

    async function loadResults(form, url, pushState) {
        const card = getCard(form);
        const previous = controllers.get(form);
        if (previous) previous.abort();

        const controller = new AbortController();
        controllers.set(form, controller);
        card.classList.add('is-loading');

        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'fetch' },
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextCard = doc.querySelector('.content-card');

            if (!nextCard) {
                window.location.href = url.toString();
                return;
            }

            const currentTitle = card.querySelector('.card-title-row h5');
            const nextTitle = nextCard.querySelector('.card-title-row h5');
            if (currentTitle && nextTitle) {
                currentTitle.replaceWith(nextTitle);
            }

            replaceOrRemove(card, '.table-responsive', nextCard, '.filter-form');
            replaceOrRemove(card, '.pagination-bar', nextCard, '.table-responsive');

            if (pushState) {
                history.pushState({ dynamicFilterUrl: url.toString() }, '', url.toString());
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url.toString();
            }
        } finally {
            if (controllers.get(form) === controller) {
                controllers.delete(form);
                card.classList.remove('is-loading');
            }
        }
    }

    function scheduleLoad(form) {
        const existingTimer = timers.get(form);
        if (existingTimer) clearTimeout(existingTimer);

        const timer = setTimeout(() => {
            loadResults(form, buildUrl(form, true), true);
        }, DEBOUNCE_MS);

        timers.set(form, timer);
    }

    document.addEventListener('input', function (event) {
        const form = event.target.closest('form[data-dynamic-filter]');
        if (!form || event.target.matches('select')) return;
        scheduleLoad(form);
    });

    document.addEventListener('change', function (event) {
        const form = event.target.closest('form[data-dynamic-filter]');
        if (!form) return;
        loadResults(form, buildUrl(form, true), true);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-dynamic-filter]');
        if (!form) return;
        event.preventDefault();
        loadResults(form, buildUrl(form, true), true);
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.content-card .pagination-links a');
        const form = document.querySelector('form[data-dynamic-filter]');
        if (!link || !form) return;

        event.preventDefault();
        loadResults(form, new URL(link.href, window.location.href), true);
    });

    window.addEventListener('popstate', function () {
        const form = document.querySelector('form[data-dynamic-filter]');
        if (!form) return;
        loadResults(form, new URL(window.location.href), false);
    });
})();
</script>

