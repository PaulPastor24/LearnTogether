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
          <a class="active" href="subjects.html">📚 My Subjects</a>
          <a href="tutors.html">🔎 Find Tutors</a>
          <a href="schedule.html">📅 My Schedule</a>
          <a href="requests.html">✉️ Requests</a>
          <a href="settings.html">⚙️ Settings</a>
        </nav>
      </div>
    </aside>

    <div class="nav" role="navigation">
      <div class="logo"><div class="mark">LT</div><div style="font-weight:700">LearnTogether</div></div>
      <div class="search"><input id="searchInput" placeholder="Search tutors, subjects or topics" /></div>
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
      <h1>My Subjects</h1>
      <div id="subjectsGrid" class="subjects-grid"></div>
    </main>
  </div>

  <script>
    const API_URL = "https://api.example.com/subjects"; 
    const subjectsGrid = document.getElementById("subjectsGrid");

    function renderSubjects(subjects) {
      subjectsGrid.innerHTML = ""; 
      subjects.forEach(sub => {
        const card = document.createElement("div");
        card.className = "subject-card";
        card.innerHTML = `
          <div class="subject-header">
            <div class="icon" style="background:${sub.color || 'linear-gradient(180deg,#2563eb,#1e40af)'}">${sub.icon || "📚"}</div>
            <div class="subject-title">${sub.title}</div>
          </div>
          <div class="subject-desc">${sub.description}</div>
          <div class="topics">
            ${sub.topics.map(t => `<span class="topic">${t}</span>`).join("")}
          </div>
        `;
        subjectsGrid.appendChild(card);
      });
    }

    async function loadSubjects(query = "") {
      try {
        const res = await fetch(`${API_URL}?q=${encodeURIComponent(query)}`);
        const data = await res.json();
        renderSubjects(data);
      } catch (err) {
        console.error("Error fetching subjects:", err);
        subjectsGrid.innerHTML = "<p>⚠️ Failed to load subjects.</p>";
      }
    }

    document.getElementById("searchInput").addEventListener("input", e => {
      loadSubjects(e.target.value);
    });

    loadSubjects();
  </script>
</body>
</html>
