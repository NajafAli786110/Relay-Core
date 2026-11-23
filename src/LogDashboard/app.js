import './app.scss';

import { createRoot, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ImportRow from './ImportRow';
import ImportLogs from './ImportLogs';

const ImportsTable = () => {
    const [userHistory, setUserHistory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedImportId, setSelectedImportId] = useState(null);

    useEffect(() => {
        const fetchUserHistory = async () => {
            try {
                const response = await apiFetch({ path: '/relay-core/v1/imports' });
                setUserHistory(response);
                setLoading(false);
            } catch (err) {
                setError(err.message);
                setLoading(false);
            }
        }
        fetchUserHistory();
    }, []);

    return (
        <div className="rc-dashboard">
            {loading ? (
                <div className="rc-card rc-state">
                    <span className="rc-spinner" aria-hidden="true"></span>
                    <p className="rc-state__text">Loading imports…</p>
                </div>
            ) : error ? (
                <div className="rc-notice rc-notice--error" role="alert">{error}</div>
            ) : userHistory.length > 0 ? (
                <>
                    <div className="rc-card rc-card--flush">
                        <div className="rc-card__header">
                            <h2 className="rc-card__title">
                                Recent imports
                                <span className="rc-count">{userHistory.length}</span>
                            </h2>
                        </div>
                        <div className="rc-table-wrap">
                            <table className="rc-table">
                                <thead>
                                    <tr>
                                        <th>Import ID</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th className="rc-num">Total</th>
                                        <th className="rc-num">Failed</th>
                                        <th className="rc-cell-action"><span className="screen-reader-text">Action</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {userHistory.map((importItem) => (
                                        <ImportRow
                                            key={importItem.id}
                                            importItem={importItem}
                                            isSelected={importItem.id === selectedImportId}
                                            setSelectedImportId={setSelectedImportId}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {
                        selectedImportId && (
                            <ImportLogs importId={selectedImportId} onClose={() => setSelectedImportId(null)} />
                        )
                    }
                </>
            ) : (
                <div className="rc-card rc-state">
                    <p className="rc-state__title">No imports yet</p>
                    <p className="rc-state__text">Imports you run from Bulk Import will show up here.</p>
                </div>
            )}
        </div>
    )
}

export default ImportsTable;

createRoot(document.getElementById('relay-user-history')).render(<ImportsTable />);
