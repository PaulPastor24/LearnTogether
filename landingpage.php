<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="CSS/modern-ui.css" />
  <link rel="stylesheet" href="CSS/style2.css" />
  <link rel="stylesheet" href="CSS/home.css" />
  <link rel="stylesheet" href="CSS/index.css" />
  <title>LearnTogether - Peer-to-Peer Tutoring Platform</title>
  <style>
    /* Enhanced Landing Page Styles with Green Theme */
    :root {
      --primary: #10b981;
      --secondary: #34d399;
      --text-primary: #1f2937;
      --text-secondary: #6b7280;
    }

    body {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-primary);
    }

    .nav {
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 253, 244, 0.95) 100%);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(16, 185, 129, 0.1);
      padding: 16px 32px;
      display: flex;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
    }

    .logo {
      gap: 12px;
      cursor: pointer;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .logo:hover {
      transform: scale(1.02);
    }

    .mark {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 16px;
    }

    .logo-text {
      font-weight: 700;
      font-size: 20px;
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .nav-links {
      display: flex;
      gap: 32px;
      flex: 1;
      justify-content: center;
    }

    .nav-link-custom {
      text-decoration: none;
      color: var(--text-primary);
      font-weight: 500;
      font-size: 14px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .nav-link-custom::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: linear-gradient(90deg, #10b981, #34d399);
      transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-link-custom:hover {
      color: var(--primary);
    }

    .nav-link-custom:hover::after {
      width: 100%;
    }

    .login-btn a {
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      color: white;
      padding: 10px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      position: relative;
      overflow: hidden;
    }

    .login-btn a::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .login-btn a:hover::before {
      width: 300px;
      height: 300px;
    }

    .login-btn a:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    }

    .hero-section {
      padding: 80px 32px;
      min-height: 600px;
      display: flex;
      align-items: center;
      animation: fadeIn 0.6s ease-out;
    }

    .hero-section h1 {
      font-size: 3rem;
      font-weight: 800;
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 24px;
      line-height: 1.2;
      animation: slideInUp 0.6s ease-out 0.1s backwards;
    }

    .hero-section p {
      font-size: 1.1rem;
      color: var(--text-secondary);
      margin-bottom: 32px;
      max-width: 500px;
      animation: slideInUp 0.6s ease-out 0.2s backwards;
    }

    .hero-section .btn {
      animation: slideInUp 0.6s ease-out 0.3s backwards;
    }

    .illustration {
      max-width: 100%;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .about-section, .services-section, .contact-section {
      padding: 80px 32px;
    }

    .about-section {
      background: white;
    }

    .services-section {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
    }

    .contact-section {
      background: white;
    }

    .section h2 {
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 24px;
    }

    .section p {
      color: var(--text-secondary);
      font-size: 1rem;
      line-height: 1.6;
    }

    .feature-box {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 20px;
      background: white;
      border-radius: 12px;
      border: 1px solid rgba(16, 185, 129, 0.1);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      margin-bottom: 20px;
    }

    .feature-box:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
      border-color: rgba(16, 185, 129, 0.2);
    }

    .feature-box h6 {
      color: var(--primary);
      font-weight: 700;
      margin-bottom: 8px;
    }

    .feature-box p {
      font-size: 0.95rem;
      color: var(--text-secondary);
      margin: 0;
    }

    .service-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 253, 244, 0.95) 100%);
      backdrop-filter: blur(4px);
      border: 1px solid rgba(16, 185, 129, 0.1);
      border-radius: 12px;
      padding: 24px !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .service-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent 30%, rgba(16, 185, 129, 0.1) 50%, transparent 70%);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0% { transform: translate(-100%, -100%) rotate(45deg); }
      100% { transform: translate(100%, 100%) rotate(45deg); }
    }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 32px rgba(16, 185, 129, 0.2);
      border-color: rgba(16, 185, 129, 0.3);
    }

    .service-card h6 {
      color: var(--primary);
      font-weight: 700;
      margin-bottom: 8px;
      position: relative;
      z-index: 1;
    }

    .service-card p {
      color: var(--text-secondary);
      margin: 0;
      position: relative;
      z-index: 1;
    }

    .form-control {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
      border: 1px solid rgba(16, 185, 129, 0.2);
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.95rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      color: var(--text-primary);
    }

    .form-control:focus {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
      transform: translateY(-2px);
    }

    .form-control::placeholder {
      color: var(--text-secondary);
    }

    @media (max-width: 768px) {
      .hero-section h1 {
        font-size: 2rem;
      }

      .section h2 {
        font-size: 1.8rem;
      }

      .nav {
        padding: 12px 16px;
      }

      .nav-links {
        gap: 16px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>


  <!-- Navigation Bar -->
  <div class="nav justify-content-between">
    <div class="logo d-flex align-items-center">
      <div class="mark">LT</div>
      <div class="logo-text">LearnTogether</div>
    </div>

    <div class="nav-links d-none d-md-flex">
      <a href="#home" class="nav-link-custom">Home</a>
      <a href="#about" class="nav-link-custom">About</a>
      <a href="#services" class="nav-link-custom">Services</a>
      <a href="#contact" class="nav-link-custom">Contact</a>
    </div>

    <div class="login-btn d-none d-md-flex">
      <a href="login.php">Login</a>
    </div>
  </div>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 text-center text-lg-start">
          <h1 class="fw-bold">Peer-to-Peer Tutoring Platform</h1>
          <p>
            A collaborative learning hub designed to connect students with one another for academic support, skill-building, and knowledge sharing.
          </p>
          <a href="login.php" class="btn btn-gradient mt-3 px-4">Get Started</a>
        </div>

        <div class="col-lg-6 text-center mt-4 mt-lg-0">
          <img src="images/home.png" class="img-fluid illustration" alt="Learning illustration"/>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="about-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <h2 class="section fw-bold">About Us</h2>
          <p>
            At <strong>LearnTogether</strong>, we believe that every student has the potential to both learn and teach. Our platform empowers the next generation of peer educators.
          </p>

          <div class="mt-5">
            <div class="feature-box">
              <div>
                <h6>🎯 Empowering Students</h6>
                <p>Become both a learner and a tutor in our vibrant community.</p>
              </div>
            </div>

            <div class="feature-box">
              <div>
                <h6>📚 Sharing Knowledge</h6>
                <p>Exchange skills, notes, and study resources with peers.</p>
              </div>
            </div>

            <div class="feature-box">
              <div>
                <h6>🤝 Building Community</h6>
                <p>Learn together, collaborate, and grow beyond traditional classrooms.</p>
              </div>
            </div>
          </div>

          <a href="login.php" class="btn btn-gradient mt-5 px-4">Join the Community</a>
        </div>

        <div class="col-lg-6 text-center mt-5 mt-lg-0">
          <img src="images/about.png" class="img-fluid" alt="About illustration" style="max-width:85%; filter: drop-shadow(0 10px 30px rgba(99, 102, 241, 0.15));">
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="services" class="services-section">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="section fw-bold mb-3">Our Services</h2>
        <p class="text-secondary" style="max-width: 600px; margin: 0 auto;">We extend comprehensive learning support through peer-to-peer collaboration and modern educational tools.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>👥 One-on-One Tutoring</h6>
            <p>Personalized help from qualified peers with expertise in your subject.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>👨‍👩‍👧‍👦 Group Study Sessions</h6>
            <p>Collaborate with multiple learners and explore topics together.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>📚 Resource Sharing</h6>
            <p>Access comprehensive notes and curated study materials.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>💡 Skill Exchange</h6>
            <p>Teach and learn new skills in a supportive community.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>📹 Video Conferencing</h6>
            <p>Real-time interactive sessions with high-quality video and audio.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>⭐ Expert Ratings</h6>
            <p>Find verified tutors through community reviews and ratings.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>📊 Progress Tracking</h6>
            <p>Monitor your learning journey with detailed progress analytics.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="service-card">
            <h6>🔒 Safe Platform</h6>
            <p>Secure environment with verified users and privacy protection.</p>
          </div>
        </div>
      </div>

      <div class="text-center mt-5">
        <p class="text-secondary mb-3">Ready to transform your learning experience?</p>
        <a href="login.php" class="btn btn-gradient px-5 py-2">Explore Now</a>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section id="contact" class="contact-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 text-center mb-5 mb-lg-0">
          <img src="images/contact.png" class="img-fluid" alt="Contact illustration" width="400" style="filter: drop-shadow(0 10px 30px rgba(99, 102, 241, 0.15));">
        </div>

        <div class="col-lg-6">
          <h2 class="section fw-bold mb-3">Get in Touch</h2>
          <p class="text-secondary mb-5">Have questions? We're here to help. Reach out to us and we'll respond as soon as possible.</p>

          <form class="text-start" id="contactForm">
            <div class="mb-3">
              <label class="form-label fw-600 mb-2">Name</label>
              <input type="text" class="form-control" placeholder="Your name" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-600 mb-2">Email</label>
              <input type="email" class="form-control" placeholder="your@email.com" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-600 mb-2">Message</label>
              <textarea class="form-control" placeholder="Your message..." rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-gradient px-5 py-2">Send Message</button>
          </form>

          <div style="margin-top: 40px; padding-top: 40px; border-top: 1px solid rgba(99, 102, 241, 0.1);">
            <p class="text-secondary mb-3"><strong>Email:</strong> support@learntogether.com</p>
            <p class="text-secondary mb-3"><strong>Phone:</strong> +1 (555) 123-4567</p>
            <p class="text-secondary"><strong>Hours:</strong> Mon-Fri, 9AM-6PM EST</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%); padding: 40px 32px; border-top: 1px solid rgba(99, 102, 241, 0.1); margin-top: 60px;">
    <div class="container">
      <div class="row mb-4">
        <div class="col-md-3 mb-4 mb-md-0">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="mark" style="width: 32px; height: 32px; font-size: 14px;">LT</div>
            <span class="fw-bold" style="color: var(--primary);">LearnTogether</span>
          </div>
          <p class="text-secondary" style="font-size: 0.9rem;">Empowering peer-to-peer learning through technology.</p>
        </div>

        <div class="col-md-3 mb-4 mb-md-0">
          <h6 class="fw-bold mb-3" style="color: var(--primary);">Product</h6>
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Features</a></li>
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Pricing</a></li>
            <li><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Security</a></li>
          </ul>
        </div>

        <div class="col-md-3 mb-4 mb-md-0">
          <h6 class="fw-bold mb-3" style="color: var(--primary);">Company</h6>
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">About</a></li>
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Blog</a></li>
            <li><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Careers</a></li>
          </ul>
        </div>

        <div class="col-md-3">
          <h6 class="fw-bold mb-3" style="color: var(--primary);">Legal</h6>
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Privacy</a></li>
            <li class="mb-2"><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Terms</a></li>
            <li><a href="#" style="text-decoration: none; color: var(--text-secondary); font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary')" onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')">Contact</a></li>
          </ul>
        </div>
      </div>

      <div style="text-align: center; padding-top: 20px; border-top: 1px solid rgba(99, 102, 241, 0.1);">
        <p class="text-secondary mb-0" style="font-size: 0.9rem;">© 2024 LearnTogether. All rights reserved. | Designed with ❤️ for learners worldwide</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Contact form submission
    document.getElementById('contactForm')?.addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Thank you for reaching out! We will get back to you shortly.');
      this.reset();
    });

    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
      const nav = document.querySelector('.nav');
      if (window.scrollY > 50) {
        nav.style.boxShadow = '0 8px 24px rgba(99, 102, 241, 0.15)';
      } else {
        nav.style.boxShadow = '0 4px 12px rgba(99, 102, 241, 0.08)';
      }
    });
  </script>
</body>
</html>
