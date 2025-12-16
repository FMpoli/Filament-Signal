// Dynamic Node Bundle Loader
// Loads node bundles at runtime without requiring rebuild

export async function loadDynamicNodeBundles() {
    // Get bundles from Filament script data
    const bundles = window.filamentData?.dynamicNodeBundles || [];
    const loadedNodes = {};

    console.log('[DynamicLoader] Found bundles:', bundles);

    for (const bundle of bundles) {
        try {
            // Load the bundle script
            await loadScript(bundle.url);

            // The bundle should register itself globally
            let NodeComponent = window[bundle.globalName];

            // Handle default export (common in UMD/ESM interop)
            if (NodeComponent && NodeComponent.default) {
                NodeComponent = NodeComponent.default;
            }

            if (NodeComponent) {
                loadedNodes[bundle.type] = NodeComponent;
                console.log(`[DynamicLoader] ✓ Loaded: ${bundle.type} from ${bundle.url}`);
            } else {
                console.warn(`[DynamicLoader] ⚠ Bundle loaded but component not found: ${bundle.globalName}`);
            }
        } catch (error) {
            console.error(`[DynamicLoader] ✗ Failed to load ${bundle.type}:`, error);
        }
    }

    return loadedNodes;
}

function loadScript(url) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}
