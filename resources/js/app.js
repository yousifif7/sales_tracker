import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-mobile-shell]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    const mobileOverlay = document.querySelector('[data-mobile-nav-overlay]');
    const openButton = document.querySelector('[data-mobile-nav-open]');
    const closeButton = document.querySelector('[data-mobile-nav-close]');
    const navLinks = document.querySelectorAll('[data-mobile-nav-link]');

    if (shell && mobileNav && mobileOverlay && openButton && closeButton) {
        const setOpen = (isOpen) => {
            mobileNav.classList.toggle('-translate-x-full', ! isOpen);
            mobileOverlay.classList.toggle('hidden', ! isOpen);
            document.body.classList.toggle('overflow-hidden', isOpen);
            openButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        openButton.addEventListener('click', () => setOpen(true));
        closeButton.addEventListener('click', () => setOpen(false));
        mobileOverlay.addEventListener('click', () => setOpen(false));
        navLinks.forEach((link) => link.addEventListener('click', () => setOpen(false)));
    }

    const sidebarRail = document.querySelector('[data-sidebar-rail]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const desktopMain = document.querySelector('[data-desktop-main]');
    const sidebarStorageKey = 'crm-sidebar-collapsed';

    if (sidebarRail && sidebarToggle) {
        const setCollapsed = (collapsed) => {
            const sidebarWidth = collapsed ? '5rem' : '18rem';

            sidebarRail.classList.toggle('sidebar-collapsed', collapsed);
            sidebarRail.classList.remove('sidebar-peek');
            sidebarRail.style.width = sidebarWidth;
            if (desktopMain) {
                desktopMain.style.marginLeft = sidebarWidth;
            }
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            sidebarToggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            window.localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
        };

        setCollapsed(window.localStorage.getItem(sidebarStorageKey) === '1');

        sidebarToggle.addEventListener('click', () => {
            setCollapsed(! sidebarRail.classList.contains('sidebar-collapsed'));
        });

        const setPeek = (enabled) => {
            const canPeek = sidebarRail.classList.contains('sidebar-collapsed');
            sidebarRail.classList.toggle('sidebar-peek', canPeek && enabled);
        };

        sidebarRail.addEventListener('mouseenter', () => setPeek(true));
        sidebarRail.addEventListener('mouseleave', () => setPeek(false));
    }

    document.querySelectorAll('[data-rich-editor]').forEach((root) => {
        const surface = root.querySelector('.rich-surface');
        const input = root.querySelector('textarea');
        if (! surface || ! input) {
            return;
        }

        const sync = () => {
            input.value = surface.innerHTML.trim() === '<br>' ? '' : surface.innerHTML;
        };

        root.querySelectorAll('[data-cmd]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const command = button.getAttribute('data-cmd');
                surface.focus();

                if (command === 'createLink') {
                    const url = window.prompt('Enter URL (https://...)');
                    if (! url) {
                        return;
                    }
                    const normalized = /^(https?:\/\/|mailto:)/i.test(url) ? url : `https://${url}`;
                    document.execCommand('createLink', false, normalized);
                } else {
                    document.execCommand(command, false, null);
                }

                sync();
            });
        });

        surface.addEventListener('input', sync);
        surface.addEventListener('blur', sync);

        const form = root.closest('form');
        form?.addEventListener('submit', sync);

        sync();
    });
});
