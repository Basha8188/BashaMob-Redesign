const root = document.documentElement;
const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
const precisePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
const memory = Number(navigator.deviceMemory || 8);
const cores = Number(navigator.hardwareConcurrency || 8);
const saveData = navigator.connection?.saveData === true;
const lowPerformance = memory <= 4 || cores <= 4 || saveData;

if (lowPerformance) root.dataset.auroraPerformance = 'low';

let frame = 0;
let targetX = 0;
let targetY = 0;
let currentX = 0;
let currentY = 0;

const renderAuroraPointer = () => {
    currentX += (targetX - currentX) * .045;
    currentY += (targetY - currentY) * .045;
    root.style.setProperty('--aurora-pointer-x', `${currentX.toFixed(2)}px`);
    root.style.setProperty('--aurora-pointer-y', `${currentY.toFixed(2)}px`);

    if (Math.abs(targetX - currentX) > .08 || Math.abs(targetY - currentY) > .08) {
        frame = requestAnimationFrame(renderAuroraPointer);
    } else {
        frame = 0;
    }
};

const onPointerMove = event => {
    if (motionQuery.matches || !precisePointer.matches || document.hidden) return;
    targetX = ((event.clientX / window.innerWidth) - .5) * 12;
    targetY = ((event.clientY / window.innerHeight) - .5) * 9;
    if (!frame) frame = requestAnimationFrame(renderAuroraPointer);
};

const resetPointer = () => {
    targetX = 0;
    targetY = 0;
    if (!frame) frame = requestAnimationFrame(renderAuroraPointer);
};

if (document.querySelector('[data-aurora-background]')) {
    if (!lowPerformance && precisePointer.matches && !motionQuery.matches) {
        window.addEventListener('pointermove', onPointerMove, { passive: true });
        window.addEventListener('blur', resetPointer);
    }
    document.addEventListener('visibilitychange', () => {
        root.dataset.auroraPaused = String(document.hidden);
        if (document.hidden && frame) {
            cancelAnimationFrame(frame);
            frame = 0;
        } else if (!document.hidden && !lowPerformance && precisePointer.matches && !motionQuery.matches) {
            resetPointer();
        }
    });
}
