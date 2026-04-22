import fs from 'fs';
import path from 'path';

const fileName = 'manifest.webmanifest';
const buildPath = path.resolve('public/build', fileName);
const publicPath = path.resolve('public', fileName);

if (fs.existsSync(buildPath)) {
    try {
        fs.copyFileSync(buildPath, publicPath);
        console.log(`successfully copied ${fileName} to public/`);
        fs.unlinkSync(buildPath);
        console.log(`removed original ${fileName} from public/build/`);
    } catch (err) {
        console.error(`failed to move manifest: ${err.message}`);
        process.exit(1);
    }
} else {
    // If not found in build/, check if it's already in public/ (maybe vite skipped generation?)
    if (fs.existsSync(publicPath)) {
        console.log(`manifest already exists in public/, skipping move.`);
    } else {
        console.warn(`Warning: ${buildPath} not found! Manifest might be missing.`);
        // Don't exit 1, maybe dev mode or other reason?
    }
}
