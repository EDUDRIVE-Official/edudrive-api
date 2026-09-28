import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

// Editorial integrity only. This does not validate educational effectiveness.
export function validateP912(bank){
    assert.equal(bank.status,'draft_requires_human_review');
    assert.equal(bank.audience.stage,'discover');
    assert.equal(bank.audience.age_min,9);
    assert.equal(bank.audience.age_max,12);
    assert.equal(bank.audience.independent_travel_authorized,false);
    const counts={diagnostic:4,practice:2,independent:4,retention:2};
    assert.equal(bank.items.length,12);
    assert.equal(new Set(bank.items.map(i=>i.id)).size,12,'Duplicate item IDs');
    assert.equal(new Set(bank.items.map(i=>i.scene)).size,12,'Do not reuse identical scenes');
    assert.equal(new Set(bank.indicator_ids).size,6);
    for(const [form,count]of Object.entries(counts)){
        const items=bank.items.filter(i=>i.form===form);
        assert.equal(items.length,count,form);
        assert.equal(bank.forms[form].allow_content_hints,form==='practice');
        assert.equal(bank.forms[form].feedback,form==='practice'?'after_response':'after_form');
        if(form==='diagnostic'||form==='independent'){
            const covered=[...new Set(items.flatMap(i=>i.criteria.map(c=>c.indicator)))].sort();
            assert.deepEqual(covered,[...bank.indicator_ids].sort(),'Missing indicator coverage');
        }
    }
    for(const i of bank.items){
        assert(i.title&&i.scene&&i.task&&i.criteria.length>0);
        assert(Object.hasOwn(counts,i.form));
        assert.equal(new Set(i.criteria.map(c=>c.indicator)).size,i.criteria.length);
        for(const c of i.criteria){assert(bank.indicator_ids.includes(c.indicator));assert(c.expected.length>15);}
        assert(i.response_modes.includes('oral_transcribed')&&i.response_modes.includes('written'));
        if(i.form!=='practice')assert(!i.hints&&!i.feedback,'Independent forms must not deliver hints');
        else assert(i.hints.length>0&&i.feedback);
        if(i.form==='diagnostic'){
            const partners=bank.items.filter(p=>p.form==='independent'&&p.pair===i.pair);
            assert.equal(partners.length,1,'One matched counterpart per baseline item');
            assert.deepEqual(i.criteria.map(c=>c.indicator).sort(),partners[0].criteria.map(c=>c.indicator).sort());
            assert.notEqual(i.scene,partners[0].scene);
        }
    }
}

const bank=JSON.parse(readFileSync(new URL('../resources/curriculum/p912/instruments.v0.1.json',import.meta.url),'utf8'));
validateP912(bank);
for(const mutate of [
    b=>b.items[0].id=b.items[1].id,
    b=>b.items[0].hints=['Respuesta sugerida'],
    b=>b.items[0].criteria[0].indicator='UNKNOWN',
    b=>b.forms.independent.feedback='after_response',
    b=>b.items.find(i=>i.id==='C01').pair='wrong',
    b=>b.audience.independent_travel_authorized=true,
]){
    const copy=structuredClone(bank);mutate(copy);assert.throws(()=>validateP912(copy));
}
const booklet=readFileSync(new URL('../docs/product/p912/CUADERNO-PARTICIPANTE-v0.1.md',import.meta.url),'utf8');
for(const i of bank.items){
    assert(booklet.includes(`### ${i.id}. ${i.title}`)&&booklet.includes(i.scene)&&booklet.includes(i.task),'Participant booklet differs from bank');
    for(const c of i.criteria)assert(!booklet.includes(c.expected),'Facilitator criteria leaked into participant booklet');
}
console.log('P912: 12 items, coverage, paired forms, delayed feedback, booklet separation and 6 negative cases verified. Educational review remains pending.');
