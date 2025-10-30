#!/usr/bin/env node

/**
 * Script de minification des assets JavaScript
 * Minifie tous les fichiers JS dans public/assets/
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');

// Configuration
const assetsDir = path.join(__dirname, 'public/assets');
const minifyOptions = {
    compress: {
        drop_console: false, // Garder les console.log pour debug
        passes: 2
    },
    mangle: true, // Raccourcir les noms de variables
    format: {
        comments: false // Supprimer les commentaires
    }
};

// Fonction pour minifier un fichier
async function minifyFile(filePath) {
    try {
        const code = fs.readFileSync(filePath, 'utf8');
        const result = await minify(code, minifyOptions);

        if (result.code) {
            fs.writeFileSync(filePath, result.code);
            const originalSize = code.length;
            const minifiedSize = result.code.length;
            const reduction = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
            console.log(`✓ ${path.basename(filePath)} - Réduit de ${reduction}% (${originalSize} → ${minifiedSize} bytes)`);
        }
    } catch (error) {
        console.error(`✗ Erreur avec ${filePath}:`, error.message);
    }
}

// Fonction récursive pour parcourir tous les fichiers JS
function walkDir(dir) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);

        if (stat.isDirectory()) {
            walkDir(filePath);
        } else if (file.endsWith('.js') && !file.endsWith('.min.js')) {
            minifyFile(filePath);
        }
    });
}

// Lancer la minification
console.log('🚀 Démarrage de la minification des fichiers JavaScript...\n');

if (fs.existsSync(assetsDir)) {
    walkDir(assetsDir);
    console.log('\n✅ Minification terminée !');
} else {
    console.error('❌ Le dossier public/assets/ n\'existe pas. Compilez d\'abord les assets avec asset-map:compile');
    process.exit(1);
}
