import test from 'node:test';
import assert from 'node:assert/strict';
import { notificationCount, formatClock } from '../src/lib/home.ts';

test('notification counts do not turn missing or malformed API values into NaN or false zero', () => {
    assert.equal(notificationCount({ count: 3 }.count), 3);
    assert.equal(notificationCount(0), 0);
    for (const value of [undefined, null, NaN, Infinity, -1, '3']) assert.equal(notificationCount(value), null);
});
test('attendance times omit seconds and never invent a shift time', () => {
    assert.equal(formatClock('07:00:00'), '07:00');
    assert.equal(formatClock('00:05'), '00:05');
    for (const value of [null, undefined, '', '25:00', '12:99']) assert.equal(formatClock(value), '—');
});
