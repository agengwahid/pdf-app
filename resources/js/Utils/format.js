/**
 * Format bytes to human readable format (KB, MB, GB).
 */
export function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Trigger file download programmatically.
 */
export function triggerDownload(url, filename = '') {
    const link = document.createElement('a');
    link.href = url;
    if (filename) {
        link.setAttribute('download', filename);
    }
    document.body.appendChild(link);
    link.click();
    link.remove();
}

/**
 * Format relative date/time.
 */
export function formatDateTime(isoString) {
    if (!isoString) return '';
    const date = new Date(isoString);
    return date.toLocaleString('id-ID', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}
