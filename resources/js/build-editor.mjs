import { cp, mkdir } from 'node:fs/promises';
await mkdir('public/vendor', { recursive: true });
await mkdir('dist', { recursive: true });
await cp('node_modules/tinymce', 'public/vendor/tinymce', { recursive: true });
console.log('TinyMCE self-host assets copied.');
