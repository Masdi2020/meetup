export async function copyText(text: string): Promise<void> {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Try the fallback when clipboard access is denied.
        }
    }

    // shortcut: HTTP needs legacy copying; remove when HTTPS is required everywhere.
    const input = document.createElement('textarea');
    const previousFocus = document.activeElement;
    input.value = text;
    input.readOnly = true;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);

    try {
        input.focus();
        input.select();

        if (!document.execCommand('copy')) {
            throw new Error('Copy failed');
        }
    } finally {
        input.remove();
        if (previousFocus instanceof HTMLElement) {
            previousFocus.focus({ preventScroll: true });
        }
    }
}
