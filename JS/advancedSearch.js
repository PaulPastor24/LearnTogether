/**
 * LearnTogether Advanced Search System
 * Handles real-time search across tutors, learners, and subjects
 */

class SearchSystem {
  constructor() {
    this.searchInput = document.getElementById('searchInput');
    this.searchFilter = document.getElementById('searchFilter');
    this.clearSearch = document.getElementById('clearSearch');
    this.noResults = document.getElementById('noResults');
    this.subjectsGrid = document.querySelector('.subjects-grid');
    
    if (!this.searchInput) return;
    
    this.init();
  }

  init() {
    this.attachEventListeners();
    this.applyStyles();
  }

  attachEventListeners() {
    // Real-time search with debounce
    this.searchInput.addEventListener('input', () => this.debounceSearch(300));
    
    // Filter change
    if (this.searchFilter) {
      this.searchFilter.addEventListener('change', () => this.performSearch());
    }
    
    // Clear search
    if (this.clearSearch) {
      this.clearSearch.addEventListener('click', () => this.clearAll());
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        this.searchInput.focus();
      }
    });
  }

  applyStyles() {
    // Add hover effects to search elements
    this.searchInput.addEventListener('focus', function() {
      this.style.borderColor = 'rgba(16, 185, 129, 0.5)';
      this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
    });

    this.searchInput.addEventListener('blur', function() {
      this.style.borderColor = 'rgba(16, 185, 129, 0.2)';
      this.style.boxShadow = 'none';
    });

    if (this.clearSearch) {
      this.clearSearch.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 8px 24px rgba(16, 185, 129, 0.4)';
      });

      this.clearSearch.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.3)';
      });
    }
  }

  debounceSearch(delay) {
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => this.performSearch(), delay);
  }

  async performSearch() {
    const query = this.searchInput.value.toLowerCase().trim();
    const filter = this.searchFilter ? this.searchFilter.value : 'all';
    
    // Local search (already displayed tutors)
    this.localSearch(query, filter);
    
    // If empty query, show all
    if (!query) {
      this.showAll();
      return;
    }
  }

  localSearch(query, filter) {
    const tutors = document.querySelectorAll('.subject-card');
    let visibleCount = 0;

    tutors.forEach(tutor => {
      const name = tutor.querySelector('.subject-title')?.textContent.toLowerCase() || '';
      const subjects = tutor.querySelector('.topics')?.textContent.toLowerCase() || '';
      const fullText = `${name} ${subjects}`;

      let shouldShow = false;

      if (filter === 'all') {
        shouldShow = fullText.includes(query);
      } else if (filter === 'name') {
        shouldShow = name.includes(query);
      } else if (filter === 'subject') {
        shouldShow = subjects.includes(query);
      }

      tutor.style.display = shouldShow ? '' : 'none';
      tutor.style.animation = shouldShow ? 'fadeIn 0.3s ease' : 'none';
      
      if (shouldShow) visibleCount++;
    });

    // Show/hide no results message
    if (this.noResults) {
      this.noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }
  }

  showAll() {
    const tutors = document.querySelectorAll('.subject-card');
    tutors.forEach(tutor => {
      tutor.style.display = '';
    });
    if (this.noResults) {
      this.noResults.style.display = 'none';
    }
  }

  clearAll() {
    this.searchInput.value = '';
    if (this.searchFilter) {
      this.searchFilter.value = 'all';
    }
    this.showAll();
    this.searchInput.focus();
  }
}

// Initialize search on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  new SearchSystem();

  // Add fade-in animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .subject-card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
  `;
  document.head.appendChild(style);
});
