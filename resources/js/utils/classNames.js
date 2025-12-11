/**
 * Utility function per combinare classi Tailwind
 * Permette a Tailwind di scansionare correttamente le classi anche quando sono dinamiche
 */
export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}
