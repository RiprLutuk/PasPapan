import test from 'node:test';
import assert from 'node:assert/strict';
import {isRecentLocation} from '../src/lib/location-policy.ts';

test('a valid fresh location can be submitted without another GPS acquisition',()=>{
 const now=100000,fix={latitude:-6.21,longitude:106.62,accuracy:35,updatedAt:new Date(now-3000)};
 assert.equal(isRecentLocation(fix,now),true);
 for(const change of [{updatedAt:new Date(now-16000)},{updatedAt:new Date(now+6000)},{accuracy:NaN},{accuracy:0},{latitude:91},{longitude:181},{updatedAt:new Date('invalid')}])assert.equal(isRecentLocation({...fix,...change},now),false);
 assert.equal(isRecentLocation(null,now),false);
});
