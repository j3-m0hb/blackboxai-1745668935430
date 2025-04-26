document.addEventListener('DOMContentLoaded', function() {
    // Initialize Courier Statistics Chart
    initializeCourierChart();
    
    // Initialize Attendance Statistics Chart
    initializeAttendanceChart();
    
    // Initialize Contract Status Chart
    initializeContractChart();
    
    // Update real-time statistics
    updateRealTimeStats();
});

// Courier Statistics Chart
function initializeCourierChart() {
    const ctx = document.getElementById('courierChart').getContext('2d');
    
    // Fetch courier data
    fetch('api/statistics/couriers.php')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Kurir',
                        data: data.values,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(52, 73, 94, 0.8)',
                            'rgba(241, 196, 15, 0.8)',
                            'rgba(230, 126, 34, 0.8)'
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(155, 89, 182, 1)',
                            'rgba(52, 73, 94, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(230, 126, 34, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Distribusi Kurir per Wilayah'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading courier statistics:', error));
}

// Attendance Statistics Chart
function initializeAttendanceChart() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    // Fetch attendance data
    fetch('api/statistics/attendance.php')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Hadir',
                            data: data.present,
                            borderColor: 'rgba(46, 204, 113, 1)',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Izin',
                            data: data.permission,
                            borderColor: 'rgba(241, 196, 15, 1)',
                            backgroundColor: 'rgba(241, 196, 15, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Sakit',
                            data: data.sick,
                            borderColor: 'rgba(231, 76, 60, 1)',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Statistik Kehadiran Bulanan'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading attendance statistics:', error));
}

// Contract Status Chart
function initializeContractChart() {
    const ctx = document.getElementById('contractChart').getContext('2d');
    
    // Fetch contract data
    fetch('api/statistics/contracts.php')
        .then(response => response.json())
        .then(data => {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aktif', 'Hampir Berakhir', 'Berakhir'],
                    datasets: [{
                        data: [
                            data.active,
                            data.expiring,
                            data.expired
                        ],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(241, 196, 15, 0.8)',
                            'rgba(231, 76, 60, 0.8)'
                        ],
                        borderColor: [
                            'rgba(46, 204, 113, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(231, 76, 60, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Status Kontrak Karyawan'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading contract statistics:', error));
}

// Update Real-time Statistics
function updateRealTimeStats() {
    // Function to update statistics
    function updateStats() {
        fetch('api/statistics/realtime.php')
            .then(response => response.json())
            .then(data => {
                // Update employee counts
                document.getElementById('totalEmployees').textContent = data.total;
                document.getElementById('contractEmployees').textContent = data.contract;
                document.getElementById('permanentEmployees').textContent = data.permanent;
                document.getElementById('freelanceEmployees').textContent = data.freelance;
                
                // Update active users
                document.getElementById('activeUsers').textContent = data.activeUsers;
                
                // Update today's attendance
                document.getElementById('todayPresent').textContent = data.todayAttendance.present;
                document.getElementById('todayAbsent').textContent = data.todayAttendance.absent;
                document.getElementById('todayLate').textContent = data.todayAttendance.late;
            })
            .catch(error => console.error('Error updating real-time statistics:', error));
    }
    
    // Update immediately
    updateStats();
    
    // Update every 5 minutes
    setInterval(updateStats, 300000);
}

// Birthday Notifications
function checkBirthdays() {
    fetch('api/notifications/birthdays.php')
        .then(response => response.json())
        .then(data => {
            if (data.birthdays.length > 0) {
                const notifications = data.birthdays.map(employee => {
                    return `
                        <div class="birthday-notification">
                            <strong>${employee.nama_lengkap}</strong> berulang tahun hari ini!
                        </div>
                    `;
                }).join('');
                
                document.getElementById('birthdayNotifications').innerHTML = notifications;
            }
        })
        .catch(error => console.error('Error checking birthdays:', error));
}

// Contract Expiry Notifications
function checkContractExpiry() {
    fetch('api/notifications/contracts.php')
        .then(response => response.json())
        .then(data => {
            if (data.expiring.length > 0) {
                const notifications = data.expiring.map(employee => {
                    return `
                        <div class="contract-notification ${employee.days <= 7 ? 'urgent' : ''}">
                            Kontrak <strong>${employee.nama_lengkap}</strong> akan berakhir dalam ${employee.days} hari
                        </div>
                    `;
                }).join('');
                
                document.getElementById('contractNotifications').innerHTML = notifications;
            }
        })
        .catch(error => console.error('Error checking contract expiry:', error));
}

// Initialize notifications
document.addEventListener('DOMContentLoaded', function() {
    checkBirthdays();
    checkContractExpiry();
    
    // Check notifications every hour
    setInterval(() => {
        checkBirthdays();
        checkContractExpiry();
    }, 3600000);
});
