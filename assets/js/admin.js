// Toast notification
function showToast(message, type) {
    Toastify({
        text: message,
        duration: 3000,
        close: true,
        gravity: "top",
        position: "center",
        backgroundColor: type === "error" ? "#FF3030" : "#2F8C37",
        stopOnFocus: true
    }).showToast();
}

// Filter table rows by plan/branch
function initBranchFilter() {
    const buttons = document.querySelectorAll('.branch-filter button');
    if (!buttons.length) return;

    buttons.forEach(btn => {
        btn.addEventListener('click', function () {
            buttons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const branch = this.dataset.branch;
            document.querySelectorAll('#adherent-list tbody tr').forEach(row => {
                row.style.display = (branch === 'all' || row.dataset.branch === branch) ? '' : 'none';
            });
        });
    });
}

// Search filter
function initSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
            const name = row.cells[0]?.textContent.toLowerCase() ?? '';
            const id   = row.cells[1]?.textContent.toLowerCase() ?? '';
            row.style.display = (name.includes(q) || id.includes(q)) ? '' : 'none';
        });
    });
}

// Delete confirmation modal
function initDeleteModal() {
    const modal     = document.getElementById('deleteModal');
    if (!modal) return;

    let deleteId = null;

    window.confirmDelete = id => {
        deleteId = id;
        modal.style.display = 'flex';
    };

    modal.querySelector('.close').onclick           = () => modal.style.display = 'none';
    document.getElementById('cancelDeleteBtn').onclick  = () => modal.style.display = 'none';
    document.getElementById('confirmDeleteBtn').onclick = () => {
        if (deleteId) window.location.href = `/sport-club/actions/adherent_delete.php?id=${deleteId}`;
    };
    window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
}

// Init everything on page load
document.addEventListener('DOMContentLoaded', function () {
    initBranchFilter();
    initSearch('search', 'adherent-list');
    initDeleteModal();
});
