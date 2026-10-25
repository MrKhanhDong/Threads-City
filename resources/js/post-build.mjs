import { cp, mkdir } from 'node:fs/promises';

try {
    await cp('public', 'dist', { recursive: true });
    console.log('Successfully synced public to dist for Vercel static serving.');
} catch (err) {
    console.error('Error copying public to dist:', err);
}
