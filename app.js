const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const dlg = $('#bm'), co = $('#co');

function openBooking(o = {}) {
  if (!dlg) return;
  const f = $('form', dlg); f.reset();
  $('#bm-id').value = o.id || '';
  if (o.room) $('#bm-room').value = o.room;
  if (o.date) $('#bm-date').value = o.date;
  if (o.start) f.elements.start_time.value = o.start;
  if (o.end) f.elements.end_time.value = o.end;
  if (o.cls) f.elements.class_name.value = o.cls;
  if (o.lname) f.elements.lendee_name.value = o.lname;
  if (o.lid) f.elements.lendee_id.value = o.lid;
  $('#bm-h').textContent = o.id ? 'Edit' : 'Book';
  $('#bm-title').textContent = $('#bm-room').selectedOptions[0].text;
  dlg.showModal();
}
$('#bm-room')?.addEventListener('change', e => $('#bm-title').textContent = e.target.selectedOptions[0].text);
$$('[data-close]').forEach(b => b.onclick = () => b.closest('dialog').close());
$$('[data-book]').forEach(el => el.addEventListener('click', e => {
  e.preventDefault(); openBooking({ room: el.dataset.id, date: el.dataset.date });
}));
$$('[data-edit]').forEach(el => el.addEventListener('click', () => openBooking(el.dataset)));
$$('[data-checkout]').forEach(el => el.addEventListener('click', () => {
  $('#co-id').value = el.dataset.id; $('#co-name').value = el.dataset.name; co.showModal();
}));
$$('.room').forEach(c => {
  const go = () => openBooking({ room: c.dataset.id });
  c.onclick = go; c.onkeydown = e => (e.key === 'Enter') && go();
});

// Dashboard filter + search
let filter = 'all';
const apply = () => {
  const q = ($('#q')?.value || '').toLowerCase();
  $$('.room').forEach(c => c.hidden = !(c.dataset.name.includes(q) && (filter === 'all' || c.dataset.status === filter)));
};
$$('.pill').forEach(p => p.onclick = () => {
  $$('.pill').forEach(x => x.classList.remove('on')); p.classList.add('on'); filter = p.dataset.f; apply();
});
$('#q')?.addEventListener('input', apply);

// QR codes page
const qb = $('#qr-base');
if (qb) {
  qb.value = location.href.replace(/qr\.php.*$/, '') + 'room.php?id=';
  const draw = () => $$('.qr').forEach(c => {
    const url = qb.value + c.dataset.id, q = qrcode(0, 'M');
    q.addData(url); q.make();
    $('.qrimg', c).innerHTML = q.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
    $('code', c).textContent = url;
  });
  qb.addEventListener('input', draw); draw();
  $('#qr-print').onclick = () => print();
}