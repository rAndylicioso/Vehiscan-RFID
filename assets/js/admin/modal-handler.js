function openEditModal(id) {
  const modal = document.getElementById("editModal");
  const body = document.getElementById("modal-body");
  modal.style.display = "flex";
  body.innerHTML = "<p>Loading...</p>";

  fetch(`homeowner_edit.php?id=${id}&ajax=1`)
    .then(r => r.text())
    .then(html => {
      body.innerHTML = html;
      bindEditForm();
    })
    .catch(() => (body.innerHTML = "<p>Failed to load form.</p>"));
}

function closeEditModal() {
  const modal = document.getElementById("editModal");
  modal.style.display = "none";
  document.getElementById("modal-body").innerHTML = "";
}

function bindEditForm() {
  const form = document.querySelector("#modal-body form");
  if (!form) return;

  form.addEventListener("submit", e => {
    e.preventDefault();
    const data = new FormData(form);
    data.set("csrf", CSRF_TOKEN);

    fetch("homeowner_edit.php", { method: "POST", body: data })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: json.message || 'Saved successfully',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
          closeEditModal();
          setTimeout(refreshHomeownersTable, 500);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: json.message || 'Save failed',
            confirmButtonColor: '#6b7280'
          });
        }
      })
      .catch(() => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Request failed',
          confirmButtonColor: '#6b7280'
        });
      });
  });
}
