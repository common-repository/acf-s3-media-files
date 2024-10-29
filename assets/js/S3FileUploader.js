class S3FileUploader {
    /**
     * @param {S3Proxy} proxy
     * @param {object|undefined} config
     */
    constructor(proxy, config) {
        this.proxy = proxy;
        this.partSize = 10e6;
        Object.assign(this, config || {});
    }

    /**
     * @param {string} key
     * @param {File} file
     * @returns {jQuery.Deferred}
     */
    upload(key, file) {
        const partCount = Math.ceil(file.size / this.partSize);
        const completedParts = [];
        const deferred = jQuery.Deferred();

        this.proxy.createMultipartUpload(key, file.type || 'text/plain').then((result) => {
            const partFunctions = [];
            for (let i = 0; i < partCount; i++) {

                // extract a slice of the file
                const part = file.slice(i * this.partSize, (i + 1) * this.partSize);

                // create a function to upload the slice
                const func = this.uploadPart.bind(this, part, result.Key, result.UploadId, i + 1);
                partFunctions.push(func);
            }

            const queue = new PromiseQueue(partFunctions);

            // after each upload is done, push the result into the result array
            queue.afterEach = (data) => {
                completedParts.push(data);
            };

            const promise = queue.run();
            let prevTime = Date.now();
            let prevLoaded = 0;
            promise.progress((event) => {
                const time = Date.now();

                // currently we do not notify the promise after
                // a whole chunk completes. this results in weird
                // progress bar behavior because sometimes the next
                // XHR event occurs before the chunk as been pushed
                // to completedParts. The Math.max() invocation
                // makes sure the progress doesn't move backwards.
                const loaded = Math.max(prevLoaded, completedParts.length * this.partSize + event.loaded);

                deferred.notify({
                    loaded: loaded,
                    position: loaded / file.size,
                    total: file.size,
                    speed: (loaded - prevLoaded) / (time - prevTime),
                });
                prevTime = time;
                prevLoaded = loaded;
            });

            // after the queue is complete, complete the multi part upload
            return promise.then(() => {
                return this.proxy.completeMultipartUpload(result.Key, result.UploadId, completedParts)
                    .then(deferred.resolve);
            });
        });

        return deferred.promise();
    }

    /**
     *
     * @param {Blob} part
     * @param {string} key
     * @param {string} uploadId
     * @param {number} partNumber
     * @returns {jQuery.Deferred}
     */
    uploadPart(part, key, uploadId, partNumber) {
        // sign the part with the proxy
        return this.proxy.signUploadPart(key, uploadId, partNumber).then((result) => {
            const d = jQuery.Deferred();

            // then upload it directly to s3
            jQuery.ajax({
                url: result.Url,
                method: 'put',
                processData: false,
                data: part,
                xhr: function () {
                    const xhr = new XMLHttpRequest();
                    const fn = (event) => {
                        d.notify(event);
                    };
                    xhr.addEventListener('progress', fn);
                    xhr.upload.addEventListener('progress', fn);
                    return xhr;
                }
            }).then((result, status, xhr) => {

                // the ETag is quoted which can mess up the js/php interaction.
                // remove the quotes.
                let etag = xhr.getResponseHeader('ETag');
                etag = etag.replace(/[^\w]/g, '');

                const data = {
                    Key: key,
                    PartNumber: partNumber,
                    ETag: etag
                };

                d.resolve(data);

                return data;
            });

            return d.promise();
        });
    }
}
