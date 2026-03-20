const mobileMenuTrigger = document.getElementById('mobileMenu');
const mobileNav = document.getElementById('mobileNavOverlay');
const closeNav = document.getElementById('closeMobile');
const tabLinks = document.querySelectorAll('.profile-nav .nav-item');
const tabContents = document.querySelectorAll('.tab-content');
const avatarUpload = document.getElementById('avatarUpload');
const profileImage = document.getElementById('profileImage');
const profileName = document.getElementById('profileName');
const profileEmail = document.getElementById('profileEmail');
const successModal = document.getElementById('successModal');
const successMessage = document.getElementById('successMessage');
const errorModal = document.getElementById('errorModal');
const errorMessage = document.getElementById('errorMessage');
const passwordStrength = document.getElementById('passwordStrength');

function toggleMobileNav(show) {
    mobileNav.classList.toggle('active', show);
}

function activateTab(tabName) {
    tabLinks.forEach((link) => link.classList.toggle('active', link.dataset.tab === tabName));
    tabContents.forEach((content) => content.classList.toggle('active', content.id === `${tabName}-tab`));
}

function showSuccess(message) {
    successMessage.textContent = message;
    successModal.classList.add('active');
}

function showError(message) {
    errorMessage.textContent = message;
    errorModal.classList.add('active');
}

function closeModal() {
    successModal.classList.remove('active');
}

function closeErrorModal() {
    errorModal.classList.remove('active');
}

function clearFormErrors(form) {
    form.querySelectorAll('.error-message').forEach((node) => {
        node.textContent = '';
    });
    form.querySelectorAll('.form-group').forEach((node) => {
        node.classList.remove('error');
    });
}

function setFormError(form, inputId, message, errorId) {
    const input = form.querySelector(`#${inputId}`);
    const group = input.closest('.form-group');
    const error = form.querySelector(`#${errorId}`);
    group.classList.add('error');
    error.textContent = message;
}

mobileMenuTrigger?.addEventListener('click', () => toggleMobileNav(true));
closeNav?.addEventListener('click', () => toggleMobileNav(false));
mobileNav?.addEventListener('click', (event) => {
    if (event.target === mobileNav) {
        toggleMobileNav(false);
    }
});

tabLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        activateTab(link.dataset.tab);
    });
});

avatarUpload?.addEventListener('change', () => {
    const [file] = avatarUpload.files;
    if (!file) {
        return;
    }

    profileImage.src = URL.createObjectURL(file);
    showSuccess('Profile picture updated for this session.');
});

document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.target);
        const showPassword = target.type === 'password';
        target.type = showPassword ? 'text' : 'password';
        button.innerHTML = showPassword
            ? '<i class="fas fa-eye-slash"></i>'
            : '<i class="fas fa-eye"></i>';
    });
});

document.querySelectorAll('.profile-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearFormErrors(form);

        if (form.id === 'personalForm') {
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();

            if (!firstName) {
                setFormError(form, 'firstName', 'First name is required.', 'firstNameError');
                return;
            }

            if (!lastName) {
                setFormError(form, 'lastName', 'Last name is required.', 'lastNameError');
                return;
            }

            profileName.textContent = `${firstName} ${lastName}`;
        }

        if (form.id === 'contactForm') {
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phoneNumber').value.trim();

            if (!email) {
                setFormError(form, 'email', 'Email is required.', 'emailError');
                return;
            }

            if (!phone) {
                setFormError(form, 'phoneNumber', 'Phone number is required.', 'phoneError');
                return;
            }

            profileEmail.textContent = email;
        }

        if (form.id === 'passwordForm') {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!currentPassword) {
                setFormError(form, 'currentPassword', 'Current password is required.', 'currentPasswordError');
                return;
            }

            if (newPassword.length < 6) {
                setFormError(form, 'newPassword', 'New password must be at least 6 characters.', 'newPasswordError');
                return;
            }

            if (newPassword !== confirmPassword) {
                setFormError(form, 'confirmPassword', 'Passwords do not match.', 'confirmPasswordError');
                return;
            }
        }

        showSuccess('Changes saved successfully.');
        form.reset();
    });
});

document.getElementById('newPassword')?.addEventListener('input', (event) => {
    const length = event.target.value.length;
    passwordStrength.className = 'password-strength';

    if (!length) {
        passwordStrength.textContent = '';
        return;
    }

    if (length < 6) {
        passwordStrength.textContent = 'Weak password';
        passwordStrength.classList.add('weak');
        return;
    }

    if (length < 10) {
        passwordStrength.textContent = 'Medium password';
        passwordStrength.classList.add('medium');
        return;
    }

    passwordStrength.textContent = 'Strong password';
    passwordStrength.classList.add('strong');
});

document.querySelector('.logout-btn')?.addEventListener('click', () => {
    showError('Logout action is ready for backend integration.');
});

window.closeModal = closeModal;
window.closeErrorModal = closeErrorModal;
