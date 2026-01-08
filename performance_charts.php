<div class="row g-4 mt-1">
    <div class="col-md-5">
        <div class="content-card shadow-sm p-4 bg-white rounded-4 h-100">
            <h6 class="fw-bold text-muted small text-uppercase mb-4">Pass vs Fail (Overall)</h6>
            <div style="max-height: 220px;">
                <canvas id="passFailChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="content-card shadow-sm p-4 bg-white rounded-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-muted small text-uppercase m-0">Performance by Dept/Sem</h6>
                <span class="badge bg-light text-dark border">Unit Test 1</span>
            </div>
            <div style="height: 220px;">
                <canvas id="deptPerformanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Logic for Pass/Fail Chart
new Chart(document.getElementById('passFailChart'), {
    type: 'doughnut',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [102, 18], // Dynamic: count passed/failed students
            backgroundColor: ['#05CD99', '#EE5D50'],
            hoverOffset: 4,
            borderWidth: 0
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom' } },
        cutout: '75%'
    }
});

// Logic for Dept Chart
new Chart(document.getElementById('deptPerformanceChart'), {
    type: 'bar',
    data: {
        labels: ['CSIT 1st', 'CSIT 3rd', 'BCA 2nd', 'BCA 4th'],
        datasets: [{
            label: 'Avg Score',
            data: [82, 65, 78, 59],
            backgroundColor: '#4318FF',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
</script>