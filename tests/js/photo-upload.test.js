import test from 'node:test';
import assert from 'node:assert/strict';
import { photoError } from '../../resources/js/photo-upload.js';

const MB = 1024 * 1024;
const photo = (name, size, type = 'image/jpeg') => ({ name, size, type });
const options = { extensions: ['jpg', 'jpeg', 'png'], maxMb: 10, maxFiles: 10, maxRequestBytes: 39 * MB };

test('photos up to the size limit are accepted', () => {
    assert.equal(photoError([photo('id.jpg', 10 * MB)], options), '');
    assert.equal(photoError([photo('id.PNG', 2 * MB, 'image/png')], options), '');
    assert.equal(photoError([], options), '');
});

test('oversized, non-photo, and too many files are rejected', () => {
    assert.match(photoError([photo('id.jpg', 10 * MB + 1)], options), /must be 10 MB or smaller/);
    assert.match(photoError([photo('clip.mp4', MB, 'video/mp4')], options), /JPG, JPEG, PNG photo only/);
    assert.match(photoError([photo('clip.jpg', MB, 'video/mp4')], options), /photo only/);
    assert.match(photoError(Array.from({ length: 11 }, (_, i) => photo(`${i}.jpg`, MB)), options), /at most 10 photos/);
});

test('photos that together exceed the server request limit are rejected', () => {
    assert.match(photoError(Array.from({ length: 4 }, (_, i) => photo(`${i}.jpg`, 10 * MB)), options), /total 40\.0 MB, but at most 39 MB/);
});
