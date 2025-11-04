#!/usr/bin/env node

/**
 * Compares the largest emitted Webpack chunk against a tracked baseline.
 * Intended to run in CI as a regression guard and locally when profiling.
 *
 * Usage:
 *   node scripts/check-bundle-size.js [statsPath] [--write-baseline]
 *
 * Environment:
 *   BUNDLE_SIZE_TOLERANCE: optional number of bytes allowed over the baseline.
 */

const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');

const cliArgs = process.argv.slice(2);
const statsOption = cliArgs.find((arg) => arg.startsWith('--stats='));
const statsPathArg = statsOption
  ? statsOption.replace('--stats=', '')
  : cliArgs.find((arg) => !arg.startsWith('--'));
const writeBaseline = cliArgs.includes('--write-baseline');

const statsPath = statsPathArg
  ? path.resolve(projectRoot, statsPathArg)
  : path.join(projectRoot, 'var', 'webpack', 'stats.json');
const baselinePath = path.join(projectRoot, 'config', 'bundle-size-baseline.json');

const tolerance = Number(process.env.BUNDLE_SIZE_TOLERANCE || 0);

function loadJson(filePath) {
  if (!fs.existsSync(filePath)) {
    throw new Error(`Missing file: ${filePath}`);
  }

  const raw = fs.readFileSync(filePath, 'utf-8');
  return JSON.parse(raw);
}

function extractChunkSizes(stats) {
  if (!stats || typeof stats !== 'object') {
    return [];
  }

  const { chunks = [], assets = [] } = stats;

  const chunkSizes = chunks
    .map((chunk) => {
      if (chunk?.sizes && typeof chunk.sizes.javascript === 'number') {
        return chunk.sizes.javascript;
      }

      if (typeof chunk?.size === 'number') {
        return chunk.size;
      }

      return 0;
    })
    .filter((value) => Number.isFinite(value) && value > 0);

  if (chunkSizes.length > 0) {
    return chunkSizes;
  }

  // fallback: derive from assets list if chunk data is missing
  return assets
    .map((asset) => (typeof asset?.size === 'number' ? asset.size : 0))
    .filter((value) => Number.isFinite(value) && value > 0);
}

const stats = loadJson(statsPath);
const chunkSizes = extractChunkSizes(stats);

if (!chunkSizes.length) {
  throw new Error(`Unable to determine chunk sizes from stats file: ${statsPath}`);
}

const currentMaxChunk = Math.max(...chunkSizes);

let baseline;
try {
  baseline = loadJson(baselinePath);
} catch (error) {
  if (!writeBaseline) {
    throw new Error(
      `Baseline file missing at ${baselinePath}. Run with --write-baseline to initialise it.`
    );
  }
}

if (writeBaseline) {
  const content = {
    maxChunkSize: currentMaxChunk,
    updatedAt: new Date().toISOString()
  };
  fs.mkdirSync(path.dirname(baselinePath), { recursive: true });
  fs.writeFileSync(baselinePath, `${JSON.stringify(content, null, 2)}\n`, 'utf-8');
  console.log(`Bundle size baseline updated to ${currentMaxChunk} bytes.`);
  process.exit(0);
}

if (!baseline || typeof baseline.maxChunkSize !== 'number') {
  throw new Error(`Baseline file (${baselinePath}) is malformed: expected { "maxChunkSize": number }.`);
}

const allowed = baseline.maxChunkSize + (Number.isFinite(tolerance) ? tolerance : 0);

if (currentMaxChunk > allowed) {
  const delta = currentMaxChunk - baseline.maxChunkSize;
  console.error(
    `Largest chunk grew by ${delta} bytes (baseline ${baseline.maxChunkSize} -> current ${currentMaxChunk}).`
  );
  console.error(
    'Update the baseline intentionally with "--write-baseline" after reviewing the bundle diff.'
  );
  process.exit(1);
}

console.log(
  `Largest chunk size OK: ${currentMaxChunk} bytes (baseline ${baseline.maxChunkSize}, tolerance ${tolerance}).`
);
