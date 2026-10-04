import { mkdir, readFile, writeFile, copyFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const packageDirectory = dirname(require.resolve('zxcvbn/package.json'));
const projectDirectory = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const outputDirectory = resolve(projectDirectory, 'public/js/vendor');
const source = await readFile(resolve(packageDirectory, 'dist/zxcvbn.js'), 'utf8');
const bundle = source.replace(/\s*\/\/# sourceMappingURL=.*$/m, '');

await mkdir(outputDirectory, { recursive: true });
await writeFile(resolve(outputDirectory, 'zxcvbn.js'), '/*! zxcvbn 4.4.2 | MIT | License: zxcvbn-LICENSE.txt */\n' + bundle);
await copyFile(resolve(packageDirectory, 'LICENSE.txt'), resolve(outputDirectory, 'zxcvbn-LICENSE.txt'));
console.log('Built local zxcvbn browser asset and license.');
