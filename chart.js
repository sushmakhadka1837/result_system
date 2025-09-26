var ctx = document.getElementById("resultChart").getContext("2d");
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ["Math", "Physics", "CS"],
    datasets: [{
      label: "Marks",
      data: [80, 75, 90],
      backgroundColor: 'rgba(54, 162, 235, 0.6)'
    }]
  }
});
