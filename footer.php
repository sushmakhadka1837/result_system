<!-- Footer -->
<footer class="pec-footer">
  <div class="container d-flex flex-wrap justify-content-between align-items-start py-4">

    <!-- Logo & Address -->
    <div class="footer-logo d-flex align-items-center mb-3">
      <img src="images/logoheader.png" alt="College Logo" class="footer-logo-img">
      <div class="ms-3">
        <h5 class="mb-1">PEC Result Hub</h5>
        <p class="mb-0">Phirke, Pokhara-8, Nepal</p>
        <small>&copy; 2025 All rights reserved.</small>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="footer-links mb-3">
      <h5>Quick Links</h5>
      <a href="index.php">Home</a>
      <a href="#">Our Programs</a>
      <a href="about.php">About Us</a>
      <a href="notice.php">Notice Board</a>
    </div>

    <!-- Useful Links -->
    <div class="footer-useful mb-3">
      <h5>Useful Links</h5>
      <a href="https://pu.edu.np/" target="_blank">Pokhara University</a>
      <a href="https://ctevt.org.np/" target="_blank">CTEVT</a>
      <a href="https://nec.gov.np/" target="_blank">Nepal Engineering Council</a>
      <a href="https://neanepal.org.np/" target="_blank">Nepal Engineer's Association</a>
      <a href="https://pu.edu.np/research/purc-seminar-series/" target="_blank">PU Research</a>
    </div>

    <!-- Google Map -->
    <div class="footer-map mb-3">
      <h5>Our Location</h5>
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3515.8767747101356!2d83.97457957494127!3d28.21105620309656!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x399595ab009e696f%3A0x8657229f67dc8afb!2sPokhara%20Engineering%20College!5e0!3m2!1sen!2snp!4v1766291399571!5m2!1sen!2snp"
        width="260"
        height="180"
        style="border:0;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>

  </div>
</footer>

<!-- Footer Styling -->
<style>
  .footer-logo-img {
    width: 90px;
    height: 90px;
    object-fit: cover;      /* prevents stretching */
    border-radius: 50%;     /* makes it circular */
    border: 3px solid #ffdd57; /* golden border */
    background-color: #ffffff;
}

.pec-footer {
    background-color: #001f4d;
    color: #ffffff;
    padding: 40px 20px 20px;
}

.pec-footer h5 {
    color: #ffdd57;
    margin-bottom: 10px;
    font-weight: 600;
}

.pec-footer a {
    display: block;
    color: #ffffff;
    text-decoration: none;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.pec-footer a:hover {
    text-decoration: underline;
}

.footer-map iframe {
    border-radius: 8px;
}

/* Responsive fix */
@media (max-width: 768px) {
    .footer-map iframe {
        width: 100%;
    }
}
</style>