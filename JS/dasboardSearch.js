/**
 * LearnTogether Dashboard Search
 * Real-time search for learners and requests
 */

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  const searchFilter = document.getElementById('searchFilter');

  if (!searchInput) return;

  const learners = document.querySelectorAll('.learner-card');
  const requests = document.querySelectorAll('tbody tr');

  // Apply green theme styles
  searchInput.addEventListener('focus', function() {
    this.style.borderColor = 'rgba(16, 185, 129, 0.5)';
    this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
  });

  searchInput.addEventListener('blur', function() {
    this.style.borderColor = 'rgba(16, 185, 129, 0.2)';
    this.style.boxShadow = 'none';
  });

  const debounce = (func, delay) => {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), delay);
    };
  };

  const performSearch = () => {
    const query = searchInput.value.toLowerCase();
    const filter = searchFilter ? searchFilter.value : 'all';
    let visibleCount = 0;

    // Search learner cards
    learners.forEach(learner => {
      const name = learner.querySelector('.learner-name')?.textContent.toLowerCase() || '';
      const subjects = learner.querySelector('.learner-subject')?.textContent.toLowerCase() || '';

      let show = false;
      if (filter === 'all') {
        show = name.includes(query) || subjects.includes(query);
      } else if (filter === 'name') {
        show = name.includes(query);
      } else if (filter === 'subject') {
        show = subjects.includes(query);
      } else {
        show = true;
      }

      learner.style.display = show ? 'flex' : 'none';
      learner.style.animation = show ? 'fadeIn 0.3s ease' : 'none';
      if (show) visibleCount++;
    });

    // Search request rows
    requests.forEach(row => {
      const tutor = row.dataset.tutor?.toLowerCase() || '';
      const subject = row.dataset.subject?.toLowerCase() || '';
      const fullText = `${tutor} ${subject}`;

      let show = false;
      if (filter === 'all') {
        show = fullText.includes(query);
      } else if (filter === 'tutor') {
        show = tutor.includes(query);
      } else if (filter === 'subject') {
        show = subject.includes(query);
      } else {
        show = true;
      }

      row.style.display = show ? '' : 'none';
      row.style.animation = show ? 'fadeIn 0.3s ease' : 'none';
      if (show) visibleCount++;
    });
  };

  const debouncedSearch = debounce(performSearch, 300);

  searchInput.addEventListener('input', debouncedSearch);
  
  if (searchFilter) {
    searchFilter.addEventListener('change', performSearch);
  }

  // Keyboard shortcuts
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
      e.preventDefault();
      searchInput.focus();
    }
    if (e.key === 'Escape') {
      searchInput.value = '';
      performSearch();
    }
  });

  // Add fade-in animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .learner-card, tbody tr {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
  `;
  document.head.appendChild(style);
});

