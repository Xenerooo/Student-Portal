<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?= APP_URL ?>/assets/images/icon.png" alt="School Logo" height="32" style="border-radius: 4px;">
            <div class="sidebar-brand-text">
                Colegio de Porta Vaga <span>Admin Panel</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="sidebar-link active" data-content="get_overview">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span class="link-text">Overview</span>
            </a>
            <a href="#" class="sidebar-link" data-content="get_student_list">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span class="link-text">Student List</span>
            </a>
            <a href="#" class="sidebar-link" data-content="get_manage_subjects">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <span class="link-text">Manage Subjects</span>
            </a>
            <a href="#" class="sidebar-link" data-content="get_manage_curriculum">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span class="link-text">Manage Curriculum</span>
            </a>
            <a href="#" class="sidebar-link" data-content="get_create_student_form">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span class="link-text">Create Student</span>
            </a>
            <a href="#" class="sidebar-link" data-content="get_calendar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span class="link-text">School Calendar</span>
            </a>
            
            <div style="flex-grow: 1;"></div>
            
            <a href="#" class="sidebar-link" data-content="admin_manage_account">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span class="link-text">Manage Account</span>
            </a>
            <a href="<?= APP_URL ?>/logout" class="sidebar-link text-danger" style="color: #fca5a5 !important;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span class="link-text">Logout (<?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>)</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="app-main-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="breadcrumb-title">
                    <span class="d-none d-sm-inline">Dashboard</span>
                    <span class="d-none d-sm-inline separator">></span>
                    <span id="header-title" style="color: #64748b; font-weight: normal; font-size: 0.95em;">Student List</span>
                </div>
            </div>
            <!-- <div class="header-right">
                <div class="search-bar d-none d-md-block">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="Search...">
                </div>
                <button class="btn-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span class="badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar text-white bg-primary">
                        A
                    </div>
                </div>
            </div> -->
        </header>

        <!-- Scrollable Content View -->
        <div class="app-content-scrollable">
            <div id="main-content-area">
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const defaultContentAction = 'get_overview';
    const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);

    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.sidebar-link[data-content]');
        const ajaxActionMap = {
            'get_overview': 'overview',
            'get_student_list': 'students',
            'get_manage_subjects': 'subjects',
            'get_manage_curriculum': 'curriculum',
            'get_create_student_form': 'students/create',
            'get_calendar': 'calendar',
            'admin_manage_account': 'account'
        };

        const apiBasePath = '<?= APP_URL ?>/admin/api/';

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

        // Sidebar Toggle Logic
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        // Create backdrop dynamically
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
        
        const sidebarNav = document.querySelector('.sidebar-nav');
        const indicator = document.createElement('div');
        indicator.className = 'sidebar-active-indicator';
        sidebarNav.appendChild(indicator);

        function updateIndicator(targetLink) {
            if (targetLink && indicator) {
                indicator.style.transform = `translateY(${targetLink.offsetTop}px)`;
                indicator.style.height = `${targetLink.offsetHeight}px`;
            }
        }
        
        window.addEventListener('resize', () => {
            const active = document.querySelector('.sidebar-link.active');
            if (active) updateIndicator(active);
        });

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 991.98) {
                    const isShown = sidebar.classList.toggle('mobile-shown');
                    sidebar.classList.remove('mobile-hidden');
                    if (isShown) {
                        backdrop.classList.add('show');
                    } else {
                        backdrop.classList.remove('show');
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                }
                setTimeout(() => {
                    const active = document.querySelector('.sidebar-link.active');
                    if (active) updateIndicator(active);
                }, 310);
            });
        }

        // Close sidebar when clicking backdrop
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('mobile-shown');
            sidebar.classList.add('mobile-hidden');
            backdrop.classList.remove('show');
        });

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

                    // If the response is json, something went wrong, let's dump it.
                    try {
                        const jsonObj = JSON.parse(html);
                        if (jsonObj.error) {
                             contentArea.innerHTML = `<div class='alert alert-danger'>${jsonObj.error}</div>`;
                             return;
                        }
                    } catch(e) { }

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
                        updateIndicator(targetLink);
                        // Update Header Text
                        const headerTitle = document.getElementById('header-title');
                        if (headerTitle) {
                            // Extract text ignoring svg 
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = targetLink.innerHTML;
                            const svg = tempDiv.querySelector('svg');
                            if (svg) svg.remove();
                            headerTitle.textContent = tempDiv.textContent.trim();
                        }
                        
                        // Auto-hide sidebar on mobile after clicking a link
                        if (window.innerWidth <= 991.98 && sidebar.classList.contains('mobile-shown')) {
                            sidebar.classList.remove('mobile-shown');
                            sidebar.classList.add('mobile-hidden');
                            backdrop.classList.remove('show');
                        }
                    }
                    
                    history.pushState(null, '', `<?= APP_URL ?>/admin/dashboard?view=${action}`);
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); 
                const actionName = this.getAttribute('data-content'); 
                loadContent(actionName, this);
            });
        });

        if(defaultLink) {
             defaultLink.classList.add('active');
        }
        loadContent(defaultContentAction, defaultLink);
    });
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

