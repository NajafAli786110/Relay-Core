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
        <div>
            {loading ? (
                <p>Loading...</p>
            ) : error ? (
                <p>{error}</p>
            ) : userHistory.length > 0 ? (
                <div>
                    <table style={{ border: '1px solid black', marginBottom: '10px', width: '100%' }}>
                        <thead>
                            <tr>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Import ID</th>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Status</th>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Created At</th>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Total</th>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Failed</th>
                                <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {userHistory.map((importItem) => (
                                <ImportRow key={importItem.id} importItem={importItem} setSelectedImportId={setSelectedImportId} />
                            ))}
                        </tbody>
                    </table>
                    {
                        selectedImportId && (
                            <>
                                <ImportLogs importId={selectedImportId} />
                                <button type='button' onClick={() => setSelectedImportId(null)} style={{ marginBottom: '10px', padding: '10px', backgroundColor: 'black', color: 'white', border: 'none', cursor: 'pointer' }}>
                                    X
                                </button>
                            </>
                        )
                    }
                </div>
            ) : (
                <p>No user history available.</p>
            )}
        </div>
    )
}

export default ImportsTable;

createRoot(document.getElementById('relay-user-history')).render(<ImportsTable />); 