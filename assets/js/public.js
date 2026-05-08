// Hero slider
document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.slide');
    if (!slides.length) return;

    let current = 0;
    setInterval(() => {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, 3000);
});

// Registration success popup
function closePopup() {
    const modal = document.getElementById('modal');
    const popup = document.getElementById('popup');
    if (modal) modal.style.display = 'none';
    if (popup) popup.style.display = 'none';
}

const modal = document.getElementById('modal');
if (modal) {
    modal.addEventListener('click', closePopup);
}
