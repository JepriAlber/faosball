export default () => ({
    show: false,
    action: '',
    name: '',
    jerseyNumber: '',

    open(action, name, jerseyNumber) {
        this.action = action;
        this.name = name;
        this.jerseyNumber = jerseyNumber;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = '';
        this.name = '';
        this.jerseyNumber = '';
    }
})
