document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.querySelector('.search input');
  if (!searchInput) return;

  const sessionBlocks = document.querySelectorAll('.session-block');

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();

    sessionBlocks.forEach(block => {
      const subject = block.querySelector('.session-subject')?.textContent.toLowerCase() || '';
      const student = block.querySelector('.session-student')?.textContent.toLowerCase() || '';

      block.style.display = (subject.includes(query) || student.includes(query)) ? 'block' : 'none';
    });

    document.querySelectorAll('.slot-cell').forEach(cell => {
      const blocks = cell.querySelectorAll('.session-block');
      const anyVisible = Array.from(blocks).some(b => b.style.display !== 'none');
      cell.style.display = anyVisible ? '' : 'none';
    });
  });
});
