const resetButton = document.getElementById('addreset');

resetButton.addEventListener('click', event => {
    event.preventDefault();

    const confirmed = confirm('Clear all fields?');
    if (confirmed) {
        const rows = document.querySelectorAll('tr.addrow');

        let rowCount = 1;
        rows.forEach(row => {
            if (rowCount > 2) {
                row.remove();
            } else {
                for (const input of row.getElementsByTagName('input')) {
                    input.value = '';
                }
            }
            ++rowCount;
        });
    }
});
