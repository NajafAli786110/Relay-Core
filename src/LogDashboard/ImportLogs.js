import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const ImportLogs = ({ importId }) => {
    const [importData, setImportData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({ path: `relay-core/v1/imports/${importId}/logs` });
            setImportData(response);
            setLoading(false);
            setError(null);
        } catch (err) {
            setError(err.message);
            setLoading(false);
        }
    }
    useEffect(() => {
        fetchLogs();
    }, [importId]);

    return (
        <div>
            {loading ? (
                <p>Loading...</p>
            ) : error ? (
                <p>{error}</p>
            ) : importData && importData.items.length > 0 ? (
                <table style={{ border: '1px solid black', marginBottom: '10px', width: '100%' }}>
                    <thead>
                        <tr>
                            <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Field</th>
                            <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Message</th>
                            <th style={{ backgroundColor: 'black', color: 'white', padding: '10px' }}>Row Num</th>
                        </tr>
                    </thead>
                    <tbody>
                        {importData.items.map((log, index) => (
                            <tr key={index}>
                                <td style={{ border: '1px solid #000020', padding: '10px' }}>{log.field}</td>
                                <td style={{ border: '1px solid #000020', padding: '10px' }}>{log.message}</td>
                                <td style={{ border: '1px solid #000020', padding: '10px' }}>{log.row_num}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <p>No logs available for this import.</p>
            )}
        </div>
    )
}

export default ImportLogs;