<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="CSS/index.css" />
  <title>LearnTogether</title>
</head>
<body>

  <div class="nav shadow-sm justify-content-between">
    <div class="logo d-flex align-items-center">
      <div class="mark">LT</div>
      <div class="logo-text">LearnTogether</div>
    </div>

    <div class="nav-links d-none d-md-flex gap-4">
      <a href="#home" class="nav-link-custom">Home</a>
      <a href="#about" class="nav-link-custom">About</a>
      <a href="#services" class="nav-link-custom">Services</a>
      <a href="#contact" class="nav-link-custom">Contact</a>
    </div>

    <div class="login-btn d-none d-md-flex">
      <a href="login.php" class="btn btn-success">Login</a>
    </div>
  </div>


  <section id="home" class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start">
          <h1 class="fw-bold">Peer-to-Peer Tutoring Platform</h1>
          <p>
            A collaborative learning hub designed to connect students with one another for academic support, skill-building, and knowledge sharing.
          </p>
          <a href="login.php" class="btn btn-success mt-3 px-4">Learn More</a>
        </div>

        <div class="col-md-6 text-center mt-4 mt-md-0">
          <img src="images/home.png" class="img-fluid illustration"/>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="about-section">
    <div class="container">
      <div class="row align-items-center">

        <div class="col-md-6">
          <h2 class="fw-bold text-success mb-3">About Us</h2>
          <p>
            At <strong>LearnTogether</strong>, we believe that every student has the potential to both learn and teach.
          </p>

          <div class="mt-4">
            <div class="d-flex align-items-start mb-3">
              <img src="icons/empower.png" width="40" class="me-3">
              <div>
                <h6 class="fw-bold">Empowering Students</h6>
                <p class="mb-0">Become both a learner and a tutor.</p>
              </div>
            </div>

            <div class="d-flex align-items-start mb-3">
              <img src="icons/share.png" width="40" class="me-3">
              <div>
                <h6 class="fw-bold">Sharing Knowledge</h6>
                <p class="mb-0">Exchange skills, notes, and resources.</p>
              </div>
            </div>

            <div class="d-flex align-items-start">
              <img src="icons/community.png" width="40" class="me-3">
              <div>
                <h6 class="fw-bold">Building Community</h6>
                <p class="mb-0">Learn together, beyond classrooms.</p>
              </div>
            </div>
          </div>

          <a href="#" class="btn btn-success mt-4 px-4">Join the Community</a>
        </div>

        <div class="col-md-6 text-center">
          <img src="images/about.png" class="img-fluid" style="max-width:80%; mix-blend-mode: multiply;">
        </div>
      </div>
    </div>
  </section>

  <section id="services" class="services-section">
    <div class="container">
      <div class="row align-items-center">

        <div class="col-md-6 text-center mb-4 mb-md-0">
          <img src="IMAGES/services.png" class="img-fluid">
        </div>

        <div class="col-md-6">
          <h2 class="fw-bold text-success">Our Services</h2>
          <p class="mt-3">We extend our learning support to the peer community.</p>

          <div class="row g-3 mt-4">
            <div class="col-12">
              <div class="service-card p-3">
                <h6 class="fw-bold">One-on-One Tutoring</h6>
                <p class="mb-0">Personalized help from peers.</p>
              </div>
            </div>

            <div class="col-12">
              <div class="service-card p-3">
                <h6 class="fw-bold">Group Study Sessions</h6>
                <p class="mb-0">Collaborate and learn together.</p>
              </div>
            </div>

            <div class="col-12">
              <div class="service-card p-3">
                <h6 class="fw-bold">Resource Sharing</h6>
                <p class="mb-0">Notes and study materials.</p>
              </div>
            </div>

            <div class="col-12">
              <div class="service-card p-3">
                <h6 class="fw-bold">Skill Exchange</h6>
                <p class="mb-0">Teach and learn new skills.</p>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>

  <section id="contact" class="contact-section">
    <div class="container">
      <div class="row align-items-center">

        <div class="col-md-6 text-center mb-4 mb-md-0">
          <img src="images/contact.png" class="img-fluid" width="350">
        </div>

        <div class="col-md-6">
          <h2 class="fw-bold text-success">Contact Us</h2>
          <p>Have questions? We're here to help.</p>

          <form class="text-start">
            <input type="text" class="form-control mb-3" placeholder="Name">
            <input type="email" class="form-control mb-3" placeholder="Email">
            <textarea class="form-control mb-3" placeholder="Message" rows="4"></textarea>
            <button class="btn btn-success px-4">Send Message</button>
          </form>

        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
