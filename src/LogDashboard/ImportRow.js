const ImportRow = ({ importItem, isSelected, setSelectedImportId }) => {
    const showImportLogs = (importId) => {
        setSelectedImportId(importId);
    }

    return (
        <tr className={isSelected ? 'is-selected' : ''}>
            <td>
                <code className="rc-id" title={importItem.id}>{importItem.id}</code>
            </td>
            <td>
                <span className={`rc-badge rc-badge--${importItem.status}`}>
                    {importItem.status.charAt(0).toUpperCase() + importItem.status.slice(1)}
                </span>
            </td>
            <td className="rc-muted">{new Date(importItem.created_at * 1000).toLocaleString()}</td>
            <td className="rc-num">{importItem.total}</td>
            <td className={`rc-num ${importItem.failed > 0 ? 'rc-text-error' : 'rc-muted'}`}>{importItem.failed}</td>
            <td className="rc-cell-action">
                <button type="button" className="button" disabled={importItem.failed === 0} onClick={() => showImportLogs(importItem.id)}>
                    View details
                </button>
            </td>
        </tr>
    )
}

export default ImportRow;
