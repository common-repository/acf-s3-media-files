class PromiseQueue {
    /**
     * @param {Function[]} funcs array of functions returning a jQuery.Deferred
     */
    constructor(funcs) {
        this.funcs = funcs;
        this.afterEach = () => {};
    }

    /**
     * @param {number} idx
     * @param {jQuery.Deferred} deferred
     * @returns {boolean}
     */
    runFrom(idx, deferred) {
        if (idx >= this.funcs.length) {
            deferred.resolve();
            return true;
        }
        const item = this.funcs[idx];
        item().then((result) => {
            this.afterEach(result);
            this.runFrom(idx + 1, deferred);
        }, null, (progress) => {
            deferred.notify(progress);
        });
        return false;
    }

    /**
     * @returns {jQuery.Deferred}
     */
    run() {
        const deferred = jQuery.Deferred();
        this.runFrom(0, deferred);
        return deferred.promise();
    }
}
