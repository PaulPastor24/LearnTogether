<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>LearnTogether — Student Dashboard</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="g">
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
          <a class="active" href="learner.html" >🏠 Overview</a>
          <a href="subjects.html">📚 My Subjects</a>
          <a href="tutors.html">🔎 Find Tutors</a>
          <a href="schedule.html">📅 My Schedule</a>
          <a href="requests.html">✉️ Requests</a>
          <a href="#">⚙️ Settings</a>
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
      <section class="welcome">
        <div class="greeting">
          <h1>Welcome back, <span style="color:var(--accent-2)">Alex</span> 👋</h1>
          <p>Here’s what’s happening with your learning. Quick actions to book a tutor, check your schedule, or review requests.</p>

          <div class="quick">
            <div class="card" style="align-items:center">
              <div class="icon" style="background:linear-gradient(180deg,#effffb,#f0fff9); color:var(--accent-2)">📚</div>
              <div>
                <div style="font-weight:700">Find a Tutor</div>
                <div style="font-size:13px;color:var(--muted)">Search tutors by subject, rating, and availability.</div>
              </div>
              <div style="margin-left:auto"><button class="cta">Search</button></div>
            </div>
            <div class="card">
              <div class="icon" style="background:linear-gradient(180deg,#fff7ed,#fff2e6);">📅</div>
              <div>
                <div style="font-weight:700">My Schedule</div>
                <div style="font-size:13px;color:var(--muted)">See and manage upcoming sessions.</div>
              </div>
              <div style="margin-left:auto"><button class="cta">Open</button></div>
            </div>
            <div class="card">
              <div class="icon" style="background:linear-gradient(180deg,#eef2ff,#f7f9ff);">✉️</div>
              <div>
                <div style="font-weight:700">My Requests</div>
                <div style="font-size:13px;color:var(--muted)">Track requests you sent to tutors.</div>
              </div>
              <div style="margin-left:auto"><button class="cta">View</button></div>
            </div>
          </div>
        </div>
        <div style="min-width:240px">
          <div class="card-lg" style="text-align:center">
            <div style="font-size:13px;color:var(--muted)">Progress</div>
            <div style="font-size:28px;font-weight:800;margin-top:8px">42%</div>
            <div style="font-size:13px;color:var(--muted);margin-top:8px">toward learning target</div>
            <div class="progress" style="margin-top:14px">
              <div class="pill">
                <div style="font-weight:700">12</div>
                <div style="font-size:12px;color:var(--muted)">Sessions</div>
              </div>
              <div class="pill">
                <div style="font-weight:700">3</div>
                <div style="font-size:12px;color:var(--muted)">Pending</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="layout">
        <div>
          <div class="card-lg">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
              <div style="font-weight:700">Upcoming Sessions</div>
              <div style="font-size:13px;color:var(--muted)">Next 7 days</div>
            </div>
            <div class="session">
              <div style="width:68px;text-align:center"><div style="font-weight:700">Sep 24</div><small>3:00 PM</small></div>
              <div>
                <div style="font-weight:700">Calculus — Mr. Lee</div>
                <small>Online • 60 min</small>
              </div>
              <div style="margin-left:auto;color:var(--accent-2);font-weight:700">Confirmed</div>
            </div>

            <div class="session">
              <div class="time">Sep 25</div>
              <div>
                <div style="font-weight:700">Physics — Ms. Ana</div>
                <small>In-person • 45 min</small>
              </div>
              <div style="margin-left:auto;color:#f59e0b;font-weight:700">Pending</div>
            </div>

            <div class="session">
              <div class="time">Sep 26</div>
              <div>
                <div style="font-weight:700">English — Mr. Cruz</div>
                <small>Online • 30 min</small>
              </div>
              <div style="margin-left:auto;color:var(--muted);font-weight:700">Requested</div>
            </div>

          </div>

          <div class="card-lg" style="margin-top:16px">
            <div style="font-weight:700;margin-bottom:12px">Recommended Tutors</div>
            <div class="tutors-grid">
              <div class="tutor">
                <div style="width:56px;height:56px;border-radius:10px;background:linear-gradient(180deg,#fff,#f1fbf9);display:grid;place-items:center;font-weight:700">ML</div>
                <div class="meta">
                  <div style="font-weight:700">Maria Lopez <span style="font-size:12px;color:var(--muted);font-weight:600">• Math</span></div>
                  <div style="font-size:13px;color:var(--muted)">5.0 • 1.2k hrs taught • Available today</div>
                </div>
                <div><button class="cta">Request</button></div>
              </div>

              <div class="tutor">
                <div style="width:56px;height:56px;border-radius:10px;background:linear-gradient(180deg,#fff,#f1fbf9);display:grid;place-items:center;font-weight:700">JC</div>
                <div class="meta">
                  <div style="font-weight:700">J.C. Rivera <span style="font-size:12px;color:var(--muted);font-weight:600">• Physics</span></div>
                  <div style="font-size:13px;color:var(--muted)">4.9 • 620 hrs taught • Available tomorrow</div>
                </div>
                <div><button class="cta">Request</button></div>
              </div>
            </div>
          </div>
        </div>

        <aside>
          <div class="card-lg">
            <div style="font-weight:700;margin-bottom:8px">Your Subjects</div>
            <div style="display:flex;flex-direction:column;gap:8px">
              <div style="padding:10px;border-radius:10px;background:linear-gradient(180deg,#ffffff,#f7fffb);">Calculus • In progress</div>
              <div style="padding:10px;border-radius:10px;background:linear-gradient(180deg,#ffffff,#f7fffb);">Physics • Interested</div>
              <div style="padding:10px;border-radius:10px;background:linear-gradient(180deg,#ffffff,#f7fffb);">English • Beginner</div>
            </div>

            <div style="margin-top:12px;display:flex;gap:8px">
              <button class="cta" style="flex:1">Manage Subjects</button>
            </div>
          </div>

          <div style="height:20px"></div>

          <div class="card-lg">
            <div style="font-weight:700;margin-bottom:8px">Notifications</div>
            <div style="font-size:13px;color:var(--muted)">You have <strong>2</strong> unread messages and <strong>1</strong> pending request.</div>
          </div>
        </aside>
      </div>

    </main>
  </div>

  <script>
    // small interactive touches could be added here
    document.querySelectorAll('.navlinks a').forEach(a=>a.addEventListener('click',()=>{document.querySelectorAll('.navlinks a').forEach(x=>x.classList.remove('active')); a.classList.add('active')}))
  </script>
</body>
</html>
