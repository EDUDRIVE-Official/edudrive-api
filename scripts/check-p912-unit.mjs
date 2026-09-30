import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const bank = JSON.parse(readFileSync(new URL('../resources/curriculum/p912/instruments.v0.1.json', import.meta.url)));
const unit = JSON.parse(readFileSync(new URL('../resources/curriculum/p912/crossing-unit.v0.1.json', import.meta.url)));
function validate(value) {
  assert.equal(value.status, 'draft_requires_human_review');
  assert.equal(value.goals.length, 5);
  assert.equal(new Set(value.goals.map(g => g.indicator)).size, 5);
  assert.equal(value.activities.length, 3);
  assert.equal(value.phases.length, 5);
  assert.ok(Object.values(value.human_review).every(v => v === null));
  for (const goal of value.goals) {
    assert.ok(bank.indicator_ids.includes(goal.indicator));
    assert.ok(value.activities.some(a => a.id === goal.practice));
    assert.equal(goal.transfer, 'T01');
    for (const id of goal.evidence) {
      const item = bank.items.find(i => i.id === id);
      assert.ok(item, `Missing item ${id}`);
      assert.ok(item.criteria.some(c => c.indicator === goal.indicator), `${id} does not assess ${goal.indicator}`);
    }
    for (const form of ['diagnostic', 'independent']) {
      assert.ok(goal.evidence.some(id => bank.items.find(i => i.id === id)?.form === form));
    }
  }
  for (const activity of value.activities) {
    for (const field of ['setup', 'prompt', 'hint', 'explanation', 'variation']) assert.ok(activity[field]?.trim());
  }
}
validate(unit);
for (const change of [
  u => { u.goals[0].evidence.push('R01'); },
  u => { u.goals[0].practice = 'missing'; },
  u => { u.goals[1].evidence = ['D01']; },
]) {
  const broken = structuredClone(unit);
  change(broken);
  assert.throws(() => validate(broken));
}
console.log('P912-U01: 5 objectives, 3 practices, 5 phases and evidence mapping verified; 3 negative cases passed. Human review pending.');
