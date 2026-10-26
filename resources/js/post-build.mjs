import { cp, rm } from 'node:fs/promises';

try {
    await rm('dist', { recursive: true, force: true });
    await cp('public', 'dist', {
        recursive: true,
        filter: (source) => !source.toLowerCase().endsWith('.php'),
    });
    console.log('Successfully synced public to dist for Vercel static serving.');
} catch (err) {
    console.error('Error copying public to dist:', err);
    process.exitCode = 1;
}
