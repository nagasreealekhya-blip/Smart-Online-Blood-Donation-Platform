const mobileMenuButton = document.getElementById('mobileMenu');
const mobileNavOverlay = document.getElementById('mobileNavOverlay');
const closeMobileButton = document.getElementById('closeMobile');
const contactForm = document.getElementById('contactForm');
const submitBtn = document.getElementById('submitBtn');
const mapOverlay = document.getElementById('mapOverlay');
const faqItems = document.querySelectorAll('.faq-item');
const newsletterForm = document.getElementById('newsletterForm');
const newsletterMessage = document.getElementById('newsletterMessage');
const successModal = document.getElementById('successModal');
const backToTop = document.getElementById('backToTop');
const emergencyBtn = document.getElementById('emergencyBtn');

function toggleMobileMenu(show) {
    mobileNavOverlay.classList.toggle('active', show);
}

function setFieldState(groupId, errorId, message) {
    const group = document.getElementById(groupId);
    const error = document.getElementById(errorId);
    group.classList.remove('success');
    error.textContent = message;

    if (message) {
        return false;
    }

    group.classList.add('success');
    return true;
}

function closeModal() {
    successModal.classList.remove('active');
}

mobileMenuButton?.addEventListener('click', () => toggleMobileMenu(true));
closeMobileButton?.addEventListener('click', () => toggleMobileMenu(false));
mobileNavOverlay?.addEventListener('click', (event) => {
    if (event.target === mobileNavOverlay) {
        toggleMobileMenu(false);
    }
});

mapOverlay?.addEventListener('click', () => {
    mapOverlay.style.display = 'none';
});

faqItems.forEach((item) => {
    const question = item.querySelector('.faq-question');
    question?.addEventListener('click', () => {
        item.classList.toggle('active');
    });
});

contactForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const nameOk = setFieldState('nameGroup', 'nameError', document.getElementById('name').value.trim() ? '' : 'Name is required.');
    const emailOk = setFieldState('emailGroup', 'emailError', document.getElementById('email').value.trim() ? '' : 'Email is required.');
    const phoneOk = setFieldState('phoneGroup', 'phoneError', document.getElementById('phone').value.trim() ? '' : 'Phone number is required.');
    const messageOk = setFieldState(
        'messageGroup',
        'messageError',
        document.getElementById('message').value.trim().length >= 10 ? '' : 'Message must be at least 10 characters.'
    );

    if (!(nameOk && emailOk && phoneOk && messageOk)) {
        return;
    }

    submitBtn.disabled = true;
    submitBtn.querySelector('.btn-text').style.display = 'none';
    submitBtn.querySelector('.btn-loader').style.display = 'inline-flex';

    window.setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.querySelector('.btn-text').style.display = 'inline-flex';
        submitBtn.querySelector('.btn-loader').style.display = 'none';
        contactForm.reset();
        successModal.classList.add('active');
    }, 900);
});

newsletterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    newsletterMessage.textContent = 'Subscription saved for this demo.';
    newsletterMessage.classList.add('success');
    newsletterForm.reset();
});

emergencyBtn?.addEventListener('click', () => {
    alert('Emergency request flow can be connected to blood request.html or your backend endpoint.');
});

window.addEventListener('scroll', () => {
    backToTop.classList.toggle('show', window.scrollY > 400);
});

backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
