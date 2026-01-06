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

  // Initialize input formatting
  initializeInputFormatting();
});

// Ghana Card formatting: GHA-724556-56
function formatGhanaCard(input) {
  // Remove all non-alphanumeric characters
  let value = input.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
  
  // Apply GHA-XXXXXX-XX format
  if (value.length <= 3) {
    input.value = value;
  } else if (value.length <= 9) {
    input.value = value.slice(0, 3) + '-' + value.slice(3);
  } else {
    input.value = value.slice(0, 3) + '-' + value.slice(3, 9) + '-' + value.slice(9, 11);
  }
}

// Address formatting: WP-3867-6327
function formatAddress(input) {
  // Remove all non-alphanumeric characters
  let value = input.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
  
  // Apply XX-XXXX-XXXX format
  if (value.length <= 2) {
    input.value = value;
  } else if (value.length <= 6) {
    input.value = value.slice(0, 2) + '-' + value.slice(2);
  } else {
    input.value = value.slice(0, 2) + '-' + value.slice(2, 6) + '-' + value.slice(6, 10);
  }
}

// Phone formatting: 0555902675
function formatPhone(input) {
  // Remove all non-numeric characters
  let value = input.value.replace(/[^0-9]/g, '');
  
  // Ensure it starts with 0 and limit to 10 digits
  if (value.length > 0 && value[0] !== '0') {
    value = '0' + value;
  }
  
  // Limit to 10 digits
  if (value.length > 10) {
    value = value.slice(0, 10);
  }
  
  input.value = value;
}

// Validation functions
function validateGhanaCard(value) {
  const ghanaCardPattern = /^GHA-\d{6}-\d{2}$/;
  return ghanaCardPattern.test(value);
}

function validateAddress(value) {
  const addressPattern = /^[A-Z]{2}-\d{4}-\d{4}$/;
  return addressPattern.test(value);
}

function validatePhone(value) {
  const phonePattern = /^0\d{9}$/;
  return phonePattern.test(value);
}

// Initialize input formatting
function initializeInputFormatting() {
  // Ghana Card inputs
  const ghanaCardInputs = document.querySelectorAll('input[name="ghana_card"]');
  ghanaCardInputs.forEach(input => {
    input.addEventListener('input', function() {
      formatGhanaCard(this);
    });
    
    input.addEventListener('blur', function() {
      if (this.value && !validateGhanaCard(this.value)) {
        showValidationError(this, 'Please enter a valid Ghana Card format: GHA-XXXXXX-XX');
      } else {
        clearValidationError(this);
      }
    });
    
    // Set placeholder
    input.placeholder = 'GHA-724556-56';
    input.maxLength = 13;
  });

  // Address inputs (looking for address inputs that might be Ghana postal codes)
  const addressInputs = document.querySelectorAll('input[name="address"]');
  addressInputs.forEach(input => {
    // Add a checkbox or radio to determine if it's a postal code format
    addPostalCodeOption(input);
  });

  // Phone inputs
  const phoneInputs = document.querySelectorAll('input[name="phone"]');
  phoneInputs.forEach(input => {
    input.addEventListener('input', function() {
      formatPhone(this);
    });
    
    input.addEventListener('blur', function() {
      if (this.value && !validatePhone(this.value)) {
        showValidationError(this, 'Please enter a valid phone number: 0XXXXXXXXX (10 digits starting with 0)');
      } else {
        clearValidationError(this);
      }
    });
    
    // Set placeholder
    input.placeholder = '0555902675';
    input.maxLength = 10;
    input.pattern = '0[0-9]{9}';
  });
}

// Add postal code option for address
function addPostalCodeOption(addressInput) {
  const container = addressInput.parentElement;
  
  // Check if postal code option already exists
  if (container.querySelector('.postal-code-option')) {
    return;
  }
  
  // Create postal code option
  const postalOption = document.createElement('div');
  postalOption.className = 'postal-code-option';
  postalOption.style.marginTop = '5px';
  
  const checkbox = document.createElement('input');
  checkbox.type = 'checkbox';
  checkbox.id = 'postal-code-' + Math.random().toString(36).substr(2, 9);
  checkbox.style.marginRight = '5px';
  
  const label = document.createElement('label');
  label.htmlFor = checkbox.id;
  label.textContent = 'Use Ghana postal code format (XX-XXXX-XXXX)';
  label.style.fontSize = '0.9em';
  label.style.color = '#666';
  
  postalOption.appendChild(checkbox);
  postalOption.appendChild(label);
  
  // Insert after the address input
  container.appendChild(postalOption);
  
  // Add event listener
  checkbox.addEventListener('change', function() {
    if (this.checked) {
      addressInput.placeholder = 'WP-3867-6327';
      addressInput.maxLength = 12;
      addressInput.addEventListener('input', function() {
        formatAddress(this);
      });
      
      addressInput.addEventListener('blur', function() {
        if (this.value && !validateAddress(this.value)) {
          showValidationError(this, 'Please enter a valid postal code format: XX-XXXX-XXXX');
        } else {
          clearValidationError(this);
        }
      });
    } else {
      addressInput.placeholder = 'Enter your address';
      addressInput.maxLength = '';
      // Remove event listeners (create new element to clear all listeners)
      const newInput = addressInput.cloneNode(true);
      addressInput.parentNode.replaceChild(newInput, addressInput);
      addressInput = newInput;
    }
  });
}

// Validation error display
function showValidationError(input, message) {
  clearValidationError(input);
  
  const errorDiv = document.createElement('div');
  errorDiv.className = 'validation-error';
  errorDiv.textContent = message;
  errorDiv.style.color = '#e74c3c';
  errorDiv.style.fontSize = '12px';
  errorDiv.style.marginTop = '5px';
  
  input.parentElement.appendChild(errorDiv);
  input.style.borderColor = '#e74c3c';
}

function clearValidationError(input) {
  const existingError = input.parentElement.querySelector('.validation-error');
  if (existingError) {
    existingError.remove();
  }
  input.style.borderColor = '';
}
