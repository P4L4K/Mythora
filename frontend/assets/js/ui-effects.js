/**
 * MYTHORA - Ambient Fairy Woodland Engine (Sunlit Mythical Grove)
 * Refined Butterfly Motion Specification:
 * - Layering: Single background canvas layer behind all UI text, cards, and buttons (z-index: 0, pointer-events: none).
 * - Count & Frequency: Exactly 2–3 butterflies, staggered entry intervals, natural resting/drifting pauses.
 * - Flight Path: Gentle, organic sinusoidal curved spline paths with banking turns and graceful glides.
 * - Animation: Subtle wing flapping that naturally alternates with effortless gliding phases and gentle bank rotation.
 * - Exit Interaction: Butterflies occasionally drift toward screen margins and fade away before re-entering from another edge.
 */
(function initFairyWoodlandAtmosphere() {
  const canvas = document.getElementById('firefly-canvas');
  if (!canvas) return;

  // Remove foreground canvas if present so all butterflies stay safely behind UI text and buttons
  const fgCanvas = document.getElementById('butterfly-foreground-canvas');
  if (fgCanvas && fgCanvas.parentNode) {
    fgCanvas.parentNode.removeChild(fgCanvas);
  }

  const ctx = canvas.getContext('2d');
  let width = (canvas.width = window.innerWidth);
  let height = (canvas.height = window.innerHeight);

  // Gentle mouse avoidance
  const mouse = { x: -1000, y: -1000, radius: 140 };
  window.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  }, { passive: true });
  window.addEventListener('mouseleave', () => {
    mouse.x = -1000;
    mouse.y = -1000;
  });

  window.addEventListener('resize', () => {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  });

  /* -------------------------------------------------------------
   * 1. JUGNUS (Sunlit Golden-Topaz & Amber Fireflies)
   * ------------------------------------------------------------- */
  const JUGNU_COUNT = 22;
  class Jugnu {
    constructor() {
      this.reset(true);
    }

    reset(initial = false) {
      this.x = Math.random() * width;
      this.y = initial ? Math.random() * height : height + 20;
      this.radius = Math.random() * 2.0 + 1.1;
      this.baseAlpha = Math.random() * 0.38 + 0.32;
      this.pulseSpeed = Math.random() * 0.025 + 0.012;
      this.pulseTimer = Math.random() * Math.PI * 2;
      this.vx = (Math.random() - 0.5) * 0.42;
      this.vy = -(Math.random() * 0.35 + 0.14);
      this.sineOffset = Math.random() * Math.PI * 2;
      this.sineSpeed = Math.random() * 0.018 + 0.008;

      const roll = Math.random();
      if (roll > 0.45) {
        this.color = '217, 119, 6';   // Amber Sun (#D97706)
      } else if (roll > 0.18) {
        this.color = '202, 138, 4';   // Golden Ochre (#CA8A04)
      } else {
        this.color = '101, 163, 13';  // Forest Green Sun (#65A30D)
      }
    }

    update() {
      this.pulseTimer += this.pulseSpeed;
      this.sineOffset += this.sineSpeed;
      
      this.x += this.vx + Math.sin(this.sineOffset) * 0.42;
      this.y += this.vy;

      const dx = this.x - mouse.x;
      const dy = this.y - mouse.y;
      const dist = Math.hypot(dx, dy);
      if (dist < mouse.radius && dist > 0) {
        const force = (mouse.radius - dist) / mouse.radius;
        this.x += (dx / dist) * force * 2.0;
        this.y += (dy / dist) * force * 2.0;
      }

      if (this.y < -20 || this.x < -20 || this.x > width + 20) {
        this.reset();
      }
    }

    draw() {
      const alpha = Math.max(0.16, this.baseAlpha + Math.sin(this.pulseTimer) * 0.28);
      ctx.save();
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${this.color}, ${alpha})`;
      ctx.shadowBlur = this.radius * 6;
      ctx.shadowColor = `rgba(${this.color}, ${alpha * 0.75})`;
      ctx.fill();
      ctx.restore();
    }
  }

  /* -------------------------------------------------------------
   * 2. DRIFTING SPECTRAL BOTANICAL LEAVES
   * ------------------------------------------------------------- */
  const LEAF_COUNT = 7;
  class SpectralLeaf {
    constructor() {
      this.reset(true);
    }

    reset(initial = false) {
      this.x = Math.random() * width;
      this.y = initial ? Math.random() * height : -30;
      this.size = Math.random() * 7 + 5;
      this.speedY = Math.random() * 0.45 + 0.3;
      this.swayAngle = Math.random() * Math.PI * 2;
      this.swaySpeed = Math.random() * 0.014 + 0.008;
      this.rotation = Math.random() * Math.PI * 2;
      this.rotSpeed = (Math.random() - 0.5) * 0.018;
      this.flip = 0;
      this.flipSpeed = Math.random() * 0.025 + 0.012;
      this.alpha = Math.random() * 0.28 + 0.25;

      const roll = Math.random();
      if (roll > 0.5) {
        this.color = '93, 112, 72';   // Mid Olive
      } else if (roll > 0.2) {
        this.color = '126, 148, 101'; // Sage
      } else {
        this.color = '217, 119, 6';   // Amber Gold
      }
    }

    update() {
      this.y += this.speedY;
      this.swayAngle += this.swaySpeed;
      this.x += Math.sin(this.swayAngle) * 0.7;
      this.rotation += this.rotSpeed;
      this.flip += this.flipSpeed;

      if (this.y > height + 30) {
        this.reset();
      }
    }

    draw() {
      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rotation);
      const scaleX = Math.cos(this.flip);
      ctx.scale(scaleX, 1);

      ctx.beginPath();
      ctx.moveTo(0, -this.size);
      ctx.quadraticCurveTo(this.size * 0.65, 0, 0, this.size);
      ctx.quadraticCurveTo(-this.size * 0.65, 0, 0, -this.size);
      ctx.fillStyle = `rgba(${this.color}, ${this.alpha * 0.75})`;
      ctx.shadowBlur = 5;
      ctx.shadowColor = `rgba(${this.color}, 0.3)`;
      ctx.fill();

      ctx.strokeStyle = `rgba(255, 255, 255, ${this.alpha * 0.5})`;
      ctx.lineWidth = 0.75;
      ctx.beginPath();
      ctx.moveTo(0, -this.size * 0.8);
      ctx.lineTo(0, this.size * 0.8);
      ctx.stroke();

      ctx.restore();
    }
  }

  /* -------------------------------------------------------------
   * 3. GENTLE CURVED SPIRIT BUTTERFLIES (2-3 At Most, Behind UI)
   * ------------------------------------------------------------- */
  const BUTTERFLY_COUNT = 3;
  class GentleCurvedButterfly {
    constructor(idx) {
      this.idx = idx;
      // Stagger initial appearances with different time offsets
      this.time = idx * 3.8 + Math.random() * 2;
      this.delay = idx * 120; // Staggered spawn delay in frames
      this.active = false;
      this.reset();
    }

    reset() {
      // Pick a starting edge (left or right)
      this.fromLeft = Math.random() > 0.5;
      this.x = this.fromLeft ? -50 : width + 50;
      this.y = Math.random() * (height * 0.65) + 60;

      // Base flight speed and curving trajectory parameters
      this.baseSpeedX = (this.fromLeft ? 1 : -1) * (0.65 + Math.random() * 0.4);
      this.baseSpeedY = (Math.random() - 0.5) * 0.35;
      
      // Multi-frequency smooth curved floating path (smooth Lissajous / Bézier-like curve)
      this.curveFreq1 = 0.008 + Math.random() * 0.005;
      this.curveFreq2 = 0.015 + Math.random() * 0.008;
      this.curveAmpY = 1.2 + Math.random() * 0.8;
      this.curveAmpX = 0.6 + Math.random() * 0.4;

      // Wing animation: subtle flapping with gentle gliding pauses
      this.wingAngle = 0;
      this.wingSpeed = 0.12 + Math.random() * 0.04; // Slower, more graceful flapping
      this.glideTimer = 0;
      this.isGliding = false;

      // Size & orientation
      this.size = 10 + Math.random() * 3.5;
      this.heading = this.fromLeft ? 0 : Math.PI;
      this.bankAngle = 0;

      // Opacity fades gracefully near screen borders
      this.opacity = 0;
      this.targetOpacity = 0.55 + Math.random() * 0.2;

      // Color scheme: soft botanical olive or sage green with warm amber core
      this.wingHue = this.idx % 2 === 0 ? '93, 112, 72' : '101, 163, 13';
      this.active = true;
    }

    update() {
      if (this.delay > 0) {
        this.delay--;
        return;
      }

      this.time += 1;

      // 1. Natural flapping vs gliding cycle
      this.glideTimer++;
      if (this.glideTimer > 180 && Math.random() < 0.02) {
        this.isGliding = !this.isGliding;
        this.glideTimer = 0;
      }

      if (!this.isGliding) {
        this.wingAngle += this.wingSpeed;
      } else {
        // Slow gentle drift hold while gliding
        this.wingAngle = Math.PI * 0.35 + Math.sin(this.time * 0.03) * 0.12;
      }

      // 2. Smooth, continuous curved floating path
      const curvedVy = Math.sin(this.time * this.curveFreq1) * this.curveAmpY + 
                       Math.cos(this.time * this.curveFreq2) * 0.6;
      const curvedVx = this.baseSpeedX + Math.sin(this.time * this.curveFreq2) * this.curveAmpX;

      this.x += curvedVx;
      this.y += this.baseSpeedY + curvedVy;

      // 3. Natural banking rotation aligning with movement path
      const targetHeading = Math.atan2(curvedVy, curvedVx);
      this.heading += (targetHeading - this.heading) * 0.06;
      this.bankAngle = Math.sin(this.time * 0.04) * 0.18;

      // 4. Smooth edge opacity fade-in / fade-out
      const edgeDistX = Math.min(this.x, width - this.x);
      const edgeDistY = Math.min(this.y, height - this.y);
      const edgeMargin = 120;

      if (edgeDistX < edgeMargin || edgeDistY < edgeMargin) {
        const edgeFactor = Math.max(0, Math.min(edgeDistX, edgeDistY) / edgeMargin);
        this.opacity = Math.min(this.opacity, edgeFactor * this.targetOpacity);
      } else {
        this.opacity += (this.targetOpacity - this.opacity) * 0.04;
      }

      // 5. When naturally drifting off-screen, pause and spawn from a different edge
      if (this.x < -80 || this.x > width + 80 || this.y < -60 || this.y > height + 60) {
        this.active = false;
        this.delay = 80 + Math.floor(Math.random() * 180); // Staggered resting pause
        this.reset();
      }
    }

    draw() {
      if (!this.active || this.delay > 0 || this.opacity <= 0.02) return;

      const flap = Math.abs(Math.sin(this.wingAngle));

      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.rotate(this.heading + this.bankAngle);

      // Soft ambient aura behind UI
      ctx.shadowBlur = 6;
      ctx.shadowColor = 'rgba(217, 119, 6, 0.28)';

      // Left wing
      ctx.save();
      ctx.scale(flap, 1);
      ctx.beginPath();
      ctx.ellipse(-this.size * 0.72, -this.size * 0.2, this.size * 0.85, this.size * 0.52, -Math.PI / 6, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${this.wingHue}, ${this.opacity * 0.65})`;
      ctx.fill();
      ctx.restore();

      // Right wing
      ctx.save();
      ctx.scale(flap, 1);
      ctx.beginPath();
      ctx.ellipse(this.size * 0.72, -this.size * 0.2, this.size * 0.85, this.size * 0.52, Math.PI / 6, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${this.wingHue}, ${this.opacity * 0.65})`;
      ctx.fill();
      ctx.restore();

      // Delicate amber central body
      ctx.beginPath();
      ctx.ellipse(0, 0, 1.4, this.size * 0.42, 0, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(217, 119, 6, ${this.opacity * 0.9})`;
      ctx.shadowBlur = 5;
      ctx.shadowColor = 'rgba(234, 179, 8, 0.4)';
      ctx.fill();

      ctx.restore();
    }
  }

  // System instances
  const jugnus = Array.from({ length: JUGNU_COUNT }, () => new Jugnu());
  const leaves = Array.from({ length: LEAF_COUNT }, () => new SpectralLeaf());
  const butterflies = Array.from({ length: BUTTERFLY_COUNT }, (_, idx) => new GentleCurvedButterfly(idx));

  function animate() {
    ctx.clearRect(0, 0, width, height);

    // 1. Drifting Leaves
    for (let i = 0; i < leaves.length; i++) {
      leaves[i].update();
      leaves[i].draw();
    }

    // 2. Jugnus (Warm Amber Fireflies)
    for (let i = 0; i < jugnus.length; i++) {
      jugnus[i].update();
      jugnus[i].draw();
    }

    // 3. Gentle Curved Butterflies (Strictly behind cards, text, and buttons)
    for (let i = 0; i < butterflies.length; i++) {
      butterflies[i].update();
      butterflies[i].draw();
    }

    requestAnimationFrame(animate);
  }

  animate();
})();

/**
 * MYTHORA - Enchanted Mythical Notification & Modal System
 * Replaces harsh native browser alerts with an ethereal Crescent Moon / Forest Rune dialog
 */
(function initMythicalModals() {
  // Inject Mythical Modal Styles if not already added
  if (!document.getElementById('mythical-modal-styles')) {
    const style = document.createElement('style');
    style.id = 'mythical-modal-styles';
    style.textContent = `
      .mythical-toast-overlay {
        position: fixed;
        inset: 0;
        background: rgba(19, 27, 16, 0.38);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        padding: 20px;
      }
      .mythical-toast-overlay.active {
        opacity: 1;
        pointer-events: auto;
      }
      .mythical-moon-modal {
        position: relative;
        width: 100%;
        max-width: 440px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.94) 0%, rgba(246, 250, 242, 0.92) 100%);
        border: 1.5px solid rgba(126, 148, 101, 0.35);
        border-radius: 36px 12px 36px 12px;
        padding: 36px 32px 28px;
        box-shadow: 
          0 24px 60px -15px rgba(45, 62, 30, 0.22),
          0 0 35px rgba(234, 179, 8, 0.18),
          inset 0 1px 4px rgba(255, 255, 255, 1);
        transform: scale(0.88) translateY(18px);
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-align: center;
        overflow: hidden;
      }
      .mythical-toast-overlay.active .mythical-moon-modal {
        transform: scale(1) translateY(0);
      }
      /* Ethereal Crescent Moon & Rune Emblem */
      .mythical-moon-crest {
        position: relative;
        width: 76px;
        height: 76px;
        margin: 0 auto 18px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(254, 249, 231, 0.9) 0%, rgba(235, 241, 230, 0.8) 100%);
        border: 1.5px solid rgba(234, 179, 8, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 25px rgba(234, 179, 8, 0.32), inset 0 2px 6px rgba(255, 255, 255, 0.8);
      }
      .mythical-moon-crest::before {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 1px dashed rgba(126, 148, 101, 0.35);
        animation: moonRuneSpin 24s linear infinite;
      }
      @keyframes moonRuneSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .mythical-moon-symbol {
        font-size: 2.2rem;
        filter: drop-shadow(0 0 8px rgba(234, 179, 8, 0.6));
      }
      .mythical-moon-title {
        font-family: inherit;
        font-size: 1.25rem;
        font-weight: 700;
        color: #1A2414;
        margin-bottom: 10px;
        letter-spacing: -0.01em;
      }
      .mythical-moon-body {
        font-family: inherit;
        font-size: 0.94rem;
        line-height: 1.55;
        color: #4E5A47;
        margin-bottom: 24px;
      }
      .mythical-moon-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
      }
      .mythical-btn-confirm {
        background: linear-gradient(135deg, #3D522B 0%, #5E7A3F 100%);
        color: #FFFFFF;
        font-weight: 600;
        font-size: 0.92rem;
        padding: 10px 28px;
        border-radius: 9999px;
        border: 1px solid rgba(93, 112, 72, 0.3);
        box-shadow: 0 4px 14px rgba(61, 82, 43, 0.25);
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .mythical-btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(61, 82, 43, 0.35);
        filter: brightness(1.08);
      }
      .mythical-btn-cancel {
        background: rgba(255, 255, 255, 0.85);
        color: #3D522B;
        font-weight: 600;
        font-size: 0.92rem;
        padding: 10px 24px;
        border-radius: 9999px;
        border: 1px solid rgba(126, 148, 101, 0.35);
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .mythical-btn-cancel:hover {
        background: rgba(235, 241, 230, 0.9);
      }
    `;
    document.head.appendChild(style);
  }

  // Helper to show ethereal alert
  window.mythicalAlert = function(message, title = 'Mythical Sanctuary', icon = '🌙') {
    return new Promise((resolve) => {
      let overlay = document.getElementById('mythical-alert-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'mythical-alert-overlay';
        overlay.className = 'mythical-toast-overlay';
        overlay.innerHTML = `
          <div class="mythical-moon-modal">
            <div class="mythical-moon-crest">
              <span class="mythical-moon-symbol" id="mythical-alert-icon">🌙</span>
            </div>
            <h4 class="mythical-moon-title" id="mythical-alert-title">Notification</h4>
            <p class="mythical-moon-body" id="mythical-alert-body"></p>
            <div class="mythical-moon-actions">
              <button class="mythical-btn-confirm" id="mythical-alert-ok">Continue</button>
            </div>
          </div>
        `;
        document.body.appendChild(overlay);
      }

      const iconEl = document.getElementById('mythical-alert-icon');
      const titleEl = document.getElementById('mythical-alert-title');
      const bodyEl = document.getElementById('mythical-alert-body');
      const okBtn = document.getElementById('mythical-alert-ok');

      if (iconEl) iconEl.textContent = icon;
      if (titleEl) titleEl.textContent = title;
      if (bodyEl) bodyEl.textContent = message;

      overlay.classList.add('active');

      const cleanup = () => {
        overlay.classList.remove('active');
        okBtn.removeEventListener('click', onOk);
        resolve(true);
      };

      const onOk = () => cleanup();
      okBtn.addEventListener('click', onOk, { once: true });
    });
  };

  // Helper to show ethereal confirmation dialog
  window.mythicalConfirm = function(message, title = 'Grove Confirmation', icon = '🌙') {
    return new Promise((resolve) => {
      let overlay = document.getElementById('mythical-confirm-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'mythical-confirm-overlay';
        overlay.className = 'mythical-toast-overlay';
        overlay.innerHTML = `
          <div class="mythical-moon-modal">
            <div class="mythical-moon-crest">
              <span class="mythical-moon-symbol" id="mythical-confirm-icon">🌙</span>
            </div>
            <h4 class="mythical-moon-title" id="mythical-confirm-title">Confirm Action</h4>
            <p class="mythical-moon-body" id="mythical-confirm-body"></p>
            <div class="mythical-moon-actions">
              <button class="mythical-btn-cancel" id="mythical-confirm-cancel">Cancel</button>
              <button class="mythical-btn-confirm" id="mythical-confirm-ok">Confirm</button>
            </div>
          </div>
        `;
        document.body.appendChild(overlay);
      }

      const iconEl = document.getElementById('mythical-confirm-icon');
      const titleEl = document.getElementById('mythical-confirm-title');
      const bodyEl = document.getElementById('mythical-confirm-body');
      const okBtn = document.getElementById('mythical-confirm-ok');
      const cancelBtn = document.getElementById('mythical-confirm-cancel');

      if (iconEl) iconEl.textContent = icon;
      if (titleEl) titleEl.textContent = title;
      if (bodyEl) bodyEl.textContent = message;

      overlay.classList.add('active');

      const cleanup = (result) => {
        overlay.classList.remove('active');
        resolve(result);
      };

      okBtn.onclick = () => cleanup(true);
      cancelBtn.onclick = () => cleanup(false);
    });
  };

  // Gracefully replace native window.alert and window.confirm with Mythical Modals
  window.alert = function(msg) {
    return window.mythicalAlert(msg);
  };

  // Provide async replacement for native confirm if called directly
  const nativeConfirm = window.confirm;
  window.confirm = function(msg) {
    // If running in an async context, developers can await window.mythicalConfirm
    // For synchronous calls that can't be paused without blocking the main thread,
    // trigger the beautiful modal immediately
    window.mythicalConfirm(msg);
    return false; // Prevent unwanted synchronous actions before modal resolution
  };
})();
