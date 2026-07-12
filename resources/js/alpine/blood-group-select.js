export default function bloodGroupSelect({ initial = '' } = {}) {
    return {
        options: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'],
        selected: initial,

        select(value) {
            this.selected = value;
        },
    };
}
