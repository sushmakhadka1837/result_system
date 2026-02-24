<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hamro Result | Pokhara Engineering College</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --navy-dark: #001f4d;
      --navy-medium: #003380;
      --accent-gold: #f4c430;
      --soft-bg: #f8fafc;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --white: #ffffff;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--soft-bg);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    /* --- Content Section Styling --- */
    .section-title {
      font-weight: 700;
      color: #8B4513;
      position: relative;
      display: inline-block;
      margin-bottom: 1rem;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: var(--accent-gold);
      border-radius: 2px;
    }

    .subtitle {
      color: var(--text-muted);
      max-width: 650px;
      margin: 0 auto;
    }

    /* --- Info Boxes (Features) --- */
    .info-box {
      background: var(--white);
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0, 31, 77, 0.05);
      border: 1px solid rgba(0, 31, 77, 0.05);
      transition: var(--transition);
      height: 100%;
      text-align: center;
    }

    .info-box:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0, 31, 77, 0.12);
      border-color: var(--accent-gold);
    }

    .icon-wrapper {
      width: 70px;
      height: 70px;
      background: var(--soft-bg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 1.5rem;
      transition: var(--transition);
    }

    .info-box:hover .icon-wrapper {
      background: var(--navy-dark);
      color: var(--white) !important;
    }

    /* --- AI Card Specific --- */
    .ai-card {
      background: linear-gradient(135deg, #ffffff 0%, #f3f0ff 100%);
      border: 1px solid #dcd0ff;
    }
    .ai-icon {
      background: linear-gradient(135deg, #6f42c1, #001f4d);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* --- Hero Section --- */
    .hero {
      position: relative;
      height: 65vh;
      margin: 2rem;
      border-radius: 30px;
      background: url('aaa.png') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      color: #8B4513;
      text-align: center;
      width: 100%;
      padding: 0 20px;
    }

    .departments-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
      margin-top: 40px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .dept-link { text-decoration: none; }

    .department-box {
      background: transparent;
      backdrop-filter: none;
      border: none;
      padding: 20px;
      border-radius: 15px;
      color: white; /* Rich brown color */
      font-weight: 700;
      font-size: 1.15rem;
      transition: var(--transition);
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .department-box:hover {
      background: transparent;
      color: white; /* Darker brown on hover */
      transform: scale(1.05);
      border-color: transparent;
      text-decoration: underline;
      text-decoration-color: #d4af37;
      text-decoration-thickness: 2px;
      text-underline-offset: 6px;
    }

    /* --- Announcements, Calendar, Feedback --- */
    .college-main-section { 
      padding: 4rem 0 5rem 0;

      margin-top: 0;
      border-radius: 20px;
      margin-left: 1rem;
      margin-right: 1rem;
    }

    .custom-card {
      background: linear-gradient(135deg, var(--white) 0%, #f9f7f4 100%);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      height: 100%;
      border: 1px solid rgba(139, 69, 19, 0.1);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      max-width: 100%;
    }

    .custom-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(180deg, #8B4513 0%, #d4af37 100%);
    }

    .custom-card:hover { 
      transform: translateY(-8px);
      box-shadow: 0 20px 50px rgba(139, 69, 19, 0.15);
      border-color: #d4af37;
    }

    .card-title {
      color: var(--navy-dark);
      font-weight: 700;
      font-size: 1.3rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      padding-bottom: 1rem;
    }

    .card-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #8B4513 0%, #d4af37 100%);
      border-radius: 2px;
    }

    .card-title i {
      font-size: 1.5rem;
      transition: all 0.3s ease;
    }

    .custom-card:hover .card-title i {
      transform: scale(1.2) rotate(5deg);
    }

    .card-title .fa-bell {
      color: #e67e22;
    }

    .card-title .fa-calendar-alt {
      color: #3498db;
    }

    .card-title .fa-comment-dots {
      color: #27ae60;
    }

    .announcement-item {
      padding: 14px;
      border-radius: 12px;
      background: linear-gradient(135deg, #f9f7f4 0%, #f5f0e6 100%);
      margin-bottom: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
      border-left: 4px solid #e67e22;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .announcement-item:hover {
      background: linear-gradient(135deg, #ffe8cc 0%, #ffd699 100%);
      border-left-color: #d4af37;
      padding-left: 20px;
      box-shadow: 0 5px 15px rgba(230, 126, 34, 0.15);
      transform: translateX(5px);
    }

    .announcement-item h6 {
      color: #8B4513;
      margin-bottom: 0.5rem;
    }

    .announcement-item small {
      color: #999;
    }

    .calendar-list { list-style: none; padding: 0; }
    .calendar-list li {
      padding: 12px;
      margin-bottom: 10px;
      border-left: 4px solid var(--accent-gold);
      background: linear-gradient(135deg, #f9f7f4 0%, #f5f0e6 100%);
      border-radius: 8px;
      font-size: 0.95rem;
      transition: var(--transition);
    }

    .calendar-list li:hover {
      background: linear-gradient(135deg, #ffe8cc 0%, #ffd699 100%);
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(244, 196, 48, 0.2);
    }

    .calendar-list li span {
      display: inline-block;
      background: var(--navy-dark);
      color: var(--accent-gold);
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.85rem;
      margin-right: 8px;
      font-weight: 600;
    }

    /* --- Feedback Form Styling --- */
    .feedback-form input, .feedback-form textarea {
      border: 2px solid #e6dcd1;
      border-radius: 12px;
      padding: 12px 14px;
      background: linear-gradient(135deg, #faf8f3 0%, #f5f0e6 100%);
      width: 100%;
      margin-bottom: 15px;
      outline: none;
      transition: all 0.3s ease;
      color: #333;
    }

    .feedback-form input::placeholder, .feedback-form textarea::placeholder {
      color: #ccc;
    }

    .feedback-form input:focus, .feedback-form textarea:focus {
      border-color: #8B4513;
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
    }

    .btn-submit {
      background: linear-gradient(135deg, #8B4513 0%, #654321 100%);
      color: var(--white);
      border: none;
      padding: 12px 20px;
      border-radius: 12px;
      font-weight: 700;
      width: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(139, 69, 19, 0.2);
    }

    .btn-submit:hover {
      background: linear-gradient(135deg, #654321 0%, #8B4513 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(139, 69, 19, 0.3);
    }

    /* --- Testimonials Section --- */
    .testimonials-section { padding-top: 3rem; padding-bottom: 3rem; }
    .testimonial-card { background: var(--white); border: 1px solid rgba(0,31,77,0.06); border-radius: 16px; padding: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.05); height: 100%; transition: var(--transition); }
    .testimonial-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.08); border-color: var(--accent-gold); }
    
    /* Department Cards - Square Shape */
    .dept-link .testimonial-card {
      background: transparent;
      border: 2px solid rgba(0,31,77,0.2);
      aspect-ratio: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 1.5rem;
      box-shadow: none;
    }
    
    .dept-link .testimonial-card:hover {
      background: rgba(244, 196, 48, 0.1);
      border-color: var(--accent-gold);
      box-shadow: 0 10px 30px rgba(244, 196, 48, 0.2);
    }
    
    .dept-link .testimonial-card h5 {
      font-size: 1.3rem;
      margin-bottom: 0.8rem !important;
    }
    
    .dept-link .testimonial-card p {
      display: none;
    }
    
    .dept-link .testimonial-card .badge {
      margin-top: auto !important;
    }
    
    .testimonial-header { display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 15px; text-align: center; }
    .avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #d4af37; background: #fff; box-shadow: 0 6px 20px rgba(139, 69, 19, 0.25); }
    .name { font-weight: 700; color: var(--navy-dark); margin: 0; }
    .role-badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; background: #eef2ff; color: #4338ca; font-weight: 600; }
    .quote { color: var(--text-muted); font-size: 0.95rem; margin: 0; }
    .stars { color: #f59e0b; letter-spacing: 2px; }
    .filter-btns .btn { border-radius: 999px; padding: 6px 14px; font-weight: 600; }
    .filter-btns .btn.active { background: var(--navy-dark); color: var(--white); }

    /* --- Features Section --- */
    .features-section {
      background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
      padding: 4rem 0;
    }

    .feature-card {
      background: var(--white);
      border: 2px solid rgba(0, 31, 77, 0.1);
      border-radius: 20px;
      padding: 2.5rem;
      text-align: center;
      transition: var(--transition);
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      animation: landingSlide 0.6s ease-out backwards;
    }

    .feature-card:nth-child(1) { animation-delay: 0.1s; }
    .feature-card:nth-child(2) { animation-delay: 0.2s; }
    .feature-card:nth-child(3) { animation-delay: 0.3s; }
    .feature-card:nth-child(4) { animation-delay: 0.4s; }
    .feature-card:nth-child(5) { animation-delay: 0.5s; }
    .feature-card:nth-child(6) { animation-delay: 0.6s; }

    @keyframes landingSlide {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .feature-card:hover {
      border-color: var(--accent-gold);
      box-shadow: 0 20px 50px rgba(244, 196, 48, 0.15);
      transform: translateY(-10px);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #001f4d 0%, #003380 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: var(--accent-gold);
      margin-bottom: 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 31, 77, 0.2);
    }

    .feature-card:hover .feature-icon {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 15px 40px rgba(244, 196, 48, 0.3);
    }

    .feature-card h5 {
      color: var(--navy-dark);
      font-weight: 700;
      font-size: 1.3rem;
      margin-bottom: 0;
    }

    .feature-desc {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.8rem;
      margin-bottom: 0;
      line-height: 1.5;
    }

    /* --- Departments Section --- */
    .departments-section {
      background: transparent;
      padding: 4rem 0;
    }

    .dept-card {
      background: linear-gradient(135deg, #ffffff 0%, #f9f7f4 100%);
      border: 2px solid rgba(244, 196, 48, 0.2);
      border-radius: 15px;
      padding: 1.5rem;
      text-align: center;
      transition: var(--transition);
      height: 100%;
      aspect-ratio: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      max-width: 250px;
      margin: 0 auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      animation: slideInMove 3s ease-in-out infinite;
    }

    .dept-card:nth-child(1) { animation-delay: 0s; }
    .dept-card:nth-child(2) { animation-delay: 0.3s; }
    .dept-card:nth-child(3) { animation-delay: 0.6s; }
    .dept-card:nth-child(4) { animation-delay: 0.9s; }

    @keyframes slideInMove {
      0%, 100% {
        transform: translateX(0);
      }
      50% {
        transform: translateX(10px);
      }
    }

    .dept-card:hover {
      box-shadow: 0 20px 50px rgba(244, 196, 48, 0.25);
      transform: translateY(-15px);
      border-color: var(--accent-gold);
    }

    .dept-icon-animated {
      width: 100%;
      height: 150px;
      background: linear-gradient(135deg, #f4c430 0%, #e6b800 100%);
      border-radius: 12px 12px 0 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: #001f4d;
      margin: 0;
      margin-bottom: 1rem;
      box-shadow: 0 8px 20px rgba(244, 196, 48, 0.3);
      position: relative;
      overflow: hidden;
    }

    .dept-photo {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 12px 12px 0 0;
    }

    .dept-icon-animated i {
      position: absolute;
      display: none;
    }

    @keyframes videoPlay {
      0%, 100% {
        transform: scale(1) rotateY(0deg);
      }
      50% {
        transform: scale(1.1) rotateY(10deg);
      }
    }

    .dept-card:hover .dept-icon-animated {
      animation: videoPlayActive 0.6s ease-in-out;
      box-shadow: 0 15px 40px rgba(244, 196, 48, 0.3);
    }

    @keyframes videoPlayActive {
      0% { transform: scale(1); }
      50% { transform: scale(1.15); }
      100% { transform: scale(1.05); }
    }

    .dept-card h5 {
      color: var(--navy-dark);
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 1rem;
      line-height: 1.3;
    }

    .dept-link-btn {
      color: #001f4d;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      padding: 0.6rem 1.5rem;
      background: transparent;
      border: 2px solid var(--accent-gold);
      border-radius: 50px;
      transition: var(--transition);
      margin-top: auto;
      font-size: 0.95rem;
    }

    .dept-link-btn:hover {
      background: var(--accent-gold);
      color: #001f4d;
      transform: scale(1.05);
      box-shadow: 0 8px 15px rgba(244, 196, 48, 0.3);
    }

    /* Animation for floating icon */
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    .hero-header {
      animation: slideInUp 0.8s ease-out;
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* New Hero Section - 3 Column Layout */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }


/* --- HERO CONTAINER --- */
.hero-container {
    width: 100%;
    height: auto;
    min-height: 500px;
    background-color: transparent;
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
}

/* --- LEFT IMAGE SECTION --- */
/* --- LEFT IMAGE SECTION --- */
.left-bg {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.left-bg img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: 70% center;
    transform: scale(1.4);
}

/* Image lai halka dark banauna photo jastai */
.left-bg::after {
    content: "";
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.15);  /* Overlay halka matra rakheko - photo clear dekhne */
    z-index: 2;
}

/* --- THE EXACT S-CURVE --- */
.yellow-curve {
    display: none; /* curve hataeko */
}


.content-right {
    position: relative;
    z-index: 10;
    margin-left: 15%;
    margin-right: auto;
    width: 45%;
    padding: 2rem;
    text-align: left;
}

  

.content-right h1 {
    font-size: 4rem;
    color: #2c1810; /* Dark brown matching photo */
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    text-shadow: 2px 2px 8px rgba(255, 255, 255, 0.3);
    letter-spacing: -0.02em;
}

.content-right h1 span {
    color: #f4c430; /* Golden color */
    position: relative;
    display: inline-block;
}

.content-right h1 span::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #d4af37 0%, #f4c430 100%);
    border-radius: 3px;
}

.content-right p {
    color: #4a3621; /* Brown color matching photo */
    font-size: 1.2rem;
    margin-bottom: 2rem;
    line-height: 1.7;
    font-weight: 500;
    text-shadow: 1px 1px 4px rgba(255, 255, 255, 0.2);
}

.content-right h2 {
    color: #4a3621; /* Brown color matching photo */
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    text-shadow: 1px 1px 4px rgba(255, 255, 255, 0.2);
}

.content-right .hero-btn {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, #8B4513 0%, #654321 100%);
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.content-right .hero-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(139, 69, 19, 0.4);
    background: linear-gradient(135deg, #654321 0%, #8B4513 100%);
    border-color: #d4af37;
}

  .content-right .hero-contact{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px;
    margin-top:12px;
    color:#4a3621;
  }

  .hero-contact .contact-label{
    font-weight:600;
    font-size:0.95rem;
  }

  .hero-contact .contact-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:0.65rem 1.1rem;
    border-radius:999px;
    text-decoration:none;
    font-weight:600;
    border:1px solid #d4af37;
    background:rgba(255,255,255,0.9);
    color:#001f4d;
    box-shadow:0 8px 18px rgba(0,0,0,0.08);
    transition:all 0.25s ease;
  }

  .hero-contact .contact-pill.primary{
    background:linear-gradient(135deg, #001f4d 0%, #003380 100%);
    color:#fff;
    border-color:transparent;
  }

  .hero-contact .contact-pill:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 28px rgba(0,0,0,0.12);
  }

/* Responsive (Mobile ma curve hataidine) */
@media (max-width: 768px) {
    .hero-container { height: 350px; margin-top: 15px; }
    .left-bg { width: 100%; }
    .content-right { width: 95%; padding: 20px; }
    .content-right h1 { font-size: 2.5rem; }
    .content-right p { font-size: 1rem; }
    .hero-contact { justify-content:center; }
}
    .info-cards-sliding {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 1.5rem;
    }

    .info-card-item {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 8px 24px rgba(0,31,77,0.08);
      border: 1px solid rgba(0,31,77,0.05);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      animation: slideInUp 0.6s ease-out both;
    }

    .info-card-item:nth-child(1),
    .info-card-item:nth-child(2),
    .info-card-item:nth-child(3) {
      grid-column: span 2;
    }

    .info-card-item:nth-child(4) {
      grid-column: 2 / 4;
    }

    .info-card-item:nth-child(5) {
      grid-column: 4 / 6;
    }

    .info-card-item:nth-child(1) { animation-delay: 0.1s; }
    .info-card-item:nth-child(2) { animation-delay: 0.2s; }
    .info-card-item:nth-child(3) { animation-delay: 0.3s; }
    .info-card-item:nth-child(4) { animation-delay: 0.4s; }
    .info-card-item:nth-child(5) { animation-delay: 0.5s; }

    .info-card-item:hover {
      transform: translateY(-10px);
      box-shadow: 0 16px 40px rgba(0,31,77,0.15);
      border-color: var(--accent-gold);
    }

    .info-card-item .icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .info-card-item:hover .icon {
      transform: scale(1.15) rotateY(10deg);
    }

    .info-card-item h5 {
      font-weight: 700;
      color: var(--navy-dark);
      margin-bottom: 0.5rem;
    }

    .info-card-item p {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin: 0;
    }

    @media (max-width: 1024px) {
      .hero-with-image {
        flex-direction: column;
        min-height: auto;
      }

      .hero-left-content {
        padding: 3rem 2rem;
      }

      .hero-right-image {
        min-height: 400px;
      }

      .hero-text-wrapper h1 {
        font-size: 3rem;
      }

      .hero-text-wrapper p {
        font-size: 1.1rem;
      }

      .info-cards-sliding {
        grid-template-columns: repeat(2, 1fr);
      }

      .info-card-item:nth-child(1),
      .info-card-item:nth-child(2),
      .info-card-item:nth-child(3),
      .info-card-item:nth-child(4),
      .info-card-item:nth-child(5) {
        grid-column: span 1;
      }
    }

    @media (max-width: 768px) {
      .hero { height: auto; padding: 3rem 1rem; margin: 10px; border-radius: 20px; }
      .hero-content h1 { font-size: 1.5rem; }
      
      .hero-with-image {
        flex-direction: column;
        min-height: auto;
      }

      .hero-left-content {
        display: none;
      }

      .hero-center-content {
        padding: 2rem 1.5rem;
      }

      .hero-right-image {
        display: none;
      }

      .hero-text-wrapper {
        text-align: center;
      }

      .hero-text-wrapper h1 {
        font-size: 2.5rem;
      }

      .hero-text-wrapper p {
        font-size: 1rem;
        margin-bottom: 0;
      }

      .info-cards-sliding { display: none; }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container-fluid p-0">
  <!-- Hero Section - 3 Column Layout -->
<section class="hero-container">
        <div class="left-bg">
            <img src="images/asasa.jpg" alt="Graduation Caps">
        </div>

        <div class="yellow-curve"></div>

        <div class="content-right">
            <h1>Hamro <span>Result</span> </h1>
            <p>Your complete academic companion—instant results, study resources, AI insights, and live college updates all in one powerful platform.</p>
            <h2> Your Guideliness to success</h2> 
            <a class="hero-btn" data-bs-toggle="modal" data-bs-target="#loginChoiceModal">Get Started <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
</div>
</div>

<!-- Login Choice Modal -->
<div class="modal fade" id="loginChoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Continue as</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3">
        <div class="d-grid gap-2">
          <a class="btn btn-primary" href="student_login.php">Student Login</a>
          <a class="btn btn-secondary" href="teacher_login.php">Teacher Login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Features Section -->
<section class="features-section py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Explore Key <span style="color: var(--navy-medium);">Features</span></h2>
    </div>
    <div class="row g-4">
      <!-- Study Materials -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-book-open"></i>
          </div>
          <h5>Study Materials</h5>
          <p class="feature-desc">Access comprehensive notes and resources from all departments</p>
        </div>
      </div>

      <!-- AI Insights -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-brain"></i>
          </div>
          <h5>AI Insights</h5>
          <p class="feature-desc">Get personalized AI-powered analysis of your academic performance</p>
        </div>
      </div>

      <!-- Chat System -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-comments"></i>
          </div>
          <h5>Live Chat</h5>
          <p class="feature-desc">Connect with teachers and peers in real-time for instant support</p>
        </div>
      </div>

      <!-- News & Announcements -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-newspaper"></i>
          </div>
          <h5>Announcements</h5>
          <p class="feature-desc">Stay updated with latest college news and important announcements</p>
        </div>
      </div>

      <!-- Results -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <h5>Results</h5>
          <p class="feature-desc">View your exam results and academic progress in real-time</p>
        </div>
      </div>

      <!-- Analytics -->
      <div class="col-lg-4 col-md-6">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-analytics"></i>
          </div>
          <h5>Analytics</h5>
          <p class="feature-desc">Track your performance metrics and academic growth statistics</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Explore Our Departments Section -->
<section class="departments-section py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title" style="color: #8B4513;">Explore Our <span style="color: var(--accent-gold);">Departments</span></h2>
    </div>
    
    <div class="row g-4 justify-content-center">
      <div class="col-lg-3 col-md-6">
        <div class="dept-card">
          <div class="dept-icon-animated">
            <img src="images/cmp11.jpg" alt="Computer Engineering" class="dept-photo">
            <i class="fas fa-laptop-code"></i>
          </div>
          <h5>Computer Engineering</h5>
          <a href="https://pu.edu.np/becomputer/" class="dept-link-btn">Explore →</a>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6">
        <div class="dept-card">
          <div class="dept-icon-animated">
            <img src="images/civil111.webp" alt="Civil Engineering" class="dept-photo">
            <i class="fas fa-building"></i>
          </div>
          <h5>Civil Engineering</h5>
          <a href="department_results.php?dept=civil" class="dept-link-btn">Explore →</a>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6">
        <div class="dept-card">
          <div class="dept-icon-animated">
            <img src="images/architecture.jpg" alt="Architecture" class="dept-photo">
            <i class="fas fa-ruler-combined"></i>
          </div>
          <h5>Architecture</h5>
          <a href="department_results.php?dept=architecture" class="dept-link-btn">Explore →</a>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6">
        <div class="dept-card">
          <div class="dept-icon-animated">
            <img src="images/it.avif" alt="Information Technology" class="dept-photo">
            <i class="fas fa-server"></i>
          </div>
          <h5>Information Technology</h5>
          <a href="department_results.php?dept=it" class="dept-link-btn">Explore →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="college-main-section">
  <div class="container">
    <div class="row g-4 justify-content-center">
    
    <div class="col-lg-4 col-md-12">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-bell text-warning"></i> Recent Announcements</h4>
        <div class="announcement-list">
          <?php
          $notices = $conn->query("SELECT n.*, t.full_name as teacher FROM notices n LEFT JOIN teachers t ON n.teacher_id=t.id ORDER BY created_at DESC LIMIT 3");
          if($notices->num_rows > 0):
            while($n = $notices->fetch_assoc()):
          ?>
            <div class="announcement-item" onclick="location.href='notice_detail.php?id=<?= $n['id'] ?>'">
              <h6 class="mb-1 fw-bold"><?= $n['title'] ?></h6>
              <small class="text-muted"><i class="far fa-clock"></i> <?= date('M j, Y', strtotime($n['created_at'])) ?> | By <?= $n['teacher'] ?></small>
            </div>
          <?php endwhile; else: echo "<p>No notices found.</p>"; endif; ?>
        </div>
        <a href="college_updates.php" class="btn btn-link text-decoration-none mt-2 p-0">View All Updates →</a>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-calendar-alt text-primary"></i> Academic Events</h4>
        <ul class="calendar-list">
          <?php
          $events = $conn->query("SELECT * FROM academic_events ORDER BY start_date ASC LIMIT 5");
          if($events->num_rows > 0):
            while($e = $events->fetch_assoc()):
          ?>
            <li><span class="fw-bold text-navy"><?= date('M d', strtotime($e['start_date'])) ?>:</span> <?= $e['title'] ?></li>
          <?php endwhile; else: echo "<li>No events listed.</li>"; endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="custom-card">
        <h4 class="card-title"><i class="fas fa-comment-dots text-success"></i> Feedback</h4>
        <p class="text-muted" style="font-size: 12px; margin-bottom: 15px;">
          <i class="fas fa-info-circle"></i> We'll send a verification email to confirm your feedback.
        </p>
        <form action="submit_feedback.php" method="POST" class="feedback-form">
          <input type="text" name="student_name" placeholder="Your Name" required>
          <input type="email" name="student_email" placeholder="Your Valid Email" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
          <textarea name="feedback" rows="3" placeholder="How can we improve?" required></textarea>
          <button type="submit" class="btn-submit">Send 🚀</button>
        </form>
      </div>
    </div>

  </div>
</section>

<!-- Community Voices / Testimonials -->
<section class="container testimonials-section">
  <div class="text-center mb-4">
    <h2 class="section-title">Community <span style="color: var(--navy-medium);">Voices</span></h2>
    <p class="subtitle">Teachers, students, and college leadership share their perspectives.</p>
    <div class="filter-btns mt-3">
      <button class="btn btn-light active" data-filter="all">All</button>
      <button class="btn btn-light" data-filter="student">Students</button>
      <button class="btn btn-light" data-filter="teacher">Teachers</button>
      <button class="btn btn-light" data-filter="principal">Principal</button>
      <button class="btn btn-light" data-filter="management">Management</button>
    </div>
  </div>

  <div class="row g-4" id="testimonialsGrid">
    <?php
    // Fetch testimonials from database (all status)
    $testimonials_q = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6");
    if($testimonials_q && $testimonials_q->num_rows > 0):
      while($t = $testimonials_q->fetch_assoc()): 
        $stars = str_repeat('★', $t['rating']) . str_repeat('☆', 5 - $t['rating']);
    ?>
    <div class="col-lg-4 col-md-6 testimonial-item" data-category="<?= $t['role'] ?>">
      <div class="testimonial-card">
        <div class="testimonial-header">
          <img class="avatar" src="<?= $t['photo_path'] ?: 'images/logoheader.png' ?>" alt="<?= htmlspecialchars($t['name']) ?>" onerror="this.src='images/logoheader.png'">
          <div>
            <p class="name mb-1"><?= htmlspecialchars($t['name']) ?></p>
            <span class="role-badge"><?= ucfirst($t['role']) ?></span>
          </div>
        </div>
        <p class="quote">"<?= htmlspecialchars($t['quote']) ?>"</p>
        <div class="stars mt-2"><?= $stars ?></div>
      </div>
    </div>
    
    <?php
      endwhile;
    else:
    ?>
    <div class="col-12 text-center text-muted">
      <p>No testimonials available yet.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Hero Background Hover Effect
  const hero = document.getElementById('hero');
  const deptBoxes = document.querySelectorAll('.department-box');
  const defaultBg = "linear-gradient(45deg, rgba(0,31,77,0.9), rgba(153, 180, 220, 0.4)), url('images/img.jpg')";

  deptBoxes.forEach(box => {
    box.addEventListener('mouseenter', () => {
      const img = box.dataset.bg;
      hero.style.backgroundImage = `linear-gradient(45deg, rgba(0,31,77,0.8), rgba(0,31,77,0.3)), url('${img}')`;
    });
    box.addEventListener('mouseleave', () => {
      hero.style.backgroundImage = defaultBg;
    });
  });

  // Testimonials Filter
  const filterButtons = document.querySelectorAll('.filter-btns .btn');
  const items = document.querySelectorAll('.testimonial-item');
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      filterButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.filter;
      items.forEach(el => {
        const match = key === 'all' || el.dataset.category === key;
        el.style.display = match ? '' : 'none';
      });
    });
  });
</script>

</body>
</html>
