<div class="col-lg-5 col-md-12">
  <div class="calendar-box">
    <div class="calendar-header">
      <span class="calendar-icon">📅</span>
      <h4>Academic Calendar (Nepali)</h4>
    </div>

    <input type="text" id="npcal" class="form-control" readonly>
    <div id="event-details" class="mt-2"></div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nepali-date-picker@2.2.0/dist/nepaliDatePicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/nepali-date-picker@2.2.0/dist/nepaliDatePicker.min.js"></script>

<script>
// Step 1: Fetch events from backend
let events = [];
fetch("fetch_events.php")
  .then(res => res.json())
  .then(data => {
    events = data;
    console.log("Events loaded:", events); // Test: check console
  })
  .catch(err => console.error("Fetch error:", err));

// Step 2: Initialize Nepali Calendar
$(document).ready(function(){
    $('#npcal').nepaliDatePicker({
        npdMonth: true,
        npdYear: true,
        onChange: function(){
            const bsDate = $('#npcal').val(); // Selected BS date
            let html = `<h6>Events on ${bsDate}</h6>`;

            let found = false;

            events.forEach(e => {
                // Convert AD → BS
                const ad = new Date(e.start);
                const bs = AD2BS(ad); // helper function below

                if(bs === bsDate){
                    html += `<div>📌 ${e.title} (${e.type})</div>`;
                    found = true;
                }
            });

            if(!found) html += `<div>No events</div>`;
            document.getElementById("event-details").innerHTML = html;
        }
    });
});

// Step 3: Simple AD → BS converter (Approximate for demo)
function AD2BS(ad){
    const bsYear = ad.getFullYear() + 57;
    const bsMonth = ("0"+(ad.getMonth()+1)).slice(-2);
    const bsDay = ("0"+ad.getDate()).slice(-2);
    return `${bsYear}-${bsMonth}-${bsDay}`; // Must match NepaliDatePicker format
}
</script>
