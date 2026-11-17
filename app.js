const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const dlg = $('#bm');

function openBooking(id, date) {
  if (!dlg) return;
  if (id) { $('#bm-room').value = id; $('#bm-title').textContent = $('#bm-room').selectedOptions[0].text; }
  if (date) $('#bm-date').value = date;
  dlg.showModal();
}
$$('[data-close]').forEach(b => b.onclick = () => dlg.close());
$$('[data-book]').forEach(el => el.addEventListener('click', e => {
  e.preventDefault(); openBooking(el.dataset.id, el.dataset.date);
}));
$$('.room').forEach(c => {
  const go = () => openBooking(c.dataset.id);
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
