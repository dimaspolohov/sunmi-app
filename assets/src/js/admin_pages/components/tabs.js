export default function () {
    const navsContainerEl = document.querySelector('[data-js-tabs="true"]');
    const tabEls = document.querySelectorAll('.js-settings-form [data-tab]');

    if (!navsContainerEl || !tabEls.length) {
        return;
    }

    const navEls = navsContainerEl.querySelectorAll('[data-tab]');
    const refererInput = document.querySelector( '.js-settings-form [name="_wp_http_referer"]' );

    changeTab((new URLSearchParams(window.location.search)).get('tab') || 'main');

    navsContainerEl.addEventListener('click', function (e) {
        const nav = e.target;

        if ('A' !== nav.tagName) {
            return;
        }

        e.preventDefault();

        changeTab(nav.getAttribute('data-tab'))
    })

    function changeTab(tabSlug) {
        navEls.forEach(el => {
            if (el.getAttribute('data-tab') === tabSlug) {
                el.classList.add('nav-tab-active')
            } else {
                el.classList.remove('nav-tab-active')
            }
        });

        tabEls.forEach(tab => {
            if (tab.getAttribute('data-tab') === tabSlug) {
                tab.classList.remove('hidden')
            } else {
                tab.classList.add('hidden')
            }
        })

        const url = new URL(window.location);
        url.searchParams.set('tab', tabSlug);
        window.history.replaceState(null, '', url.toString());
        refererInput.value = url.toString()
    }
}