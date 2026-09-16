(function () {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const toggle = document.querySelector('.sidebar-toggle');

    if (!sidebar || !toggle) return;

    const storageKey = 'olfu-sidebar-state';

    let savedState = null;
    try {
        savedState = localStorage.getItem(storageKey);
    } catch (e) {
        savedState = null;
    }

    let isCollapsed = savedState
        ? savedState === 'collapsed'
        : window.matchMedia('(max-width: 860px)').matches;

    document.querySelectorAll('.sidebar-nav a').forEach(function (link) {
        if (link.hasAttribute('title')) return;
        const label = link.querySelector('span')?.textContent?.trim();
        if (label) link.setAttribute('title', label);
    });

    function persist(collapsed) {
        try {
            localStorage.setItem(storageKey, collapsed ? 'collapsed' : 'expanded');
        } catch (e) {
            /* ignore (private mode / storage disabled) */
        }
    }

    function setState(collapsed, persistState) {
        isCollapsed = collapsed;
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        toggle.querySelector('i').className = collapsed
            ? 'fas fa-chevron-right'
            : 'fas fa-chevron-left';

        if (persistState) persist(collapsed);
    }

    toggle.addEventListener('click', function () {
        setState(!isCollapsed, true);
    });

    if (!savedState) {
        window.addEventListener('resize', function () {
            if (savedState) return;
            const shouldBeCollapsed = window.matchMedia('(max-width: 860px)').matches;
            if (shouldBeCollapsed !== isCollapsed) setState(shouldBeCollapsed, false);
        });
    }

    setState(isCollapsed, false);
})();