#!/usr/bin/env node

/**
 * Runs webpack-bundle-analyzer in static mode against a stats file.
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');
const args = process.argv.slice(2);

const statsFile = path.resolve(projectRoot, args[0] || 'var/webpack/stats.json');
const reportFile = path.resolve(projectRoot, args[1] || 'var/webpack/bundle-report.html');
const bundleDir = path.resolve(projectRoot, args[2] || path.join('public', 'dist'));

if (!fs.existsSync(statsFile)) {
  throw new Error(`Stats file not found at ${statsFile}. Run "npm run bundle:stats" first.`);
}

fs.mkdirSync(path.dirname(reportFile), { recursive: true });

if (!fs.existsSync(bundleDir)) {
  throw new Error(`Bundle directory not found at ${bundleDir}. Ensure the Webpack build has been generated.`);
}

let analyzerCli;
try {
  analyzerCli = require.resolve('webpack-bundle-analyzer/lib/bin/analyzer.js', { paths: [projectRoot] });
} catch (error) {
  console.error('Unable to resolve webpack-bundle-analyzer. Install dependencies with npm install.');
  process.exitCode = 1;
  throw error;
}

const analyzerArgs = [
  analyzerCli,
  statsFile,
  bundleDir,
  '--mode',
  'static',
  '--report',
  reportFile,
  '--no-open',
  '--log-level',
  'silent'
];

const result = spawnSync(process.execPath, analyzerArgs, {
  cwd: projectRoot,
  stdio: 'inherit'
});

if (result.error) {
  throw result.error;
}

if (typeof result.status === 'number' && result.status !== 0) {
  process.exit(result.status);
}

console.log(`Bundle analysis report written to ${path.relative(projectRoot, reportFile)}`);
