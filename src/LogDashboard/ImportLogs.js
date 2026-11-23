import apiFetch from '@wordpress/api-fetch';
import { createPortal, useEffect, useRef, useState } from '@wordpress/element';

const ImportLogs = ({ importId, onClose }) => {
    const [importData, setImportData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchLogs = async ( page ) => {
        setLoading(true);
        try {
            const response = await apiFetch({ path: `relay-core/v1/imports/${importId}/logs?page=${page}` });
            setImportData(response);
            setLoading(false);
            setError(null);
        } catch (err) {
            setError(err.message);
            setLoading(false);
        }
    }
    useEffect(() => {
        fetchLogs(1);
    }, [importId]);

    function getPrevPage() {
        if (importData.page > 1) {
            fetchLogs(importData.page - 1);
        }
    }

    function getNextPage() {
        if (importData.page < Math.ceil(importData.total / importData.per_page)) {
            fetchLogs(importData.page + 1);
        }
    }

    const totalPages = importData ? Math.ceil(importData.total / importData.per_page) : 1;

    // Popup behaviour: focus the close button when it opens, close on Escape.
    const closeButtonRef = useRef(null);

    useEffect(() => {
        closeButtonRef.current.focus();

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', closeOnEscape);
        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [onClose]);

    // createPortal renders the popup at the end of <body>, outside the dashboard markup.
    return createPortal(
        <div className="rc-app rc-modal-backdrop" onClick={onClose}>
        <div className="rc-card rc-card--flush rc-modal" role="dialog" aria-modal="true" aria-labelledby="rc-logs-title" onClick={(event) => event.stopPropagation()}>
            <div className="rc-card__header">
                <div>
                    <h2 className="rc-card__title" id="rc-logs-title">
                        Failed rows
                        {importData && <span className="rc-count">{importData.total}</span>}
                    </h2>
                    <p className="rc-card__subtitle">
                        Import <code className="rc-id" title={importId}>{importId}</code>
                    </p>
                </div>
                <button type="button" className="rc-icon-button" ref={closeButtonRef} onClick={onClose} aria-label="Close failed rows">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            {loading ? (
                <div className="rc-state">
                    <span className="rc-spinner" aria-hidden="true"></span>
                    <p className="rc-state__text">Loading failed rows…</p>
                </div>
            ) : error ? (
                <div className="rc-state">
                    <p className="rc-text-error" role="alert">{error}</p>
                </div>
            ) : importData && importData.items.length > 0 ? (
                <>
                    <div className="rc-table-wrap">
                        <table className="rc-table">
                            <thead>
                                <tr>
                                    <th className="rc-num">Row</th>
                                    <th>Field</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                {importData.items.map((log, index) => (
                                    <tr key={index}>
                                        <td className="rc-num rc-muted">{log.row_num}</td>
                                        <td><code className="rc-field-name">{log.field}</code></td>
                                        <td>{log.message}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {
                        importData.total > 20 && (
                            <div className="rc-pagination">
                                <button type="button" className="button" disabled={importData.page <= 1} onClick={() => getPrevPage()}>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                    Previous
                                </button>
                                <p className="rc-pagination__info">
                                    Page <strong>{importData.page}</strong> of <strong>{totalPages}</strong>
                                </p>
                                <button type="button" className="button" disabled={importData.page >= totalPages} onClick={() => getNextPage()}>
                                    Next
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </button>
                            </div>
                        )
                    }
                </>
            ) : (
                <div className="rc-state">
                    <p className="rc-state__text">No failed rows for this import.</p>
                </div>
            )}
        </div>
        </div>,
        document.body
    )
}

export default ImportLogs;
