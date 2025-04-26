<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}
?>
<!-- Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
        <button class="nav-toggler d-lg-none" type="button" id="sidebarCollapse">
            <i class="bi bi-list"></i>
        </button>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="d-none d-lg-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <?php if (isset($breadcrumbs)): ?>
                    <?php foreach ($breadcrumbs as $label => $url): ?>
                        <?php if ($url): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $label; ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="ms-auto d-flex align-items-center">
            <!-- Notifications Dropdown -->
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" type="button" id="notificationsDropdown" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                          style="display: none;">
                        0
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationsDropdown" 
                     style="width: 300px; max-height: 400px; overflow-y: auto;">
                    <h6 class="dropdown-header">Notifikasi</h6>
                    <div id="notificationsList">
                        <!-- Notifications will be inserted here via JavaScript -->
                        <div class="text-center p-3 text-muted">
                            <small>Memuat notifikasi...</small>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center" href="notifications.php">
                        <small>Lihat Semua Notifikasi</small>
                    </a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle d-flex align-items-center" type="button" 
                        id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-none d-sm-block me-2 text-end">
                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="small text-muted">
                            <?php 
                            $user = getUserById($_SESSION['user_id']);
                            echo htmlspecialchars($user['jabatan'] ?? '');
                            ?>
                        </div>
                    </div>
                    <div class="avatar">
                        <!-- Default avatar if no photo -->
                        <i class="bi bi-person-circle fs-4"></i>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                    <li>
                        <div class="dropdown-item-text">
                            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <div class="small text-muted">
                                <?php echo htmlspecialchars($user['wilayah'] ?? ''); ?>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person me-2"></i> Profile
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <i class="bi bi-gear me-2"></i> Pengaturan
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php" 
                           onclick="return confirm('Apakah Anda yakin ingin logout?');">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Notifications Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateNotifications() {
        // Fetch birthdays
        fetch('api/notifications/birthdays.php')
            .then(response => response.json())
            .then(data => {
                let notifications = '';
                
                // Today's birthdays
                if (data.today && data.today.length > 0) {
                    data.today.forEach(employee => {
                        notifications += `
                            <a class="dropdown-item py-2" href="profile.php?id=${employee.id}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-gift text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold">${employee.nama_lengkap}</div>
                                        <div class="small text-muted">
                                            Berulang tahun hari ini (${employee.age} tahun)
                                        </div>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                }
                
                // Contract notifications
                fetch('api/notifications/contracts.php')
                    .then(response => response.json())
                    .then(contractData => {
                        // Urgent contracts
                        if (contractData.urgent && contractData.urgent.length > 0) {
                            contractData.urgent.forEach(contract => {
                                notifications += `
                                    <a class="dropdown-item py-2" href="contracts.php?id=${contract.id}">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="bi bi-exclamation-triangle text-danger"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <div class="fw-bold">${contract.nama_lengkap}</div>
                                                <div class="small text-muted">
                                                    Kontrak berakhir dalam ${contract.days_remaining} hari
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            });
                        }
                        
                        // Update notification badge
                        const totalNotifications = 
                            (data.today ? data.today.length : 0) + 
                            (contractData.urgent ? contractData.urgent.length : 0);
                            
                        const badge = document.querySelector('.notification-badge');
                        if (totalNotifications > 0) {
                            badge.style.display = 'block';
                            badge.textContent = totalNotifications;
                        } else {
                            badge.style.display = 'none';
                        }
                        
                        // Update notifications list
                        const notificationsList = document.getElementById('notificationsList');
                        if (notifications) {
                            notificationsList.innerHTML = notifications;
                        } else {
                            notificationsList.innerHTML = `
                                <div class="text-center p-3 text-muted">
                                    <small>Tidak ada notifikasi baru</small>
                                </div>
                            `;
                        }
                    })
                    .catch(error => console.error('Error loading contract notifications:', error));
            })
            .catch(error => console.error('Error loading birthday notifications:', error));
    }
    
    // Update notifications immediately and every 5 minutes
    updateNotifications();
    setInterval(updateNotifications, 300000);
});
</script>
