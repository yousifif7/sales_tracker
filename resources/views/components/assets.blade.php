@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { overflow-x: hidden; }
        body { background-color: #020617; color: #f1f5f9; }
        .panel { border-radius: 1rem; border: 1px solid #1e293b; background: rgba(15, 23, 42, 0.7); padding: 1rem; box-shadow: 0 25px 50px -12px rgba(2, 6, 23, 0.3); }
        @media (min-width: 640px) {
            .panel { border-radius: 1.5rem; padding: 1.5rem; }
        }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; border-radius: 0.75rem; background: #0ea5e9; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 600; color: #020617; }
        .btn-primary:hover { background: #38bdf8; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; border-radius: 0.75rem; border: 1px solid #334155; background: #0f172a; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 600; color: #f1f5f9; }
        .btn-secondary:hover { border-color: #475569; background: #1e293b; }
        .page-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        @media (min-width: 640px) { .page-actions { gap: 0.75rem; } }
        .page-actions > .btn-primary,
        .page-actions > .btn-secondary { flex-grow: 1; }
        @media (min-width: 640px) {
            .page-actions > .btn-primary,
            .page-actions > .btn-secondary { flex-grow: 0; }
        }
        .input { margin-top: 0.5rem; width: 100%; border-radius: 1rem; border: 1px solid #334155; background: #020617; padding: 0.75rem 1rem; color: #f1f5f9; color-scheme: dark; }
        .input:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2); }
        .label { font-size: 0.875rem; font-weight: 500; color: #e2e8f0; }
        .table-wrap { position: relative; overflow: hidden; border-radius: 1rem; border: 1px solid #1e293b; background: rgba(15, 23, 42, 0.7); }
        @media (min-width: 640px) { .table-wrap { border-radius: 1.5rem; } }
        .table-scroll { overflow-x: auto; overscroll-behavior-x: contain; -webkit-overflow-scrolling: touch; scrollbar-width: thin; scrollbar-color: #334155 transparent; }
        .table-scroll::-webkit-scrollbar { height: 8px; }
        .table-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 9999px; }
        .table { width: 100%; min-width: 44rem; border-collapse: collapse; text-align: left; font-size: 0.875rem; color: #cbd5e1; }
        .table.table-compact { min-width: 40rem; }
        .table.table-wide { min-width: 56rem; }
        .table th { white-space: nowrap; background: #0f172a; padding: 0.75rem 0.75rem; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: #64748b; }
        .table td { padding: 0.875rem 0.75rem; vertical-align: top; border-top: 1px solid #1e293b; }
        @media (min-width: 640px) {
            .table th, .table td { padding-left: 1rem; padding-right: 1rem; }
        }
        .table-compact th, .table-compact td { padding: 0.625rem 0.625rem; }
        .filter-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        @media (min-width: 640px) { .filter-actions { gap: 0.75rem; } }
        .filter-actions > .btn-primary,
        .filter-actions > .btn-secondary,
        .filter-actions > a { flex-grow: 1; }
        @media (min-width: 640px) {
            .filter-actions > .btn-primary,
            .filter-actions > .btn-secondary,
            .filter-actions > a { flex-grow: 0; }
        }
        .link-action { color: #7dd3fc; text-decoration: none; }
        .link-action:hover { color: #bae6fd; }
        .link-danger { color: #fda4af; background: none; border: 0; padding: 0; cursor: pointer; font: inherit; }
        .link-danger:hover { color: #fecdd3; }
        .row-actions { display: inline-flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.25rem 0.75rem; }
        .rich-editor { display: grid; gap: 0.5rem; }
        .rich-toolbar { margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.25rem; border: 1px solid #334155; border-radius: 1rem; background: #0f172a; padding: 0.5rem; }
        .rich-toolbar button { border: 1px solid transparent; border-radius: 0.5rem; background: transparent; color: #e2e8f0; padding: 0.35rem 0.65rem; font-size: 0.875rem; cursor: pointer; }
        .rich-toolbar button:hover { border-color: #475569; background: #1e293b; }
        .rich-sep { width: 1px; height: 1.25rem; background: #334155; margin: 0 0.25rem; }
        .rich-surface { min-height: 16rem; overflow-y: auto; white-space: pre-wrap; word-break: break-word; }
        .rich-surface p { margin: 0 0 0.75rem; }
        .rich-surface ul { margin: 0 0 0.75rem; padding-left: 1.5rem; list-style: disc; }
        .rich-surface ol { margin: 0 0 0.75rem; padding-left: 1.5rem; list-style: decimal; }
        .rich-surface a { color: #7dd3fc; text-decoration: underline; }
        .prose-email { color: #e2e8f0; overflow-wrap: anywhere; word-break: break-word; }
        .prose-email * { color: inherit !important; background: transparent !important; background-color: transparent !important; border-color: #334155 !important; max-width: 100% !important; box-shadow: none !important; }
        .prose-email p { margin: 0 0 0.75rem; }
        .prose-email ul { margin: 0 0 0.75rem; padding-left: 1.5rem; list-style: disc; }
        .prose-email ol { margin: 0 0 0.75rem; padding-left: 1.5rem; list-style: decimal; }
        .prose-email a { color: #7dd3fc !important; text-decoration: underline; }
        .prose-email blockquote { margin: 0.75rem 0; padding-left: 0.75rem; border-left: 2px solid #475569; color: #94a3b8 !important; }
        .prose-email table { width: 100%; border-collapse: collapse; margin: 0 0 0.75rem; }
        .prose-email td, .prose-email th { border: 1px solid #334155; padding: 0.25rem 0.5rem; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var mobileNav = document.querySelector('[data-mobile-nav]');
            var mobileOverlay = document.querySelector('[data-mobile-nav-overlay]');
            var openButton = document.querySelector('[data-mobile-nav-open]');
            var closeButton = document.querySelector('[data-mobile-nav-close]');
            var navLinks = document.querySelectorAll('[data-mobile-nav-link]');

            if (mobileNav && mobileOverlay && openButton && closeButton) {
                var setOpen = function (isOpen) {
                    mobileNav.classList.toggle('-translate-x-full', !isOpen);
                    mobileOverlay.classList.toggle('hidden', !isOpen);
                    document.body.classList.toggle('overflow-hidden', isOpen);
                    openButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                };
                openButton.addEventListener('click', function () { setOpen(true); });
                closeButton.addEventListener('click', function () { setOpen(false); });
                mobileOverlay.addEventListener('click', function () { setOpen(false); });
                navLinks.forEach(function (link) {
                    link.addEventListener('click', function () { setOpen(false); });
                });
            }

            document.querySelectorAll('[data-rich-editor]').forEach(function (root) {
                var surface = root.querySelector('.rich-surface');
                var input = root.querySelector('textarea');
                if (!surface || !input) return;
                var sync = function () {
                    input.value = surface.innerHTML.trim() === '<br>' ? '' : surface.innerHTML;
                };
                root.querySelectorAll('[data-cmd]').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var command = button.getAttribute('data-cmd');
                        surface.focus();
                        if (command === 'createLink') {
                            var url = window.prompt('Enter URL (https://...)');
                            if (!url) return;
                            if (!/^(https?:\/\/|mailto:)/i.test(url)) url = 'https://' + url;
                            document.execCommand('createLink', false, url);
                        } else {
                            document.execCommand(command, false, null);
                        }
                        sync();
                    });
                });
                surface.addEventListener('input', sync);
                surface.addEventListener('blur', sync);
                var form = root.closest('form');
                if (form) form.addEventListener('submit', sync);
                sync();
            });
        });
    </script>
@endif
