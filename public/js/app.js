const modal = document.getElementById('modal');
const modalText = document.getElementById('modal-text');
const closeBtn = document.querySelector('.close');

document.querySelectorAll('.clickable').forEach(cell => {
    cell.addEventListener('click', () => {
        const fullText = cell.getAttribute('data-full');
        modalText.innerText = fullText;
        modal.style.display = 'flex';
    });
});

closeBtn.onclick = () => modal.style.display = 'none';

window.onclick = (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
};