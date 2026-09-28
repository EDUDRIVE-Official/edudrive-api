import { build } from 'esbuild';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
const result = await build({ entryPoints:['resources/standalone/intersection.js'], bundle:true, minify:true, format:'iife', target:'es2020', write:false, legalComments:'inline' });
const template = await readFile('resources/standalone/intersection.html','utf8');
const html = template.replace('/* INTERSECTION_BUNDLE */',()=>result.outputFiles[0].text.replaceAll('</script','<\\/script'));
await mkdir('public/animations', { recursive:true });
await writeFile('public/animations/interseccion-semaforizada.html',html);
console.log(`HTML independiente generado: ${Math.round(Buffer.byteLength(html)/1024)} KB. Sin descargas externas.`);
