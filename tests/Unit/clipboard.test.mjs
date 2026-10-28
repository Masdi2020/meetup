import assert from 'node:assert/strict';
import { test } from 'node:test';
import { copyText } from '../../resources/js/lib/clipboard.ts';

function clipboardEnvironment(t, clipboard, result = true) {
    const originalNavigator = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
    const calls = [];
    class Element {
        focus() { calls.push('focus'); }
    }
    const input = {
        style: {},
        focus() { calls.push('input focus'); },
        select() { calls.push('select'); },
        remove() { calls.push('remove'); },
    };
    Object.defineProperty(globalThis, 'navigator', {
        configurable: true,
        value: { clipboard },
    });
    globalThis.HTMLElement = Element;
    globalThis.document = {
        activeElement: new Element(),
        createElement: () => input,
        body: { appendChild() { calls.push('append'); } },
        execCommand(command) { calls.push(command); return result; },
    };
    t.after(() => {
        if (originalNavigator) {
            Object.defineProperty(globalThis, 'navigator', originalNavigator);
        } else {
            delete globalThis.navigator;
        }
        delete globalThis.HTMLElement;
        delete globalThis.document;
    });
    return { input, calls };
}

test('copies through the Clipboard API without creating a temporary field', async (t) => {
    const copied = [];
    const { calls } = clipboardEnvironment(t, {
        writeText: async (value) => copied.push(value),
    });
    await copyText('test-password');
    assert.deepEqual(copied, ['test-password']);
    assert.deepEqual(calls, []);
});

test('copies without the Clipboard API and cleans up the password field', async (t) => {
    const { input, calls } = clipboardEnvironment(t, undefined);
    await copyText('test-password');
    assert.equal(input.value, 'test-password');
    assert.deepEqual(calls, ['append', 'input focus', 'select', 'copy', 'remove', 'focus']);
});

test('falls back when the Clipboard API rejects access', async (t) => {
    const { calls } = clipboardEnvironment(t, {
        writeText: async () => { throw new Error('Permission denied'); },
    });
    await copyText('test-password');
    assert.ok(calls.includes('copy'));
});

test('reports failure and cleans up when fallback copying fails', async (t) => {
    const { calls } = clipboardEnvironment(t, undefined, false);
    await assert.rejects(copyText('test-password'), /Copy failed/);
    assert.deepEqual(calls.slice(-2), ['remove', 'focus']);
});
