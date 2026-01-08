<?php
// PHP logic remains same as you provided
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$stmt2 = $conn->prepare("
    SELECT n.*, t.full_name AS teacher_name 
    FROM notices n 
    JOIN teachers t ON n.teacher_id=t.id 
    WHERE n.notice_type='internal' 
      AND (n.department_id=? OR n.department_id='0') 
      AND (n.semester=? OR n.semester='all') 
    ORDER BY n.created_at DESC
    LIMIT 3
");
$stmt2->bind_param("is", $student['department_id'], $student['semester']);
$stmt2->execute();
$internal_notices = $stmt2->get_result();

$events = $conn->query("SELECT * FROM academic_events ORDER BY start_date ASC LIMIT 10");
?>

<div class="dashboard-content-wrapper">
    <div class="notices-column">
        <div class="glass-card">
            <div class="card-header-main">
                <div class="header-info">
                    <h3><i class="fas fa-bullhorn text-primary"></i> Internal Notices</h3>
                    <p class="sub-text">Stay updated with latest department news</p>
                </div>
                <a href="student_announcement.php" class="btn-outline-custom">View All</a>
            </div>

            <div class="notices-list">
                <?php if ($internal_notices->num_rows > 0): ?>
                    <?php while ($row = $internal_notices->fetch_assoc()): ?>
                        <?php
                            $first_image = null;
                            if (!empty($row['notice_images'])) {
                                $images = json_decode($row['notice_images']);
                                if (!empty($images)) $first_image = $images[0];
                            }
                        ?>
                        <a href="notice_detail.php?id=<?php echo $row['id']; ?>" class="notice-item-link">
                            <div class="notice-item">
                                <?php if($first_image): ?>
                                    <div class="notice-thumb">
                                        <img src="<?php echo htmlspecialchars($first_image); ?>" alt="Notice">
                                    </div>
                                <?php else: ?>
                                    <div class="notice-thumb icon-thumb">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="notice-details">
                                    <h4 class="title"><?php echo htmlspecialchars($row['title']); ?></h4>
                                    <p class="preview"><?php echo substr(strip_tags($row['message']), 0, 90); ?>...</p>
                                    <div class="meta-tags">
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date("M d", strtotime($row['created_at'])); ?></span>
                                        <span><i class="far fa-user"></i> <?php echo htmlspecialchars($row['teacher_name']); ?></span>
                                    </div>
                                </div>
                                <div class="arrow-icon">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">No notices available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="calendar-column">
        <div class="glass-card">
            <div class="calendar-widget">
                <div class="cal-nav">
                    <button id="prevMonth" class="nav-btn"><i class="fas fa-chevron-left"></i></button>
                    <h4 id="calendarMonth"></h4>
                    <button id="nextMonth" class="nav-btn"><i class="fas fa-chevron-right"></i></button>
                </div>
                
                <div class="cal-grid" id="calendarDays"></div>
                
                <div class="cal-events-box">
                    <h5><i class="far fa-calendar-check text-success"></i> Upcoming Events</h5>
                    <ul class="event-list-minimal" id="calendarEvents"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-blue: #3b82f6;
    --soft-gray: #f8fafc;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

.dashboard-content-wrapper {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.notices-column { flex: 2; min-width: 350px; }
.calendar-column { flex: 1; min-width: 320px; }

.glass-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: var(--card-shadow);
    height: 100%;
}

/* Notice Header */
.card-header-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-header-main h3 { font-size: 1.3rem; font-weight: 700; color: var(--text-main); margin: 0; }
.sub-text { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

.btn-outline-custom {
    padding: 6px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary-blue);
    transition: 0.3s;
}
.btn-outline-custom:hover { background: #f1f5f9; border-color: var(--primary-blue); }

/* Notice Items */
.notice-item-link { text-decoration: none; color: inherit; }
.notice-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 15px;
    margin-bottom: 12px;
    transition: 0.3s;
    background: #fff;
    border: 1px solid transparent;
}
.notice-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateX(5px);
}

.notice-thumb {
    width: 65px; height: 65px;
    border-radius: 12px;
    overflow: hidden;
    margin-right: 15px;
    flex-shrink: 0;
}
.notice-thumb img { width: 100%; height: 100%; object-fit: cover; }
.icon-thumb { background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

.notice-details { flex-grow: 1; }
.notice-details .title { font-size: 1rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px; }
.notice-details .preview { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px; }

.meta-tags { display: flex; gap: 12px; font-size: 0.75rem; color: #94a3b8; }
.arrow-icon { color: #cbd5e1; transition: 0.3s; }
.notice-item:hover .arrow-icon { color: var(--primary-blue); transform: translateX(3px); }

/* Calendar Styling */
.calendar-widget { width: 100%; }
.cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.cal-nav h4 { font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0; }
.nav-btn { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; transition: 0.3s; }
.nav-btn:hover { background: #e2e8f0; }

.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; }
.day-header { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); padding-bottom: 8px; }
.calendar-cell { padding: 8px 0; font-size: 0.85rem; border-radius: 8px; color: var(--text-main); transition: 0.2s; cursor: default; }
.calendar-cell.today { background: var(--primary-blue); color: white; font-weight: bold; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); }
.calendar-cell:not(.empty):hover { background: #f1f5f9; }

.cal-events-box { margin-top: 25px; padding-top: 20px; border-top: 1px dashed #e2e8f0; }
.cal-events-box h5 { font-size: 0.95rem; font-weight: 700; margin-bottom: 15px; }
.event-list-minimal { list-style: none; padding: 0; margin: 0; }
.event-list-minimal li { font-size: 0.85rem; padding: 8px 0; border-bottom: 1px solid #f1f5f9; color: var(--text-main); }
.event-list-minimal li strong { color: var(--primary-blue); margin-right: 5px; }

@media (max-width: 768px) {
    .dashboard-content-wrapper { flex-direction: column; }
}
</style>

<script>
// (Previous JavaScript Logic is kept but updated for IDs)
let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

let events = <?php
    $event_arr = [];
    if($events && $events->num_rows>0){
        while($e = $events->fetch_assoc()){
            $event_arr[] = ['date'=>$e['start_date'], 'title'=>$e['title']];
        }
    }
    echo json_encode($event_arr);
?>;

const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

function renderCalendar(month, year){
    const calendarDays = document.getElementById('calendarDays');
    const calendarMonth = document.getElementById('calendarMonth');
    const calendarEvents = document.getElementById('calendarEvents');

    calendarMonth.textContent = monthNames[month] + " " + year;

    let days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    let dayHeaderHTML = '';
    days.forEach(d => dayHeaderHTML += `<div class='day-header'>${d}</div>`);
    calendarDays.innerHTML = dayHeaderHTML;

    let firstDay = new Date(year, month, 1).getDay();
    let totalDays = new Date(year, month + 1, 0).getDate();

    for(let i=0;i<firstDay;i++) calendarDays.innerHTML += "<div class='calendar-cell empty'></div>";

    for(let d=1; d<=totalDays; d++){
        let fullDate = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        let isToday = (fullDate === today.toISOString().split('T')[0]) ? "today" : "";
        calendarDays.innerHTML += `<div class='calendar-cell ${isToday}'>${d}</div>`;
    }

    calendarEvents.innerHTML = '';
    let monthEvents = events.filter(e => {
        let d = new Date(e.date);
        return d.getMonth()===month && d.getFullYear()===year;
    });

    if(monthEvents.length>0){
        monthEvents.forEach(e=>{
            let d = new Date(e.date);
            let start = d.toLocaleString('default',{month:'short', day:'numeric'});
            calendarEvents.innerHTML += `<li><strong>${start}</strong> ${e.title}</li>`;
        });
    } else {
        calendarEvents.innerHTML = "<li class='text-muted'>No events this month</li>";
    }
}

document.getElementById('prevMonth').addEventListener('click',()=>{
    currentMonth--; if(currentMonth<0){ currentMonth=11; currentYear--; }
    renderCalendar(currentMonth, currentYear);
});
document.getElementById('nextMonth').addEventListener('click',()=>{
    currentMonth++; if(currentMonth>11){ currentMonth=0; currentYear++; }
    renderCalendar(currentMonth, currentYear);
});

renderCalendar(currentMonth, currentYear);
</script>