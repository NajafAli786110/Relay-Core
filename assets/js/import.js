async function checkStatus() {
    const progressText = document.getElementById('relay-core-progress');
    const progressBar = document.getElementById('relay-core-progress-bar');
    const progressCard = document.getElementById('relay-core-progress-card');
    let result;

    const body = new URLSearchParams({
        action: 'relay_core_import_status',
        nonce: relayCoreImport.nonce,
        import_id: relayCoreImport.importId
    });

    try {
        const response = await fetch(relayCoreImport.ajaxUrl, {
            method: 'POST',
            body,
        });

        result = await response.json();
    } catch (error) {
        progressText.textContent = 'Import stopped. Please reload the page and try again.';
        return;
    }

    if (!result || true !== result.success) {
        const message = result && result.data && result.data.message;
        progressText.textContent = message || 'Import stopped. Please reload the page and try again.';
        return;
    }

    if (result.data.status === "running") {
        const { offset, total } = result.data;
        const percent = total > 0 ? Math.round(offset / total * 100) : 0;
        progressBar.style.width = `${percent}%`;

        if ( offset == 0 ) {
            progressText.textContent = `Queued, waiting to start…`
        } else {
            progressText.textContent = `${offset} / ${total} rows (${percent}%)`;
        }
        setTimeout(checkStatus, 3000);
    } else if (result.data.status === "failed") {
        const error = result.data.error ? result.data.error : 'Error but it is not showing!';
        progressText.textContent = `${error}`;
        progressCard.classList.add('is-failed');
    } else if (result.data.status === "pending") {
        progressText.textContent = `Queued, waiting to start…`;
    } else if (result.data.status === "done") {
        progressText.textContent = `Import complete. ${result.data.offset} rows processed. Created: ${result.data.created}, Updated: ${result.data.updated}, Failed: ${result.data.failed}`;
        progressBar.style.width = '100%';
        progressCard.classList.add('is-done');
    } else {
        progressText.textContent = `Something else!`;
    }

    if (result.data.status === "done" && Array.isArray(result.data.errors)) {
        const errorList = result.data.errors;

        const ul = document.createElement("ul");
        ul.className = "rc-error-list";

        if (errorList.length !== 0) {
            errorList.forEach((item) => {

                const rowNumber = item.row_num;
                const field = item.field;
                const message = item.message;
                const fieldText = field.trim() === '' ? '' : `(${field})`

                const li = document.createElement("li");
                li.textContent = `Row ${rowNumber}: ${message} ${fieldText}`;
                ul.appendChild(li);
            })

            progressText.after(ul);
        }
    }
}

checkStatus();