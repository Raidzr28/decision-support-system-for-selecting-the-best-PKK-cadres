// ====================================================================
// SPK MAUT — Front-end JS (AJAX + interaksi UI)
// ====================================================================

// Toggle sidebar (default tersembunyi — dibuka/ditutup lewat tombol burger)
function toggleSidebar() {
  const sidebar  = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  const open = sidebar?.classList.toggle('open');
  backdrop?.classList.toggle('show', open);
}

// Tutup sidebar dengan tombol Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && document.querySelector('.sidebar')?.classList.contains('open')) {
    toggleSidebar();
  }
});

// Toggle password visibility
function togglePw(btn) {
  const input = btn.parentElement.querySelector('input');
  input.type = input.type === 'password' ? 'text' : 'password';
}

// ---------- Helper AJAX ----------
async function ajaxPost(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: data,
  });
  return res.json();
}

// Toast sederhana
function toast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = 'flash flash-' + type;
  t.style.cssText = 'position:fixed;top:24px;right:24px;z-index:9999;box-shadow:0 12px 34px rgba(0,0,0,.15);animation:slideIn .3s';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; }, 2200);
  setTimeout(() => t.remove(), 2700);
}

// ---------- Konfirmasi hapus ----------
function konfirmHapus(url, nama) {
  if (confirm('Hapus data "' + nama + '" ? Tindakan ini tidak dapat dibatalkan.')) {
    window.location.href = url;
  }
}

// ---------- AJAX simpan nilai matriks (inline edit) ----------
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.nilai-input').forEach(inp => {
    inp.addEventListener('change', async function () {
      const altId = this.dataset.alt;
      const kriId = this.dataset.kri;
      let val = parseFloat(this.value);
      if (isNaN(val) || val < 0) { val = 0; this.value = 0; }
      const fd = new FormData();
      fd.append('alternatif_id', altId);
      fd.append('kriteria_id', kriId);
      fd.append('nilai', val);
      this.style.borderColor = '#ffa552';
      try {
        const r = await ajaxPost('actions/simpan_nilai.php', fd);
        if (r.ok) {
          this.style.borderColor = '#2bb673';
          setTimeout(() => this.style.borderColor = '', 800);
        } else {
          this.style.borderColor = '#ef5350';
          toast(r.msg || 'Gagal menyimpan', 'error');
        }
      } catch (e) {
        this.style.borderColor = '#ef5350';
        toast('Kesalahan jaringan', 'error');
      }
    });
  });

  // Validasi total bobot realtime (halaman kriteria)
  const bobotInputs = document.querySelectorAll('.bobot-check');
  if (bobotInputs.length) {
    const recalc = () => {
      let total = 0;
      bobotInputs.forEach(i => total += parseFloat(i.value || 0));
      const el = document.getElementById('total-bobot');
      if (el) {
        el.textContent = total.toFixed(2).replace('.', ',');
        el.style.color = Math.abs(total - 1) < 0.001 ? '#2bb673' : '#ef5350';
      }
    };
    bobotInputs.forEach(i => i.addEventListener('input', recalc));
    recalc();
  }

  // Animasi reveal kartu
  document.querySelectorAll('.card, .stat-card, .hero-banner').forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(14px)';
    setTimeout(() => {
      el.style.transition = 'all .5s cubic-bezier(.2,.8,.2,1)';
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 60 * i);
  });
});

// Animasi keyframe untuk toast
const styleSheet = document.createElement('style');
styleSheet.textContent = '@keyframes slideIn{from{transform:translateX(40px);opacity:0}to{transform:translateX(0);opacity:1}}';
document.head.appendChild(styleSheet);
