export function scoreFor(password) {
    if (!password) return 0;

    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    return Math.min(score, 4);
}

export default function passwordStrength() {
    return {
        password: '',

        get score() {
            return scoreFor(this.password);
        },

        get label() {
            return ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][this.score];
        },

        get color() {
            return ['#B8452A', '#B8452A', '#E8A544', '#17847A', '#0F6A61'][this.score];
        },

        get widthPercent() {
            return this.password ? Math.max(this.score, 1) * 25 : 0;
        },
    };
}
