/**
 * LearnTogether Calendar Search
 * Real-time search for calendar sessions
 */

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('.search input');
  if (!searchInput) return;

  const sessionBlocks = document.querySelectorAll('.session-block');

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

    sessionBlocks.forEach(block => {
      const subject = block.querySelector('.session-subject')?.textContent.toLowerCase() || '';
      const student = block.querySelector('.session-student')?.textContent.toLowerCase() || '';
      const fullText = `${subject} ${student}`;

      const show = fullText.includes(query);
      block.style.display = show ? 'block' : 'none';
      block.style.animation = show ? 'fadeIn 0.3s ease' : 'none';
    });

    // Show/hide cells with visible sessions
    document.querySelectorAll('.slot-cell').forEach(cell => {
      const blocks = cell.querySelectorAll('.session-block');
      const anyVisible = Array.from(blocks).some(b => b.style.display !== 'none');
      cell.style.display = anyVisible ? '' : 'none';
    });
  };

  const debouncedSearch = debounce(performSearch, 300);
  searchInput.addEventListener('input', debouncedSearch);

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
    
    .session-block, .slot-cell {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
  `;
  document.head.appendChild(style);
});

