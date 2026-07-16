import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.dirname(scriptDirectory);
const templatesRoot = path.join(repositoryRoot, 'templates');

const requiredPages = [
  'pages/index.html',
  'pages/page.html',
  'pages/article.html',
  'pages/article-list.html',
  'pages/product.html',
  'pages/product-list.html',
  'pages/search.html',
  'pages/contact.html',
  'pages/order.html',
  'pages/cart.html',
  'pages/404.html'
];
const requiredPartials = ['partials/header.html', 'partials/footer.html'];

function isPlainObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function isNonEmptyString(value) {
  return typeof value === 'string' && value.trim().length > 0;
}

function relativePathIsSafe(relativePath) {
  if (!isNonEmptyString(relativePath) || path.isAbsolute(relativePath)) {
    return false;
  }

  const normalized = path.normalize(relativePath);
  return normalized !== '..' && !normalized.startsWith(`..${path.sep}`);
}

function fileExists(templateRoot, relativePath) {
  if (!relativePathIsSafe(relativePath)) {
    return false;
  }

  return fs.statSync(path.join(templateRoot, relativePath), { throwIfNoEntry: false })?.isFile() === true;
}

function directoryExists(templateRoot, relativePath) {
  return fs.statSync(path.join(templateRoot, relativePath), { throwIfNoEntry: false })?.isDirectory() === true;
}

function readJson(filePath) {
  try {
    return { value: JSON.parse(fs.readFileSync(filePath, 'utf8')) };
  } catch (error) {
    return { error: error instanceof Error ? error.message : String(error) };
  }
}

function validateEditableRegions(templateRoot, errors) {
  const regionsPath = path.join(templateRoot, 'editable-regions.json');
  if (!fs.statSync(regionsPath, { throwIfNoEntry: false })?.isFile()) {
    errors.push('missing editable-regions.json');
    return;
  }

  const result = readJson(regionsPath);
  if (result.error) {
    errors.push(`invalid editable-regions.json: ${result.error}`);
    return;
  }

  if (!isPlainObject(result.value) || !Array.isArray(result.value.regions)) {
    errors.push('editable-regions.json must contain a regions array');
    return;
  }

  result.value.regions.forEach((region, index) => {
    const label = `editable region #${index + 1}`;
    if (!isPlainObject(region)) {
      errors.push(`${label} must be an object`);
      return;
    }

    if (!isNonEmptyString(region.id)) {
      errors.push(`${label} is missing id`);
    }

    if (!isNonEmptyString(region.source_file)) {
      errors.push(`${label} is missing source_file`);
    } else if (!fileExists(templateRoot, region.source_file)) {
      errors.push(`${label} source_file does not exist: ${region.source_file}`);
    }

    if (!Array.isArray(region.selectors) || region.selectors.length === 0) {
      errors.push(`${label} selectors must be a non-empty array`);
    } else if (region.selectors.some((selector) => !isNonEmptyString(selector))) {
      errors.push(`${label} selectors must contain non-empty strings`);
    }
  });
}

function validateTemplate(templateDirectory) {
  const templateRoot = path.join(templatesRoot, templateDirectory);
  const errors = [];
  const templateJsonPath = path.join(templateRoot, 'template.json');
  const metadataResult = readJson(templateJsonPath);

  if (metadataResult.error) {
    errors.push(`invalid template.json: ${metadataResult.error}`);
    return { key: templateDirectory, errors };
  }

  const metadata = metadataResult.value;
  const key = isPlainObject(metadata) && isNonEmptyString(metadata.key)
    ? metadata.key.trim()
    : templateDirectory;

  if (!isPlainObject(metadata)) {
    errors.push('template.json must contain a JSON object');
  } else {
    if (!isNonEmptyString(metadata.key)) {
      errors.push('template.json is missing key');
    }
    if (!isNonEmptyString(metadata.entry)) {
      errors.push('template.json is missing entry');
    } else if (!fileExists(templateRoot, metadata.entry)) {
      errors.push(`entry does not exist: ${metadata.entry}`);
    }
  }

  for (const relativePath of requiredPages) {
    if (!fileExists(templateRoot, relativePath)) {
      errors.push(`missing required page: ${relativePath}`);
    }
  }
  for (const relativePath of requiredPartials) {
    if (!fileExists(templateRoot, relativePath)) {
      errors.push(`missing required partial: ${relativePath}`);
    }
  }

  if (isPlainObject(metadata) && metadata.clone_mode === 'static_mirror') {
    if (!isNonEmptyString(metadata.mirror_entry)) {
      errors.push('static_mirror template is missing mirror_entry');
    } else if (!metadata.mirror_entry.startsWith(`mirror${path.sep}`) && !metadata.mirror_entry.startsWith('mirror/')) {
      errors.push(`mirror_entry must be inside mirror/: ${metadata.mirror_entry}`);
    } else if (!fileExists(templateRoot, metadata.mirror_entry)) {
      errors.push(`mirror_entry does not exist: ${metadata.mirror_entry}`);
    }

    if (!directoryExists(templateRoot, 'mirror')) {
      errors.push('static_mirror template is missing mirror directory');
    }

    validateEditableRegions(templateRoot, errors);
  }

  return { key, errors };
}

function findTemplateDirectories() {
  if (!directoryExists(repositoryRoot, 'templates')) {
    return [];
  }

  return fs.readdirSync(templatesRoot, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .filter((entry) => fs.statSync(path.join(templatesRoot, entry.name, 'template.json'), { throwIfNoEntry: false } )?.isFile())
    .map((entry) => entry.name)
    .sort();
}

const templateDirectories = findTemplateDirectories();
const results = templateDirectories.map(validateTemplate);
if (results.length === 0) {
  console.error('No templates/*/template.json found.');
}

let failedCount = 0;
for (const result of results) {
  if (result.errors.length === 0) {
    console.log(`${result.key}: PASS`);
    continue;
  }

  failedCount += 1;
  console.log(`${result.key}: FAIL`);
  for (const error of result.errors) {
    console.log(`  - ${error}`);
  }
}

const exitCode = results.length === 0 || failedCount > 0 ? 1 : 0;
console.log(`Summary: ${results.length - failedCount} passed, ${failedCount} failed`);
console.log(`Exit code: ${exitCode}`);
process.exitCode = exitCode;
