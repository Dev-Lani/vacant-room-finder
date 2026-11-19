const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const dlg = $('#bm'), co = $('#co');

// Dialogs: enter from the trigger (scale .95 -> 1, ease-out), exit 20% shorter
let lastTrig = null;
document.addEventListener('click', e => { lastTrig = e.target.closest('button,a'); }, true);
function openDlg(d) {
  d.classList.remove('out');
  $$('form > *', d).forEach((el, i) => el.style.setProperty('--k', i));
  d.showModal();
  const b = d.getBoundingClientRect(), t = lastTrig;
  if (t && document.contains(t)) {
    const r = t.getBoundingClientRect();
    d.style.transformOrigin = `${r.left + r.width / 2 - b.left}px ${r.top + r.height / 2 - b.top}px`;
  } else d.style.transformOrigin = 'center';
}
function closeDlg(d) {
  if (!d.open || d.classList.contains('out')) return;
  d.classList.add('out');
  setTimeout(() => { d.classList.remove('out'); d.close(); }, 176);
}
[dlg, co].forEach(d => d?.addEventListener('cancel', e => { e.preventDefault(); closeDlg(d); }));

// Class picker: program -> year -> section / specialization
const CM = dlg ? JSON.parse(dlg.dataset.classes) : {};
function fillSec(sel) {
  if (!dlg) return;
  const list = CM[$('#bm-prog').value]?.[$('#bm-year').value] || [];
  $('#bm-sec').innerHTML = list.map(s => `<option>${s}</option>`).join('');
  if (sel && list.includes(sel)) $('#bm-sec').value = sel;
  $('#bm-seclbl').textContent = +$('#bm-year').value >= 3 ? 'Specialization' : 'Section';
}
$('#bm-prog')?.addEventListener('change', () => fillSec());
$('#bm-year')?.addEventListener('change', () => fillSec());
fillSec();

// Student ID auto-format: 0000-0000
$('#bm-lid')?.addEventListener('input', e => {
  const d = e.target.value.replace(/\D/g, '').slice(0, 8);
  e.target.value = d.length > 4 ? d.slice(0, 4) + '-' + d.slice(4) : d;
});


function openBooking(o = {}) {
  if (!dlg) return;
  const f = $('form', dlg); f.reset();
  $('#bm-id').value = o.id || '';
  if (o.room) $('#bm-room').value = o.room;
  if (o.date) { const d = $('#bm-date'); d.value = (d.min && o.date < d.min) ? d.min : o.date; }
  if (o.start) f.elements.start_time.value = o.start;
  if (o.end) f.elements.end_time.value = o.end;
  const m = /^(\w+) (\d)-(.+)$/.exec(o.cls || '');
  if (m && CM[m[1]]) { $('#bm-prog').value = m[1]; $('#bm-year').value = m[2]; fillSec(m[3]); } else fillSec();
  if (o.lname) f.elements.lendee_name.value = o.lname;
  if (o.lid) f.elements.lendee_id.value = o.lid;
  $('#bm-h').textContent = o.id ? 'Edit' : 'Book';
  $('#bm-title').textContent = $('#bm-room').selectedOptions[0].text;
  openDlg(dlg);
}
$('#bm-room')?.addEventListener('change', e => $('#bm-title').textContent = e.target.selectedOptions[0].text);
$$('[data-close]').forEach(b => b.onclick = () => closeDlg(b.closest('dialog')));
$$('[data-book]').forEach(el => el.addEventListener('click', e => {
  e.preventDefault();
  openBooking({ room: el.dataset.id, date: el.dataset.date, start: el.dataset.start, end: el.dataset.end });
}));
$$('[data-edit]').forEach(el => el.addEventListener('click', () => openBooking(el.dataset)));
$$('[data-checkout]').forEach(el => el.addEventListener('click', () => {
  $('#co-id').value = el.dataset.id; $('#co-name').value = el.dataset.name; openDlg(co);
}));

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

// Stagger list items by 30ms (capped) and the hour cells inside each card
['.stat', '.room', '.qr', '.tbl tbody tr', '.sched li', '.tt-r'].forEach(s => $$(s).forEach((el, i) => el.style.setProperty('--i', Math.min(i, 12))));
$$('.hrs').forEach(h => $$('.hc', h).forEach((c, j) => c.style.setProperty('--j', j)));

// Toasts: floating, top-right, auto-dismiss (timer pauses while hovered/focused)
function dismissToast(t) {
  if (t.dataset.out) return;
  t.dataset.out = 1; t.classList.add('out');
  setTimeout(() => { const c = t.parentNode; t.remove(); if (c && !c.children.length) c.remove(); }, 208);
}
function initToast(t, n = 0) {
  t.style.setProperty('--n', n);
  $('.tx', t).onclick = () => dismissToast(t);
  $('.tbar', t).addEventListener('animationend', () => dismissToast(t));
}
$$('.toast').forEach(initToast);
window.toast = (msg, bad = false) => {
  const box = $('.toasts') || document.body.appendChild(Object.assign(document.createElement('div'), { className: 'toasts' }));
  const t = document.createElement('div');
  t.className = 'toast ' + (bad ? 'bad' : 'ok'); t.setAttribute('role', bad ? 'alert' : 'status');
  t.innerHTML = '<span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" pathLength="1"/><path d="' + (bad ? 'M12 7v6M12 16.5v.01' : 'M7.5 12.5l3 3 6-6.5') + '" pathLength="1"/></svg></span><p></p><button type="button" class="tx" aria-label="Dismiss">&times;</button><i class="tbar"></i>';
  $('p', t).textContent = msg;
  box.appendChild(t); initToast(t, box.children.length - 1);
};