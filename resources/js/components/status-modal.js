export default () => ({
    show: false,
    action: null,
    name: null,
    status: null,

    open(action, name, status) {
        this.action = action;
        this.name = name;
        this.status = status;
        this.show = true;
    },

    close() {
        this.show = false;
        this.action = null;
        this.name = null;
        this.status = null;
    }
});