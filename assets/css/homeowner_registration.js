document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('regForm');
  const submitBtn = document.getElementById('submitBtn');
  const resetBtn = document.getElementById('resetBtn');
  const successEl = document.getElementById('success');
  const errorEl = document.getElementById('error');

  resetBtn.addEventListener('click', () => form.reset());

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    successEl.style.display = 'none';
    errorEl.style.display = 'none';
    submitBtn.disabled = true;

    const fd = new FormData(form);
    try {
      const res = await fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' });
      const json = await res.json();
      if (json.success) {
        successEl.textContent = json.message;
        successEl.style.display = 'block';
        setTimeout(() => window.location.reload(), 1500);
      } else {
        errorEl.textContent = json.message || 'Registration failed';
        errorEl.style.display = 'block';
      }
    } catch (err) {
      errorEl.textContent = 'Request failed — please try again';
      errorEl.style.display = 'block';
      console.error(err);
    } finally {
      submitBtn.disabled = false;
    }
  });
});
