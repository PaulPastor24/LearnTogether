document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  const searchFilter = document.getElementById('searchFilter');

  if (!searchInput || !searchFilter) return;

  const tutors = document.querySelectorAll('.tutor');
  const requests = document.querySelectorAll('tbody tr');

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    const filter = searchFilter.value;

    tutors.forEach(t => {
      const name = t.dataset.name?.toLowerCase() || '';
      const specialization = t.dataset.specialization?.toLowerCase() || '';
      let show = false;

      if(filter === 'all') {
        show = name.includes(query) || specialization.includes(query);
      } else if(filter === 'tutor') {
        show = name.includes(query);
      } else if(filter === 'subject') {
        show = specialization.includes(query);
      }

      t.style.display = show ? 'flex' : 'none';
    });

    requests.forEach(r => {
      const tutor = r.dataset.tutor?.toLowerCase() || '';
      const subject = r.dataset.subject?.toLowerCase() || '';
      let show = false;

      if(filter === 'all') {
        show = tutor.includes(query) || subject.includes(query);
      } else if(filter === 'tutor') {
        show = tutor.includes(query);
      } else if(filter === 'subject') {
        show = subject.includes(query);
      }

      r.style.display = show ? '' : 'none';
    });
  });
});
