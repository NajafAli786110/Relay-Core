async function runBatch() {
    const progressText = document.getElementById('relay-core-progress');
    let result;

    const body = new URLSearchParams({
        action: 'relay_core_import_batch',
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
        progressText.textContent = `${offset} / ${total} rows (${percent}%)`;
        runBatch();
    } else {
        progressText.textContent = `Congratulations Imported Successfully! ${result.data.offset} rows done. Created: ${result.data.created}, Updated: ${result.data.updated}, Failed: ${result.data.failed}`;
    }
}

runBatch();