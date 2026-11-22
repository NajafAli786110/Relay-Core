const ImportRow = ({ importItem, setSelectedImportId }) => {
    const showImportLogs = (importId) => {
        setSelectedImportId(importId);
    }

    return (
        <tr>
            <td style={{ padding: '10px', border: '1px solid #000020' }}>{importItem.id}</td>
            <td style={{ color: importItem.status === 'pending' ? 'orange' : importItem.status === 'done' ? 'green' : importItem.status === 'failed' ? 'red' : 'black', padding: '10px', border: '1px solid #000020' }}>{importItem.status.charAt(0).toUpperCase() + importItem.status.slice(1)}</td>
            <td style={{ padding: '10px', border: '1px solid #000020' }}>{new Date(importItem.created_at * 1000).toLocaleString()}</td>
            <td style={{ padding: '10px', border: '1px solid #000020' }}>{importItem.total}</td>
            <td style={{ color: importItem.failed > 0 ? 'red' : 'green', padding: '10px', border: '1px solid #000020' }}>{importItem.failed}</td>
            <td style={{ padding: '10px', border: '1px solid #000020' }}>
                <button type='button' disabled={importItem.failed === 0} onClick={() => showImportLogs(importItem.id)}>
                    View Details
                </button>
            </td>
        </tr>
    )
}

export default ImportRow;