<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="portal-shell">
    <div class="portal-page">
        <aside class="portal-sidebar d-flex flex-column">
            <a class="portal-brand" href="/Student-Portal/student/dashboard">
                <span class="portal-brand-mark">
                    <img src="/Student-Portal/assets/images/icon.png" alt="School Logo">
                </span>
                <span class="portal-brand-copy">
                    <strong>Student Portal</strong>
                    <small>School workspace</small>
                </span>
            </a>

            <nav class="portal-nav">
                <a class="nav-link" href="/Student-Portal/student/dashboard?view=get_student_grades" data-content="get_student_grades">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM13 10h7v10h-7V10ZM4 13h7v7H4v-7Z" fill="currentColor"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link" href="/Student-Portal/student/dashboard?view=get_student_info" data-content="get_student_info">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0H5Z" fill="currentColor"/>
                    </svg>
                    <span>Student Information</span>
                </a>
            </nav>

            <div class="portal-sidebar-footer">
                <a href="/Student-Portal/logout" class="btn btn-outline-light"><span>Log out</span></a>
            </div>
        </aside>

        <main class="portal-main">
            <div class="portal-canvas">
                <div class="portal-topbar">
                    <div class="portal-breadcrumb">
                        <button class="portal-toggle" type="button" id="sidebar-toggle" aria-label="Toggle sidebar">&#9776;</button>
                        <span class="dot"></span>
                        <span>Current view</span>
                        <span>&rsaquo;</span>
                        <span class="current" id="student-current-view">Dashboard</span>
                    </div>
                    <div class="portal-tools">
                        <input class="portal-search" type="text" value="Search" readonly aria-label="Search">
                        <span class="portal-avatar"><?= htmlspecialchars(strtoupper(substr($student['student_name'] ?? 'S', 0, 1))) ?></span>
                    </div>
                </div>

                <section class="portal-hero">
                    <div class="eyebrow">Student Workspace</div>
                    <h2>Welcome, <?= htmlspecialchars($student['student_name'] ?? '') ?></h2>
                    <p>Track grades, review student information, and keep up with your academic progress in one cleaner space.</p>
                    <div class="portal-hero-meta">
                        <span class="portal-pill">Course: <?= htmlspecialchars($student['course_name'] ?? 'Not set') ?></span>
                        <span class="portal-pill">Status: Active portal access</span>
                    </div>
                </section>

                <div class="portal-surface">
                    <div id="main-content-area">
                        <div class="d-flex justify-content-center py-5">
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
    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.nav-link[data-content]');
        const currentViewLabel = document.getElementById('student-current-view');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const collapsedStorageKey = 'portal-sidebar-collapsed';
        
        const ajaxActionMap = {
            'get_student_info': 'info',
            'get_student_grades': 'grades'
        };

        const apiBasePath = '/Student-Portal/student/api/';

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
                    
                    history.pushState(null, '', `/Student-Portal/student/dashboard?view=${action}`);
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

        const defaultContentAction = 'get_student_grades';
        const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);
        
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
