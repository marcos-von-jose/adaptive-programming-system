// Add accessible column labels so data tables can become readable cards on
// narrow screens without duplicating labels throughout each Blade view.
document.querySelectorAll('.table-wrap table:not(.user-management-table)').forEach((table) => {
    const headers = [...table.querySelectorAll('thead th')].map((header) => header.textContent.trim());
    table.classList.add('responsive-card-table');

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cells = [...row.querySelectorAll(':scope > td')];

        if (cells.length === 1 && Number(cells[0].colSpan) > 1) {
            row.classList.add('responsive-table-empty');
            return;
        }

        cells.forEach((cell, index) => {
            const isActionCell = cell.querySelector('.actions, a.button, button, form');
            const label = headers[index] || (isActionCell ? 'Actions' : 'Details');

            cell.dataset.label = label;
            if (isActionCell) cell.classList.add('responsive-table-actions');
        });
    });
});
