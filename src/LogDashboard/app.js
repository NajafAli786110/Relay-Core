import { createRoot, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const ImportsTable = () => {
    const [userHistory, setUserHistory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

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
                <table style={{ border: '1px solid black', marginBottom: '10px', width: '100%' }}>
                    <thead>
                        <tr>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Import ID</th>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Status</th>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Created At</th>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Total</th>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Failed</th>
                            <th colSpan="2" style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {userHistory.map((importItem, index) => (
                            <tr key={index}>
                                <td colSpan="2" style={{ padding: '10px', border: '1px solid #000020' }}>{importItem.id}</td>
                                <td colSpan="2" style={{ color: importItem.status == 'pending' ? 'orange' : importItem.status == 'done' ? 'green' : importItem.status == 'failed' ? 'red' : 'black', padding: '10px', border: '1px solid #000020' }}>{importItem.status.charAt(0).toUpperCase() + importItem.status.slice(1)}</td>
                                <td colSpan="2" style={{ padding: '10px', border: '1px solid #000020' }}>{new Date(importItem.created_at * 1000).toLocaleString()}</td>
                                <td colSpan="2" style={{ padding: '10px', border: '1px solid #000020' }}>{importItem.total}</td>
                                <td colSpan="2" style={{ color: importItem.failed > 0 ? 'red' : 'green', padding: '10px', border: '1px solid #000020' }}>{importItem.failed}</td>
                                <td colSpan="2" style={{ padding: '10px', border: '1px solid #000020' }}>
                                    <button type='button' disabled={importItem.failed == 0}>View Details</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <p>No user history available.</p>
            )}
        </div>
    )
}

export default ImportsTable;

createRoot(document.getElementById('relay-user-history')).render(<ImportsTable />); 