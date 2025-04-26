<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header">
        <h3><?php echo APP_NAME; ?></h3>
    </div>

    <div class="sidebar-menu">
        <ul>
            <!-- Dashboard - All Users -->
            <li>
                <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Kepegawaian Section -->
            <?php if (isAdmin() || isHRD()): ?>
            <li>
                <a href="data_personal.php" class="<?php echo $currentPage === 'data_personal.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-vcard"></i>
                    Data Personal
                </a>
            </li>
            <li>
                <a href="data_pegawai.php" class="<?php echo $currentPage === 'data_pegawai.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    Data Pegawai
                </a>
            </li>
            <?php endif; ?>

            <!-- Absensi Section -->
            <li>
                <a href="absensi.php" class="<?php echo $currentPage === 'absensi.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check"></i>
                    Absensi
                </a>
            </li>
            <?php if (isAdmin() || isHRD()): ?>
            <li>
                <a href="rekap_absensi.php" class="<?php echo $currentPage === 'rekap_absensi.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-range"></i>
                    Rekap Absensi
                </a>
            </li>
            <?php endif; ?>

            <!-- Penggajian Section -->
            <?php if (isAdmin() || isHRD()): ?>
            <li>
                <a href="komponen_gaji.php" class="<?php echo $currentPage === 'komponen_gaji.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cash-stack"></i>
                    Komponen Gaji
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="slip_gaji.php" class="<?php echo $currentPage === 'slip_gaji.php' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    Slip Gaji
                </a>
            </li>

            <!-- Dokumen Section -->
            <li>
                <a href="dokumen.php" class="<?php echo $currentPage === 'dokumen.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    Dokumen
                </a>
            </li>

            <!-- Admin Section -->
            <?php if (isAdmin()): ?>
            <li>
                <a href="user_management.php" class="<?php echo $currentPage === 'user_management.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i>
                    User Management
                </a>
            </li>
            <li>
                <a href="backup.php" class="<?php echo $currentPage === 'backup.php' ? 'active' : ''; ?>">
                    <i class="bi bi-database"></i>
                    Backup & Restore
                </a>
            </li>
            <li>
                <a href="activity_log.php" class="<?php echo $currentPage === 'activity_log.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i>
                    Log Activity
                </a>
            </li>
            <?php endif; ?>

            <!-- Profile & Logout -->
            <li>
                <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle"></i>
                    Profile
                </a>
            </li>
            <li>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Nav Toggler Button -->
<button class="nav-toggler" id="sidebarCollapse">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    sidebarCollapse.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });

    // Mobile responsive behavior
    function checkWidth() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
        } else {
            sidebar.classList.remove('collapsed');
            content.classList.remove('expanded');
        }
    }

    // Check on load
    checkWidth();

    // Check on resize
    window.addEventListener('resize', checkWidth);
});
</script>
