            </main>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                sidebar.classList.remove('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // User Dropdown
        const userBtn = document.getElementById('userBtn');
        const userMenu = document.getElementById('userMenu');
        
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                userMenu.classList.remove('show');
            });
        }
        
        // Notification Dropdown
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        
        if (notificationBtn && notificationMenu) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationMenu.classList.toggle('show');
                loadNotifications();
            });
            
            document.addEventListener('click', () => {
                notificationMenu.classList.remove('show');
            });
        }
        
        // Load notifications
        function loadNotifications() {
            const notificationList = document.getElementById('notificationList');
            if (!notificationList) return;
            
            fetch('pages/tasks/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        notificationList.innerHTML = '<div class="empty-notif">Tidak ada notifikasi</div>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(notif => {
                        html += `
                            <div class="notification-item ${notif.is_read ? '' : 'unread'}">
                                <div class="notif-icon ${notif.type}">
                                    <i class="fas ${notif.icon}"></i>
                                </div>
                                <div class="notif-content">
                                    <p>${notif.message}</p>
                                    <small>${notif.time_ago}</small>
                                </div>
                            </div>
                        `;
                    });
                    notificationList.innerHTML = html;
                    
                    // Update badge
                    const unreadCount = data.filter(n => !n.is_read).length;
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        badge.textContent = unreadCount;
                        badge.style.display = unreadCount > 0 ? 'flex' : 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }
        
        // Global Search
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            let searchTimeout;
            globalSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const keyword = this.value;
                    if (keyword.length >= 2) {
                        window.location.href = `search.php?q=${encodeURIComponent(keyword)}`;
                    }
                }, 500);
            });
        }
        
        // Load today's progress
        function loadTodayProgress() {
            fetch('pages/tasks/get_today_progress.php')
                .then(response => response.json())
                .then(data => {
                    const progressPercent = document.getElementById('todayProgressPercent');
                    const progressFill = document.getElementById('todayProgressFill');
                    if (progressPercent && progressFill) {
                        progressPercent.textContent = `${data.completed_percent}%`;
                        progressFill.style.width = `${data.completed_percent}%`;
                    }
                })
                .catch(error => console.error('Error loading progress:', error));
        }
        
        // Auto-load progress on pages with sidebar
        if (document.getElementById('todayProgressPercent')) {
            loadTodayProgress();
        }
    </script>
    
    <?php if(isset($page_script)): ?>
        <script><?= $page_script ?></script>
    <?php endif; ?>
</body>
</html>