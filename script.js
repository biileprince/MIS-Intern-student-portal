// Simple Login
document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.querySelector(".login-box form");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const email = loginForm.querySelector('input[type="email"]').value;
      const password = loginForm.querySelector('input[type="password"]').value;

      // Simple check
      if (email === "admin@email.com" && password === "admin123") {
        window.location.href = "dashboard.html";
      } else if (email === "prince@email.com" && password === "prince123") {
        window.location.href = "portal.html";
      } else {
        alert("Invalid email or password");
      }
    });
  }

  // Admin Dashboard Edit Modal
  const editButtons = document.querySelectorAll("table button:first-child");
  const modal = document.getElementById("editModal");
  const closeBtn = document.querySelector(".close-btn");

  if (editButtons && modal && closeBtn) {
    editButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        modal.style.display = "flex";
      });
    });

    closeBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  }

  // Student Portal Update Info
  const studentForm = document.querySelector(".student-info form");
  if (studentForm) {
    studentForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const phone = studentForm.querySelector('input[type="text"]').value;
      const address = studentForm.querySelectorAll('input[type="text"]')[1].value;

      alert("Info updated:\nPhone: " + phone + "\nAddress: " + address);
    });
  }

  // Logout
  const logoutBtns = document.querySelectorAll(".logout-btn");
  logoutBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      window.location.href = "index.html";
    });
  });
});
