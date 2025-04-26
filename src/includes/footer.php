<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}
?>
        </div> <!-- End of #content -->
    </div> <!-- End of .wrapper -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Version <?php echo APP_VERSION; ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Common Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="assets/js/main.js"></script>
    
    <!-- Page Specific Scripts -->
    <?php if (isset($extraJS)) echo $extraJS; ?>

    <script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });

    // Add loading state to buttons
    document.querySelectorAll('.btn-loading').forEach(function(button) {
        button.addEventListener('click', function() {
            var loadingText = this.getAttribute('data-loading-text') || 'Loading...';
            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`;
        });
    });

    // Prevent form double submission
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var submitButtons = this.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(function(button) {
                button.disabled = true;
            });
        });
    });

    // Handle session timeout
    var sessionTimeout = <?php echo SESSION_LIFETIME; ?> * 1000; // Convert to milliseconds
    var warningTimeout = sessionTimeout - (5 * 60 * 1000); // Show warning 5 minutes before timeout

    var timeoutTimer = setTimeout(function() {
        // Show warning modal
        var modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
        modal.show();
    }, warningTimeout);

    // Reset timer on user activity
    function resetTimer() {
        clearTimeout(timeoutTimer);
        timeoutTimer = setTimeout(function() {
            var modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
            modal.show();
        }, warningTimeout);
    }

    // Monitor user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(function(event) {
        document.addEventListener(event, resetTimer, false);
    });
    </script>

    <!-- Session Timeout Modal -->
    <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sesi Akan Berakhir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Sesi Anda akan berakhir dalam 5 menit. Apakah Anda ingin tetap login?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Logout</button>
                    <button type="button" class="btn btn-primary" onclick="window.location.reload()">Tetap Login</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
