<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="portal-shell">
    <div class="portal-page">
        <aside class="portal-sidebar d-flex flex-column">
            <a class="portal-brand" href="/Student-Portal/admin/dashboard">
                <span class="portal-brand-mark">
                    <img src="/Student-Portal/assets/images/icon.png" alt="School Logo">
                </span>
                <span class="portal-brand-copy">
                    <strong>Student Portal</strong>
                    <small>Administration</small>
                </span>
            </a>

            <nav class="portal-nav">
                <a class="nav-link" href="#" data-content="get_student_list">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM13 10h7v10h-7V10ZM4 13h7v7H4v-7Z" fill="currentColor"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link" href="#" data-content="get_manage_subjects">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 4.5A2.5 2.5 0 0 0 3.5 7v10A2.5 2.5 0 0 0 6 19.5h12a2.5 2.5 0 0 0 2.5-2.5V7A2.5 2.5 0 0 0 18 4.5H6Zm1.5 3h9v1.5h-9V7.5Zm0 4h9V13h-9v-1.5Zm0 4H13V17H7.5v-1.5Z" fill="currentColor"/>
                    </svg>
                    <span>Subjects</span>
                </a>
                <a class="nav-link" href="#" data-content="get_manage_curriculum">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 4h11a3 3 0 0 1 3 3v11H9a3 3 0 0 0-3 3V4Zm0 0a3 3 0 0 0-3 3v13h2a3 3 0 0 1 3-3h10V7a1 1 0 0 0-1-1H6Z" fill="currentColor"/>
                    </svg>
                    <span>Curriculum</span>
                </a>
                <a class="nav-link" href="#" data-content="get_create_student_form">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-6 13a6 6 0 0 1 12 0H6Zm13-8V7h-2v3h-3v2h3v3h2v-3h3v-2h-3Z" fill="currentColor"/>
                    </svg>
                    <span>Create Student</span>
                </a>
                <a class="nav-link" href="" data-content="admin_manage_account">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 8a8 8 0 1 0-16 0h16Z" fill="currentColor"/>
                    </svg>
                    <span>Account</span>
                </a>
            </nav>

            <div class="portal-sidebar-footer">
                <a class="btn btn-outline-light" href="/Student-Portal/logout"><span>Log out</span></a>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-canvas">
                <div class="portal-topbar">
                    <div class="portal-breadcrumb">
                        <button class="portal-toggle" type="button" id="sidebar-toggle" aria-label="Toggle sidebar">&#9776;</button>
                        <span class="dot"></span>
                        <span>Current module</span>
                        <span>&rsaquo;</span>
                        <span class="current" id="admin-current-view">Dashboard</span>
                    </div>
                    <div class="portal-tools">
                        <input class="portal-search" type="text" value="Search" readonly aria-label="Search">
                        <span class="portal-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['role'] ?? 'A', 0, 1))) ?></span>
                    </div>
                </div>

                <section class="portal-hero">
                    <div class="eyebrow">Admin Workspace</div>
                    <h2>Manage records with less clutter</h2>
                    <p>Search students faster, update curriculum cleanly, and handle enrollment work from a calmer, more focused dashboard.</p>
                    <div class="portal-hero-meta">
                        <span class="portal-pill">Role: <?= htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></span>
                        <span class="portal-pill">Modules: Students, Subjects, Curriculum</span>
                    </div>
                </section>

                <div class="portal-surface">
                    <div id="main-content-area">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const defaultContentAction = 'get_student_list';
    const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);

    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.nav-link[data-content]');
        const currentViewLabel = document.getElementById('admin-current-view');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const collapsedStorageKey = 'portal-sidebar-collapsed';
        const ajaxActionMap = {
            'get_student_list': 'students',
            'get_manage_subjects': 'subjects',
            'get_manage_curriculum': 'curriculum',
            'get_create_student_form': 'students/create',
            'admin_manage_account': 'account'
        };

        const apiBasePath = '/Student-Portal/admin/api/';

        function applySidebarState(isCollapsed) {
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
        }

        applySidebarState(localStorage.getItem(collapsedStorageKey) === 'true');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                const nextState = !document.body.classList.contains('sidebar-collapsed');
                applySidebarState(nextState);
                localStorage.setItem(collapsedStorageKey, String(nextState));
            });
        }

        // Global Fetch Interceptor to include CSRF token in all POST/PUT/DELETE requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken && options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
                options.headers = options.headers || {};
                if (options.headers instanceof Headers) {
                    options.headers.set('X-CSRF-TOKEN', csrfToken);
                } else {
                    options.headers['X-CSRF-TOKEN'] = csrfToken;
                }
            }
            return originalFetch(url, options);
        };

        async function loadContent(action, targetLink) {
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Map the old action names to the new RESTful endpoints
            const endpoint = ajaxActionMap[action] || action;
            const url = `${apiBasePath}${endpoint}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.body;
                    const scripts = doc.querySelectorAll('script');

                    contentArea.innerHTML = newContent.innerHTML;

                    const existingExternalScripts = new Set(
                        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
                    );
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            if (existingExternalScripts.has(script.src)) {
                                return;
                            }
                            newScript.src = script.src;
                            existingExternalScripts.add(script.src);
                        } else {
                            newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                        }
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                    
                    navLinks.forEach(link => link.classList.remove('active'));
                    if (targetLink) {
                        targetLink.classList.add('active');
                        if (currentViewLabel) {
                            currentViewLabel.textContent = targetLink.textContent.trim();
                        }
                    }
                    
                    history.pushState(null, '', `/Student-Portal/admin/dashboard?view=${action}`);
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        window.loadContent = loadContent;

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); 
                const actionName = this.getAttribute('data-content'); 
                loadContent(actionName, this);
            });
        });

        if(defaultLink) {
             defaultLink.classList.add('active');
             if (currentViewLabel) {
                 currentViewLabel.textContent = defaultLink.textContent.trim();
             }
        }
        loadContent(defaultContentAction, defaultLink);
    });
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
