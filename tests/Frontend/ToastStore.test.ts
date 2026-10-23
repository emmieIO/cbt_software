import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
import { toastStore } from '../../resources/js/stores/toast.ts';

beforeEach(() => {
    toastStore.items.splice(0);
});

test('deduplicates visible notifications without suppressing later actions', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    toastStore.add('Question generation has been queued.');
    toastStore.add('Question generation has been queued.');
    assert.equal(toastStore.items.length, 1);
    context.mock.timers.tick(5000);
    assert.equal(toastStore.items.length, 0);
    toastStore.add('Question generation has been queued.');
    assert.equal(toastStore.items.length, 1);
});

test('uses distinct IDs and dismisses only the requested notification', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    toastStore.add('Saved');
    toastStore.add('Unable to save', 'error');
    const [error, success] = toastStore.items;
    assert.notEqual(error.id, success.id);
    toastStore.remove(error.id);
    assert.equal(toastStore.items[0].message, 'Saved');
    context.mock.timers.tick(5000);
    assert.equal(toastStore.items.length, 0);
});

test('limits stacking to three notifications and distinguishes severity', (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    toastStore.add('Same message');
    toastStore.add('Same message', 'error');
    assert.equal(toastStore.items.length, 2);
    toastStore.add('Third');
    toastStore.add('Fourth');
    assert.equal(toastStore.items.length, 3);
    assert.equal(toastStore.items[0].message, 'Fourth');
    context.mock.timers.tick(5000);
    assert.equal(toastStore.items.length, 0);
});
