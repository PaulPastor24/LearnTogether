document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  const searchFilter = document.getElementById('searchFilter');

  if (!searchInput) return;

  const learners = document.querySelectorAll('.learner-card');
  const requests = document.querySelectorAll('tbody tr');

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    const filter = searchFilter ? searchFilter.value : 'all';

    learners.forEach(learner => {
      const name = learner.querySelector('.learner-name')?.textContent.toLowerCase() || '';
      const subjects = learner.querySelector('.learner-subject')?.textContent.toLowerCase() || '';

      let show = false;
      if (filter === 'all') show = name.includes(query) || subjects.includes(query);
      else if (filter === 'name') show = name.includes(query);
      else if (filter === 'subject') show = subjects.includes(query);

      learner.style.display = show ? 'flex' : 'none';
    });

    requests.forEach(row => {
      const tutor = row.dataset.tutor?.toLowerCase() || '';
      const subject = row.dataset.subject?.toLowerCase() || '';

      let show = false;
      if (filter === 'all') show = tutor.includes(query) || subject.includes(query);
      else if (filter === 'tutor') show = tutor.includes(query);
      else if (filter === 'subject') show = subject.includes(query);

      row.style.display = show ? '' : 'none';
    });
  });
});
