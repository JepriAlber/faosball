export default () => ({
    show: false,
    action: null,
    name: null,

    open(action, name) {
        this.action = action;
        this.name = name;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = null;
        this.name = null;
    }
});