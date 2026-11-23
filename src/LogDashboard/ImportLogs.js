import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const ImportLogs = ({ importId }) => {
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
            console.log('Fetched logs for import ID:', response);
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

    return (
        <div>
            {loading ? (
                <p>Loading...</p>
            ) : error ? (
                <p>{error}</p>
            ) : importData && importData.items.length > 0 ? (
                <>
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
                    {
                        importData.total > 20 && (
                            <div className='pagination'>
                                <button type='button' onClick={() => getPrevPage()}>
                                    {/* Prev Icon Code */}
                                    <svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-label="Previous">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                    Previous
                                </button>
                                <p>Page {importData.page} out of {Math.ceil(importData.total / importData.per_page)}</p>
                                <button type='button' onClick={() => getNextPage()}>
                                    {/* Next Icon Code */}
                                    <svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                    Next
                                </button>
                            </div>
                        )
                    }
                </>
            ) : (
                <p>No logs available for this import.</p>
            )}
        </div>
    )
}

export default ImportLogs;