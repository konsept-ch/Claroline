#!/usr/bin/env node

/**
 * Generates Webpack profile stats in JSON format.
 * This is a thin wrapper around webpack-cli that ensures
 * stats are written to a predictable location with the
 * flags we expect for bundle analysis work.
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');

const args = process.argv.slice(2);

let statsFile = path.join(projectRoot, 'var', 'webpack', 'stats.json');

for (let index = 0; index < args.length; index += 1) {
  const token = args[index];

  if ((token === '--out' || token === '--output') && args[index + 1]) {
    statsFile = path.resolve(projectRoot, args[index + 1]);
    index += 1;
  }
}

fs.mkdirSync(path.dirname(statsFile), { recursive: true });

let webpackCli;
try {
  webpackCli = require.resolve('webpack-cli/bin/cli.js', { paths: [projectRoot] });
} catch (error) {
  console.error('Unable to resolve webpack-cli. Please ensure dependencies are installed with npm install.');
  process.exitCode = 1;
  throw error;
}

const env = { ...process.env };
const legacyProviderFlag = '--openssl-legacy-provider';

if (!env.NODE_OPTIONS) {
  env.NODE_OPTIONS = legacyProviderFlag;
} else if (!env.NODE_OPTIONS.includes(legacyProviderFlag)) {
  env.NODE_OPTIONS = `${env.NODE_OPTIONS} ${legacyProviderFlag}`.trim();
}

env.WEBPACK_BUNDLE_ANALYSIS = '1';

const webpackArgs = [
  webpackCli,
  '--config',
  'webpack.config.prod.js',
  '--bail',
  '--profile',
  '--json',
  statsFile
];

const result = spawnSync(process.execPath, webpackArgs, {
  cwd: projectRoot,
  stdio: 'inherit',
  env
});

if (result.error) {
  throw result.error;
}

if (typeof result.status === 'number' && result.status !== 0) {
  process.exit(result.status);
}

console.log(`Webpack stats written to ${path.relative(projectRoot, statsFile)}`);
