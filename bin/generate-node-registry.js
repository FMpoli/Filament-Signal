#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const nodesDir = path.join(__dirname, '../src/Nodes');
const outputFile = path.join(__dirname, '../resources/js/nodeRegistry.js');

console.log('[NodeRegistry] Scanning for nodes in:', nodesDir);

// Scan for all node components
const nodes = [];

if (fs.existsSync(nodesDir)) {
    const nodeDirs = fs.readdirSync(nodesDir, { withFileTypes: true })
        .filter(dirent => dirent.isDirectory())
        .map(dirent => dirent.name);

    for (const nodeDir of nodeDirs) {
        const componentPath = path.join(nodesDir, nodeDir, 'components', `${nodeDir}.jsx`);

        if (fs.existsSync(componentPath)) {
            // Convert PascalCase to snake_case
            const nodeType = nodeDir
                .replace(/([A-Z])/g, '_$1')
                .toLowerCase()
                .replace(/^_/, '');

            nodes.push({
                name: nodeDir,
                type: nodeType,
                path: `../../src/Nodes/${nodeDir}/components/${nodeDir}`
            });

            console.log(`[NodeRegistry] Found: ${nodeType} (${nodeDir})`);
        }
    }
}

// Generate the registry file
const imports = nodes.map(node =>
    `import ${node.name} from '${node.path}';`
).join('\n');

const exports = nodes.map(node =>
    `    ${node.type}: ${node.name},`
).join('\n');

const content = `// Auto-generated node registry
// Generated at: ${new Date().toISOString()}
// Do not edit manually - this file is regenerated on build

${imports}

export const nodeRegistry = {
${exports}
};

export default nodeRegistry;
`;

fs.writeFileSync(outputFile, content, 'utf8');
console.log(`[NodeRegistry] Generated registry with ${nodes.length} nodes`);
console.log(`[NodeRegistry] Output: ${outputFile}`);
