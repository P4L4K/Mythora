/**
 * VYRAL STUDIO - Ambient Bioluminescent Firefly Particles Engine
 * Lightweight, hardware accelerated HTML5 Canvas effect
 */
(function initFireflies() {
  const canvas = document.getElementById('firefly-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height;
  let particles = [];
  const PARTICLE_COUNT = 38;

  function resize() {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  }

  class Firefly {
    constructor() {
      this.reset();
    }

    reset() {
      this.x = Math.random() * width;
      this.y = Math.random() * height;
      this.radius = Math.random() * 2.2 + 0.8;
      this.vx = (Math.random() - 0.5) * 0.45;
      this.vy = (Math.random() - 0.5) * 0.45;
      this.alpha = Math.random() * 0.6 + 0.2;
      this.alphaChange = (Math.random() * 0.015 + 0.005) * (Math.random() > 0.5 ? 1 : -1);
      // Colors: Emerald (majority) with occasional Violet or Cyan sparks
      const roll = Math.random();
      if (roll > 0.3) {
        this.color = '16, 185, 129'; // emerald
      } else if (roll > 0.1) {
        this.color = '139, 92, 246'; // violet
      } else {
        this.color = '6, 182, 212'; // cyan
      }
    }

    update() {
      this.x += this.vx;
      this.y += this.vy;
      this.alpha += this.alphaChange;

      if (this.alpha <= 0.1 || this.alpha >= 0.85) {
        this.alphaChange = -this.alphaChange;
      }

      if (this.x < 0 || this.x > width || this.y < 0 || this.y > height) {
        this.reset();
      }
    }

    draw() {
      ctx.save();
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${this.color}, ${this.alpha})`;
      ctx.shadowBlur = this.radius * 6;
      ctx.shadowColor = `rgba(${this.color}, 0.8)`;
      ctx.fill();
      ctx.restore();
    }
  }

  function setup() {
    resize();
    particles = [];
    for (let i = 0; i < PARTICLE_COUNT; i++) {
      particles.push(new Firefly());
    }
  }

  function loop() {
    ctx.clearRect(0, 0, width, height);
    for (let i = 0; i < particles.length; i++) {
      particles[i].update();
      particles[i].draw();
    }
    requestAnimationFrame(loop);
  }

  window.addEventListener('resize', resize);
  setup();
  loop();
})();

