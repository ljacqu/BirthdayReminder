const allRows = document.querySelectorAll('.addrow');
var lastRow = allRows[allRows.length - 1];

var inputs = lastRow.querySelectorAll('input');
inputs.forEach(function (input) {
    input.addEventListener('input', handleInputChange);
});

function handleInputChange(event) {
    if (event.target.value.trim() !== '') {
        addNewRow();
    }
}

function addNewRow() {
    const newRow = lastRow.cloneNode(true);
    lastRow.after(newRow);

    const newInputs = newRow.querySelectorAll('input');
    newInputs.forEach(function (input) {
        input.value = '';
    });

    lastRow = newRow;
    inputs.forEach(function (input) {
        input.removeEventListener('input', handleInputChange);
    });
    inputs = newInputs;
    inputs.forEach(function (input) {
        input.addEventListener('input', handleInputChange);
    });
}
