<div
    x-data="{
        pieces: [],
        colors: ['#14503F', '#2A6B55', '#F5C879', '#F4A9A0', '#8FB8E0', '#E8615A'],
        burst() {
            this.pieces = Array.from({ length: 60 }, (_, i) => ({
                id: i,
                left: Math.random() * 100,
                delay: Math.random() * 0.3,
                duration: 2.2 + Math.random() * 1.2,
                color: this.colors[Math.floor(Math.random() * this.colors.length)],
                rotate: Math.random() * 360,
                drift: (Math.random() - 0.5) * 160,
                size: 6 + Math.random() * 6,
            }));
            setTimeout(() => (this.pieces = []), 3800);
        },
    }"
    @novix:confetti.window="burst()"
    class="pointer-events-none fixed inset-0 z-[999] overflow-hidden"
    aria-hidden="true"
>
    <template x-for="piece in pieces" :key="piece.id">
        <span
            class="novix-confetti-piece absolute top-[-5%]"
            :style="`left:${piece.left}%; width:${piece.size}px; height:${piece.size * 0.4}px; background:${piece.color}; animation-delay:${piece.delay}s; animation-duration:${piece.duration}s; --novix-drift:${piece.drift}px; --novix-rotate:${piece.rotate}deg;`"
        ></span>
    </template>
</div>

@once
<style>
    @keyframes novix-confetti-fall {
        0% { transform: translate(0, 0) rotate(0deg); opacity: 1; }
        100% { transform: translate(var(--novix-drift), 105vh) rotate(var(--novix-rotate)); opacity: 0; }
    }
    .novix-confetti-piece {
        border-radius: 2px;
        animation-name: novix-confetti-fall;
        animation-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        animation-fill-mode: forwards;
    }
</style>
@endonce
