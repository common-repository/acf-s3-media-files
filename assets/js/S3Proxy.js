class S3Proxy {
    /**
     * @param {string} proxyUrl
     */
    constructor(proxyUrl) {
        this.proxyUrl = proxyUrl;
    }

    /**
     *
     * @param {string} action
     * @returns {string}
     */
    buildUrl(action) {
        return this.proxyUrl + '?action=' + action;
    }

    /**
     * @param {string} key
     * @param {string} contentType
     * @returns {jQuery.Deferred}
     */
    createMultipartUpload(key, contentType) {
        return jQuery.ajax({
            url: this.buildUrl('createMultipartUpload'),
            method: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                Key: key,
                ContentType: contentType,
            }),
        });
    }

    /**
     * @returns {jQuery.Deferred}
     */
    listMultipartUploads() {
        return jQuery.ajax({
            url: this.buildUrl('listMultipartUploads'),
            method: 'get',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
        });
    }

    /**
     * @param {string} key
     * @param {string} uploadId
     * @returns {jQuery.Deferred}
     */
    abortMultipartUpload(key, uploadId) {
        return jQuery.ajax({
            url: this.buildUrl('abortMultipartUpload'),
            method: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                Key: key,
                UploadId: uploadId,
            }),
        });
    }

    /**
     * @param {string} key
     * @param {string} uploadId
     * @param {object[]} parts
     * @returns {jQuery.Deferred}
     */
    completeMultipartUpload(key, uploadId, parts) {
        return jQuery.ajax({
            url: this.buildUrl('completeMultipartUpload'),
            method: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                Key: key,
                UploadId: uploadId,
                Parts: parts,
            }),
        });
    }

    /**
     * @param {string} key
     * @param {string} uploadId
     * @param {number} partNumber
     * @returns {jQuery.Deferred}
     */
    signUploadPart(key, uploadId, partNumber) {
        return jQuery.ajax({
            url: this.buildUrl('signUploadPart'),
            method: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                Key: key,
                UploadId: uploadId,
                PartNumber: partNumber,
            }),
        });
    }

    /**
     * @param {string} key
     * @returns {jQuery.Deferred}
     */
    deleteObject(key) {
        return jQuery.ajax({
            url: this.buildUrl('deleteObject'),
            method: 'post',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                Key: key
            }),
        });
    }
}
