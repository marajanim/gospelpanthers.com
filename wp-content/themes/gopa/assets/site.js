(() => {
  const toggle=document.querySelector('.menu-toggle');
  const nav=document.querySelector('#primary-nav');
  const close=()=>{toggle?.setAttribute('aria-expanded','false');nav?.classList.remove('is-open');};
  toggle?.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')!=='true';toggle.setAttribute('aria-expanded',String(open));nav.classList.toggle('is-open',open);});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&nav?.classList.contains('is-open')){close();toggle.focus();}});
  nav?.addEventListener('click',e=>{if(e.target.closest('a'))close();});
  document.addEventListener('click',e=>{if(!e.target.closest('.site-header'))close();});
  if('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('in-view');observer.unobserve(entry.target);}}),{threshold:.08});
    document.querySelectorAll('.reveal').forEach(el=>{el.classList.add('will-reveal');observer.observe(el);});
  }
})();

