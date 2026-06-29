export default () => ({
    show: false,
    action: '',
    name: '',

    open(action, name) {
        this.action = action;
        this.name = name;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = '';
        this.name = '';
    }
})