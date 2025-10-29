<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Subjects — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
</head>
<body>
  <div class="app">
    <aside>
      <div class="sidebar">
        <div class="profile">
          <div class="avatar">A</div>
          <div>
            <div style="font-weight:700">Alex Mercado</div>
            <div style="font-size:13px;color:var(--muted)">Active student</div>
          </div>
        </div>
        <nav class="navlinks">
          <a href="learner.html">🏠 Overview</a>
          <a href="subjects.html">📚 My Subjects</a>
          <a class="active" href="tutors.html">🔎 Find Tutors</a>
          <a href="schedule.html">📅 My Schedule</a>
          <a href="requests.html">✉️ Requests</a>
          <a href="settings.html">⚙️ Settings</a>
        </nav>
      </div>
    </aside>

    <div class="nav" role="navigation">
      <div class="logo"><div class="mark">LT</div><div style="font-weight:700">LearnTogether</div></div>
      <div class="search"><input placeholder="Search tutors, subjects or topics" /></div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px"><div style="font-weight:700">Alex</div><div style="font-size:12px;color:var(--muted)">Student</div></div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">AM</div>
        </div>
      </div>
    </div>

<main>
  <h1>Find Tutors</h1>
  <div class="subjects-grid">
    <div class="subject-card">
      <div class="subject-header">
        <div class="icon" style="background: linear-gradient(180deg,#2563eb,#1e40af)">👩‍🏫</div>
        <div class="subject-title">Maria Santos</div>
      </div>
      <div class="subject-desc">Expert in Mathematics & Physics. 5+ years of tutoring experience.</div>
      <div class="topics">
        <span class="topic">Calculus</span>
        <span class="topic">Physics</span>
        <span class="topic">Algebra</span>
      </div>
    </div>

    <div class="subject-card">
      <div class="subject-header">
        <div class="icon" style="background: linear-gradient(180deg,#0f766e,#065f46)">👨‍🏫</div>
        <div class="subject-title">John Dela Cruz</div>
      </div>
      <div class="subject-desc">Specializes in English & Communication Skills.</div>
      <div class="topics">
        <span class="topic">Essay Writing</span>
        <span class="topic">Speaking</span>
        <span class="topic">Grammar</span>
      </div>
    </div>
  </div>
</main>

  </div>

  <script>
    document.querySelectorAll('.navlinks a').forEach(a => {
      a.addEventListener('click', () => {
        document.querySelectorAll('.navlinks a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
      });
    });
  </script>
</body>
</html>
