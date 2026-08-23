@php
    // Enrollment and payment are handled by hand over Messenger, so every CTA points here.
    $fb = 'https://www.facebook.com/uvnis92jfzsg';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#06030d" />
<title>IncepXion Complete E-Commerce System</title>
<meta name="description" content="The complete IncepXion e-commerce system for the AI & Automation Era." />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#05030a;
  --bg2:#090413;
  --panel:rgba(13,8,24,.66);
  --line:rgba(155,94,255,.24);
  --violet:#8a36ff;
  --purple:#5f18d8;
  --pink:#ff2c78;
  --text:#f7f3ff;
  --muted:#b9aecb;
  --green:#6dffb1;
  --shadow:0 30px 90px rgba(82,16,176,.28);
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  margin:0;background:
    radial-gradient(circle at 15% 10%,rgba(109,21,222,.22),transparent 30%),
    radial-gradient(circle at 85% 15%,rgba(255,44,120,.12),transparent 26%),
    linear-gradient(180deg,#030207 0%,#080311 45%,#030207 100%);
  color:var(--text);font-family:Inter,system-ui,sans-serif;overflow-x:hidden;
}
body::before{
  content:"";position:fixed;inset:0;pointer-events:none;z-index:-1;opacity:.22;
  background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
  background-size:52px 52px;
  mask-image:linear-gradient(to bottom,black,transparent 88%);
}
a{color:inherit;text-decoration:none}
.container{width:min(1180px,calc(100% - 36px));margin:auto}
.nav{
  position:fixed;top:0;left:0;right:0;z-index:50;
  background:rgba(5,3,10,.58);backdrop-filter:blur(18px);
  border-bottom:1px solid rgba(255,255,255,.06)
}
.nav-inner{height:76px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:25px;letter-spacing:-1px}
.brand-x{font-size:34px;color:#8d35ff;text-shadow:0 0 24px #8d35ff}
.nav-links{display:flex;gap:28px;color:#b9aecb;font-size:14px}
.nav-links a:hover{color:white}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  padding:15px 22px;border-radius:12px;font-weight:800;border:1px solid var(--line);
  transition:.25s ease;cursor:pointer;position:relative;overflow:hidden
}
.btn-primary{
  background:linear-gradient(120deg,#7a2dff,#9e3fff 54%,#ff2d7b);
  box-shadow:0 12px 42px rgba(128,43,255,.35)
}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 18px 55px rgba(128,43,255,.5)}
.btn-secondary{background:rgba(255,255,255,.035)}
.btn-secondary:hover{background:rgba(255,255,255,.07);transform:translateY(-2px)}
.btn-outline{background:transparent;border-color:rgba(174,115,255,.5);color:#d7b9ff}
.btn-outline:hover{background:rgba(132,54,255,.14);border-color:rgba(200,150,255,.75);transform:translateY(-2px)}
/* Nav-sized buttons: smaller than the page's main CTAs, since three sit side by side. */
.btn-sm{padding:13px 19px;font-size:14px;border-radius:11px}
.nav-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.btn-short{display:none}
/* The buttons are the point of the bar; let the wordmark give up room before they do. */
.brand{flex-shrink:1;min-width:0}
.hero{min-height:100vh;display:grid;align-items:center;padding:130px 0 70px;position:relative}
.hero-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:54px;align-items:center}
.eyebrow{
  display:inline-flex;align-items:center;gap:9px;padding:8px 12px;border-radius:999px;
  border:1px solid rgba(170,106,255,.25);background:rgba(108,31,220,.1);
  color:#d7b9ff;font-weight:700;font-size:12px;letter-spacing:.14em;text-transform:uppercase
}
.dot{width:7px;height:7px;border-radius:50%;background:#a35cff;box-shadow:0 0 16px #a35cff;animation:pulse 1.6s infinite}
@keyframes pulse{50%{opacity:.35;transform:scale(.72)}}
h1,h2,h3{margin:0}
h1{
  font-family:Oswald,Inter,sans-serif;font-size:clamp(58px,7vw,104px);line-height:.91;
  text-transform:uppercase;letter-spacing:-.045em;margin:24px 0 24px
}
.gradient-text{background:linear-gradient(90deg,#fff 3%,#c49aff 46%,#9a41ff 68%,#ff4f91);-webkit-background-clip:text;color:transparent}
.hero-copy{font-size:18px;line-height:1.75;color:#c7bbd6;max-width:700px}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}
.micro{margin-top:18px;color:#9f93b4;font-size:13px}
.hero-visual{position:relative;min-height:560px;display:grid;place-items:center;perspective:1100px}
.orbit{position:absolute;border:1px solid rgba(145,77,255,.18);border-radius:50%;animation:spin 18s linear infinite}
.orbit.o1{width:440px;height:440px}
.orbit.o2{width:330px;height:330px;animation-direction:reverse;animation-duration:12s}
@keyframes spin{to{transform:rotate(360deg)}}
.hero-x{
  font-family:Oswald;font-size:390px;line-height:.7;font-weight:700;color:transparent;
  -webkit-text-stroke:2px rgba(142,57,255,.7);filter:drop-shadow(0 0 28px rgba(126,43,255,.58));
  transform:rotate(-7deg);user-select:none
}
.float-card{
  position:absolute;background:rgba(11,6,20,.8);border:1px solid rgba(156,92,255,.25);
  box-shadow:var(--shadow);backdrop-filter:blur(18px);border-radius:18px;padding:18px
}
.float-card strong{font-family:Oswald;font-size:28px;display:block}
.float-card span{color:#aa9bbd;font-size:12px}
.fc1{left:2%;top:22%} .fc2{right:0;bottom:22%} .fc3{left:14%;bottom:6%}
section{padding:105px 0}
.section-kicker{font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#a664ff;font-weight:800}
.section-title{font-family:Oswald;font-size:clamp(40px,5vw,66px);line-height:1;text-transform:uppercase;margin:12px 0 18px}
.section-sub{max-width:760px;color:#aaa0bb;line-height:1.75}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:45px}
.stat{padding:26px;border-radius:18px;border:1px solid var(--line);background:linear-gradient(180deg,rgba(142,64,255,.08),rgba(255,255,255,.025))}
.stat b{font-family:Oswald;font-size:42px;display:block}
.stat span{color:#a99db8;font-size:13px;line-height:1.45}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:44px}
.card{
  min-height:220px;padding:24px;border:1px solid rgba(146,80,255,.2);border-radius:18px;
  background:linear-gradient(145deg,rgba(20,11,36,.82),rgba(8,5,14,.76));
  position:relative;overflow:hidden;transition:.25s ease;transform-style:preserve-3d
}
.card::after{content:"";position:absolute;width:170px;height:170px;border-radius:50%;right:-70px;top:-90px;background:#7c2cff;filter:blur(70px);opacity:.13}
.card:hover{border-color:rgba(174,115,255,.55);transform:translateY(-6px)}
.icon{font-size:25px;margin-bottom:30px;display:inline-grid;place-items:center;width:48px;height:48px;border-radius:13px;background:rgba(132,54,255,.12);border:1px solid rgba(161,94,255,.28)}
.card h3{font-size:18px;margin-bottom:10px} .card p{color:#a99db8;line-height:1.65;font-size:14px}
.roadmap-wrap{margin-top:45px;padding:10px;border-radius:26px;background:linear-gradient(140deg,rgba(155,83,255,.35),rgba(255,40,122,.12),rgba(255,255,255,.04));box-shadow:var(--shadow);perspective:1200px}
.roadmap{
  width:100%;height:auto;display:block;border-radius:20px;transition:transform .16s ease;transform-origin:center;
  border:1px solid rgba(255,255,255,.07)
}
.roadmap-note{display:flex;justify-content:space-between;gap:20px;color:#8f839f;font-size:12px;margin-top:12px;padding:0 5px}
.offer{position:relative;overflow:hidden}
.offer-panel{
  display:grid;grid-template-columns:1.1fr .9fr;gap:44px;align-items:center;
  padding:54px;border:1px solid rgba(167,94,255,.27);border-radius:28px;
  background:radial-gradient(circle at 83% 20%,rgba(123,42,255,.24),transparent 30%),rgba(12,7,22,.72)
}
.price-old{font-size:20px;color:#8d829d;text-decoration:line-through;margin-bottom:6px}
.price{font-family:Oswald;font-size:clamp(56px,8vw,96px);line-height:1;background:linear-gradient(90deg,#fff,#bb8dff,#ff4f91);-webkit-background-clip:text;color:transparent}
.price-label{color:#c7b8d7;margin-top:8px}
.checks{display:grid;gap:11px;margin:28px 0 0;padding:0;list-style:none}
.checks li{display:flex;gap:11px;color:#d8cfdf;line-height:1.45}
.check{color:#7dffb9;font-weight:900;flex-shrink:0}
/* Secondary line under an item — indented with the text, not the tick. */
.check-note{display:block;margin-top:4px;color:#9287a4;font-size:13px;line-height:1.5}
.offer-cta{padding:34px;border-radius:22px;background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.07)}
.offer-cta h3{font-family:Oswald;font-size:34px;text-transform:uppercase}
.offer-cta p{color:#a99db8;line-height:1.7}
.btn-wide{width:100%;margin-top:16px;padding:18px 22px}
.closing{text-align:center;padding:120px 0}
.closing h2{font-family:Oswald;font-size:clamp(54px,8vw,110px);line-height:.94;text-transform:uppercase}
.closing p{color:#a99db8;max-width:700px;margin:25px auto 30px;line-height:1.7}
footer{padding:30px 0;border-top:1px solid rgba(255,255,255,.06);color:#786d87;font-size:12px}
.footer-inner{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
.cursor-glow{position:fixed;width:430px;height:430px;border-radius:50%;pointer-events:none;z-index:-1;background:radial-gradient(circle,rgba(112,37,238,.14),transparent 62%);transform:translate(-50%,-50%)}
.reveal{opacity:0;transform:translateY(28px);transition:.75s cubic-bezier(.2,.7,.2,1)} .reveal.visible{opacity:1;transform:none}
.scrollbar{position:fixed;top:0;left:0;height:2px;background:linear-gradient(90deg,#7a2dff,#ff2d7b);z-index:99;width:0}
@media(max-width:900px){
  .nav-links{display:none} .hero-grid,.offer-panel{grid-template-columns:1fr}
  .stats{grid-template-columns:repeat(2,1fr)} .feature-grid{grid-template-columns:1fr 1fr}
  .offer-panel{padding:30px} section{padding:78px 0}

  /* The three stat cards are pinned by percentage, which works while the column is wide.
     Once the hero stacks, they drift into each other — "AI + AUTOMATION" was sitting on
     top of "250+ STUDENTS". Below this width they stop floating and simply stack, with
     the X left behind them as the backdrop it always was. */
  .hero-visual{display:flex;flex-direction:column;justify-content:center;gap:12px;min-height:0;padding:34px 0}
  .hero-x{position:absolute;font-size:300px;opacity:.45;z-index:0;pointer-events:none}
  .float-card{position:relative;z-index:1;width:100%}
  /* The per-card offsets still bite on a relatively positioned box, which left the three
     stacked but stepped across the screen. Clear them so they line up. */
  .fc1,.fc2,.fc3{left:auto;right:auto;top:auto;bottom:auto}
}
@media(max-width:620px){
  /* Three buttons won't fit a phone at full width, so the wording shortens and the
     padding tightens rather than any of them being dropped. */
  .btn-sm{padding:11px 13px;font-size:13px;gap:5px}
  .nav-actions{gap:7px}
  .btn-full{display:none} .btn-short{display:inline}
  .container{width:min(100% - 24px,1180px)} .nav-inner{height:68px} .brand{font-size:21px}
  .hero{padding-top:108px} h1{font-size:55px} .hero-copy{font-size:16px}
  .hero-x{font-size:230px}
  .stats,.feature-grid{grid-template-columns:1fr} .roadmap-wrap{border-radius:18px;padding:6px}
  .roadmap{border-radius:14px} .roadmap-note{display:none} .offer-panel{padding:24px;border-radius:20px}
  .closing{padding:90px 0} .btn{width:100%}
  /* ...but not the nav ones, which sit side by side in a row. */
  .nav-actions .btn{width:auto}
}
/* Narrow phones (360–390px): squeeze the bar rather than lose a button. */
@media(max-width:430px){
  .brand img{height:24px}
  .btn-sm{padding:9px 10px;font-size:12px}
  .nav-actions{gap:5px}
}
</style>
{{-- .reveal starts invisible and is uncovered by script. Without this, a blocked or failed
     script would leave the whole page blank — to a visitor and to a crawler alike. --}}
<noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
</head>
<body>
<div class="scrollbar" id="scrollbar"></div>
<div class="cursor-glow" id="cursorGlow"></div>

<nav class="nav">
  <div class="container nav-inner">
    <a class="brand" href="#top">
      <img src="{{ asset('logo-light.png') }}" alt="Incepxion Services Inc." style="height:30px;width:auto;display:block">
    </a>
    <div class="nav-links">
      <a href="#system">The System</a>
      <a href="#roadmap">Roadmap</a>
      <a href="#offer">Enrollment</a>
    </div>
    {{-- Kept outside .nav-links, which the design hides on phones — these are the only way
         into the app, so they have to survive on every screen. --}}
    <div class="nav-actions">
      @auth
        <a class="btn btn-secondary btn-sm" href="{{ route('dashboard') }}">Dashboard</a>
      @else
        <a class="btn btn-secondary btn-sm" href="{{ route('login') }}">Log in</a>
        <a class="btn btn-outline btn-sm" href="{{ route('register') }}">Register</a>
      @endauth
      <a class="btn btn-primary btn-sm" href="{{ $fb }}" target="_blank" rel="noopener">
        <span class="btn-full">Message Nand Sam ↗</span><span class="btn-short">Enroll ↗</span>
      </a>
    </div>
  </div>
</nav>

<main id="top">
<section class="hero">
  <div class="container hero-grid">
    <div class="reveal">
      <div class="eyebrow"><span class="dot"></span> IncepXion Complete E-Commerce System</div>
      <h1>BUILD A SYSTEM<br><span class="gradient-text">THAT PRINTS PROFIT.</span></h1>
      <p class="hero-copy">Everything learned from almost 7 years of actual e-commerce experience — rebuilt into one complete system for today's AI & Automation Era.</p>
      <div class="hero-actions">
        <a class="btn btn-primary magnetic" href="{{ $fb }}" target="_blank" rel="noopener">Secure Your Slot →</a>
        <a class="btn btn-secondary" href="#roadmap">Explore the Roadmap ↓</a>
      </div>
      <div class="micro">Less manpower. More automation. Smarter execution.</div>
    </div>
    <div class="hero-visual reveal" id="heroVisual">
      <div class="orbit o1"></div><div class="orbit o2"></div>
      <div class="hero-x">X</div>
      <div class="float-card fc1"><strong>7 YEARS</strong><span>ACTUAL E-COMMERCE EXPERIENCE</span></div>
      <div class="float-card fc2"><strong>250+</strong><span>PREVIOUS STUDENTS</span></div>
      <div class="float-card fc3"><strong>AI + AUTOMATION</strong><span>BUILT FOR HOW E-COMMERCE WORKS TODAY</span></div>
    </div>
  </div>
</section>

<section id="system">
  <div class="container">
    <div class="reveal">
      <div class="section-kicker">The IncepXion Standard</div>
      <h2 class="section-title">Not another course.<br><span class="gradient-text">A complete operating system.</span></h2>
      <p class="section-sub">The goal is simple: build a leaner e-commerce operation with fewer people, less manual work, stronger data, and more automation.</p>
    </div>
    <div class="stats">
      <div class="stat reveal"><b>7</b><span>Years of actual experience, testing, mistakes, systems and scaling.</span></div>
      <div class="stat reveal"><b>250+</b><span>Previous students from the original IncepXion batch.</span></div>
      <div class="stat reveal"><b>3</b><span>Years of an active community that continues to support members.</span></div>
      <div class="stat reveal"><b>1</b><span>Complete AI-powered e-commerce system built for execution.</span></div>
    </div>

    <div class="feature-grid">
      <div class="card reveal tilt"><div class="icon">⚡</div><h3>Complete E-Commerce Training</h3><p>From product discovery and creatives to ads, profitability, systems, and scaling.</p></div>
      <div class="card reveal tilt"><div class="icon">◎</div><h3>AI + Full Automation</h3><p>Build workflows that reduce repetitive work and keep operations moving faster.</p></div>
      <div class="card reveal tilt"><div class="icon">✕</div><h3>No VA. No Encoder.</h3><p>Design a leaner operation with less dependency on manual manpower.</p></div>
      <div class="card reveal tilt"><div class="icon">AI</div><h3>AI-Assisted Creatives</h3><p>Move from idea to execution faster with a modern AI creative workflow.</p></div>
      <div class="card reveal tilt"><div class="icon">↗</div><h3>Data-Driven Ads Execution</h3><p>Use clearer metrics and decision rules instead of guesswork and random scaling.</p></div>
      <div class="card reveal tilt"><div class="icon">∞</div><h3>Lifetime Community Support</h3><p>Stay connected to the IncepXion FB Group Community for continued learning and support.</p></div>
    </div>
  </div>
</section>

<section id="roadmap">
  <div class="container">
    <div class="reveal">
      <div class="section-kicker">Complete Training Roadmap</div>
      <h2 class="section-title">FROM PRODUCT TO PROFIT.<br><span class="gradient-text">SYSTEM. STRATEGY. AUTOMATION.</span></h2>
      <p class="section-sub">A full 15-part roadmap covering the complete IncepXion system. Move your cursor over the roadmap for an interactive tilt effect.</p>
    </div>
    <div class="roadmap-wrap reveal" id="roadmapWrap">
      {{-- The roadmap ships as a file rather than inline base64: the browser can cache it,
           and it no longer rides along in the HTML on every page load. WebP carries the
           same picture at a tenth of the weight — 300KB against 2.9MB, which matters on
           mobile data — with the PNG left as a fallback for anything that can't read it. --}}
      <picture>
        <source srcset="{{ asset('images/roadmap.webp') }}" type="image/webp">
        <img class="roadmap" id="roadmapImg" loading="lazy" decoding="async"
             width="1024" height="1536"
             src="{{ asset('images/roadmap.png') }}" alt="IncepXion Complete Training Roadmap" />
      </picture>
    </div>
    <div class="roadmap-note"><span>15 focused training modules</span><span>Built for the AI & Automation Era</span></div>
  </div>
</section>

<section id="offer" class="offer">
  <div class="container">
    <div class="offer-panel reveal">
      <div>
        <div class="section-kicker">Limited Enrollment</div>
        <h2 class="section-title">ONE COMPLETE<br><span class="gradient-text">AI-POWERED SYSTEM.</span></h2>
        <div class="price-old">Regular Price: ₱75,000</div>
        <div class="price">₱49,500</div>
        <div class="price-label">Limited offer · Limited slots only</div>
        <ul class="checks">
          <li><span class="check">✓</span><span>Complete E-Commerce Training</span></li>
          <li><span class="check">✓</span><span>AI + Full Automation System</span></li>
          <li><span class="check">✓</span><span>No VA. No Encoder.</span></li>
          <li><span class="check">✓</span><span>AI-Assisted Creative Production</span></li>
          <li><span class="check">✓</span><span>Simplified &amp; Data-Driven Ads Execution</span></li>
          <li><span class="check">✓</span><span>Less Dependency on Highly Skilled Advertisers</span></li>
          <li>
            <span class="check">✓</span>
            <span>FREE 1-Year IncepXion Website Subscription
              {{-- Said here rather than left for after enrolment: a cost that only turns up
                   later reads as something that was hidden, even when it wasn't. --}}
              <span class="check-note">₱1,000/month after the first 12 months, if you choose to keep using the platform.</span>
            </span>
          </li>
          <li><span class="check">✓</span><span>Lifetime Support through the IncepXion FB Group Community</span></li>
          <li>
            <span class="check">✓</span>
            <span>Exclusive IncepXion Mastermind Meetups
              <span class="check-note">You'll always be invited to future private mastermind sessions and community meetups.</span>
            </span>
          </li>
        </ul>
      </div>
      <div class="offer-cta">
        <h3>Enrollment closes when support capacity is full.</h3>
        <p>Just like the previous IncepXion batch, enrollment will close once the maximum number of students we can properly support is reached.</p>
        <a class="btn btn-primary btn-wide magnetic" href="{{ $fb }}" target="_blank" rel="noopener">Contact / Pay via Nand Sam →</a>
        <a class="btn btn-secondary btn-wide" href="{{ $fb }}" target="_blank" rel="noopener">Open Facebook Profile ↗</a>
      </div>
    </div>
  </div>
</section>

<section class="closing">
  <div class="container reveal">
    <div class="section-kicker">The IncepXion Way</div>
    <h2>LEARN. APPLY.<br><span class="gradient-text">SCALE.</span></h2>
    <p>Build the systems, understand the numbers, automate what should be automated, and execute with clarity.</p>
    <a class="btn btn-primary magnetic" href="{{ $fb }}" target="_blank" rel="noopener">Message Nand Sam to Enroll →</a>
  </div>
</section>
</main>

<footer>
  <div class="container footer-inner">
    <span>© {{ date('Y') }} IncepXion. All rights reserved.</span>
    @auth
      <a href="{{ route('dashboard') }}" style="color:#a664ff;font-weight:700">Go to dashboard →</a>
    @else
      <span>
        <a href="{{ route('login') }}" style="color:#a664ff;font-weight:700">Member log in</a>
        <span style="opacity:.4;margin:0 8px">·</span>
        <a href="{{ route('register') }}" style="color:#a664ff;font-weight:700">Create account</a>
      </span>
    @endauth
    <span>Build systems that print profit.</span>
  </div>
</footer>

<script>
const revealObserver = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:.12});
document.querySelectorAll('.reveal').forEach(el=>revealObserver.observe(el));

const glow = document.getElementById('cursorGlow');
window.addEventListener('pointermove', e=>{
  glow.style.left=e.clientX+'px'; glow.style.top=e.clientY+'px';
});

window.addEventListener('scroll',()=>{
  const h=document.documentElement.scrollHeight-innerHeight;
  document.getElementById('scrollbar').style.width=((scrollY/h)*100)+'%';
});

document.querySelectorAll('.tilt').forEach(card=>{
  card.addEventListener('mousemove',e=>{
    const r=card.getBoundingClientRect(), x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
    card.style.transform=`translateY(-6px) rotateX(${-y*7}deg) rotateY(${x*7}deg)`;
  });
  card.addEventListener('mouseleave',()=>card.style.transform='');
});

const rw=document.getElementById('roadmapWrap'), ri=document.getElementById('roadmapImg');
rw.addEventListener('mousemove',e=>{
  const r=rw.getBoundingClientRect(), x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
  ri.style.transform=`rotateX(${-y*3.2}deg) rotateY(${x*3.2}deg) scale(.992)`;
});
rw.addEventListener('mouseleave',()=>ri.style.transform='');

document.querySelectorAll('.magnetic').forEach(btn=>{
  btn.addEventListener('mousemove',e=>{
    const r=btn.getBoundingClientRect();
    btn.style.transform=`translate(${(e.clientX-r.left-r.width/2)*.08}px,${(e.clientY-r.top-r.height/2)*.08}px)`;
  });
  btn.addEventListener('mouseleave',()=>btn.style.transform='');
});
</script>
</body>
</html>
