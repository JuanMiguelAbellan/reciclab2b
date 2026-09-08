import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
}

const PARTICLE_COUNT = 80;
const MAX_PARTICLES = 220;
const LINK_DISTANCE = 130;
const CURSOR_DISTANCE = 170;

/**
 * A full-viewport (fixed) canvas of drifting dots that link up with faint
 * lines when close to each other, and to the cursor when close to it.
 * Click anywhere on the background to plant a new point.
 *
 * Rendered through a portal straight into <body>: a `fixed` element is only
 * guaranteed to size/position itself against the real viewport if none of
 * its ancestors has a `transform` (or filter/perspective) in effect — and a
 * parent animating `transform` (e.g. a page-transition fade) does count
 * while the animation runs. Portalling to <body> sidesteps that regardless
 * of which page or layout renders this component.
 */
export default function InteractiveBackground() {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) {
            return;
        }

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let width = 0;
        let height = 0;
        let particles: Particle[] = [];
        let frameId = 0;
        const mouse = { x: -9999, y: -9999 };

        const randomVelocity = () => (Math.random() - 0.5) * 0.25;

        // Map viewport (clientX/Y) coordinates to the canvas's own drawing
        // buffer space via its actual rendered rect, instead of assuming
        // they're always a 1:1 match.
        const toCanvasPoint = (clientX: number, clientY: number) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: ((clientX - rect.left) / rect.width) * width,
                y: ((clientY - rect.top) / rect.height) * height,
            };
        };

        const resize = () => {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;
        };

        const createParticles = () => {
            particles = Array.from({ length: PARTICLE_COUNT }, () => ({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: randomVelocity(),
                vy: randomVelocity(),
            }));
        };

        const draw = () => {
            ctx.clearRect(0, 0, width, height);

            for (const particle of particles) {
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, 1.6, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(217, 119, 6, 0.55)';
                ctx.fill();
            }

            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const a = particles[i];
                    const b = particles[j];
                    const dist = Math.hypot(a.x - b.x, a.y - b.y);
                    if (dist < LINK_DISTANCE) {
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = `rgba(180, 83, 9, ${0.15 * (1 - dist / LINK_DISTANCE)})`;
                        ctx.lineWidth = 1;
                        ctx.stroke();
                    }
                }

                const dist = Math.hypot(particles[i].x - mouse.x, particles[i].y - mouse.y);
                if (dist < CURSOR_DISTANCE) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(245, 158, 11, ${0.4 * (1 - dist / CURSOR_DISTANCE)})`;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            }
        };

        const step = () => {
            for (const particle of particles) {
                particle.x += particle.vx;
                particle.y += particle.vy;

                if (particle.x < 0 || particle.x > width) {
                    particle.vx *= -1;
                }
                if (particle.y < 0 || particle.y > height) {
                    particle.vy *= -1;
                }
            }

            draw();
            frameId = requestAnimationFrame(step);
        };

        resize();
        createParticles();
        draw();

        if (!prefersReducedMotion) {
            frameId = requestAnimationFrame(step);
        }

        const handleResize = () => {
            // Keep existing (and any user-planted) points across resizes —
            // only the wrap-around bounds change, not the particle set.
            resize();
            if (prefersReducedMotion) {
                draw();
            }
        };
        const handleMouseMove = (event: MouseEvent) => {
            const point = toCanvasPoint(event.clientX, event.clientY);
            mouse.x = point.x;
            mouse.y = point.y;
            if (prefersReducedMotion) {
                draw();
            }
        };
        const handleMouseLeave = () => {
            mouse.x = -9999;
            mouse.y = -9999;
            if (prefersReducedMotion) {
                draw();
            }
        };
        const handleClick = (event: MouseEvent) => {
            if (particles.length >= MAX_PARTICLES) {
                particles.splice(0, particles.length - MAX_PARTICLES + 1);
            }
            const point = toCanvasPoint(event.clientX, event.clientY);
            particles.push({ ...point, vx: randomVelocity(), vy: randomVelocity() });
            if (prefersReducedMotion) {
                draw();
            }
        };

        window.addEventListener('resize', handleResize);
        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseleave', handleMouseLeave);
        // Bound on window (not the canvas element): real page content sits
        // above the canvas in stacking order so links/buttons stay usable,
        // which means clicks over that content never hit the canvas as a
        // target. A window-level listener still fires on every click
        // regardless of what was hit, so points appear anywhere on the page.
        window.addEventListener('click', handleClick);

        return () => {
            cancelAnimationFrame(frameId);
            window.removeEventListener('resize', handleResize);
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseleave', handleMouseLeave);
            window.removeEventListener('click', handleClick);
        };
    }, []);

    // z-0, not a negative z-index: layout wrappers like AppLayout's root div
    // are unpositioned (position: static), so they always paint above any
    // *negatively* z-indexed sibling regardless of DOM order — a negative
    // value here would sit behind their opaque background and never show.
    // Page content is given z-10 (see AppLayout/GuestLayout) to stay on top.
    return createPortal(
        <canvas ref={canvasRef} className="fixed inset-0 z-0 h-full w-full" aria-hidden="true" />,
        document.body,
    );
}
